<?php
session_start();
include 'includes/db.php'; // veritabanı bağlantısı

if (isset($_POST['giris_yap'])) {
    $kullanici_adi = $_POST['kullanici_adi'];
    $sifre = $_POST['sifre'];

    // kullanıcıyı veritabanında bul
    $sql = "SELECT * FROM kullanicilar WHERE kullanici_adi = '$kullanici_adi' AND sifre = '$sifre'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // oturum değişkenlerini ata
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['rol'] = $user['rol']; // admin mi normal kullanıcı mı

        // yönlendirme mantığı
        if ($user['rol'] == 'admin') {
            header("Location: basvuru_yonetim.php"); // buraya gidecek
        } else {
            header("Location: index.php"); // normal kullanıcı paneli
        }
        exit();
    } else {
        echo "Hatalı kullanıcı adı veya şifre!";
    }
}
?>