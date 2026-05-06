<?php
$host = "127.0.0.1"; // localhost yerine IP kullanarak port çakışmasını önledik
$port = "3307";      // XAMPP panelinde gördüğün aktif portu buraya tanımladık
$user = "root";
$pass = ""; 
$db   = "saglik_portali"; 

try {
    // Bağlantı satırına port bilgisini (port=$port) ekledik
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test etmek istersen aşağıdaki satırı aktif edebilirsin
    // echo "Bağlantı başarılı!"; 
} catch (PDOException $e) {
    die("Bağlantı başarısız: " . $e->getMessage());
}
?>