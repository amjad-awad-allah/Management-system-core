<?php

namespace App\Modules\Nachhilfe\Application\Queries;

use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ReminderCandidateQuery
{
    private string $timezone = 'Europe/Berlin';

    /**
     * Get candidate lessons for a given reminder window ('24h' or '2h').
     *
     * @param string $window '24h' | '2h'
     * @param CarbonImmutable|null $referenceNow
     * @return Collection<int, Lesson>
     */
    public function getCandidatesForWindow(string $window, ?CarbonImmutable $referenceNow = null): Collection
    {
        $now = $referenceNow ?? CarbonImmutable::now($this->timezone);

        [$windowStart, $windowEnd] = match ($window) {
            '24h' => [
                $now->addDay()->subMinutes(10),
                $now->addDay()->addMinutes(10),
            ],
            '2h' => [
                $now->addHours(1)->addMinutes(50),
                $now->addHours(2)->addMinutes(10),
            ],
            default => throw new \InvalidArgumentException("Unsupported reminder window: {$window}"),
        };

        return $this->getCandidatesBetweenInstants($windowStart, $windowEnd);
    }

    /**
     * Inclusive instant comparison strictly adhering to Europe/Berlin timezone.
     *
     * @param CarbonImmutable $windowStart
     * @param CarbonImmutable $windowEnd
     * @return Collection<int, Lesson>
     */
    public function getCandidatesBetweenInstants(CarbonImmutable $windowStart, CarbonImmutable $windowEnd): Collection
    {
        $startDate = $windowStart->toDateString();
        $endDate = $windowEnd->toDateString();

        $startTs = $windowStart->getTimestamp();
        $endTs = $windowEnd->getTimestamp();

        // 1. Filter by scheduled status and relevant dates
        $lessons = Lesson::with(['teacher', 'students', 'room', 'subject'])
            ->where('status', 'scheduled')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // 2. Exact inclusive timestamp comparison (handles DST transitions accurately)
        return $lessons->filter(function (Lesson $lesson) use ($startTs, $endTs) {
            $lessonInstant = CarbonImmutable::parse("{$lesson->date} {$lesson->start_time}", $this->timezone);
            $lessonTs = $lessonInstant->getTimestamp();

            return $lessonTs >= $startTs && $lessonTs <= $endTs;
        })->values();
    }
}
