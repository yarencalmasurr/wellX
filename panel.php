<?php
/**
 * Proje: saglik_portali
 * Dosya: panel.php
 * Açıklama: Danışanların günlük veri girişi ve takip paneli
 */

// Oturumu başlat
session_start();

// Veritabanı bağlantısını dahil et
include 'baglan.php';

// Güvenlik Kontrolü: Kullanıcı giriş yapmamışsa veya danışan değilse index'e gönder
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] != 'danışan' && $_SESSION['rol'] != 'danisan')) {
    header("Location: index.php"); 
    exit();
}

// Kullanıcı bilgilerini ve tarih bilgisini al
$user_id = $_SESSION['user_id'];
$bugun = date('Y-m-d');

// --- GÜNLÜK KAYIT SINIRI KONTROLÜ ---
// Eğer bugün veritabanında bu kullanıcıya ait kayıt varsa $mevcut_kayit dolu dönecek
$kontrol = $conn->prepare("SELECT id FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
$kontrol->execute([$user_id, $bugun]);
$mevcut_kayit = $kontrol->fetch();

// İstatistikleri çek (Bugünün verileri)
$sorgu = $conn->prepare("SELECT SUM(su_miktari) as t_su, SUM(alinan_kalori) as t_alinan, SUM(uyku_suresi) as t_uyku FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
$sorgu->execute([$user_id, $bugun]);
$veri = $sorgu->fetch(PDO::FETCH_ASSOC);

$su = $veri['t_su'] ?? 0;
$alinan = $veri['t_alinan'] ?? 0;
$uyku = $veri['t_uyku'] ?? 0;

// Bildirimleri kontrol et (Beslenme)
$d_uyari = $conn->prepare("SELECT id FROM beslenme_planlari WHERE user_id = ? AND okundu = 0 LIMIT 1");
$d_uyari->execute([$user_id]);
$yeni_diyet = $d_uyari->fetch();

// Bildirimleri kontrol et (Egzersiz)
$h_uyari = $conn->prepare("SELECT id FROM egzersiz_planlari WHERE user_id = ? AND okundu = 0 LIMIT 1");
$h_uyari->execute([$user_id]);
$yeni_hoca = $h_uyari->fetch();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sağlık Takip | Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { 
            --blue: #0ea5e9; 
            --orange: #f59e0b; 
            --green: #10b981; 
            --bg: #f8fafc; 
            --sidebar: #ffffff; 
        }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: var(--bg); 
            margin: 0; 
            display: flex; 
            color: #1e293b; 
        }
        
        /* Sidebar Tasarımı */
        .sidebar { 
            width: 260px; 
            background: var(--sidebar); 
            height: 100vh; 
            padding: 30px 20px; 
            box-shadow: 4px 0 24px rgba(0,0,0,0.03); 
            position: fixed; 
        }
        .logo { 
            font-size: 22px; 
            font-weight: 600; 
            color: #0f172a; 
            margin-bottom: 40px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .menu-item { 
            display: flex; 
            align-items: center; 
            padding: 14px 18px; 
            color: #64748b; 
            text-decoration: none; 
            border-radius: 12px; 
            margin-bottom: 8px; 
            transition: 0.2s; 
        }
        .menu-item.active { background: #f0f9ff; color: var(--blue); font-weight: 600; }
        .menu-item:hover:not(.active) { background: #f1f5f9; }

        /* Ana İçerik */
        .main { margin-left: 300px; padding: 40px; width: calc(100% - 300px); }
        h1 { font-size: 28px; margin-bottom: 30px; }

        /* Mesaj Kutuları */
        .msg-box { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-radius: 16px; margin-bottom: 20px; border: 1px solid; }
        
        /* İstatistik Kartları */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 24px; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); border-left: 6px solid; }
        .stat-card.su { border-color: var(--blue); }
        .stat-card.kalori { border-color: var(--orange); }
        .stat-card.uyku { border-color: var(--green); }
        
        .stat-label { font-size: 14px; color: #64748b; display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
        .stat-value { font-size: 24px; font-weight: 600; margin-bottom: 15px; }
        
        .progress-container { background: #f1f5f9; height: 10px; border-radius: 99px; overflow: hidden; }
        .progress-bar { height: 100%; border-radius: 99px; transition: width 0.5s ease-out; }

        /* Grid ve Kartlar */
        .grid-container { display: grid; grid-template-columns: 1.4fr 1fr; gap: 30px; }
        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); margin-bottom: 20px; }
        h3 { margin-top: 0; margin-bottom: 25px; font-size: 18px; display: flex; align-items: center; gap: 10px; }

        /* Form ve Butonlar */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        input { padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; font-family: inherit; font-size: 14px; outline: none; }
        input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }
        .btn-submit { width: 100%; padding: 16px; background: var(--blue); color: white; border: none; border-radius: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: #0284c7; }

        /* Kayıt Listesi */
        .records-list { list-style: none; padding: 0; margin: 0; }
        .record-item { display: flex; flex-direction: column; padding: 15px; border-bottom: 1px solid #f1f5f9; background: #fcfcfc; border-radius: 10px; margin-bottom: 10px; }
        .record-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .btn-del { color: #ef4444; text-decoration: none; font-size: 13px; font-weight: 600; }
        .val-label { color: #64748b; font-size: 13px; }
        .val-data { font-weight: 600; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">🩺 Sağlık Takip</div>
    <a href="panel.php" class="menu-item active">🏠 Özet Paneli</a>
    <a href="beslenme.php" class="menu-item">🥗 Beslenme</a>
    <a href="egzersiz.php" class="menu-item">🏋️ Egzersiz</a>
    <a href="gelisim.php" class="menu-item">📈 Gelişim</a>
    <a href="danisan_mesajlar.php" class="menu-item">📩 Uzman Notlarım</a>
    <a href="cikis.php" class="menu-item" style="color:#ef4444; margin-top: 40px;">🚪 Çıkış Yap</a>
</div>

<div class="main">
    <h1>Hoş Geldin, <?php echo $_SESSION['ad_soyad']; ?>! 👋</h1>

    <?php if ($yeni_diyet): ?>
        <div class="msg-box" style="background:#fff9db; border-color:#fab005; color:#856404;">
            <span>🍎 Yeni bir beslenme planınız var!</span>
            <a href="islem_v2.php?is=mesaj_oku&tip=diyet" style="background:#f08c00; color:white; padding:6px 12px; border-radius:8px; text-decoration:none; font-size:12px;">Görüntüle</a>
        </div>
    <?php endif; ?>

    <?php if ($yeni_hoca): ?>
        <div class="msg-box" style="background:#e0f2fe; border-color:#0ea5e9; color:#0369a1;">
            <span>💪 Yeni bir antrenman notunuz var!</span>
            <a href="islem_v2.php?is=mesaj_oku&tip=hoca" style="background:var(--blue); color:white; padding:6px 12px; border-radius:8px; text-decoration:none; font-size:12px;">Görüntüle</a>
        </div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-card su">
            <div class="stat-label">💧 Bugün İçilen Su</div>
            <div class="stat-value"><?php echo $su; ?> / 2.5 L</div>
            <div class="progress-container">
                <div class="progress-bar" style="background: var(--blue); width: <?php echo min(($su/2.5)*100, 100); ?>%"></div>
            </div>
        </div>

        <div class="stat-card kalori">
            <div class="stat-label">🔥 Alınan Kalori</div>
            <div class="stat-value"><?php echo round($alinan); ?> / 2000 kcal</div>
            <div class="progress-container">
                <div class="progress-bar" style="background: var(--orange); width: <?php echo min(($alinan/2000)*100, 100); ?>%"></div>
            </div>
        </div>

        <div class="stat-card uyku">
            <div class="stat-label">😴 Uyku Süresi</div>
            <div class="stat-value"><?php echo $uyku; ?> / 8 Saat</div>
            <div class="progress-container">
                <div class="progress-bar" style="background: var(--green); width: <?php echo min(($uyku/8)*100, 100); ?>%"></div>
            </div>
        </div>
    </div>

    <div class="grid-container">
        <div class="card">
            <h3>➕ Bugünün Verilerini Gir</h3>
            
            <?php if ($mevcut_kayit): ?>
                <div style="background:#fff7ed; padding:20px; border-radius:12px; border:1px solid #ffedd5; text-align:center;">
                    <p>⚠️ Bugün zaten kayıt yaptınız.</p>
                    <a href="guncelle.php" style="background:#f97316; color:white; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600;">Güncellemek ister misiniz?</a>
                </div>
            <?php else: ?>
                <form action="islem_v2.php?is=verileri_kaydet" method="POST">
                    <div class="form-grid">
                        <input type="number" step="0.1" name="su_miktari" placeholder="Su (Litre)">
                        <input type="number" step="0.1" name="uyku_suresi" placeholder="Uyku (Saat)">
                        <input type="number" name="alinan_kalori" placeholder="Alınan Kalori">
                        <input type="number" name="yakilan_kalori" placeholder="Yakılan Kalori">
                        <input type="number" name="spor_suresi" placeholder="Spor (Dakika)">
                        <input type="number" step="0.1" name="guncel_kilo" placeholder="Kilo (kg)">
                    </div>
                    <button type="submit" class="btn-submit">Kaydı Sisteme İşle</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>📋 Bugünün Kayıtları</h3>
            <div class="records-list">
                <?php
                $liste = $conn->prepare("SELECT * FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ? ORDER BY id DESC");
                $liste->execute([$user_id, $bugun]);
                $satirlar = $liste->fetchAll();

                foreach ($satirlar as $k):
                ?>
                <div class="record-item">
                    <div class="record-row">
                        <span>Detaylar</span>
                        <a href="islem_v2.php?is=kayit_sil&id=<?php echo $k['id']; ?>" onclick="return confirm('Silmek istediğine emin misin?')" class="btn-del">Sil</a>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:5px; font-size:13px;">
                        <?php if($k['alinan_kalori'] > 0) echo "<div><span class='val-label'>🍎 Alınan:</span> <span class='val-data'>".$k['alinan_kalori']." kcal</span></div>"; ?>
                        <?php if($k['su_miktari'] > 0) echo "<div><span class='val-label'>💧 Su:</span> <span class='val-data'>".$k['su_miktari']." L</span></div>"; ?>
                        <?php if($k['uyku_suresi'] > 0) echo "<div><span class='val-label'>😴 Uyku:</span> <span class='val-data'>".$k['uyku_suresi']." s</span></div>"; ?>
                        <?php if($k['spor_suresi'] > 0) echo "<div><span class='val-label'>🏋️ Spor:</span> <span class='val-data'>".$k['spor_suresi']." dk</span></div>"; ?>
                        <?php if($k['yakilan_kalori'] > 0) echo "<div><span class='val-label'>🔥 Yakılan:</span> <span class='val-data'>".$k['yakilan_kalori']." kcal</span></div>"; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(!$satirlar): ?>
                    <p style="text-align:center; color:#94a3b8; margin-top:20px;">Henüz kayıt yok.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>