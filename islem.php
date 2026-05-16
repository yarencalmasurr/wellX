<?php
ob_start();
session_start();
include 'baglan.php';

// hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

$is = $_GET['is'] ?? '';

// 1. danışan kayıt işlemi
if ($is == 'danisan_kayit') {
    $ad_soyad = $_POST['ad_soyad'] ?? '';
    $kadi     = $_POST['kullanici'] ?? '';
    $email    = $_POST['email']    ?? '';
    $sifre    = $_POST['sifre']     ?? '';
    $rol      = 'danışan';

    if (!empty($ad_soyad) && !empty($kadi) && !empty($sifre)) {
        try {
            // kullanıcı adı kontrolü
            $kontrol = $conn->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ?");
            $kontrol->execute([$kadi]);
            
            if ($kontrol->rowCount() > 0) {
                echo "<script>alert('Bu kullanıcı adı zaten alınmış!'); window.history.back();</script>";
            } else {
                $sorgu = $conn->prepare("INSERT INTO kullanicilar (ad_soyad, kullanici_adi, email, sifre, rol) VALUES (?, ?, ?, ?, ?)");
                $kayit = $sorgu->execute([$ad_soyad, $kadi, $email, $sifre, $rol]);
                
                if ($kayit) {
                    echo "<script>alert('Kayıt Başarılı! Giriş yapabilirsiniz.'); window.location.href='index.php';</script>";
                }
            }
        } catch (PDOException $e) { 
            die("Hata: " . $e->getMessage()); 
        }
    } else {
        echo "<script>alert('Lütfen tüm alanları doldurun!'); window.history.back();</script>";
    }
}

// 2. uzman başvuru kaydı
elseif ($is == 'uzman_kayit' || $is == 'hoca_kayit') {
    $ad_soyad = $_POST['ad_soyad'] ?? '';
    $email    = $_POST['email']    ?? '';
    $uzmanlik = $_POST['uzmanlik'] ?? '';
    $tecrube  = $_POST['tecrube']  ?? '';
    $rol      = ($is == 'hoca_kayit') ? 'hoca' : 'diyetisyen';
    $belge    = $_FILES['belge']['name'] ?? 'yok.pdf';

    if (!empty($ad_soyad) && !empty($email)) {
        try {
            $sorgu = $conn->prepare("INSERT INTO uzman_basvurulari (ad_soyad, email, uzmanlik, tecrube, belge, rol, durum) VALUES (?, ?, ?, ?, ?, ?, 'beklemede')");
            $kayit = $sorgu->execute([$ad_soyad, $email, $uzmanlik, $tecrube, $belge, $rol]);
            
            if ($kayit) {
                echo "<script>alert('Başvurunuz alındı! Yönetici onayı bekleniyor.'); window.location.href='index.php';</script>";
            }
        } catch (PDOException $e) { 
            die("Hata: " . $e->getMessage()); 
        }
    }
}

// 3. uzman onaylama ve otomatik kullanıcı oluşturma
elseif ($is == 'onayla') {
    $bid = $_GET['id'] ?? 0;

    $sorgu = $conn->prepare("SELECT * FROM uzman_basvurulari WHERE id = ?");
    $sorgu->execute([$bid]);
    $basvuru = $sorgu->fetch(PDO::FETCH_ASSOC);

    if ($basvuru && $basvuru['durum'] == 'beklemede') {
        $temiz_isim = strtolower(str_replace(' ', '', $basvuru['ad_soyad']));
        $yeni_kadi  = $temiz_isim . rand(10, 99);
        $yeni_sifre = rand(100000, 999999);

        try {
            $conn->beginTransaction();

            $ins = $conn->prepare("INSERT INTO kullanicilar (ad_soyad, kullanici_adi, email, sifre, rol) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$basvuru['ad_soyad'], $yeni_kadi, $basvuru['email'], $yeni_sifre, $basvuru['rol']]);

            $upd = $conn->prepare("UPDATE uzman_basvurulari SET durum = 'onaylandi' WHERE id = ?");
            $upd->execute([$bid]);

            $conn->commit();

            echo "<script>
                    alert('Onay Başarılı!\\n\\nKullanıcı Adı: $yeni_kadi\\nŞifre: $yeni_sifre');
                    window.location.href='basvuru_yonetim.php';
                  </script>";
            exit();
        } catch (Exception $e) { 
            $conn->rollBack(); 
            die("Hata: " . $e->getMessage()); 
        }
    }
}


// 4. giriş işlemi
elseif ($is == 'login') {
    $kadi  = $_POST['kullanici'] ?? '';
    $sifre = $_POST['sifre']     ?? '';

    $sorgu = $conn->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ? AND sifre = ?");
    $sorgu->execute([$kadi, $sifre]);
    $user = $sorgu->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['ad_soyad'] = $user['ad_soyad'];
        $_SESSION['rol']      = $user['rol'];

        // yönlendirme mantığı 
        if ($user['rol'] == 'admin') {
            header("Location: basvuru_yonetim.php");
        } elseif ($user['rol'] == 'diyetisyen') {
            header("Location: diyetisyen_paneli.php");
        } elseif ($user['rol'] == 'hoca') {
            header("Location: hoca_paneli.php");
        } else {
            header("Location: panel.php"); // normal danışan paneli
        }
        exit();
    } else {
        header("Location: index.php?hata=1");
        exit();
    }
}

ob_end_flush();
?>