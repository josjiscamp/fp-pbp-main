<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Username default phpMyAdmin
define('DB_PASS', '');               // Password default kosong
define('DB_NAME', 'fet_system');     // Nama database yang tadi dibuat

// Fungsi koneksi database dengan PDO
function getPDOConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>