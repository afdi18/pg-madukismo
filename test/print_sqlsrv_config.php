<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$config = $app->make('config');
print_r($config->get('database.connections.sqlsrv'));
