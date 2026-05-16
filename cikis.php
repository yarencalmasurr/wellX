
<?php
session_start(); // mevcut oturumu yakala 
session_destroy(); // tüm oturum verilerini sil
header("Location: index.php"); // kullanıcıyı giriş sayfasına geri gönder
exit();
?>