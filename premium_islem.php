<?php
session_start();
include 'baglan.php'; // Veritabanı bağlantı dosyanızın adı farklıysa güncelleyin

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id']; // Giriş yapan kullanıcının ID'si
    $plan = $_POST['plan'];
    $dosya_yolu = null;

    // 1. Öğrenci Belgesi Kontrolü ve Yükleme
    if ($plan == 'ogrenci' && isset($_FILES['belge'])) {
        $hedef_klasor = "uploads/belgeler/";
        
        // Klasör yoksa oluştur
        if (!file_exists($hedef_klasor)) {
            mkdir($hedef_klasor, 0777, true);
        }
        
        $dosya_adi = time() . "_" . basename($_FILES['belge']['name']);
        $hedef_yol = $hedef_klasor . $dosya_adi;
        
        if (move_uploaded_file($_FILES['belge']['tmp_name'], $hedef_yol)) {
            $dosya_yolu = $hedef_yol;
        }
    }

    // 2. Veritabanına Kayıt (Ödeme öncesi başvuru oluşturma)
    // Not: Tablonuzda bu alanların olduğundan emin olun veya bu kısmı şimdilik pas geçebilirsiniz
    try {
        $sorgu = $conn->prepare("INSERT INTO premium_basvurulari (user_id, plan_tipi, belge_yolu, durum) VALUES (?, ?, ?, 'beklemede')");
        $sorgu->execute([$user_id, $plan, $dosya_yolu]);
    } catch (Exception $e) {
        // Tablo henüz yoksa hata vermemesi için bu kısmı loglayabilirsiniz
    }

    // 3. Ödeme Sayfasına Yönlendir
    header("Location: odeme_sayfasi.php?plan=" . $plan);
    exit();
} else {
    // Doğrudan bu dosyaya erişilirse geri gönder
    header("Location: premium_planlar.php");
    exit();
}
?>