<?php

use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

test('stores the activity with the user, subject, ip address, and user agent', function () {
    $admin = User::factory()->admin()->create();
    $booking = Booking::factory()->create();

    $this->actingAs($admin);
    request()->server->set('REMOTE_ADDR', '10.20.1.15');
    request()->headers->set('User-Agent', 'PestBrowser/1.0');

    $activity = app(ActivityLogger::class)->log(
        'booking.created',
        $booking,
        "Admin membuat pemesanan {$booking->code}",
        ['vehicle' => 'DT 1234 AB'],
    );

    expect($activity->fresh())
        ->user_id->toBe($admin->id)
        ->action->toBe('booking.created')
        ->subject_type->toBe('booking')
        ->subject_id->toBe($booking->id)
        ->description->toBe("Admin membuat pemesanan {$booking->code}")
        ->properties->toBe(['vehicle' => 'DT 1234 AB'])
        ->ip_address->toBe('10.20.1.15')
        ->user_agent->toBe('PestBrowser/1.0');
});

test('writes the activity to the activity log channel', function () {
    $channel = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->once()->with('activity')->andReturn($channel);

    app(ActivityLogger::class)->log('report.exported', null, 'Admin mengekspor laporan', ['status' => 'completed']);

    $channel->shouldHaveReceived('info')->once()->with(
        'Admin mengekspor laporan',
        Mockery::on(fn (array $context) => $context['action'] === 'report.exported'
            && $context['user_id'] === null
            && $context['subject'] === null
            && $context['properties'] === ['status' => 'completed']),
    );
});

test('stores empty properties as null', function () {
    $activity = app(ActivityLogger::class)->log('auth.logout', null, 'Keluar');

    expect($activity->fresh()->properties)->toBeNull();
});
