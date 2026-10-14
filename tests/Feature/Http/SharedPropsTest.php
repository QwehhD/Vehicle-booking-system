<?php

use App\Models\Booking;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('shares the role of the authenticated user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.role', 'admin')
            ->where('pendingApprovalsCount', null));
});

test('counts only approvals waiting for the approver turn', function () {
    $approver = User::factory()->approver()->create();
    $other = User::factory()->approver()->create();

    // Giliran approver sekarang (level 1 dari booking pending level 1).
    Booking::factory()->withApprovers($approver, $other)->create();
    // Giliran approver sekarang (level 2, level 1 sudah disetujui).
    Booking::factory()->state(['current_level' => 2])->withApprovers($other, $approver)->create();
    // Masih menunggu level 1 milik approver lain.
    Booking::factory()->withApprovers($other, $approver)->create();
    // Booking sudah diputuskan.
    Booking::factory()->approved()->state(['current_level' => 2])->withApprovers($approver, $other)->create();
    Booking::factory()->rejected()->withApprovers($other, $approver)->create();

    $this->actingAs($approver)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.role', 'approver')
            ->where('pendingApprovalsCount', 2));
});

test('shares flash messages from the session', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withSession(['success' => 'Pemesanan berhasil dibuat.'])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('flash.success', 'Pemesanan berhasil dibuat.')
            ->where('flash.error', null));
});
