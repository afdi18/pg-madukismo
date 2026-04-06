<?php
$server = '172.16.2.6,1433';
$connectionInfo = array('UID'=>'sa','PWD'=>'dbakulali','Database'=>'madukismo_tanaman','Encrypt'=>'no','TrustServerCertificate'=>true);
$conn = sqlsrv_connect($server, $connectionInfo);
if($conn){
    echo "sqlsrv_connect OK\n";
    sqlsrv_close($conn);
} else {
    $errors = sqlsrv_errors();
    print_r($errors);
}
