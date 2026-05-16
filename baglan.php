<?php
$host = "127.0.0.1";
$user = "root";
$pass = ""; 
$db   = "saglik_portali"; 
// Veritabanına PDO ile bağlantı kuruluyor
// İlk olarak 3307 portu deneniyor bağlantı olmazsa 3306 portu ile tekrar bağlantı kuruluyor
// Hata oluşursa kullanıcıya bağlantı hatası mesajı gösteriliyor

try {
    
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