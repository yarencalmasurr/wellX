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
            else header("Location: panel.php");
            exit();
        } else {
            header("Location: index.php?hata=1");
            exit();
        }
    }

    // --- 2. UZMAN KAYIT (BAŞVURU) ---
    elseif ($is == 'uzman_kayit') {
        $ad_soyad = $_POST['ad_soyad'];
        $email = $_POST['email'];
        $uzmanlik = $_POST['uzmanlik'];
        $tecrube = $_POST['tecrube'];
        
        $ekle = $conn->prepare("INSERT INTO uzman_basvurulari (ad_soyad, email, uzmanlik, tecrube, durum) VALUES (?, ?, ?, ?, 'beklemede')");
        $ekle->execute([$ad_soyad, $email, $uzmanlik, $tecrube]);
        header("Location: index.php?basvuru=basarili");
        exit();
    }

    // --- 3. DANIŞAN KAYIT ---
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

    // --- 4. VERİLERİ KAYDET VEYA GÜNCELLE (DANIŞAN PANELİ) ---
    elseif ($is == 'verileri_kaydet') {
        $user_id = $_SESSION['user_id'];
        $su = $_POST['su_miktari'];
        $uyku = $_POST['uyku_suresi'];
        $alinan = $_POST['alinan_kalori'];
        $yakilan = $_POST['yakilan_kalori'];
        $spor = $_POST['spor_suresi'];
        $kilo = $_POST['guncel_kilo'];
        $tarih = date('Y-m-d');

        $ekle = $conn->prepare("INSERT INTO aktivite_kayitlari (user_id, su_miktari, uyku_suresi, alinan_kalori, yakilan_kalori, spor_suresi, guncel_kilo, kayit_tarihi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ekle->execute([$user_id, $su, $uyku, $alinan, $yakilan, $spor, $kilo, $tarih]);
        header("Location: panel.php?kayit=tamam");
        exit();
    }

    // --- 5. UZMAN ATAMA (DANIŞANIN UZMAN SEÇMESİ) ---
    elseif ($is == 'uzman_atama') {
        $danisan_id = $_SESSION['user_id'];
        $uzman_id = $_GET['uzman_id'];
        $rol = $_GET['rol'];

        $sil = $conn->prepare("DELETE FROM uzman_danisan_eslesmeleri WHERE danisan_id = ? AND uzman_rol = ?");
        $sil->execute([$danisan_id, $rol]);

        $ekle = $conn->prepare("INSERT INTO uzman_danisan_eslesmeleri (danisan_id, uzman_id, uzman_rol) VALUES (?, ?, ?)");
        $ekle->execute([$danisan_id, $uzman_id, $rol]);
        header("Location: panel.php?atama=basarili");
        exit();
    }

    // --- 6. UZMANIN PLAN YAZMASI ---
    
    elseif ($is == 'plan_yaz') {
        $uzman_id = $_SESSION['user_id'];
        $danisan_id = $_POST['danisan_id'];
        $plan = $_POST['plan_metni'];
        $rol = $_SESSION['rol'];

        try {
            if ($rol == 'diyetisyen') {
                // Diyetisyen tablosu kontrol edildi: plan_metni
                $sorgu = $conn->prepare("INSERT INTO beslenme_planlari (user_id, diyetisyen_id, plan_metni) VALUES (?, ?, ?)");
            } else {
                // Hoca tablosu (HATA BURADAYDI): 
                // Eğer egzersiz_notu hata veriyorsa, veritabanındaki ismin ne olduğunu buraya yazmalısın.
                // Senin paylaştığın image_8e66b9.jpg'de sütun adı 'egzersiz_notu' görünüyordu.
                $sorgu = $conn->prepare("INSERT INTO egzersiz_planlari (user_id, hoca_id, egzersiz_notu) VALUES (?, ?, ?)");
            }
            
            $sorgu->execute([$danisan_id, $uzman_id, $plan]);
            header("Location: " . $rol . "_paneli.php?islem=basarili");
            exit();

        } catch (PDOException $e) {
            // Hatanın tam olarak nerede olduğunu anlamak için geçici bir hata mesajı:
            die("SQL Hatası: " . $e->getMessage() . " | Rol: " . $rol);
        }
    }

    // --- 7. TARİFE PUAN VERME ---
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

    // --- 8. ADMIN BAŞVURU ONAY/RED ---
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
        header("Location: profil.php?durum=ok");
        exit();
    }

    // --- 10. PREMIUM: UZMANA SORU SORMA ---
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

    // --- 11. UZMAN: SORUYU CEVAPLAMA ---
    elseif ($is == 'cevapla') {
        $soru_id = $_POST['soru_id'];
        $cevap = $_POST['cevap_metni'];

        if(!empty($soru_id) && !empty($cevap)) {
            $guncelle = $conn->prepare("UPDATE uzman_sorulari SET cevap_metni = ?, durum = 'cevaplandi' WHERE id = ?");
            $guncelle->execute([$cevap, $soru_id]);
            
            $rol = $_SESSION['rol'];
            if($rol == 'diyetisyen') {
                header("Location: diyetisyen_paneli.php?durum=cevaplandi");
            } else {
                header("Location: hoca_paneli.php?durum=cevaplandi");
            }
        } else {
            header("Location: panel.php?durum=hata");
        }
        exit();
    }

    // --- 12. PREMIUM: FOTOĞRAF YÜKLEME ---
    elseif ($is == 'foto_yukle') {
        $user_id = $_SESSION['user_id'];
        
        if (isset($_FILES['form_foto']) && $_FILES['form_foto']['error'] == 0) {
            $dosya = $_FILES['form_foto'];
            $dizin = "uploads/form_fotolar/";
            
            if (!file_exists($dizin)) { mkdir($dizin, 0777, true); }

            $uzanti = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));
            $izinli = ['jpg','jpeg','png','webp'];

            if(!in_array($uzanti, $izinli)){
                die("Geçersiz dosya formatı.");
            }

            $yeni_ad = time() . "_" . rand(1000,9999) . "." . $uzanti;
            $hedef = $dizin . $yeni_ad;

            if (move_uploaded_file($dosya['tmp_name'], $hedef)) {
                $ekle = $conn->prepare("INSERT INTO gelisim_fotograflari (user_id, foto_yolu, aciklama, yuklenme_tarihi) VALUES (?, ?, ?, NOW())");
                $ekle->execute([$user_id, $hedef, '']); 
                header("Location: gelisim.php?yukleme=basarili");
            } else {
                die("Dosya taşıma hatası.");
            }
        }
        exit();
    }

    // --- 13. PREMIUM: FOTOĞRAF SİLME ---
    elseif ($is == 'foto_sil') {
        $foto_id = $_GET['id'];
        $user_id = $_SESSION['user_id'];

        $sorgu = $conn->prepare("SELECT foto_yolu FROM gelisim_fotograflari WHERE id = ? AND user_id = ?");
        $sorgu->execute([$foto_id, $user_id]);
        $foto = $sorgu->fetch();

        if ($foto) {
            if (file_exists($foto['foto_yolu'])) {
                unlink($foto['foto_yolu']);
            }
            $conn->prepare("DELETE FROM gelisim_fotograflari WHERE id = ?")->execute([$foto_id]);
            header("Location: gelisim.php?silme=basarili");
        }
        exit();
    }

    // --- 14. HIZLI VERİ GÜNCELLEME (TEKİL) ---
    elseif ($is == 'hizli_guncelle') {
        $user_id = $_SESSION['user_id'];
        $alan = $_POST['alan']; 
        $deger = $_POST['deger'];
        $tarih = date('Y-m-d');

        $izinli = ['su_miktari', 'uyku_suresi', 'alinan_kalori', 'yakilan_kalori', 'spor_suresi', 'guncel_kilo'];
        if (!in_array($alan, $izinli)) { die("Hata: Yetkisiz alan!"); }

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

} catch (PDOException $e) {
    die("İşlem hatası: " . $e->getMessage());
}
?>
