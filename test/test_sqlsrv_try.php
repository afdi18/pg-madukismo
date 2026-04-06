<?php
function test($dsn){
    try{
        $pdo = new PDO($dsn, 'sa', 'dbakulali');
        echo "$dsn => OK\n";
    }catch(PDOException $e){
        echo "$dsn => ERR: " . $e->getMessage() . "\n";
    }
}

$dsns = [
    'sqlsrv:Server=172.16.2.6,1433;Database=madukismo_tanaman;Encrypt=no;TrustServerCertificate=Yes',
    'sqlsrv:Server=172.16.2.6,1433;Database=madukismo_tanaman;Encrypt=yes;TrustServerCertificate=Yes',
    'sqlsrv:Server=172.16.2.6;Database=madukismo_tanaman;Encrypt=no;TrustServerCertificate=Yes',
];

foreach($dsns as $dsn){
    test($dsn);
}
