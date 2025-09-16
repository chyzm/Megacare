<?php
$host = 'localhost';
$dbname = 'megacare';
$username = 'root';
$password = '';



try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    //  $pdo = new PDO('mysql:host=localhost;dbname=your_db', 'username', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    
    error_log("Database connection successful"); // Optional logging
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


?>



