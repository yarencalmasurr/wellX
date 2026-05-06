<?php
$host = "127.0.0.1";
$user = "root";
$pass = ""; 
$db   = "saglik_portali"; 

// Hangi portun aktif olduğunu anlamak için küçük bir mantık kuruyoruz
// Eğer senin bilgisayarınsa 3307, değilse 3306'yı dener
try {
    // Önce 3307'yi dene (Senin için)
    $conn = new PDO("mysql:host=$host;port=3307;dbname=$db;charset=utf8mb4", $user, $pass);
} catch (PDOException $e) {
    try {
        // Eğer 3307 başarısız olursa 3306'yı dene (Arkadaşların için)
        $conn = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4", $user, $pass);
    } catch (PDOException $e2) {
        die("Bağlantı başarısız: " . $e2->getMessage());
    }
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>