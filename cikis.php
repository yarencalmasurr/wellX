
<?php
session_start(); // Mevcut oturumu yakala ŞAMPİYON ERZURUMSPOR
session_destroy(); // Tüm oturum verilerini (id, rol vb.) sil
header("Location: index.php"); // Kullanıcıyı giriş sayfasına geri gönder
exit();
?>