<?php
ob_start();
session_start();
include 'baglan.php'; 

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$is = $_GET['is'] ?? '';

try {
    // --- 1. GİRİŞ SİSTEMİ ---
    if ($is == 'login') {
        $kadi  = trim($_POST['kullanici'] ?? '');
        $sifre = trim($_POST['sifre']     ?? '');
        
        $sorgu = $conn->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ? AND sifre = ?");
        $sorgu->execute([$kadi, $sifre]);
        $user = $sorgu->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['ad_soyad'] = $user['ad_soyad'];
            $rol = mb_strtolower(trim($user['rol']), 'UTF-8');
            $_SESSION['rol'] = $rol;

            if ($rol == 'admin') header("Location: basvuru_yonetim.php");
            elseif ($rol == 'diyetisyen') header("Location: diyetisyen_paneli.php");
            elseif ($rol == 'hoca') header("Location: hoca_paneli.php");
            elseif ($rol == 'danışan' || $rol == 'danisan') header("Location: panel.php");
            exit();
        } else {
            header("Location: index.php?hata=1");
            exit();
        }
    }

    // --- 2. DANIŞAN GÜNLÜK VERİ KAYDI ---
    elseif ($is == 'verileri_kaydet') {
        if (!isset($_SESSION['user_id'])) { die("Oturum hatası!"); }
        
        $user_id = $_SESSION['user_id'];
        $tarih   = date('Y-m-d');
        
        $chk = $conn->prepare("SELECT id FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
        $chk->execute([$user_id, $tarih]);
        if($chk->fetch()) {
            header("Location: panel.php?hata=zaten_kayitli");
            exit();
        }

        $sorgu = $conn->prepare("INSERT INTO aktivite_kayitlari (user_id, alinan_kalori, yakilan_kalori, su_miktari, uyku_suresi, guncel_kilo, spor_suresi, kayit_tarihi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $sorgu->execute([$user_id, $_POST['alinan_kalori'], $_POST['yakilan_kalori'], $_POST['su_miktari'], $_POST['uyku_suresi'], $_POST['guncel_kilo'], $_POST['spor_suresi'], $tarih]);
        
        // --- ROZET SİSTEMİ TETİKLEYİCİ (DÜZELTİLDİ) ---
        include_once 'rozet_fonksiyonu.php';
        $yeni_rozet = null;

        // Her kategori için kontrol et ve kazanılan en son rozeti yakala
        $r1 = rozetKontrolEt($conn, $user_id, 'su', $_POST['su_miktari']);
        $r2 = rozetKontrolEt($conn, $user_id, 'uyku', $_POST['uyku_suresi']);
        $r3 = rozetKontrolEt($conn, $user_id, 'spor', $_POST['spor_suresi']);

        if ($r1) $yeni_rozet = $r1;
        if ($r2) $yeni_rozet = $r2;
        if ($r3) $yeni_rozet = $r3;

        // Eğer bir rozet kazanıldıysa URL'ye ekle
        if ($yeni_rozet) {
            header("Location: panel.php?durum=ok&yeni_rozet=" . urlencode($yeni_rozet));
        } else {
            header("Location: panel.php?durum=ok");
        }
        exit();
    }

    // --- 3. VERİ GÜNCELLEME VE SİLME ---
    elseif ($is == 'guncelle') {
        if (!isset($_SESSION['user_id'])) { die("Oturum hatası!"); }
        $user_id = $_SESSION['user_id'];
        $tarih = date('Y-m-d');
        
        $sorgu = $conn->prepare("UPDATE aktivite_kayitlari SET alinan_kalori=?, yakilan_kalori=?, su_miktari=?, uyku_suresi=?, guncel_kilo=?, spor_suresi=? WHERE user_id=? AND kayit_tarihi=?");
        $sorgu->execute([$_POST['alinan_kalori'], $_POST['yakilan_kalori'], $_POST['su_miktari'], $_POST['uyku_suresi'], $_POST['guncel_kilo'], $_POST['spor_suresi'], $user_id, $tarih]);
        
        include_once 'rozet_fonksiyonu.php';
        $yeni_rozet = null;
        $r1 = rozetKontrolEt($conn, $user_id, 'su', $_POST['su_miktari']);
        $r2 = rozetKontrolEt($conn, $user_id, 'uyku', $_POST['uyku_suresi']);
        $r3 = rozetKontrolEt($conn, $user_id, 'spor', $_POST['spor_suresi']);

        if ($r1) $yeni_rozet = $r1;
        if ($r2) $yeni_rozet = $r2;
        if ($r3) $yeni_rozet = $r3;

        if ($yeni_rozet) {
            header("Location: panel.php?durum=guncellendi&yeni_rozet=" . urlencode($yeni_rozet));
        } else {
            header("Location: panel.php?durum=guncellendi");
        }
        exit();
    }

    elseif ($is == 'kayit_sil') {
        $conn->prepare("DELETE FROM aktivite_kayitlari WHERE id = ? AND user_id = ?")->execute([$_GET['id'], $_SESSION['user_id']]);
        header("Location: panel.php?sil=ok");
        exit();
    }

    // --- 4, 5 ve 6. Bölümler (Duyurular, Admin vs.) aynı kalıyor...
    elseif ($is == 'mesaj_oku') {
        $tablo = ($_GET['tip'] == 'diyet') ? 'beslenme_planlari' : 'egzersiz_planlari';
        $conn->prepare("UPDATE $tablo SET okundu = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
        header("Location: " . ($_GET['tip'] == 'diyet' ? 'beslenme.php' : 'egzersiz.php'));
        exit();
    }
    
    elseif ($is == 'plan_yaz') {
        $conn->prepare("INSERT INTO beslenme_planlari (user_id, diyetisyen_id, plan_metni, okundu) VALUES (?, ?, ?, 0)")
              ->execute([$_POST['danisan_id'], $_SESSION['user_id'], $_POST['plan_metni']]);
        header("Location: diyetisyen_paneli.php?durum=mesaj_gonderildi");
        exit();
    }

    elseif ($is == 'egzersiz_yaz') {
        $conn->prepare("INSERT INTO egzersiz_planlari (user_id, hoca_id, antrenman_notu, okundu) VALUES (?, ?, ?, 0)")
              ->execute([$_POST['danisan_id'], $_SESSION['user_id'], $_POST['antrenman_notu']]);
        header("Location: hoca_paneli.php?durum=mesaj_gonderildi");
        exit();
    }

    elseif ($is == 'tarif_kaydet') {
        $diyetisyen_id = $_SESSION['user_id'];
        $baslik = $_POST['tarif_baslik'];
        $icerik = $_POST['tarif_icerik'];
        
        $conn->prepare("INSERT INTO gunun_tarifi (diyetisyen_id, tarif_baslik, tarif_icerik) VALUES (?, ?, ?)")
              ->execute([$diyetisyen_id, $baslik, $icerik]);
        
        $danisanlar = $conn->prepare("SELECT id FROM kullanicilar WHERE diyetisyen_id = ?");
        $danisanlar->execute([$diyetisyen_id]);
        $liste = $danisanlar->fetchAll(PDO::FETCH_COLUMN);
        
        $mesaj = "🥗 YENİ TARİF: " . $baslik . "\n\n" . $icerik;
        foreach ($liste as $danisan_id) {
            $conn->prepare("INSERT INTO beslenme_planlari (user_id, diyetisyen_id, plan_metni, okundu) VALUES (?, ?, ?, 0)")
                  ->execute([$danisan_id, $diyetisyen_id, $mesaj]);
        }
        header("Location: diyetisyen_paneli.php?durum=tarif_ok");
        exit();
    }

    elseif ($is == 'puan_ver') {
        if (!isset($_SESSION['user_id'])) { die("Oturum hatası!"); }
        $tarif_id = $_POST['tarif_id'];
        $user_id  = $_SESSION['user_id'];
        $puan     = $_POST['puan'];

        $kontrol = $conn->prepare("SELECT id FROM tarif_puanlari WHERE tarif_id = ? AND user_id = ?");
        $kontrol->execute([$tarif_id, $user_id]);
        
        if ($kontrol->fetch()) {
            $sorgu = $conn->prepare("UPDATE tarif_puanlari SET puan = ? WHERE tarif_id = ? AND user_id = ?");
            $sorgu->execute([$puan, $tarif_id, $user_id]);
        } else {
            $sorgu = $conn->prepare("INSERT INTO tarif_puanlari (tarif_id, user_id, puan) VALUES (?, ?, ?)");
            $sorgu->execute([$puan, $tarif_id, $user_id]);
        }
        header("Location: panel.php?puan=ok");
        exit();
    }
    
    elseif ($is == 'antrenman_duyuru_kaydet') {
        $conn->prepare("INSERT INTO gunun_antrenmani (hoca_id, duyuru_baslik, duyuru_icerik) VALUES (?, ?, ?)")
              ->execute([$_SESSION['user_id'], $_POST['duyuru_baslik'], $_POST['duyuru_icerik']]);
        header("Location: hoca_paneli.php?duyuru_ok");
        exit();
    }

    elseif ($is == 'onayla') {
        $conn->prepare("UPDATE uzman_basvurulari SET durum = 'onaylandi' WHERE id = ?")->execute([$_GET['id']]);
        header("Location: basvuru_yonetim.php?durum=onaylandi");
        exit();
    }
    elseif ($is == 'reddet') {
        $conn->prepare("UPDATE uzman_basvurulari SET durum = 'reddedildi' WHERE id = ?")->execute([$_GET['id']]);
        header("Location: basvuru_yonetim.php?durum=reddedildi");
        exit();
    }

} catch (PDOException $e) { 
    die("Sistem Hatası: " . $e->getMessage()); 
}
ob_end_flush();
?>