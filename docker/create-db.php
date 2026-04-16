#!/usr/bin/env php
<?php
/**
 * Verifica si la base de datos configurada en DB_PG_DATABASE existe.
 * Si no existe, la crea conectándose al catálogo "postgres".
 *
 * Uso: php docker/create-db.php
 *
 * Variables de entorno requeridas:
 *   DB_PG_HOST, DB_PG_PORT, DB_PG_USERNAME, DB_PG_PASSWORD, DB_PG_DATABASE
 */

$host     = getenv('DB_PG_HOST')     ?: 'alfa_postgres';
$port     = getenv('DB_PG_PORT')     ?: '5432';
$user     = getenv('DB_PG_USERNAME') ?: 'alfa_user';
$password = getenv('DB_PG_PASSWORD') ?: '';
$dbName   = getenv('DB_PG_DATABASE') ?: 'alfa';

echo "[create-db] Verificando si la base de datos '{$dbName}' existe en {$host}:{$port}...\n";

try {
    // Conectar al catálogo del sistema (siempre existe)
    $dsn = "pgsql:host={$host};port={$port};dbname=postgres";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Verificar si la base de datos ya existe
    $stmt = $pdo->prepare("SELECT 1 FROM pg_database WHERE datname = :dbname");
    $stmt->execute([':dbname' => $dbName]);

    if ($stmt->fetchColumn()) {
        echo "[create-db] La base de datos '{$dbName}' ya existe. OK.\n";
    } else {
        // Crear la base de datos (DDL no admite parámetros preparados)
        // Sanitizar el nombre: solo alfanuméricos y guiones bajos
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);
        $pdo->exec("CREATE DATABASE \"{$safeName}\" OWNER \"{$user}\"");
        echo "[create-db] Base de datos '{$safeName}' creada exitosamente.\n";
    }
} catch (PDOException $e) {
    echo "[create-db] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
