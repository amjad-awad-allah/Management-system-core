<?php

namespace App\Modules\Nachhilfe\Presentation\Console;

use App\Modules\Nachhilfe\Application\Services\LessonReminderService;
use Illuminate\Console\Command;

class SendLessonRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nachhilfe:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan and enqueue automated lesson reminders for 24h and 2h sliding windows';

    /**
     * Execute the console command.
     */
    public function handle(LessonReminderService $reminderService): int
    {
        $this->info('Starting automated lesson reminders scanner...');

        $stats = $reminderService->process();

        $this->info("24h Window: {$stats['lessons_24h']} lessons found, {$stats['enqueued_24h']} recipients enqueued.");
        $this->info("2h Window: {$stats['lessons_2h']} lessons found, {$stats['enqueued_2h']} recipients enqueued.");
        $this->info('Lesson reminder scan completed successfully.');

        return 0;
    }
}
