<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] != 'hoca')) {
    header("Location: index.php"); 
    exit();
}

$hoca_id = $_SESSION['user_id'];
$bugun = date('Y-m-d');
$son_duyuru = null;

try {
    // 1. Danışanları Çek ve Bugünkü Spor Günlüğünü Getir
    $sorgu = $conn->prepare("
        SELECT k.id, k.ad_soyad, k.email,
        (SELECT SUM(spor_suresi) FROM aktivite_kayitlari WHERE user_id = k.id AND kayit_tarihi = ?) as bugunku_spor,
        (SELECT GROUP_CONCAT(CONCAT(sure_dk, ' dk ', egzersiz_adi, ' (', yakilan_kalori, ' kcal yaktı)') SEPARATOR '<br>') 
         FROM egzersiz_gunlugu 
         WHERE user_id = k.id AND tarih = ?) as yapilan_sporlar
        FROM kullanicilar k 
        JOIN uzman_danisan_eslesmeleri ude ON k.id = ude.danisan_id
        WHERE ude.uzman_id = ? AND ude.uzman_rol = 'hoca'
    ");
    $sorgu->execute([$bugun, $bugun, $hoca_id]);
    $danisanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    // 2. Bekleyen Soruları Çek
    $soru_sorgu = $conn->prepare("
        SELECT us.*, k.ad_soyad as danisan_adi 
        FROM uzman_sorulari us 
        JOIN kullanicilar k ON us.danisan_id = k.id 
        WHERE us.uzman_id = ? AND us.durum = 'beklemede'
        ORDER BY us.soru_tarihi DESC
    ");
    $soru_sorgu->execute([$hoca_id]);
    $gelen_sorular = $soru_sorgu->fetchAll(PDO::FETCH_ASSOC);

    // 3. Geçmiş Duyuruları Çek (Son 5 Duyuru)
    $duyuru_sorgu = $conn->prepare("SELECT * FROM gunun_antrenmani WHERE hoca_id = ? ORDER BY id DESC LIMIT 5");
    $duyuru_sorgu->execute([$hoca_id]);
    $gecmis_duyurular = $duyuru_sorgu->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veritabanı hatası oluştu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoca Paneli | WellX Elite</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root { 
            --accent: #3b82f6; --accent-dark: #2563eb;
            --text-main: #1e293b; --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.6);
        }
        body { font-family: 'Poppins', sans-serif; margin: 0; display: flex; color: var(--text-main); background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 50%, #bfdbfe 100%); background-attachment: fixed; min-height: 100vh; }
        #particles-js { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; pointer-events: none; }
        
        .sidebar { width: 260px; height: 100vh; padding: 30px 20px; position: fixed; z-index: 100; background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(20px); border-right: 1px solid var(--glass-border); box-shadow: 10px 0 30px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 26px; font-weight: 800; color: #111827; margin-bottom: 30px; letter-spacing: -1px; display: flex; align-items: center; gap: 10px;}
        .sidebar h2 i { color: var(--accent); }
        .user-info { background: rgba(255,255,255,0.7); padding: 15px; border-radius: 16px; margin-bottom: 30px; border: 1px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.02);}
        .user-info p { margin: 0; font-size: 13px; color: var(--text-muted); font-weight:500;}
        .user-info strong { font-size: 16px; color: var(--text-main); display: block; margin-top: 4px; }
        
        .menu-item { display: flex; align-items: center; padding: 14px 18px; color: var(--text-muted); text-decoration: none; border-radius: 16px; margin-bottom: 8px; transition: 0.3s; font-weight: 500; border: 1px solid transparent; }
        .menu-item.active { background: linear-gradient(135deg, #dbeafe, #eff6ff); color: var(--accent-dark); font-weight: 700; box-shadow: 0 8px 20px rgba(59,130,246,0.1); border-color: white; }
        .logout-btn { margin-top: auto !important; background: rgba(254, 226, 226, 0.6); color: #ef4444 !important; font-weight: 600; text-decoration:none; padding: 14px 18px; border-radius:16px; transition:0.3s; display:flex; align-items:center;}
        .logout-btn:hover { background: #fee2e2; color: #dc2626 !important; }

        .main { margin-left: 260px; padding: 40px 50px; width: calc(100% - 260px); position: relative; z-index: 10; box-sizing: border-box;}
        .page-title { font-size: 28px; font-weight: 800; margin-top: 0; margin-bottom: 30px; color: #0f172a; letter-spacing:-0.5px;}

        .glass-card { background: var(--glass-bg); backdrop-filter: blur(15px); border-radius: 24px; border: 1px solid var(--glass-border); box-shadow: 0 15px 35px rgba(0,0,0,0.03); padding: 30px; transition: 0.3s ease; }
        .glass-card h3 { margin-top: 0; margin-bottom: 20px; font-size: 18px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        
        /* Grid Güncellemesi */
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; align-items: start; }
        
        input[type="text"], textarea { width: 100%; padding: 15px 18px; border-radius: 16px; border: 1px solid #e2e8f0; background: rgba(255,255,255,0.9); margin-bottom: 20px; font-family: inherit; font-size: 14px; transition: 0.3s; }
        input[type="text"]:focus, textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); background: white;}
        .btn-custom { background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%); color: white; border: none; padding: 15px 24px; border-radius: 16px; cursor: pointer; font-weight: 600; font-size: 15px; width: 100%; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 10px 20px rgba(59,130,246,0.2);}
        .btn-custom:hover { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(59,130,246,0.3); color:white;}
        
        .history-list { display: flex; flex-direction: column; gap: 15px; }
        .history-item { display: flex; gap: 15px; background: rgba(255,255,255,0.8); padding: 15px; border-radius: 16px; border: 1px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.02); transition: 0.2s;}
        .history-item:hover { background: white; transform: translateX(5px); }
        .history-date { background: #eff6ff; color: var(--accent-dark); font-size: 11px; font-weight: 700; padding: 8px 12px; border-radius: 12px; height: fit-content; white-space: nowrap; }
        .history-content strong { color: var(--text-main); font-size: 14px; display: block; margin-bottom: 4px; }
        .history-content p { color: var(--text-muted); font-size: 12px; margin: 0; line-height: 1.5; }

        .student-item { background: rgba(255,255,255,0.6); border: 1px solid white; border-radius: 18px; padding: 20px; margin-bottom: 15px; transition: 0.3s;}
        .student-item:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.04); background: white;}
        .student-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        
        .alert-msg { background: #dcfce7; color: #166534; padding: 15px 20px; border-radius: 16px; font-weight: 600; font-size: 14px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; border:1px solid #bbf7d0;}
        .badge-warning { background: #fef3c7; color: #d97706; padding: 6px 12px; border-radius: 12px; font-size: 11px; font-weight: 700;}
        .modal-content { border-radius: 24px; border: none; background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); box-shadow: 0 25px 50px rgba(0,0,0,0.1);}
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="sidebar">
    <h2><i class="fas fa-trophy"></i> Hoca Paneli</h2>
    <div class="user-info">
        <p>Hoş Geldiniz,</p>
        <strong><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></strong>
    </div>
    <nav style="flex-grow: 1;">
        <a href="hoca_paneli.php" class="menu-item active"><i class="fas fa-home me-2"></i> Yönetim Paneli</a>
    </nav>
    <a href="cikis.php" class="logout-btn"><i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap</a>
</div>

<div class="main">
    <?php if(isset($_GET['durum']) && $_GET['durum'] == 'ok'): ?>
        <div class="alert-msg"><i class="fas fa-check-circle"></i> İşlem başarıyla tamamlandı.</div>
    <?php elseif(isset($_GET['durum']) && $_GET['durum'] == 'cevaplandi'): ?>
        <div class="alert-msg"><i class="fas fa-check-circle"></i> Soruya verdiğiniz cevap sporcuya iletildi.</div>
    <?php endif; ?>

    <h2 class="page-title">Bugün sporcuların için neler hazırladın?</h2>

    <div class="dashboard-grid">
        <div class="glass-card" style="border-top: 5px solid var(--accent);">
            <h3><i class="fas fa-bullhorn" style="color: var(--accent);"></i> Antrenman Duyurusunu Paylaş</h3>
            <form action="islem_v2.php?is=antrenman_duyuru_kaydet" method="POST">
                <label style="font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px; margin-left:5px;">Antrenman Başlığı</label>
                <input type="text" name="duyuru_baslik" placeholder="Örn: Full Body Kardiyo Günü" required>
                
                <label style="font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px; margin-left:5px;">Hareketler & Setler</label>
                <textarea name="duyuru_icerik" placeholder="Program detayları..." required></textarea>
                
                <button type="submit" class="btn-custom"><i class="fas fa-paper-plane"></i> Duyuruyu Yayınla</button>
            </form>
        </div>

        <div class="glass-card" style="border-top: 5px solid #10b981;">
            <h3><i class="fas fa-users" style="color: #10b981;"></i> Size Bağlı Sporcular</h3>
            
            <?php if($danisanlar): ?>
                <?php foreach($danisanlar as $d): ?>
                    <div class="student-item">
                        <div class="student-header">
                            <div>
                                <h4 style="margin:0 0 5px 0; font-size: 16px; font-weight:700; display:flex; align-items:center; gap:8px;">
                                    <i class="fas fa-user-circle text-muted"></i> 
                                    <?php echo htmlspecialchars($d['ad_soyad']); ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-bold" style="font-size:11px; padding:4px 10px;" data-bs-toggle="modal" data-bs-target="#sporModal<?php echo $d['id']; ?>">
                                        <i class="fas fa-running me-1"></i> Ne Yaptı?
                                    </button>
                                </h4>
                                <span style="font-size: 13px; color: var(--text-muted);"><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($d['email']); ?></span>
                            </div>
                            <div style="text-align: right; background:rgba(255,255,255,0.8); padding:8px 12px; border-radius:12px;">
                                <span style="font-size: 11px; color: #64748b; display: block; font-weight:600;">Bugünkü Spor</span>
                                <strong style="color: var(--accent); font-size: 16px;"><?php echo $d['bugunku_spor'] ?? 0; ?> dk</strong>
                                <?php if(($d['bugunku_spor'] ?? 0) < 30): ?>
                                    <div class="badge-warning mt-1">Hedef Altı</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <form action="islem_v2.php?is=egzersiz_yaz" method="POST" style="display:flex; gap:10px;">
                            <input type="hidden" name="danisan_id" value="<?php echo $d['id']; ?>">
                            <input type="text" name="plan_metni" placeholder="Özel antrenman programı..." required style="margin-bottom:0; flex-grow:1; border-radius:14px; padding:12px 15px;">
                            <button type="submit" class="btn btn-primary" style="border-radius:14px; padding: 0 20px; font-weight:600;"><i class="fas fa-check"></i></button>
                        </form>
                    </div>

                    <div class="modal fade" id="sporModal<?php echo $d['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold text-primary"><i class="fas fa-dumbbell me-2"></i> <?php echo explode(' ', $d['ad_soyad'])[0]; ?>'nin Spor Günlüğü</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body p-4">
                            <?php if($d['yapilan_sporlar']): ?>
                                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; font-size: 14px; color: #334155; line-height: 1.8;">
                                    <?php echo $d['yapilan_sporlar']; ?>
                                </div>
                                <div class="mt-3 text-end fw-bold text-primary" style="font-size:18px;">
                                    Toplam Süre: <?php echo $d['bugunku_spor']; ?> dk
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning text-center rounded-4 border-0 mb-0" style="background:#fef3c7; color:#d97706; font-weight:600;">
                                    <i class="fas fa-bed mb-2" style="font-size:24px;"></i><br>
                                    Sporcu bugün henüz bir egzersiz girmemiş.
                                </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px 0;">
                    <i class="fas fa-users-slash" style="font-size: 40px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p style="color: var(--text-muted); margin: 0; font-weight:500;">Henüz size atanmış bir sporcu bulunmuyor.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="glass-card" style="border-top: 5px solid var(--text-muted);">
            <h3><i class="fas fa-history" style="color: var(--text-muted);"></i> Geçmiş Duyurularım</h3>
            <?php if($gecmis_duyurular): ?>
                <div class="history-list">
                    <?php foreach($gecmis_duyurular as $gd): ?>
                        <div class="history-item">
                            <div class="history-date"><?php echo date('d.m.Y', strtotime($gd['ekleme_tarihi'] ?? date('Y-m-d'))); ?></div>
                            <div class="history-content">
                                <strong><?php echo htmlspecialchars($gd['antrenman_baslik']); ?></strong>
                                <p><?php echo htmlspecialchars(mb_substr($gd['antrenman_icerik'], 0, 60)) . '...'; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-clock" style="font-size: 30px; color: #cbd5e1; margin-bottom: 10px;"></i>
                    <p class="text-muted small m-0">Henüz geçmiş bir duyurunuz yok.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-card" style="border-top: 5px solid #f59e0b;">
            <h3><i class="fas fa-question-circle" style="color: #f59e0b;"></i> Cevap Bekleyen Sorular</h3>
            
            <?php if($gelen_sorular): ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach($gelen_sorular as $soru): ?>
                        <div style="background: rgba(255,255,255,0.8); padding: 20px; border-radius: 20px; border: 1px solid white;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <strong style="color: #1e293b; font-size:14px;"><i class="fas fa-user-circle text-muted"></i> <?php echo htmlspecialchars($soru['danisan_adi']); ?></strong>
                                <small style="color: #64748b; font-weight:600;"><i class="far fa-clock"></i> <?php echo date('d.m.Y H:i', strtotime($soru['soru_tarihi'])); ?></small>
                            </div>
                            <div style="color: #334155; font-size: 13px; margin-bottom: 20px; padding: 15px; background: white; border-radius: 14px; border-left: 4px solid #f59e0b; box-shadow:0 2px 10px rgba(0,0,0,0.02);">
                                "<?php echo nl2br(htmlspecialchars($soru['soru_metni'])); ?>"
                            </div>
                            <form action="islem_v2.php?is=cevapla" method="POST">
                                <input type="hidden" name="soru_id" value="<?php echo $soru['id']; ?>">
                                <textarea name="cevap_metni" placeholder="Sporcunuza cevabınızı yazın..." required style="min-height: 70px; border-radius:14px; margin-bottom: 10px; font-size:13px;"></textarea>
                                <button type="submit" class="btn-custom" style="padding: 10px; background: #f59e0b; color: white;"><i class="fas fa-paper-plane"></i> Gönder</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 30px; background: rgba(255,255,255,0.5); border-radius: 20px; border: 1px dashed #cbd5e1;">
                    <i class="fas fa-check-circle" style="font-size: 40px; color: #3b82f6; margin-bottom: 15px;"></i>
                    <h4 style="color: #1e293b; font-weight:700;">Harika!</h4>
                    <p style="color: var(--text-muted); margin: 0; font-size:14px;">Şu an cevap bekleyen hiçbir soru yok.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", { "particles": { "number": { "value": 30 }, "color": { "value": "#3b82f6" }, "opacity": { "value": 0.2 }, "size": { "value": 4 }, "line_linked": { "enable": true, "color": "#3b82f6", "opacity": 0.15 }, "move": { "enable": true, "speed": 1.5 } } });
</script>
</body>
</html>