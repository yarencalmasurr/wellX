<?php
$host = "localhost";
$user = "root";
$pass = ""; // Eğer XAMPP kurulumunda şifre belirlediysen buraya yaz
$db   = "saglik_portali"; // Veritabanı adını yeni yapıya göre güncelledik YAREN ÇALMAŞUR

try {
    // charset kısmını arkadaşının SQL dosyasına tam uyum için utf8mb4 olarak bıraktık
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // Hataları ekranda görebilmek için hata modunu aktif ediyoruz
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Bağlantının başarılı olduğunu test etmek istersen aşağıdaki satırı yorumdan çıkarabilirsin
    // echo "Bağlantı başarılı!"; 
} catch (PDOException $e) {
    // Bağlantı hatası olursa burası çalışır
    die("Bağlantı başarısız: " . $e->getMessage());
}
?>