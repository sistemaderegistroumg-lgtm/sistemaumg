<?php
$host = "aws-1-us-east-2.pooler.supabase.com";
$port = "6543"; // ✅ Transaction pooler
$db   = "postgres";
$user = "postgres.nzsoiurryswdsbcfwsu"; // ✅ SIN errores
$pass = "854384Est@123";

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false
    ]);

    echo "✅ Conectado correctamente";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}