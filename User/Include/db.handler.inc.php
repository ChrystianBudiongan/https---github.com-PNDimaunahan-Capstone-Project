<?php
$host = "localhost";
$dbname = "u216028102_healthpal_db";
$username = "u216028102_healthpal";
$password = "e2>lmf@0F";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>