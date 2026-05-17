<?php
session_start();
include 'baglan.php';

// kullanıcının giriş yapıp yapmadığı ve admin yetkisine sahip olup olmadığı kontrol ediliyor. BU satır sayesinde güvenlik açığı önleniyor.
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit();
}

try {
    // bekleyen uzman başvuruları listeleniyor
    $bekleyen_sorgu = $conn->prepare("SELECT * FROM uzman_basvurulari WHERE durum = 'beklemede' ORDER BY id DESC");
    $bekleyen_sorgu->execute();
    $bekleyenler = $bekleyen_sorgu->fetchAll(PDO::FETCH_ASSOC);

    // onaylanan ya da reddedilen başvuru sayıları
    $istatistik = $conn->query("
        SELECT 
            SUM(CASE WHEN durum = 'beklemede' THEN 1 ELSE 0 END) as beklemede,
            SUM(CASE WHEN durum = 'onaylandi' THEN 1 ELSE 0 END) as onaylanan,
            SUM(CASE WHEN durum = 'reddedildi' THEN 1 ELSE 0 END) as reddedilen
        FROM uzman_basvurulari
    ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>

<!-- admin paneli ve  yönetim sayfası stilleri -->
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Paneli | WellX Yönetim</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --admin-dark: #0f172a; 
            --admin-primary: #4f46e5; 
            --success: #10b981; 
            --danger: #ef4444; 
            --bg-light: #f1f5f9; 
            --text-main: #334155;
        }
        
        body { margin: 0; font-family: 'Poppins', sans-serif; background: var(--bg-light); display: flex; color: var(--text-main); }
        
        /* sidebar tasarımı */
        .sidebar { 
            width: 260px; background: var(--admin-dark); height: 100vh; color: white; 
            padding: 40px 25px; position: fixed; display: flex; flex-direction: column; box-sizing: border-box; 
        }
        .logo { font-size: 24px; font-weight: 700; margin-bottom: 40px; display: flex; align-items: center; gap: 12px; color: white; text-decoration:none; }
        .logo i { color: var(--admin-primary); font-size: 28px; }
        
        .menu-item { 
            display: flex; align-items: center; padding: 15px 20px; color: #94a3b8; 
            text-decoration: none; border-radius: 12px; margin-bottom: 10px; transition: 0.3s; font-weight: 500;
        }
        .menu-item:hover, .menu-item.active { background: var(--admin-primary); color: white; }
        
        .logout-btn { margin-top: auto; color: #fca5a5; display: flex; align-items: center; padding: 15px 20px; text-decoration:none; border-radius:12px; transition:0.3s;}
        .logout-btn:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* ana içerik */
        .main { margin-left: 260px; padding: 40px 50px; width: calc(100% - 260px); box-sizing: border-box; }
        
        .header-title { font-size: 28px; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 30px; }

        /* istatistik kartları */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 40px; }
        .stat-card { 
            background: white; padding: 25px; border-radius: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;
            display: flex; align-items: center; gap: 20px;
        }
        .stat-icon { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-info span { display: block; font-size: 14px; color: #64748b; font-weight: 500; text-transform: uppercase; }
        .stat-info strong { font-size: 28px; color: #1e293b; font-weight: 700; line-height: 1.2; }

        /* başvuru kartları */
        .applications-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        .app-card { 
            background: white; border-radius: 20px; padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;
            display: flex; flex-direction: column;
        }
        
        .app-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
        .app-header h3 { margin: 0 0 5px 0; font-size: 18px; color: #0f172a; }
        .app-header span { font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 5px; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-hoca { background: #e0e7ff; color: #4338ca; }
        .badge-diyet { background: #dcfce7; color: #166534; }

        .app-body { flex-grow: 1; margin-bottom: 20px; }
        .app-body strong { display: block; font-size: 13px; color: #475569; margin-bottom: 5px; text-transform: uppercase; }
        .app-body p { margin: 0 0 15px 0; font-size: 14px; color: #334155; line-height: 1.6; background: #f8fafc; padding: 12px; border-radius: 12px; }

        .app-actions { display: flex; gap: 10px; }
        .btn { 
            flex: 1; padding: 12px; border: none; border-radius: 12px; cursor: pointer; 
            font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;
        }
        .btn-approve { background: #dcfce7; color: #166534; }
        .btn-approve:hover { background: var(--success); color: white; }
        .btn-reject { background: #fee2e2; color: #9f1239; }
        .btn-reject:hover { background: var(--danger); color: white; }

        .alert-box { background: #dcfce7; color: #166534; padding: 15px 20px; border-radius: 12px; margin-bottom: 30px; font-weight: 500; display:flex; align-items:center; gap:10px; border: 1px solid #bbf7d0;}
        .alert-error { background: #fee2e2; color: #9f1239; border-color: #fecaca;}
    </style>
</head>
<body>

<div class="sidebar">
    <a href="basvuru_yonetim.php" class="logo"><i class="fas fa-shield-alt"></i> WellX Admin</a>
    <nav style="margin-top: 30px;">
        <a href="basvuru_yonetim.php" class="menu-item active"><i class="fas fa-users-cog"></i> Uzman Başvuruları</a>
        </nav>
    <a href="cikis.php" class="logout-btn"><i class="fas fa-power-off"></i> Güvenli Çıkış</a>
</div>

<div class="main">
    <h1 class="header-title">Yönetim Paneli</h1>

    <?php if(isset($_GET['durum']) && $_GET['durum'] == 'onaylandi'): ?>
        <div class="alert-box"><i class="fas fa-check-circle"></i> Başvuru başarıyla onaylandı ve uzman sisteme eklendi.</div>
    <?php elseif(isset($_GET['durum']) && $_GET['durum'] == 'reddedildi'): ?>
        <div class="alert-box alert-error"><i class="fas fa-times-circle"></i> Başvuru reddedildi.</div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #fff7ed; color: #ea580c;"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-info"><span>Bekleyen Başvuru</span><strong><?php echo $istatistik['beklemede'] ?? 0; ?></strong></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #f0fdf4; color: #166534;"><i class="fas fa-user-check"></i></div>
            <div class="stat-info"><span>Onaylanan Uzman</span><strong><?php echo $istatistik['onaylanan'] ?? 0; ?></strong></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #fef2f2; color: #991b1b;"><i class="fas fa-user-times"></i></div>
            <div class="stat-info"><span>Reddedilen</span><strong><?php echo $istatistik['reddedilen'] ?? 0; ?></strong></div>
        </div>
    </div>

    <h2 style="font-size: 20px; color: #1e293b; margin-bottom: 20px; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-inbox" style="color:var(--admin-primary)"></i> Değerlendirme Bekleyenler
    </h2>

    <div class="applications-grid">
        <?php if($bekleyenler): ?>
            <?php foreach($bekleyenler as $b): ?>
                <?php 
                    // uzmanlık alanı rollerini ayrıştırma arayüz burda veriye göre şekilleniyor.
                    $rol_badge = (strpos(strtolower($b['uzmanlik']), 'hoca') !== false) ? 'badge-hoca' : 'badge-diyet';
                ?>
                <div class="app-card">
                    <div class="app-header">
                        <div>
                            <h3><?php echo htmlspecialchars($b['ad_soyad']); ?></h3>
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($b['email']); ?></span>
                        </div>
                        <span class="badge <?php echo $rol_badge; ?>"><?php echo explode(' - ', $b['uzmanlik'])[0]; ?></span>
                    </div>
                    
                    <div class="app-body">
                        <strong>Uzmanlık / Branş</strong>
                        <p><?php echo htmlspecialchars($b['uzmanlik']); ?></p>
                        
                        <strong>Geçmiş ve Tecrübeler</strong>
                        <p style="white-space: pre-wrap; font-size: 13px;"><?php echo htmlspecialchars($b['tecrube']); ?></p>
                    </div>

                    <div class="app-actions">
                        <a href="islem_v2.php?is=onayla&id=<?php echo $b['id']; ?>" class="btn btn-approve" onclick="return confirm('Bu başvuruyu onaylamak istediğinize emin misiniz?');">
                            <i class="fas fa-check"></i> Onayla
                        </a>
                        <a href="islem_v2.php?is=reddet&id=<?php echo $b['id']; ?>" class="btn btn-reject" onclick="return confirm('Bu başvuruyu reddetmek istediğinize emin misiniz?');">
                            <i class="fas fa-times"></i> Reddet
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; background: white; border-radius: 20px; padding: 50px; text-align: center; border: 1px dashed #cbd5e1;">
                <i class="fas fa-coffee" style="font-size: 48px; color: #cbd5e1; margin-bottom: 20px;"></i>
                <h3 style="color: #334155; margin: 0 0 10px 0;">Şu an her şey sakin!</h3>
                <p style="color: #64748b; margin: 0;">Bekleyen hiçbir uzman başvurusu bulunmuyor. Kahvenizi yudumlayabilirsiniz.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>