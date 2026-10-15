<?php

use App\Models\User;

test('adds security headers to pages', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
        ->assertHeaderMissing('Strict-Transport-Security')
        ->assertHeaderMissing('X-Powered-By');
});

test('adds security headers to error pages and downloads', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/halaman-tidak-ada')
        ->assertNotFound()
        ->assertHeader('X-Frame-Options', 'DENY');

    $this->actingAs($admin)
        ->get(route('reports.export'))
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY');
});

test('enables HSTS only over https', function () {
    $this->get('https://localhost/login')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});
