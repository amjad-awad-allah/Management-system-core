<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Modules\Nachhilfe\Infrastructure\Models\Holiday;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HolidayService
{
    private string $timezone = 'Europe/Berlin';

    /**
     * Get all applicable holidays (API + DB Overrides) for a given date range and German state.
     *
     * @param string $startDate Y-m-d
     * @param string $endDate   Y-m-d
     * @param string $state     e.g. 'NW' (North Rhine-Westphalia)
     * @param string $country   e.g. 'DE'
     * @return array<int, array<string, mixed>>
     */
    public function getHolidays(string $startDate, string $endDate, string $state = 'NW', string $country = 'DE'): array
    {
        $start = Carbon::parse($startDate, $this->timezone)->startOfDay();
        $end = Carbon::parse($endDate, $this->timezone)->endOfDay();
        $year = $start->year;

        // 1. Fetch external API holidays (Public & School) cached for 24 hours
        $externalHolidays = $this->fetchExternalHolidays($year, $country, $state);

        // 2. Fetch active custom/override holidays from local DB
        $dbHolidays = Holiday::where('is_active', true)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('start_date', '<=', $start->toDateString())
                          ->where('end_date', '>=', $end->toDateString());
                  });
            })
            ->where(function ($q) use ($state) {
                $q->whereNull('state')
                  ->orWhere('state', $state);
            })
            ->get();

        // 3. Merge API & DB holidays: DB overrides replace external ones with matching external_id or name
        $mergedMap = [];

        foreach ($externalHolidays as $holiday) {
            $key = $holiday['external_id'] ?? ($holiday['type'] . '_' . $holiday['start_date'] . '_' . $holiday['name']);
            $mergedMap[$key] = $holiday;
        }

        foreach ($dbHolidays as $dbItem) {
            $key = $dbItem->external_id ? 'ext_' . $dbItem->external_id : ($dbItem->source . '_' . $dbItem->id);
            $mergedMap[$key] = [
                'id' => $dbItem->id,
                'source' => $dbItem->source,
                'external_id' => $dbItem->external_id,
                'type' => $dbItem->type,
                'name' => $dbItem->name,
                'start_date' => $dbItem->start_date->format('Y-m-d'),
                'end_date' => $dbItem->end_date->format('Y-m-d'),
                'state' => $dbItem->state,
                'is_active' => $dbItem->is_active,
                'metadata' => $dbItem->metadata,
            ];
        }

        // Filter merged holidays within range
        $result = array_filter(array_values($mergedMap), function ($item) use ($start, $end) {
            $hStart = Carbon::parse($item['start_date'], $this->timezone);
            $hEnd = Carbon::parse($item['end_date'], $this->timezone);
            return $hStart->lte($end) && $hEnd->gte($start);
        });

        // Sort by start_date asc
        usort($result, function ($a, $b) {
            return strcmp($a['start_date'], $b['start_date']);
        });

        return $result;
    }

    /**
     * Fetch external holidays for Germany & State (Nager.Date / OpenHolidays API) with 24-hour cache.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchExternalHolidays(int $year, string $country = 'DE', string $state = 'NW'): array
    {
        $cacheKey = "holidays_external_{$country}_{$state}_{$year}";

        return Cache::remember($cacheKey, 86400, function () use ($year, $country, $state) {
            $holidays = [];

            try {
                // Public Holidays via Nager.Date API
                $url = "https://date.nager.at/api/v3/PublicHolidays/{$year}/{$country}";
                $response = Http::timeout(4)->get($url);

                if ($response->successful()) {
                    $items = $response->json();
                    foreach ($items as $item) {
                        // Filter by state if Counties is specified
                        if (!empty($item['counties'])) {
                            $formattedCounty = "DE-{$state}";
                            if (!in_array($formattedCounty, $item['counties'], true)) {
                                continue;
                            }
                        }

                        $holidays[] = [
                            'id' => 'api_pub_' . md5($item['date'] . $item['name']),
                            'source' => 'external',
                            'external_id' => 'nager_' . $item['date'] . '_' . ($item['global'] ? 'all' : $state),
                            'type' => 'public',
                            'name' => $item['localName'] ?? $item['name'],
                            'start_date' => $item['date'],
                            'end_date' => $item['date'],
                            'state' => $item['global'] ? null : $state,
                            'is_active' => true,
                            'metadata' => [
                                'global' => $item['global'] ?? true,
                                'types' => $item['types'] ?? [],
                            ],
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to fetch external public holidays API: " . $e->getMessage());
            }

            return $holidays;
        });
    }
}
