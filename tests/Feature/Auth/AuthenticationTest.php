<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

test('users can authenticate and the login is logged', function () {
    $user = User::factory()->admin()->create();

    $response = $this->withHeader('User-Agent', 'PestBrowser/1.0')->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));

    $log = ActivityLog::where('action', 'auth.login')->sole();
    expect($log->user_id)->toBe($user->id)
        ->and($log->subject_type)->toBe('user')
        ->and($log->subject_id)->toBe($user->id)
        ->and($log->description)->toBe("{$user->name} masuk ke aplikasi")
        ->and($log->ip_address)->toBe('127.0.0.1')
        ->and($log->user_agent)->toBe('PestBrowser/1.0');
});

test('users can not authenticate with an invalid password', function () {
    $user = User::factory()->create();

    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai dengan data kami.']);

    $log = ActivityLog::where('action', 'auth.failed')->sole();
    expect($log->user_id)->toBeNull()
        ->and($log->properties)->toBe(['email' => $user->email])
        ->and($log->description)->toBe("Percobaan masuk gagal untuk {$user->email}");
});

test('login requires email and password', function () {
    $response = $this->post(route('login.store'));

    $response->assertSessionHasErrors([
        'email' => 'Email wajib diisi.',
        'password' => 'Kata sandi wajib diisi.',
    ]);
});

test('users are rate limited after too many failed attempts', function () {
    $user = User::factory()->create();

    RateLimiter::increment(strtolower($user->email).'|127.0.0.1', amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toStartWith('Terlalu banyak percobaan masuk.');
});

test('users can logout and the logout is logged', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));

    $log = ActivityLog::where('action', 'auth.logout')->sole();
    expect($log->user_id)->toBe($user->id)
        ->and($log->description)->toBe("{$user->name} keluar dari aplikasi");
});

test('public registration is not available', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Penyusup',
        'email' => 'penyusup@nikel.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::count())->toBe(0);
});
