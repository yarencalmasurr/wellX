<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'baglan.php';

// kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    die("Lütfen giriş yapın.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $user_id = $_SESSION['user_id'];
    // plan_turu olarak güncelle
    $plan = $_POST['plan_turu'] ?? ''; 

    $dosya_yolu = null;

    // plan kontrolü
    $gecerli_planlar = ['bireysel', 'ogrenci', 'yetiskin', 'kurumsal'];

    if (!in_array($plan, $gecerli_planlar)) {
        die("Geçersiz plan seçildi.");
    }

    // öğrenci belgesi yükleme
    if ($plan == 'ogrenci') {
        // ogrenci_belgesi olarak güncelle
        if (!isset($_FILES['ogrenci_belgesi'])) {
            die("Öğrenci belgesi yüklenmedi.");
        }

        if ($_FILES['ogrenci_belgesi']['error'] != 0) {
            die("Dosya yükleme hatası oluştu.");
        }

        $hedef_klasor = "uploads/belgeler/";

        // klasör yoksa oluştur
        if (!file_exists($hedef_klasor)) {
            mkdir($hedef_klasor, 0777, true);
        }

        $orijinal_dosya_adi = basename($_FILES['ogrenci_belgesi']['name']);

        $uzanti = strtolower(pathinfo($orijinal_dosya_adi, PATHINFO_EXTENSION));

        $izinli_uzantilar = ['pdf', 'jpg', 'jpeg', 'png'];

        if (!in_array($uzanti, $izinli_uzantilar)) {
            die("Sadece PDF, JPG, JPEG ve PNG dosyaları yüklenebilir.");
        }

        $yeni_dosya_adi = time() . "_" . uniqid() . "." . $uzanti;

        $hedef_yol = $hedef_klasor . $yeni_dosya_adi;

        if (move_uploaded_file($_FILES['ogrenci_belgesi']['tmp_name'], $hedef_yol)) {

            $dosya_yolu = $hedef_yol;

        } else {

            die("Dosya yüklenemedi.");
        }
    }

    try {

        // premium başvurusu oluştur
        $sorgu = $conn->prepare("
            INSERT INTO premium_basvurulari 
            (user_id, plan_tipi, ogrenci_belgesi, durum) 
            VALUES (?, ?, ?, 'beklemede')
        ");

        $sorgu->execute([
            $user_id,
            $plan,
            $dosya_yolu
        ]);

        // kullanıcıyı premium yap
        $premium_yap = $conn->prepare("
            UPDATE kullanicilar
            SET is_premium = 1
            WHERE id = ?
        ");

        $premium_yap->execute([$user_id]);

        // ödeme sayfasına yönlendir
        header("Location: odeme_sayfasi.php?plan=" . urlencode($plan));
        exit();

    } catch (PDOException $e) {

        die("Veritabanı hatası: " . $e->getMessage());

    } catch (Exception $e) {

        die("Genel hata: " . $e->getMessage());
    }

} else {

    // direkt erişim engeli
    header("Location: premium_planlar.php");
    exit();
}
?>