<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

ob_start();
session_start();
include 'baglan.php'; 

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$is = $_GET['is'] ?? '';

try {
    // 1.giriş sistemi
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
            else header("Location: panel.php");
            exit();
        } else {
            header("Location: index.php?hata=1");
            exit();
        }
    }

    // 2. uzman kayıt
    elseif ($is == 'uzman_basvuru' || $is == 'uzman_kayit') {
        $ad_soyad = $_POST['ad_soyad'];
        $email    = $_POST['email'];
        $uzmanlik = $_POST['uzmanlik'];
        $tecrube  = $_POST['ozgecmis'] ?? $_POST['tecrube']; // Tasarımdan 'ozgecmis' geliyor
        $rol      = $_POST['uzmanlik_turu'] ?? 'Bilinmiyor'; // Hoca mı diyetisyen mi?
        
        // Admin onaylarken kimin neye başvurduğunu net görsün diye rolü uzmanlığa ekliyoruz
        $detayli_uzmanlik = strtoupper($rol) . " - " . $uzmanlik;

        $ekle = $conn->prepare("INSERT INTO uzman_basvurulari (ad_soyad, email, uzmanlik, tecrube, durum) VALUES (?, ?, ?, ?, 'beklemede')");
        $ekle->execute([$ad_soyad, $email, $detayli_uzmanlik, $tecrube]);
        
        header("Location: index.php?basvuru=basarili");
        exit();
    }

    // 3. danışan kayıt 
    elseif ($is == 'kayit_ol') {
        $ad_soyad = $_POST['ad_soyad'];
        $kadi = $_POST['kullanici_adi'];
        $email = $_POST['email'];
        $sifre = $_POST['sifre'];

        $ekle = $conn->prepare("INSERT INTO kullanicilar (ad_soyad, kullanici_adi, email, sifre, rol) VALUES (?, ?, ?, ?, 'danışan')");
        $ekle->execute([$ad_soyad, $kadi, $email, $sifre]);
        header("Location: index.php?kayit=basarili");
        exit();
    }

