<?php

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

test('renders forbidden pages in Indonesian', function () {
    $this->actingAs(User::factory()->approver()->create())
        ->get(route('bookings.index'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 403)
            ->where('message', null));
});

test('shows the policy reason on a forbidden action', function () {
    $booking = Booking::factory()->completed()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('bookings.complete', $booking), [
            'odometer_start' => 1,
            'odometer_end' => 2,
            'fuel_used' => 1,
        ])
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('message', 'Hanya pemesanan yang sudah disetujui yang dapat diselesaikan.'));
});

test('renders missing pages for guests', function () {
    $this->get('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 404)
            ->where('auth.user', null));
});

test('sends expired sessions back with a message', function () {
    Route::middleware('web')->post('/_test/expired', fn () => abort(419));

    $this->from(route('login'))
        ->post('/_test/expired')
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Sesi Anda sudah berakhir. Silakan coba lagi.');
});
