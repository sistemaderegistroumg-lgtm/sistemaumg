<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {

    $pdo = new PDO(
        "pgsql:host=aws-1-us-east-2.pooler.supabase.com;port=5432;dbname=postgres;sslmode=require",
        "postgres.nzsoiurryswdsbcfcwsu",
        "854384Est@*"
    );

    echo "✅ FUNCIONA";

} catch (PDOException $e) {

    die("❌ " . $e->getMessage());
}
?>