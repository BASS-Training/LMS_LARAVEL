<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\ConfigurationUrlParser;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$path = $argv[1] ?? null;
$connection = config('database.default');
$database = config("database.connections.{$connection}");

if (! $path || $connection !== 'mysql' || ! is_array($database)) {
    fwrite(STDERR, "Production backup requires a configured MySQL database.\n");
    exit(1);
}

$database = (new ConfigurationUrlParser)->parseConfiguration($database);

$name = (string) ($database['database'] ?? '');
if (! preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
    fwrite(STDERR, "Invalid database name for backup.\n");
    exit(1);
}

$settings = [
    'user' => (string) ($database['username'] ?? ''),
    'password' => (string) ($database['password'] ?? ''),
];

if ($settings['user'] === '') {
    fwrite(STDERR, "MySQL username is required for backup.\n");
    exit(1);
}

if (! empty($database['unix_socket'])) {
    $settings['socket'] = (string) $database['unix_socket'];
} else {
    $settings['host'] = (string) ($database['host'] ?? '127.0.0.1');
    $settings['port'] = (string) ($database['port'] ?? '3306');
}

$lines = ['[client]'];
foreach ($settings as $key => $value) {
    $lines[] = $key.'="'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $value).'"';
}

if (file_put_contents($path, implode("\n", $lines)."\n") === false) {
    fwrite(STDERR, "Could not write temporary MySQL options.\n");
    exit(1);
}
chmod($path, 0600);

echo $name;
