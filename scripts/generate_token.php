<?php
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pgsql\User;

$user = User::where('username', 'admin')->first();
if (!$user) {
    echo "ERROR: admin user not found\n";
    exit(1);
}
$token = $user->createToken('cli-test');
if (!$token) {
    echo "ERROR: token creation failed\n";
    exit(1);
}
echo $token->plainTextToken . PHP_EOL;
