<?php
$dsn = "sqlsrv:Server=172.16.2.6,1433;Database=madukismo_tanaman;Encrypt=no;TrustServerCertificate=Yes";
$user = 'sa';
$pass = 'dbakulali';

$attributes = [
    PDO::ATTR_CASE => PDO::CASE_NATURAL,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
    PDO::ATTR_STRINGIFY_FETCHES => false,
];

foreach ($attributes as $const => $value) {
    try {
        new PDO($dsn, $user, $pass, [$const => $value]);
        echo "OK: attribute $const\n";
    } catch (Exception $e) {
        echo "FAIL: attribute $const -> ", $e->getMessage(), "\n";
    }
}

// Try with all attributes at once
try {
    new PDO($dsn, $user, $pass, $attributes);
    echo "OK: all attributes\n";
} catch (Exception $e) {
    echo "FAIL: all attributes -> ", $e->getMessage(), "\n";
}

echo "Done\n";
