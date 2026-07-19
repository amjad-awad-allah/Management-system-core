<?php

namespace App\Modules\Nachhilfe\Application\Actions\Chat;

use App\Modules\Nachhilfe\Infrastructure\Models\Survey;
use App\Modules\Nachhilfe\Infrastructure\Models\SurveyResponse;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatMessage;
use App\Core\Broadcasting\ChatMessageSent;
use App\Core\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitSurveyAction
{
    /**
     * Submit a survey response.
     *
     * @param  string $surveyId
     * @param  string $userId
     * @param  array  $answers   Key-value pairs where key is question_id or question index and value is the response
     * @return SurveyResponse
     */
    public function execute(string $surveyId, string $userId, array $answers): SurveyResponse
    {
        return DB::transaction(function () use ($surveyId, $userId, $answers) {
            $survey = Survey::findOrFail($surveyId);

            // Create response
            $response = SurveyResponse::create([
                'survey_id' => $surveyId,
                'user_id' => $userId,
                'answers' => $answers,
                'submitted_at' => now(),
            ]);

            // Add notification message to the channel (e.g. system info)
            $user = User::find($userId);
            $userName = $user ? $user->name : 'طالب';

            $message = ChatMessage::create([
                'channel_id' => $survey->channel_id,
                'sender_id' => $userId,
                'body' => "قام {$userName} بالإجابة على الاستبيان: {$survey->title}",
                'type' => 'system',
                'metadata' => [
                    'survey_id' => $survey->id,
                    'response_id' => $response->id,
                ],
            ]);

            // Broadcast message
            try {
                broadcast(new ChatMessageSent($message->load('sender')))->toOthers();
            } catch (\Exception $e) {
                // Ignore broadcast failures
            }

            return $response;
        });
    }
}
