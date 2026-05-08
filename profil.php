<?php
/**
 * Proje: saglik_portali
 * Dosya: profil.php
 */

session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mevcut kullanıcı verilerini çek
$sorgu = $conn->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$sorgu->execute([$user_id]);
$user = $sorgu->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profil Ayarlarım</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --blue: #0ea5e9; --bg: #f8fafc; --sidebar: #ffffff; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; display: flex; color: #1e293b; }
        .sidebar { width: 260px; background: var(--sidebar); height: 100vh; padding: 30px 20px; box-shadow: 4px 0 24px rgba(0,0,0,0.03); position: fixed; }
        .logo { font-size: 22px; font-weight: 600; color: #0f172a; margin-bottom: 40px; display: flex; align-items: center; gap: 10px; text-decoration:none; }
        .menu-item { display: flex; align-items: center; padding: 14px 18px; color: #64748b; text-decoration: none; border-radius: 12px; margin-bottom: 8px; transition: 0.2s; }
        .menu-item.active { background: #f0f9ff; color: var(--blue); font-weight: 600; }
        .main { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        
        .profile-card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 15px rgba(0,0,0,0.04); max-width: 600px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #64748b; font-size: 14px; }
        input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; box-sizing: border-box; font-family: inherit; }
        .btn-update { background: var(--blue); color: white; border: none; padding: 15px; border-radius: 14px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 20px; }
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="panel.php" class="logo">🩺 Sağlık Takip</a>
    <a href="panel.php" class="menu-item">🏠 Özet Paneli</a>
    <a href="beslenme.php" class="menu-item">🥗 Beslenme</a>
    <a href="egzersiz.php" class="menu-item">🏋️ Egzersiz</a>
    <a href="gelisim.php" class="menu-item">📈 Gelişim</a>
    <a href="profil.php" class="menu-item active">👤 Profil Ayarları</a>
    <a href="cikis.php" class="menu-item" style="color:#ef4444; margin-top: 40px;">🚪 Çıkış Yap</a>
</div>

<div class="main">
    <h1>Profil Ayarların</h1>

    <?php if(isset($_GET['durum'])): ?>
        <div class="alert" style="background: #dcfce7; color: #166534;">✅ Bilgilerin başarıyla güncellendi.</div>
    <?php endif; ?>

    <div class="profile-card">
        <form action="islem_v2.php?is=profil_guncelle" method="POST">
            <div class="form-group">
                <label>Ad Soyad</label>
                <input type="text" name="ad_soyad" value="<?php echo htmlspecialchars($user['ad_soyad']); ?>" required>
            </div>
            <div class="form-group">
                <label>Kullanıcı Adı (Giriş için)</label>
                <input type="text" name="kullanici_adi" value="<?php echo htmlspecialchars($user['kullanici_adi']); ?>" required>
            </div>
            <div class="form-group">
                <label>E-posta Adresi</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 30px 0;">
            <div class="form-group">
                <label>Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)</label>
                <input type="password" name="yeni_sifre" placeholder="••••••••">
            </div>
            <button type="submit" class="btn-update">Ayarları Kaydet</button>
        </form>
    </div>
</div>

</body>
</html>