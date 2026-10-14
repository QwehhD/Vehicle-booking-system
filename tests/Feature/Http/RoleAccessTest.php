<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

dataset('admin pages', [
    'bookings' => ['bookings.index', 'bookings/index'],
    'reports' => ['reports.index', 'reports/index'],
    'activity logs' => ['activity-logs.index', 'activity-logs/index'],
    'vehicles' => ['vehicles.index', 'vehicles/index'],
]);

test('guests are redirected to the login page', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with([
    'dashboard' => 'dashboard',
    'bookings' => 'bookings.index',
    'approvals' => 'approvals.index',
]);

test('admins can open admin pages', function (string $route, string $component) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with('admin pages');

test('approvers are forbidden from admin pages', function (string $route) {
    $approver = User::factory()->approver()->create();

    $this->actingAs($approver)->get(route($route))->assertForbidden();
})->with('admin pages');

test('approvers can open the approval inbox', function () {
    $approver = User::factory()->approver()->create();

    $this->actingAs($approver)
        ->get(route('approvals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('approvals/index'));
});

test('admins are forbidden from the approval inbox', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('approvals.index'))->assertForbidden();
});

test('every role can open the dashboard', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard'));
})->with(['admin', 'approver']);
