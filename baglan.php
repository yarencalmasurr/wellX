<?php
$host = "127.0.0.1"; // veritabanının  ip adresi
$user = "root";
$pass = ""; 
$db   = "saglik_portali"; 

try { // çakışma olmaması için hem 3306 hem de 3307 portunu kullanıyoruz
    
    $conn = new PDO("mysql:host=$host;port=3307;dbname=$db;charset=utf8mb4", $user, $pass);
} catch (PDOException $e) {
    try {
        
        $conn = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4", $user, $pass);
    } catch (PDOException $e2) {
        die("Bağlantı başarısız: " . $e2->getMessage());
    }
} 

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>