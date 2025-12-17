<?php
require_once 'config/database.php';

try {
    $pdo = getPDOConnection();
    echo "✅ Database connection successful!";
    echo "<br>Database: " . DB_NAME;
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>