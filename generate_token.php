<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Core\Models\User::first();
if (!$user) {
    $user = \App\Core\Models\User::forceCreate([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('password')
    ]);
}

echo $user->createToken('test-token')->plainTextToken;
