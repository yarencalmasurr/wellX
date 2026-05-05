<?php
session_start();
include 'baglan.php';

$islem = $_GET['is'] ?? '';
$diyetisyen_id = $_SESSION['user_id'];

// --- GÜNÜN TARİFİNİ VEYA DUYURUYU KAYDET ---
if ($islem == 'tarif_kaydet' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $baslik = trim($_POST['tarif_baslik']);
    $icerik = trim($_POST['tarif_icerik']);

    if (!empty($baslik) && !empty($icerik)) {
        try {
            $sorgu = $conn->prepare("INSERT INTO gunun_tarifi (diyetisyen_id, baslik, icerik) VALUES (?, ?, ?)");
            $sorgu->execute([$diyetisyen_id, $baslik, $icerik]);
            
            header("Location: diyetisyen_paneli.php?durum=tarif_ok");
            exit; 
        } catch (PDOException $e) {
            die("Hata Oluştu: " . $e->getMessage());
        }
    }
}

// --- DANIŞANA ÖZEL NOT GÖNDERME ---
if ($islem == 'plan_yaz' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $danisan_id = $_POST['danisan_id'];
    $plan_metni = trim($_POST['plan_metni']);

    try {
        $sorgu = $conn->prepare("INSERT INTO beslenme_planlari (user_id, plan_notu, okundu) VALUES (?, ?, 0)");
        $sorgu->execute([$danisan_id, $plan_metni]);
        
        header("Location: diyetisyen_paneli.php?durum=not_ok");
        exit;
    } catch (PDOException $e) {
        die("Hata Oluştu: " . $e->getMessage());
    }
}
?>