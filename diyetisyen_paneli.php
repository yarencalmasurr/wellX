<?php
/**
 * Proje: saglik_portali
 * Dosya: diyetisyen_paneli.php
 * Açıklama: Diyetisyenin tarif paylaştığı ve danışanlarına plan gönderdiği modern panel
 */

session_start();
include 'baglan.php';

// Güvenlik Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'diyetisyen') {
    header("Location: index.php");
    exit();
}

$diyetisyen_id = $_SESSION['user_id'];
$bugun = date('Y-m-d');

try {
    // 1. Aktif Danışanları Çek
    $sorgu = $conn->prepare("
        SELECT k.id, k.ad_soyad, k.email, 
        (SELECT SUM(alinan_kalori) FROM aktivite_kayitlari WHERE user_id = k.id AND kayit_tarihi = ?) as bugunku_kalori 
        FROM kullanicilar k 
        JOIN uzman_danisan_eslesmeleri ude ON k.id = ude.danisan_id
        WHERE ude.uzman_id = ? 
        AND (LOWER(ude.uzman_rol) = 'diyetisyen' OR ude.uzman_rol = 'diyetisyen')
    ");
    $sorgu->execute([$bugun, $diyetisyen_id]);
    $danisanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    // 2. Tarif İstatistiklerini Çek
    $istatistik_sorgu = $conn->prepare("
        SELECT t.tarif_baslik, t.ekleme_tarihi, 
               COALESCE(AVG(p.puan), 0) as ort_puan, 
               COUNT(p.id) as katilim 
        FROM gunun_tarifi t 
        LEFT JOIN tarif_puanlari p ON t.id = p.tarif_id 
        WHERE t.diyetisyen_id = ? 
        GROUP BY t.id 
        ORDER BY t.ekleme_tarihi DESC
    ");
    $istatistik_sorgu->execute([$diyetisyen_id]);
    $tarifler = $istatistik_sorgu->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veritabanı hatası oluştu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Diyetisyen Yönetim Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --primary: #1e293b; /* Koyu lacivert menü */
            --accent: #10b981; /* Zümrüt Yeşili (Diyetisyen Teması) */
            --bg: #f8fafc; 
            --card-bg: #ffffff;
            --text-main: #334155;
            --text-muted: #94a3b8;
        }
        body { background: var(--bg); font-family: 'Poppins', sans-serif; margin: 0; display: flex; color: var(--text-main); }
        
        /* Modern Sidebar */
        .sidebar { 
            width: 260px; 
            background: var(--primary); 
            height: 100vh; 
            color: white; 
            padding: 40px 30px; 
            position: fixed; 
            box-shadow: 4px 0 24px rgba(0,0,0,0.06); 
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }
        .sidebar .logo {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 40px;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .sidebar .logo i { color: var(--accent); font-size: 26px; }
        
        .user-info {
            background: rgba(255,255,255,0.05);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .user-info p { margin: 0; font-size: 14px; color: #cbd5e1; }
        .user-info strong { font-size: 16px; color: white; display: block; margin-top: 4px; }
        
        .sidebar nav { flex-grow: 1; }
        .sidebar .logout-btn { 
            margin-top: auto; color: #fca5a5; display: flex; align-items: center; gap: 10px; text-decoration: none; padding: 12px; border-radius: 12px; transition: 0.3s; font-weight: 500;
        }
        .sidebar .logout-btn:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* Main Content */
        .main { margin-left: 260px; padding: 40px 50px; width: calc(100% - 260px); box-sizing: border-box; }
        .page-title { font-size: 26px; font-weight: 700; margin-top: 0; margin-bottom: 30px; color: #0f172a; }

        /* Grid System */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        /* Modern Cards */
        .card { 
            background: var(--card-bg); 
            padding: 30px; 
            border-radius: 24px; 
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.04); 
            margin-bottom: 30px; 
            border: 1px solid rgba(226,232,240,0.8);
        }
        .card h3 { margin-top: 0; display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px; }
        
        /* Inputs & Buttons */
        label { display: block; font-size: 14px; font-weight: 500; color: #475569; margin-bottom: 8px; }
        input[type="text"], textarea { 
            width: 100%; 
            padding: 14px 16px; 
            border-radius: 14px; 
            border: 1px solid #e2e8f0; 
            background: #f8fafc;
            margin-bottom: 20px; 
            box-sizing: border-box; 
            font-family: inherit; 
            font-size: 14px;
            transition: all 0.3s;
        }
        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
            background: white;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }
        textarea { resize: vertical; min-height: 120px; }

        .btn { 
            background: var(--accent); 
            color: white; 
            border: none; 
            padding: 14px 24px; 
            border-radius: 12px; 
            cursor: pointer; 
            font-weight: 600; 
            font-size: 15px;
            width: 100%; 
            transition: 0.3s; 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn:hover { background: #059669; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,185,129,0.2); }
        
        /* Alert */
        .alert-msg { background: #dcfce7; color: #166534; padding: 15px 20px; border-radius: 12px; font-weight: 500; font-size: 14px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }

        /* Announcement Box */
        .tarif-box { 
            background: linear-gradient(to right, #ffffff, #f0fdf4);
            border: 2px dashed #a7f3d0; 
        }

        /* Students & Stats */
        .student-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 15px; transition: 0.2s;}
        .student-item:hover { border-color: #cbd5e1; }
        .student-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        
        .stat-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .stat-table th { text-align: left; padding: 12px; color: #64748b; font-size: 13px; border-bottom: 2px solid #e2e8f0; }
        .stat-table td { padding: 15px 12px; color: #334155; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
        .stat-table tr:last-child td { border-bottom: none; }
        .badge-success { background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="diyetisyen_paneli.php" class="logo"><i class="fas fa-apple-alt"></i> Diyetisyen</a>
    
    <div class="user-info">
        <p>Hoş Geldin,</p>
        <strong><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></strong>
    </div>

    <a href="cikis.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
</div>

<div class="main">
    <?php if(isset($_GET['durum']) && $_GET['durum'] == 'ok'): ?>
        <div class="alert-msg">
            <i class="fas fa-check-circle"></i> Tarif başarıyla paylaşıldı.
        </div>
    <?php elseif(isset($_GET['islem']) && $_GET['islem'] == 'basarili'): ?>
        <div class="alert-msg">
            <i class="fas fa-check-circle"></i> Beslenme planı danışana iletildi.
        </div>
    <?php endif; ?>

    <h2 class="page-title">Bugün danışanların için neler hazırladın?</h2>

    <div class="dashboard-grid">
        
        <div class="left-col">
            <div class="card tarif-box">
                <h3><i class="fas fa-utensils text-primary" style="color: var(--accent);"></i> Günün Tarifini Paylaş</h3>
                <form action="islem_v2.php?is=tarif_paylas" method="POST">
                    <label>Tarif Başlığı</label>
                    <input type="text" name="tarif_baslik" placeholder="Örn: Avokadolu Omlet" required>
                    
                    <label>Tarif Detayları ve Malzemeler</label>
                    <textarea name="tarif_icerik" placeholder="Detayları buraya yazın..." required></textarea>
                    
                    <button type="submit" class="btn"><i class="fas fa-share"></i> Tarifi Yayınla</button>
                </form>
            </div>

            <div class="card">
                <h3><i class="fas fa-star" style="color: #f59e0b;"></i> Tarif Başarı İstatistikleri</h3>
                <?php if($tarifler): ?>
                    <table class="stat-table">
                        <thead>
                            <tr>
                                <th>TARİF</th>
                                <th>ORT. PUAN</th>
                                <th>KATILIM</th>
                                <th>TARİH</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tarifler as $t): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($t['tarif_baslik']); ?></td>
                                    <td><i class="fas fa-star" style="color: #f59e0b; font-size:12px;"></i> <?php echo number_format($t['ort_puan'], 1); ?></td>
                                    <td><span class="badge-success"><?php echo $t['katilim']; ?> Kişi</span></td>
                                    <td style="color: #94a3b8; font-size: 13px;"><?php echo date('d.m.Y', strtotime($t['ekleme_tarihi'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: var(--text-muted); font-size: 14px;">Henüz puanlanan bir tarifiniz bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="right-col">
            <div class="card" style="border-top: 5px solid var(--accent);">
                <h3><i class="fas fa-user-friends" style="color: var(--accent);"></i> Aktif Danışanlarım</h3>
                
                <?php if($danisanlar): ?>
                    <?php foreach($danisanlar as $d): ?>
                        <div class="student-item">
                            <div class="student-header">
                                <div>
                                    <h4 style="margin:0 0 5px 0; font-size: 16px; color:#1e293b;">
                                        <i class="fas fa-user-circle" style="color:#cbd5e1; margin-right:5px;"></i> 
                                        <?php echo htmlspecialchars($d['ad_soyad']); ?>
                                    </h4>
                                    <span style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($d['email']); ?></span>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 12px; color: #64748b; display: block;">Bugün</span>
                                    <strong style="color: var(--accent); font-size: 15px;"><?php echo $d['bugunku_kalori'] ?? 0; ?> kcal</strong>
                                </div>
                            </div>
                            
                            <form action="islem_v2.php?is=plan_yaz" method="POST" style="display:flex; gap:10px;">
                                <input type="hidden" name="danisan_id" value="<?php echo $d['id']; ?>">
                                <input type="text" name="plan_metni" placeholder="Özel not veya beslenme planı..." required style="margin-bottom:0; flex-grow:1; padding: 10px 15px;">
                                <button type="submit" class="btn" style="width: auto; padding: 10px 20px;"><i class="fas fa-paper-plane"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 0;">
                        <i class="fas fa-users-slash" style="font-size: 40px; color: #cbd5e1; margin-bottom: 15px;"></i>
                        <p style="color: var(--text-muted); margin: 0;">Henüz size atanmış bir danışan bulunmuyor.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>