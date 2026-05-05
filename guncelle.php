<?php
session_start();
include 'baglan.php';

// Güvenlik Kontrolü
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] != 'danışan' && $_SESSION['rol'] != 'danisan')) {
    header("Location: index.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];
$tarih = date('Y-m-d');

// Mevcut verileri çek
$k = $conn->prepare("SELECT * FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
$k->execute([$user_id, $tarih]);
$veri = $k->fetch();

// Eğer bugün kayıt yoksa güncelleme yapamaz
if (!$veri) {
    header("Location: panel.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Veri Güncelle | Sağlık Takip</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --blue: #0ea5e9; --bg: #f8fafc; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); width: 100%; max-width: 500px; }
        h3 { margin-top: 0; margin-bottom: 25px; font-size: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .input-group { display: flex; flex-direction: column; gap: 8px; }
        label { font-size: 13px; font-weight: 600; color: #64748b; }
        input { padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; font-family: inherit; width: 100%; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 16px; background: var(--blue); color: white; border: none; border-radius: 14px; font-weight: 600; cursor: pointer; }
        .btn-cancel { display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<div class="card">
    <h3>✏️ Verileri Güncelle</h3>
    <form action="islem_v2.php?is=guncelle" method="POST">
        <div class="form-grid">
            <div class="input-group">
                <label>Su (Litre)</label>
                <input type="number" step="0.1" name="su_miktari" value="<?=$veri['su_miktari']?>">
            </div>
            <div class="input-group">
                <label>Uyku (Saat)</label>
                <input type="number" step="0.1" name="uyku_suresi" value="<?=$veri['uyku_suresi']?>">
            </div>
            <div class="input-group">
                <label>Alınan Kalori</label>
                <input type="number" name="alinan_kalori" value="<?=$veri['alinan_kalori']?>">
            </div>
            <div class="input-group">
                <label>Yakılan Kalori</label>
                <input type="number" name="yakilan_kalori" value="<?=$veri['yakilan_kalori']?>">
            </div>
            <div class="input-group">
                <label>Spor (Dakika)</label>
                <input type="number" name="spor_suresi" value="<?=$veri['spor_suresi']?>">
            </div>
            <div class="input-group">
                <label>Kilo (kg)</label>
                <input type="number" step="0.1" name="guncel_kilo" value="<?=$veri['guncel_kilo']?>">
            </div>
        </div>
        <button type="submit" class="btn-submit">Güncellemeleri Kaydet</button>
        <a href="panel.php" class="btn-cancel">İptal et ve Geri Dön</a>
    </form>
</div>

</body>
</html>