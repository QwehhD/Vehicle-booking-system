<?php

namespace App\Http\Controllers;

use App\Enums\ActivityAction;
use App\Http\Requests\ActivityLogRequest;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(ActivityLogRequest $request): Response
    {
        $filters = $request->filters();

        $logs = ActivityLog::query()
            ->with(['user', 'subject'])
            ->filter($filters)
            ->latest()
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('activity-logs/index', [
            'logs' => ActivityLogResource::collection($logs),
            'filters' => $filters,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'actions' => ActivityAction::options(),
        ]);
    }
}
