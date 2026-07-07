<?php

namespace App\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

class OutboxWorkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outbox:work {--sleep=3 : Number of seconds to sleep when no events are available}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending domain events from the transactional outbox';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting outbox worker...');
        
        $sleep = (int) $this->option('sleep');

        /** @phpstan-ignore-next-line */
        while (true) {
            $processed = $this->processNextBatch();

            if ($processed === 0) {
                sleep($sleep);
            }
        }
    }

    private function processNextBatch(): int
    {
        $processedCount = 0;

        DB::transaction(function () use (&$processedCount) {
            $events = DB::table('outbox_events')
                ->where('status', 'pending')
                ->where('available_at', '<=', now())
                ->orderBy('created_at', 'asc')
                ->limit(50)
                ->lockForUpdate()
                /** @phpstan-ignore-next-line */
                ->skipLocked()
                ->get();

            if ($events->isEmpty()) {
                return;
            }

            $ids = $events->pluck('id')->toArray();

            DB::table('outbox_events')
                ->whereIn('id', $ids)
                ->update(['status' => 'processing']);

            foreach ($events as $row) {
                /** @var object{id: string, event_type: string, payload: string, attempts: int, available_at: string} $eventRow */
                $eventRow = $row;
                try {
                    $this->dispatchEvent($eventRow);

                    DB::table('outbox_events')
                        ->where('id', $eventRow->id)
                        ->update([
                            'status' => 'completed',
                            'processed_at' => now(),
                        ]);

                } catch (Throwable $e) {
                    Log::error("Outbox event processing failed for ID {$eventRow->id}", [
                        'exception' => $e->getMessage()
                    ]);

                    $attempts = $eventRow->attempts + 1;
                    $status = $attempts >= 3 ? 'failed' : 'pending';

                    DB::table('outbox_events')
                        ->where('id', $eventRow->id)
                        ->update([
                            'status' => $status,
                            'attempts' => $attempts,
                            'available_at' => $status === 'pending' ? now()->addMinutes(5 * $attempts) : $eventRow->available_at,
                        ]);
                }

                $processedCount++;
            }
        });

        return $processedCount;
    }

    /**
     * @param object{id: string, event_type: string, payload: string, attempts: int, available_at: string} $eventRow
     */
    private function dispatchEvent(object $eventRow): void
    {
        $eventType = $eventRow->event_type;
        
        if (!class_exists($eventType)) {
            throw new \RuntimeException("Event class {$eventType} does not exist.");
        }
        
        if (!is_subclass_of($eventType, \App\Shared\Contracts\Events\DomainEvent::class)) {
            throw new \RuntimeException("Event class {$eventType} must implement DomainEvent.");
        }

        $payloadString = $eventRow->payload;
        /** @var array<string, mixed> $payload */
        $payload = json_decode($payloadString, true, 512, JSON_THROW_ON_ERROR);

        $eventInstance = $eventType::fromPayload($payload);
        
        Event::dispatch($eventInstance);
    }
}
