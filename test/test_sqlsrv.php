<?php
try {
  $pdo = new PDO('sqlsrv:Server=172.16.2.6,1433;Database=madukismo_tanaman;Encrypt=no;TrustServerCertificate=Yes', 'sa', 'dbakulali');
  echo "OK\n";
} catch (PDOException $e) {
  echo $e->getMessage();
}