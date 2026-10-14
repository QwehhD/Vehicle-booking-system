<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;

class ActivityLogger
{
    public function __construct(
        private AuthFactory $auth,
        private Request $request,
        private LogManager $logger,
    ) {}

    /**
     * Mencatat aktivitas ke tabel activity_logs dan channel log `activity`.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, ?Model $subject, string $description, array $properties = []): ActivityLog
    {
        $activity = ActivityLog::create([
            'user_id' => $this->auth->guard()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);

        $this->logger->channel('activity')->info($description, [
            'action' => $action,
            'user_id' => $activity->user_id,
            'subject' => $subject ? $activity->subject_type.'#'.$activity->subject_id : null,
            'properties' => $properties,
            'ip_address' => $activity->ip_address,
        ]);

        return $activity;
    }
}
