<?php
session_start();
include 'includes/db.php'; // Veritabanı bağlantın

if (isset($_POST['giris_yap'])) {
    $kullanici_adi = $_POST['kullanici_adi'];
    $sifre = $_POST['sifre'];

    // Kullanıcıyı veritabanında bul
    $sql = "SELECT * FROM kullanicilar WHERE kullanici_adi = '$kullanici_adi' AND sifre = '$sifre'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Oturum değişkenlerini ata
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['rol'] = $user['rol']; // admin mi, normal kullanıcı mı?

        // --- YÖNLENDİRME MANTIĞI ---
        if ($user['rol'] == 'admin') {
            header("Location: basvuru_yonetim.php"); // Buraya gidecek
        } else {
            header("Location: index.php"); // Normal kullanıcı panelin
        }
        exit();
    } else {
        echo "Hatalı kullanıcı adı veya şifre!";
    }
}
?>