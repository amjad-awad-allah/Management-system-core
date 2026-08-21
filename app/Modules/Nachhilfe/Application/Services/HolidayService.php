<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Core\Services\CenterSettingsService;
use App\Modules\Nachhilfe\Infrastructure\Models\Holiday;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HolidayService
{
    private string $timezone = 'Europe/Berlin';

    public function __construct(
        private readonly ?CenterSettingsService $centerSettings = null
    ) {}

    /**
     * Get all applicable holidays (Public, School, DB Overrides) for a given date range and German state.
     *
     * @param string      $startDate Y-m-d
     * @param string      $endDate   Y-m-d
     * @param string|null $state     e.g. 'NW', 'BY', 'BE' (null defaults to Center Bundesland)
     * @param string      $country   e.g. 'DE'
     * @return array<int, array<string, mixed>>
     */
    public function getHolidays(string $startDate, string $endDate, ?string $state = null, string $country = 'DE'): array
    {
        $resolvedState = $state ? strtoupper($state) : ($this->centerSettings?->getCenterBundesland() ?? 'NW');

        $start = Carbon::parse($startDate, $this->timezone)->startOfDay();
        $end = Carbon::parse($endDate, $this->timezone)->endOfDay();
        $startYear = $start->year;
        $endYear = $end->year;

        $externalHolidays = [];
        for ($y = $startYear; $y <= $endYear; $y++) {
            $publicHolidays = $this->getPublicHolidays($y, $resolvedState, $country);
            $schoolHolidays = $this->getSchoolHolidays($y, $resolvedState, $country);
            $externalHolidays = array_merge($externalHolidays, $publicHolidays, $schoolHolidays);
        }

        // Fetch active custom/override holidays from local DB
        $dbHolidays = Holiday::where('is_active', true)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('start_date', '<=', $start->toDateString())
                          ->where('end_date', '>=', $end->toDateString());
                  });
            })
            ->where(function ($q) use ($resolvedState) {
                $q->whereNull('state')
                  ->orWhere('state', $resolvedState);
            })
            ->get();

        // Merge API & DB holidays: DB overrides replace external ones with matching external_id or key
        $mergedMap = [];

        foreach ($externalHolidays as $holiday) {
            $key = $holiday['external_id'] ?? ($holiday['type'] . '_' . $holiday['start_date'] . '_' . $holiday['name']);
            $mergedMap[$key] = $holiday;
        }

        foreach ($dbHolidays as $dbItem) {
            $key = $dbItem->external_id ? 'ext_' . $dbItem->external_id : ($dbItem->source . '_' . $dbItem->id);
            $mergedMap[$key] = [
                'id' => $dbItem->id,
                'source' => $dbItem->source ?: 'local_center',
                'external_id' => $dbItem->external_id,
                'type' => $dbItem->type ?: 'center_closure',
                'name' => $dbItem->name,
                'start_date' => $dbItem->start_date->format('Y-m-d'),
                'end_date' => $dbItem->end_date->format('Y-m-d'),
                'state' => $dbItem->state,
                'is_active' => $dbItem->is_active,
                'metadata' => $dbItem->metadata,
            ];
        }

        // Filter merged holidays strictly within range
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
     * Get German Public Holidays (Gesetzliche Feiertage) with state-aware caching.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPublicHolidays(int $year, string $state = 'NW', string $country = 'DE'): array
    {
        $cacheKey = "holiday:{$country}:{$state}:{$year}:public:v1";

        return Cache::remember($cacheKey, 86400, function () use ($year, $country, $state) {
            $holidays = [];

            try {
                $url = "https://date.nager.at/api/v3/PublicHolidays/{$year}/{$country}";
                $response = Http::timeout(4)->get($url);

                if ($response->successful()) {
                    $items = $response->json();
                    foreach ($items as $item) {
                        // Filter by state if counties constraint exists
                        if (!empty($item['counties'])) {
                            $formattedCounty = "DE-{$state}";
                            if (!in_array($formattedCounty, $item['counties'], true)) {
                                continue;
                            }
                        }

                        $holidays[] = [
                            'id' => 'pub_' . md5($item['date'] . $item['name']),
                            'source' => 'official',
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
                Log::warning("Failed to fetch official public holidays API: " . $e->getMessage());
            }

            return $holidays;
        });
    }

    /**
     * Get German School Holidays (Schulferien) with state-aware caching.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSchoolHolidays(int $year, string $state = 'NW', string $country = 'DE'): array
    {
        $cacheKey = "holiday:{$country}:{$state}:{$year}:school:v1";

        return Cache::remember($cacheKey, 86400, function () use ($year, $country, $state) {
            $holidays = [];

            try {
                // OpenHolidays API for German School Holidays
                $url = "https://openholidaysapi.org/SchoolHolidays?countryIsoCode={$country}&subdivisionCode=DE-{$state}&validFrom={$year}-01-01&validTo={$year}-12-31&languageIsoCode=DE";
                $response = Http::timeout(4)->get($url);

                if ($response->successful()) {
                    $items = $response->json();
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $name = $item['name'][0]['text'] ?? 'Schulferien';
                            $holidays[] = [
                                'id' => 'school_' . md5($item['startDate'] . $item['endDate'] . $name),
                                'source' => 'openholidays',
                                'external_id' => 'openholidays_' . $item['id'],
                                'type' => 'school',
                                'name' => $name,
                                'start_date' => $item['startDate'],
                                'end_date' => $item['endDate'],
                                'state' => $state,
                                'is_active' => true,
                                'metadata' => [
                                    'nationwide' => $item['nationwide'] ?? false,
                                ],
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to fetch school holidays API: " . $e->getMessage());
            }

            return $holidays;
        });
    }

    /**
     * Invalidate cached holiday datasets for a state/year.
     */
    public function invalidateCache(?string $state = null, ?int $year = null, string $country = 'DE'): void
    {
        $targetState = $state ? strtoupper($state) : ($this->centerSettings?->getCenterBundesland() ?? 'NW');
        $targetYear = $year ?? Carbon::now($this->timezone)->year;

        Cache::forget("holiday:{$country}:{$targetState}:{$targetYear}:public:v1");
        Cache::forget("holiday:{$country}:{$targetState}:{$targetYear}:school:v1");
    }
}
