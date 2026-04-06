<?php
try {
    $dsn = "sqlsrv:Server=172.16.2.6,1433;Database=madukismo_tanaman;Encrypt=no;TrustServerCertificate=Yes";
    $pdo = new PDO($dsn, 'sa', 'dbakulali', [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
    ]);
    echo "OK\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}
