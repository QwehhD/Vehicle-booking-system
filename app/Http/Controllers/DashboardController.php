<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Models\Region;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function __invoke(DashboardRequest $request, #[CurrentUser] User $user): Response
    {
        $filters = $request->filters();

        return Inertia::render('dashboard', [
            'filters' => $filters,
            'years' => $this->dashboard->availableYears(),
            'regions' => Region::query()->orderBy('name')->get(['id', 'name']),
            'dashboard' => $user->isAdmin()
                ? ['view' => 'admin', ...$this->dashboard->admin($filters)]
                : ['view' => 'approver', ...$this->dashboard->approver($user, $filters)],
        ]);
    }
}
