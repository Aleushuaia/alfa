<?php
$host='192.168.0.45';
$port=3306;
$db='api_expedientes';
$user='alejandro';
$pass='alejo';
try{
    $pdo=new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_TIMEOUT=>5]);
    echo "CONNECTED\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