// 4. verileri kaydet
    elseif ($is == 'verileri_kaydet') {
        $user_id = $_SESSION['user_id'];
        $su      = (float)$_POST['su_miktari'];
        $uyku    = (float)$_POST['uyku_suresi'];
        $alinan  = (int)$_POST['alinan_kalori'];
        $yakilan = (int)$_POST['yakilan_kalori'];
        $spor    = (int)$_POST['spor_suresi'];
        $kilo    = (float)$_POST['guncel_kilo'];
        $tarih   = date('Y-m-d');

        // mevcut verileri çek
        $kontrol = $conn->prepare("SELECT id, su_miktari, alinan_kalori, spor_suresi, yakilan_kalori, uyku_suresi FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
        $kontrol->execute([$user_id, $tarih]);
        $mevcut_kayit = $kontrol->fetch(PDO::FETCH_ASSOC);

        if ($mevcut_kayit) {
            // kayıt var ise üzerine ekleyerek güncelle 
            $yeni_su = $mevcut_kayit['su_miktari'] + $su;
            $yeni_alinan = $mevcut_kayit['alinan_kalori'] + $alinan;
            $yeni_spor = $mevcut_kayit['spor_suresi'] + $spor;
            $yeni_yakilan = $mevcut_kayit['yakilan_kalori'] + $yakilan;
            $yeni_uyku = $mevcut_kayit['uyku_suresi'] + $uyku; 

            $guncelle = $conn->prepare("
                UPDATE aktivite_kayitlari 
                SET su_miktari = ?, alinan_kalori = ?, spor_suresi = ?, uyku_suresi = ?, guncel_kilo = ?, yakilan_kalori = ?
                WHERE id = ?
            ");
            $guncelle->execute([$yeni_su, $yeni_alinan, $yeni_spor, $yeni_uyku, $kilo, $yeni_yakilan, $mevcut_kayit['id']]);
            
            // rozetler için toplam değerleri değişkene atıyoruz
            $toplam_su_kontrol = $yeni_su;
            $toplam_spor_kontrol = $yeni_spor;
            $toplam_uyku_kontrol = $yeni_uyku;
        } else {
            // kayıt yoksa yeni satır oluştur
            $ekle = $conn->prepare("INSERT INTO aktivite_kayitlari (user_id, su_miktari, uyku_suresi, alinan_kalori, yakilan_kalori, spor_suresi, guncel_kilo, kayit_tarihi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ekle->execute([$user_id, $su, $uyku, $alinan, $yakilan, $spor, $kilo, $tarih]);
            
            // rozetler için toplam değerleri değişkene atıyoruz
            $toplam_su_kontrol = $su;
            $toplam_spor_kontrol = $spor;
            $toplam_uyku_kontrol = $uyku;
        }

        
        // rozet kontrolü
$yeni_rozet = "";

if(file_exists('rozet_fonksiyonu.php')) {

    include 'rozet_fonksiyonu.php';

    $rozet1 = rozetKontrolEt($conn, $user_id, 'su', $toplam_su_kontrol);
    $rozet2 = rozetKontrolEt($conn, $user_id, 'spor', $toplam_spor_kontrol);
    $rozet3 = rozetKontrolEt($conn, $user_id, 'uyku', $toplam_uyku_kontrol);

    if(!empty($rozet1)) {
        $yeni_rozet = $rozet1;
    }
    elseif(!empty($rozet2)) {
        $yeni_rozet = $rozet2;
    }
    elseif(!empty($rozet3)) {
        $yeni_rozet = $rozet3;
    }
}

if(!empty($yeni_rozet)) {
    header("Location: panel.php?yeni_rozet=" . urlencode($yeni_rozet));
} else {
    header("Location: panel.php?islem=basarili");
}

exit();
}

    // 5. danışanın uzmanı seçmesi
    elseif ($is == 'uzman_atama') {
        $danisan_id = $_SESSION['user_id'];
        $uzman_id = $_GET['uzman_id'];
        $rol = strtolower(trim($_GET['rol'])); 

        $sil = $conn->prepare("DELETE FROM uzman_danisan_eslesmeleri WHERE danisan_id = ? AND uzman_rol = ?");
        $sil->execute([$danisan_id, $rol]);

        $ekle = $conn->prepare("INSERT INTO uzman_danisan_eslesmeleri (danisan_id, uzman_id, uzman_rol) VALUES (?, ?, ?)");
        $ekle->execute([$danisan_id, $uzman_id, $rol]);
        header("Location: panel.php?atama=basarili");
        exit();
    }

    // 6. uzmanın kişiye özel plan yazması
    elseif ($is == 'plan_yaz' || $is == 'egzersiz_yaz') {
        $uzman_id = $_SESSION['user_id'];
        $danisan_id = $_POST['danisan_id'];
        $plan = $_POST['plan_metni'] ?? $_POST['antrenman_notu'];
        $rol = $_SESSION['rol'];

        if ($rol == 'diyetisyen') {
            $sorgu = $conn->prepare("INSERT INTO beslenme_planlari (user_id, diyetisyen_id, plan_metni) VALUES (?, ?, ?)");
        } else {
            $sorgu = $conn->prepare("INSERT INTO egzersiz_planlari (user_id, hoca_id, antrenman_notu) VALUES (?, ?, ?)");
        }
        $sorgu->execute([$danisan_id, $uzman_id, $plan]);
        header("Location: " . $rol . "_paneli.php?islem=basarili");
        exit();
    }

    // 7. tarife puan verme
    elseif ($is == 'puan_ver') {
        $user_id = $_SESSION['user_id'];
        $tarif_id = $_POST['tarif_id'];
        $puan = $_POST['puan'];
        $kontrol = $conn->prepare("SELECT id FROM tarif_puanlari WHERE tarif_id = ? AND user_id = ?");
        $kontrol->execute([$tarif_id, $user_id]);
        if ($kontrol->fetch()) {
            $guncelle = $conn->prepare("UPDATE tarif_puanlari SET puan = ? WHERE tarif_id = ? AND user_id = ?");
            $guncelle->execute([$puan, $tarif_id, $user_id]);
        } else {
            $ekle = $conn->prepare("INSERT INTO tarif_puanlari (tarif_id, user_id, puan) VALUES (?, ?, ?)");
            $ekle->execute([$tarif_id, $user_id, $puan]);
        }
        header("Location: panel.php?puan=verildi");
        exit();
    }

    // 8. admin başvuru onaylama ya da ret
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

    // 9. profil güncelleme
    elseif ($is == 'profil_guncelle') {
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
        header("Location: profil.php?durum=ok");
        exit();
    }

    // 10. premium özellik uzmana soru sorma
    elseif ($is == 'soru_sor') {
        $danisan_id = $_SESSION['user_id'];
        $uzman_id = $_POST['uzman_id'];
        $soru = $_POST['soru_metni'];
        if(!empty($uzman_id) && !empty($soru)) {
            $ekle = $conn->prepare("INSERT INTO uzman_sorulari (danisan_id, uzman_id, soru_metni) VALUES (?, ?, ?)");
            $ekle->execute([$danisan_id, $uzman_id, $soru]);
            header("Location: panel.php?durum=soru_gonderildi");
        } else {
            header("Location: panel.php?durum=hata");
        }
        exit();
    }

    // -11. uzmanın soruyu cevaplaması
    elseif ($is == 'cevapla') {
        $soru_id = $_POST['soru_id'];
        $cevap = $_POST['cevap_metni'];

        if(!empty($soru_id) && !empty($cevap)) {
            $guncelle = $conn->prepare("UPDATE uzman_sorulari SET cevap_metni = ?, durum = 'cevaplandi' WHERE id = ?");
            $guncelle->execute([$cevap, $soru_id]);
            
            $rol = $_SESSION['rol'];
            header("Location: " . $rol . "_paneli.php?durum=cevaplandi");
        } else {
            header("Location: panel.php?durum=hata");
        }
        exit();
    }

    // 12. günün tarifi paylaşma
    elseif ($is == 'tarif_paylas') {
        $diyetisyen_id = $_SESSION['user_id'];
        $baslik = $_POST['tarif_baslik'];
        $icerik = $_POST['tarif_icerik'];

        $sorgu = $conn->prepare("INSERT INTO gunun_tarifi (diyetisyen_id, tarif_baslik, tarif_icerik) VALUES (?, ?, ?)");
        $sorgu->execute([$diyetisyen_id, $baslik, $icerik]);
        header("Location: diyetisyen_paneli.php?durum=ok");
        exit();
    }

    // 13. günün antrenmanını paylaşma
    elseif ($is == 'antrenman_paylas' || $is == 'antrenman_duyuru_kaydet') {
        $hoca_id = $_SESSION['user_id'];
        
        // hoca panelindeki formdan gelen veriler alınıyor
        $baslik = $_POST['duyuru_baslik'] ?? $_POST['antrenman_baslik'];
        $icerik = $_POST['duyuru_icerik'] ?? $_POST['antrenman_icerik'];

        $sorgu = $conn->prepare("INSERT INTO gunun_antrenmani (hoca_id, antrenman_baslik, antrenman_icerik) VALUES (?, ?, ?)");
        $sorgu->execute([$hoca_id, $baslik, $icerik]);
        
        header("Location: hoca_paneli.php?durum=ok");
        exit();
    }

    // 14. premium özellik fotoğraf yükleme
    elseif ($is == 'foto_yukle') {
        $user_id = $_SESSION['user_id'];
        if (isset($_FILES['form_foto']) && $_FILES['form_foto']['error'] == 0) {
            $dosya = $_FILES['form_foto'];
            $dizin = "uploads/form_fotolar/";
            if (!file_exists($dizin)) { mkdir($dizin, 0777, true); }
            $uzanti = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));
            $yeni_ad = time() . "_" . rand(1000,9999) . "." . $uzanti;
            $hedef = $dizin . $yeni_ad;
            if (move_uploaded_file($dosya['tmp_name'], $hedef)) {
                $ekle = $conn->prepare("INSERT INTO gelisim_fotograflari (user_id, foto_yolu, aciklama, yuklenme_tarihi) VALUES (?, ?, '', NOW())");
                $ekle->execute([$user_id, $hedef]);
                header("Location: gelisim.php?yukleme=basarili");
            }
        }
        exit();
    }

    // 15. premium özellik fotoğraf silme
    elseif ($is == 'foto_sil') {
        $foto_id = $_GET['id'];
        $user_id = $_SESSION['user_id'];
        $sorgu = $conn->prepare("SELECT foto_yolu FROM gelisim_fotograflari WHERE id = ? AND user_id = ?");
        $sorgu->execute([$foto_id, $user_id]);
        $foto = $sorgu->fetch();
        if ($foto) {
            if (file_exists($foto['foto_yolu'])) { unlink($foto['foto_yolu']); }
            $conn->prepare("DELETE FROM gelisim_fotograflari WHERE id = ?")->execute([$foto_id]);
        }
        header("Location: gelisim.php?silme=basarili");
        exit();
    }

    // 16. hızlı veri güncelleme
    elseif ($is == 'hizli_guncelle') {
        $user_id = $_SESSION['user_id'];
        $alan = $_POST['alan']; 
        $deger = $_POST['deger'];
        $tarih = date('Y-m-d');
        $izinli = ['su_miktari', 'uyku_suresi', 'alinan_kalori', 'yakilan_kalori', 'spor_suresi', 'guncel_kilo'];
        if (!in_array($alan, $izinli)) { die("Yetkisiz alan!"); }
        $kontrol = $conn->prepare("SELECT id FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
        $kontrol->execute([$user_id, $tarih]);
        $mevcut = $kontrol->fetch();
        if ($mevcut) {
            $sorgu = $conn->prepare("UPDATE aktivite_kayitlari SET $alan = ? WHERE id = ?");
            $sorgu->execute([$deger, $mevcut['id']]);
        } else {
            $sorgu = $conn->prepare("INSERT INTO aktivite_kayitlari (user_id, $alan, kayit_tarihi) VALUES (?, ?, ?)");
            $sorgu->execute([$user_id, $deger, $tarih]);
        }
        header("Location: panel.php?durum=guncellendi");
        exit();
    }

    // 17. yemek ekleme işlemi
    elseif ($is == 'yemek_ekle') {
        $user_id = $_SESSION['user_id'];
        $tarih = date('Y-m-d');
        
        $besin_adi = $_POST['besin_adi'];
        $miktar = $_POST['miktar'];
        $toplam_kalori = $_POST['toplam_kalori'];

        // 1. veriyi beslenme günlüğüne ekle
        $birim = $_POST['birim'] ?? 'Adet'; // formdan gelen birimi al
        $yemek_kaydet = $conn->prepare("INSERT INTO beslenme_gunlugu (user_id, tarih, besin_adi, miktar, birim, toplam_kalori) VALUES (?, ?, ?, ?, ?, ?)");
        $yemek_kaydet->execute([$user_id, $tarih, $besin_adi, $miktar, $birim, $toplam_kalori]);

        // 2. aktivite_kayitlari toplam kaloriyi güncelle
        $kontrol = $conn->prepare("SELECT id FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
        $kontrol->execute([$user_id, $tarih]);
        
        if ($kontrol->rowCount() > 0) {
            // bugün kayıt varsa kalorinin üzerine ekle
            $guncelle = $conn->prepare("UPDATE aktivite_kayitlari SET alinan_kalori = alinan_kalori + ? WHERE user_id = ? AND kayit_tarihi = ?");
            $guncelle->execute([$toplam_kalori, $user_id, $tarih]);
        } else {
            // bugün ilk defa bir şey giriyorsa yeni satır oluştur
            $yeni_kayit = $conn->prepare("INSERT INTO aktivite_kayitlari (user_id, kayit_tarihi, alinan_kalori, su_miktari, uyku_suresi, yakilan_kalori, spor_suresi, guncel_kilo) VALUES (?, ?, ?, 0, 0, 0, 0, 0)");
            $yeni_kayit->execute([$user_id, $tarih, $toplam_kalori]);
        }

        // işlem bitince panele geri gönder
        header("Location: panel.php?durum=basarili");
        exit();
    }

    // premium iptal işlemi
    elseif (isset($_GET['is']) && $_GET['is'] == 'premium_iptal') {
        // oturumdaki kullanıcı id alıyoruz
        $uid = $_SESSION['user_id'];
        
        try {
            // veritabanında is_premium sütununu 0 yapıyoruz
            $sorgu = $conn->prepare("UPDATE kullanicilar SET is_premium = 0 WHERE id = ?");
            $sonuc = $sorgu->execute([$uid]);
            
            if ($sonuc) {
                // başarılıysa panel.php geri gönder
                header("Location: panel.php?durum=iptal_basarili");
                exit();
            } else {
                echo "Güncelleme sırasında bir hata oluştu.";
            }
        } catch (PDOException $e) {
            die("Veritabanı hatası: " . $e->getMessage());
        }
    }

    // 18. egzersiz ekleme işlemi
    elseif ($is == 'egzersiz_ekle') {
        $user_id = $_SESSION['user_id'];
        $tarih = date('Y-m-d');
        
        $egzersiz_adi = $_POST['egzersiz_adi'];
        $sure_dk = $_POST['sure_dk'];
        $yakilan_kalori = $_POST['yakilan_kalori'];

        // 1. veriyi egzersiz günlüğüne ekle
        $spor_kaydet = $conn->prepare("INSERT INTO egzersiz_gunlugu (user_id, tarih, egzersiz_adi, sure_dk, yakilan_kalori) VALUES (?, ?, ?, ?, ?)");
        $spor_kaydet->execute([$user_id, $tarih, $egzersiz_adi, $sure_dk, $yakilan_kalori]);

        // 2. aktivite_kayitlari toplam süre ve yakılan kaloriyi güncelle
        $kontrol = $conn->prepare("SELECT id FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
        $kontrol->execute([$user_id, $tarih]);
        
        if ($kontrol->rowCount() > 0) {
            // kayıt varsa üzerine ekle
            $guncelle = $conn->prepare("UPDATE aktivite_kayitlari SET yakilan_kalori = yakilan_kalori + ?, spor_suresi = spor_suresi + ? WHERE user_id = ? AND kayit_tarihi = ?");
            $guncelle->execute([$yakilan_kalori, $sure_dk, $user_id, $tarih]);
        } else {
            // kayıt yoksa yeni satır oluştur
            $yeni_kayit = $conn->prepare("INSERT INTO aktivite_kayitlari (user_id, kayit_tarihi, yakilan_kalori, spor_suresi, su_miktari, uyku_suresi, alinan_kalori, guncel_kilo) VALUES (?, ?, ?, ?, 0, 0, 0, 0)");
            $yeni_kayit->execute([$user_id, $tarih, $yakilan_kalori, $sure_dk]);
        }

        header("Location: panel.php?durum=basarili");
        exit();
    }

} catch (PDOException $e) {
    die("İşlem hatası: " . $e->getMessage());
}
?>