<?php
/**
 * Proje: saglik_portali
 * Dosya: uzman_secmesi.php
 */

session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Gelen rolü temizle ve küçük harfe çevir (hoca veya diyetisyen)
$rol_tipi = isset($_GET['rol']) ? strtolower(trim($_GET['rol'])) : ''; 

// GÜVENLİK: Sadece 'diyetisyen' ve 'hoca' rollerinin aranmasına izin veriyoruz.
// URL'den manipüle edilip 'danışan' listelenmesi engellendi.
if (!in_array($rol_tipi, ['diyetisyen', 'hoca'])) {
    header("Location: panel.php"); 
    exit();
}

// Veritabanındaki uzmanları çekerken kullanıcı adını (kullanici_adi) da ekledik
$sorgu = $conn->prepare("SELECT id, ad_soyad, kullanici_adi, email FROM kullanicilar WHERE LOWER(rol) = ?");
$sorgu->execute([$rol_tipi]);
$uzmanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($rol_tipi); ?> Seçimi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f1f5f9; margin: 0; padding: 40px; color: #1e293b; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 40px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); text-align: center; transition: 0.3s; border: 2px solid transparent; }
        .card:hover { transform: translateY(-5px); border-color: #10b981; }
        .icon { width: 60px; height: 60px; background: #f0fdf4; color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 24px; }
        .card.hoca-card .icon { background: #eff6ff; color: #3b82f6; }
        .card.hoca-card:hover { border-color: #3b82f6; }
        .btn-select { display: block; background: #1e293b; color: white; padding: 12px; border-radius: 12px; text-decoration: none; font-weight: 600; margin-top: 20px; transition: 0.3s; }
        .btn-select:hover { background: #10b981; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #64748b; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <a href="panel.php" class="back-link"><i class="fas fa-arrow-left"></i> Panele Geri Dön</a>
    
    <div class="header">
        <h1><?php echo ($rol_tipi == 'diyetisyen' ? '🍏 Diyetisyenini Seç' : '🏋️ Spor Hocanı Seç'); ?></h1>
        <p>Gelişiminizi takip edecek uzmanı listeden belirleyebilirsiniz.</p>
    </div>

    <div class="grid">
        <?php foreach($uzmanlar as $u): ?>
            <div class="card <?php echo ($rol_tipi == 'hoca' ? 'hoca-card' : ''); ?>">
                <div class="icon">
                    <i class="fas <?php echo ($rol_tipi == 'diyetisyen' ? 'fa-user-md' : 'fa-running'); ?>"></i>
                </div>
                <h3 style="margin: 0;"><?php echo htmlspecialchars($u['ad_soyad']); ?></h3>
                <small style="color: #94a3b8; font-weight: 500;">(@<?php echo htmlspecialchars($u['kullanici_adi']); ?>)</small>
                
                <p style="color: #64748b; font-size: 14px; margin: 10px 0;"><?php echo htmlspecialchars($u['email']); ?></p>
                
                <a href="islem_v2.php?is=uzman_atama&uzman_id=<?php echo $u['id']; ?>&rol=<?php echo $rol_tipi; ?>" 
                   class="btn-select" 
                   onclick="return confirm('Bu uzman ile çalışmak istediğinize emin misiniz?')">
                   Seç ve Başla
                </a>
            </div>
        <?php endforeach; ?>

        <?php if(empty($uzmanlar)): ?>
            <p style="grid-column: 1/-1; text-align: center; color: #64748b;">Henüz bu rolde bir uzman kaydı bulunmuyor.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>