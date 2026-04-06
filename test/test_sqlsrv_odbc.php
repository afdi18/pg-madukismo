<?php
try{
    $pdo = new PDO('odbc:Driver={ODBC Driver 18 for SQL Server};Server=172.16.2.6,1433;Database=madukismo_tanaman;Encrypt=no;TrustServerCertificate=Yes', 'sa', 'dbakulali');
    echo "OK_ODBC\n";
}catch(PDOException $e){
    echo 'ERR_ODBC: ' . $e->getMessage() . "\n";
}
