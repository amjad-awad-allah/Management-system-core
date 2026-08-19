<?php

namespace App\Modules\Nachhilfe\Application\Actions\Chat;

use App\Modules\Nachhilfe\Infrastructure\Models\Survey;
use App\Modules\Nachhilfe\Infrastructure\Models\SurveyQuestion;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatMessage;
use App\Core\Broadcasting\ChatMessageSent;
use Illuminate\Support\Facades\DB;

class CreateSurveyAction
{
    /**
     * Create a new survey in a chat channel.
     *
     * @param  string      $channelId
     * @param  string      $createdBy
     * @param  string      $title
     * @param  string|null $description
     * @param  array       $questions   Array of arrays: [['question' => '...', 'type' => '...', 'options' => [...], 'order' => 1]]
     * @param  string|null $expiresAt
     * @return Survey
     */
    public function execute(
        string $channelId,
        string $createdBy,
        string $title,
        ?string $description,
        array $questions,
        ?string $expiresAt = null
    ): Survey {
        return DB::transaction(function () use ($channelId, $createdBy, $title, $description, $questions, $expiresAt) {
            $survey = Survey::create([
                'channel_id' => $channelId,
                'created_by' => $createdBy,
                'title' => $title,
                'description' => $description,
                'status' => 'active',
                'expires_at' => $expiresAt ? now()->parse($expiresAt) : null,
            ]);

            foreach ($questions as $index => $qData) {
                SurveyQuestion::create([
                    'survey_id' => $survey->id,
                    'question' => $qData['question'],
                    'type' => $qData['type'] ?? 'text',
                    'options' => $qData['options'] ?? null,
                    'order' => $qData['order'] ?? $index,
                ]);
            }

            // Create a system notification message inside the channel
            $message = ChatMessage::create([
                'channel_id' => $channelId,
                'sender_id' => $createdBy,
                'body' => "New survey created: {$title}",
                'type' => 'survey_response',
                'metadata' => [
                    'survey_id' => $survey->id,
                    'survey_title' => $title,
                ],
            ]);

            // Broadcast message
            try {
                broadcast(new ChatMessageSent($message->load('sender')))->toOthers();
            } catch (\Exception $e) {
                // Ignore broadcast issues in tests/seeding
            }

            return $survey->load('questions');
        });
    }
}
