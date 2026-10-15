<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-14 08:00:00');
});

test('lists the newest activities first, 20 per page', function () {
    $admin = User::factory()->admin()->create();
    $logs = ActivityLog::factory()
        ->count(21)
        ->sequence(fn ($sequence) => ['created_at' => now()->subMinutes(21 - $sequence->index)])
        ->create();

    $this->actingAs($admin)
        ->get(route('activity-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('activity-logs/index')
            ->has('logs.data', 20)
            ->where('logs.data.0.id', $logs->last()->id)
            ->where('logs.meta.total', 21)
            ->has('actions', 9)
            ->has('users'));
});

test('shows the action as a labelled badge with the related booking', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin Pool']);
    $booking = Booking::factory()->create();
    ActivityLog::factory()->for($admin)->for($booking, 'subject')->create([
        'action' => 'booking.created',
        'properties' => ['vehicle' => 'DT 1234 AB'],
        'user_agent' => 'PestBrowser/1.0',
    ]);

    $this->actingAs($admin)
        ->get(route('activity-logs.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('logs.data.0.action_label', 'Pemesanan dibuat')
            ->where('logs.data.0.action_color', 'violet')
            ->where('logs.data.0.user', ['id' => $admin->id, 'name' => 'Admin Pool'])
            ->where('logs.data.0.booking', ['id' => $booking->id, 'code' => $booking->code])
            ->where('logs.data.0.properties', ['vehicle' => 'DT 1234 AB'])
            ->where('logs.data.0.user_agent', 'PestBrowser/1.0'));
});

test('filters by search, user, action, and date range', function (array $filters, array $expected) {
    $admin = User::factory()->admin()->create();
    $budi = User::factory()->approver()->create();
    $logs = [
        'old_login' => ActivityLog::factory()->for($budi)->create(['action' => 'auth.login', 'description' => 'Budi masuk', 'created_at' => '2026-10-01 09:00:00']),
        'approval' => ActivityLog::factory()->for($budi)->create(['action' => 'approval.approved', 'description' => 'Budi menyetujui BK-202610-00001', 'created_at' => '2026-10-10 09:00:00']),
        'export' => ActivityLog::factory()->for($admin)->create(['action' => 'report.exported', 'description' => 'Admin mengekspor laporan', 'ip_address' => '10.20.1.99', 'created_at' => '2026-10-12 09:00:00']),
    ];

    $query = array_map(fn (mixed $value) => $value === ':budi' ? $budi->id : $value, $filters);
    $expectedIds = array_map(fn (string $key) => $logs[$key]->id, $expected);

    $this->actingAs($admin)
        ->get(route('activity-logs.index', $query))
        ->assertInertia(fn (Assert $page) => $page
            ->where('logs.data', fn ($data) => collect($data)->pluck('id')->all() === $expectedIds));
})->with([
    'search in description' => [['search' => 'menyetujui'], ['approval']],
    'search by IP address' => [['search' => '10.20.1.99'], ['export']],
    'user' => [['user_id' => ':budi'], ['approval', 'old_login']],
    'action' => [['action' => 'auth.login'], ['old_login']],
    'date range' => [['date_from' => '2026-10-05', 'date_to' => '2026-10-10'], ['approval']],
]);

test('redirects invalid filters to the unfiltered log', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('activity-logs.index', ['action' => 'booking.deleted']))
        ->assertRedirect(route('activity-logs.index'));
});

test('treats the search term as plain text', function () {
    $admin = User::factory()->admin()->create();
    ActivityLog::factory()->for($admin)->create(['description' => 'Admin Pool masuk ke aplikasi']);
    ActivityLog::factory()->for($admin)->create(['description' => 'Diskon 50% untuk BK_01']);

    $search = fn (string $term) => $this->actingAs($admin)->get(route('activity-logs.index', ['search' => $term]));

    $search('%')->assertInertia(fn (Assert $page) => $page
        ->has('logs.data', 1)
        ->where('logs.data.0.description', 'Diskon 50% untuk BK_01'));
    $search('BK_01')->assertInertia(fn (Assert $page) => $page->has('logs.data', 1));
    $search('_')->assertInertia(fn (Assert $page) => $page->has('logs.data', 1));
});
