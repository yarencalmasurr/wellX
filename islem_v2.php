<?php
/**
 * Proje: saglik_portali
 * Dosya: islem_v2.php
 * Açıklama: Tüm form işlemlerinin yönetildiği tam ve güncel dosya
 */

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
        
        // --- ROZET SİSTEMİ TETİKLEYİCİ ---
        include_once 'rozet_fonksiyonu.php';
        $yeni_rozet = null;
        
        // Kazanılan rozetleri kontrol et ve ismini yakala
        $r1 = rozetKontrolEt($conn, $user_id, 'su', $_POST['su_miktari']);
        $r2 = rozetKontrolEt($conn, $user_id, 'uyku', $_POST['uyku_suresi']);
        $r3 = rozetKontrolEt($conn, $user_id, 'spor', $_POST['spor_suresi']);

        if ($r1) $yeni_rozet = $r1;
        elseif ($r2) $yeni_rozet = $r2;
        elseif ($r3) $yeni_rozet = $r3;

        // Rozet kazanıldıysa animasyon için URL'ye ekle
        if ($yeni_rozet) {
            header("Location: panel.php?durum=ok&yeni_rozet=" . urlencode($yeni_rozet));
        } else {
            header("Location: panel.php?durum=ok");
        }
        exit();
    }

    // --- 3. UZMAN ATAMA ---
    elseif ($is == 'uzman_atama') {
        if (!isset($_SESSION['user_id'])) { die("Oturum hatası!"); }
        $danisan_id = $_SESSION['user_id'];
        $uzman_id = intval($_GET['uzman_id']);
        $rol = trim(strtolower($_GET['rol']));

        $kontrol = $conn->prepare("SELECT id FROM uzman_danisan_eslesmeleri WHERE danisan_id = ? AND uzman_rol = ?");
        $kontrol->execute([$danisan_id, $rol]);

        if ($kontrol->rowCount() > 0) {
            $sql = $conn->prepare("UPDATE uzman_danisan_eslesmeleri SET uzman_id = ? WHERE danisan_id = ? AND uzman_rol = ?");
            $sql->execute([$uzman_id, $danisan_id, $rol]);
        } else {
            $sql = $conn->prepare("INSERT INTO uzman_danisan_eslesmeleri (danisan_id, uzman_id, uzman_rol) VALUES (?, ?, ?)");
            $sql->execute([$danisan_id, $uzman_id, $rol]);
        }

        header("Location: panel.php?durum=uzman_secildi");
        exit();
    }

    // --- 4. VERİ GÜNCELLEME VE SİLME ---
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
        elseif ($r2) $yeni_rozet = $r2;
        elseif ($r3) $yeni_rozet = $r3;

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

    // --- 5. BİLDİRİM OKUMA VE PLAN YAZMA ---
    elseif ($is == 'mesaj_oku') {
        $tablo = ($_GET['tip'] == 'diyet') ? 'beslenme_planlari' : 'egzersiz_planlari';
        $conn->prepare("UPDATE $tablo SET okundu = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
        header("Location: " . ($_GET['tip'] == 'diyet' ? 'beslenme.php' : 'egzersiz.php'));
        exit();
    }
    
    elseif ($is == 'plan_yaz') {
        $conn->prepare("INSERT INTO beslenme_planlari (user_id, diyetisyen_id, plan_metni, okundu, kayit_tarihi) VALUES (?, ?, ?, 0, NOW())")
              ->execute([$_POST['danisan_id'], $_SESSION['user_id'], $_POST['plan_metni']]);
        header("Location: diyetisyen_paneli.php?durum=mesaj_gonderildi");
        exit();
    }

    elseif ($is == 'egzersiz_yaz') {
        $conn->prepare("INSERT INTO egzersiz_planlari (user_id, hoca_id, antrenman_notu, okundu, kayit_tarihi) VALUES (?, ?, ?, 0, NOW())")
              ->execute([$_POST['danisan_id'], $_SESSION['user_id'], $_POST['antrenman_notu']]);
        header("Location: hoca_paneli.php?durum=ok");
        exit();
    }

    // --- 6. TARİF VE ANTRENMAN DUYURULARI ---
    elseif ($is == 'tarif_kaydet') {
        $diyetisyen_id = $_SESSION['user_id'];
        $baslik = $_POST['tarif_baslik'];
        $icerik = $_POST['tarif_icerik'];
        
        $conn->prepare("INSERT INTO gunun_tarifi (diyetisyen_id, tarif_baslik, tarif_icerik, ekleme_tarihi) VALUES (?, ?, ?, NOW())")
              ->execute([$diyetisyen_id, $baslik, $icerik]);
        
        $danisanlar = $conn->prepare("SELECT danisan_id FROM uzman_danisan_eslesmeleri WHERE uzman_id = ? AND uzman_rol = 'diyetisyen'");
        $danisanlar->execute([$diyetisyen_id]);
        $liste = $danisanlar->fetchAll(PDO::FETCH_COLUMN);
        
        $mesaj = "🥗 YENİ TARİF: " . $baslik . "\n\n" . $icerik;
        foreach ($liste as $danisan_id) {
            $conn->prepare("INSERT INTO beslenme_planlari (user_id, diyetisyen_id, plan_metni, okundu, kayit_tarihi) VALUES (?, ?, ?, 0, NOW())")
                  ->execute([$danisan_id, $diyetisyen_id, $mesaj]);
        }
        header("Location: diyetisyen_paneli.php?durum=tarif_ok");
        exit();
    }

    elseif ($is == 'antrenman_duyuru_kaydet') {
        $hoca_id = $_SESSION['user_id'];
        $baslik = $_POST['duyuru_baslik'];
        $icerik = $_POST['duyuru_icerik'];

        $conn->prepare("INSERT INTO gunun_antrenmani (hoca_id, duyuru_baslik, duyuru_icerik) VALUES (?, ?, ?)")
              ->execute([$hoca_id, $baslik, $icerik]);

        $sporcular = $conn->prepare("SELECT danisan_id FROM uzman_danisan_eslesmeleri WHERE uzman_id = ? AND uzman_rol = 'hoca'");
        $sporcular->execute([$hoca_id]);
        $liste = $sporcular->fetchAll(PDO::FETCH_COLUMN);

        foreach ($liste as $danisan_id) {
            $conn->prepare("INSERT INTO egzersiz_planlari (user_id, hoca_id, antrenman_notu, okundu, kayit_tarihi) VALUES (?, ?, ?, 0, NOW())")
                  ->execute([$danisan_id, $hoca_id, "📢 DUYURU: $baslik\n$icerik"]);
        }
        header("Location: hoca_paneli.php?durum=ok");
        exit();
    }

    // --- 7. PUANLAMA ---
    elseif ($is == 'puan_ver') {
        if (!isset($_SESSION['user_id'])) { die("Oturum hatası!"); }
        $tarif_id = $_POST['tarif_id'];
        $user_id  = $_SESSION['user_id'];
        $puan     = $_POST['puan'];

        $kontrol = $conn->prepare("SELECT id FROM tarif_puanlari WHERE tarif_id = ? AND user_id = ?");
        $kontrol->execute([$tarif_id, $user_id]);
        
        if ($kontrol->fetch()) {
            $conn->prepare("UPDATE tarif_puanlari SET puan = ? WHERE tarif_id = ? AND user_id = ?")->execute([$puan, $tarif_id, $user_id]);
        } else {
            $conn->prepare("INSERT INTO tarif_puanlari (tarif_id, user_id, puan) VALUES (?, ?, ?)")->execute([$tarif_id, $user_id, $puan]);
        }
        header("Location: panel.php?puan=ok");
        exit();
    }

    // --- 8. ADMİN İŞLEMLERİ ---
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

    // --- 9. PROFİL GÜNCELLEME ---
    elseif ($is == 'profil_guncelle') {
        if (!isset($_SESSION['user_id'])) { die("Oturum hatası!"); }
        $user_id = $_SESSION['user_id'];
        $ad_soyad = $_POST['ad_soyad'];
        $kadi = $_POST['kullanici_adi'];
        $email = $_POST['email'];
        $yeni_sifre = $_POST['yeni_sifre'];

        if (!empty($yeni_sifre)) {
            $sorgu = $conn->prepare("UPDATE kullanicilar SET ad_soyad=?, kullanici_adi=?, email=?, sifre=? WHERE id=?");
            $sorgu->execute([$ad_soyad, $kadi, $email, $yeni_sifre, $user_id]);
        } else {
            $sorgu = $conn->prepare("UPDATE kullanicilar SET ad_soyad=?, kullanici_adi=?, email=? WHERE id=?");
            $sorgu->execute([$ad_soyad, $kadi, $email, $user_id]);
        }

        $_SESSION['ad_soyad'] = $ad_soyad;
        header("Location: profil.php?durum=ok");
        exit();
    }

} catch (PDOException $e) { 
    die("Sistem Hatası: " . $e->getMessage()); 
}
ob_end_flush();
?>