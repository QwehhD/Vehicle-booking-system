<?php

test('passes a known appearance cookie to the theme script', function (string $appearance) {
    $this->withUnencryptedCookie('appearance', $appearance)
        ->get(route('login'))
        ->assertSee("const appearance = '{$appearance}';", false);
})->with(['light', 'dark', 'system']);

test('falls back to dark for unknown appearance cookies', function (string $value) {
    $this->withUnencryptedCookie('appearance', $value)
        ->get(route('login'))
        ->assertSee("const appearance = 'dark';", false);
})->with([
    'trailing backslash' => 'x\\',
    'quote' => "dark';alert(1);//",
    'markup' => '</script><script>alert(1)</script>',
]);
