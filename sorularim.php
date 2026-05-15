<?php
/**
 * Proje: saglik_portali
 * Dosya: sorularim.php
 * Açıklama: Premium üyeler için Soru Sorma ve Cevap Takip sayfası.
 */

session_start(); 
include 'baglan.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Kullanıcı Verilerini Çek
$sorgu = $conn->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$sorgu->execute([$user_id]);
$user_data = $sorgu->fetch(PDO::FETCH_ASSOC);

$is_premium = $user_data['is_premium'] ?? 0;

$current_page = basename($_SERVER['PHP_SELF']);
function isActive($page, $current) { return ($page == $current) ? 'active' : ''; }
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Uzmana Sorular | Sağlık Takip</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --blue: #0ea5e9; --orange: #f59e0b; --bg: #f8fafc; --sidebar: #ffffff; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); display: flex; margin: 0; }
        .sidebar { width: 260px; background: var(--sidebar); height: 100vh; padding: 30px 20px; box-shadow: 4px 0 24px rgba(0,0,0,0.03); position: fixed; }
        .logo { font-size: 22px; font-weight: 600; color: #0f172a; margin-bottom: 40px; display: flex; align-items: center; gap: 10px; text-decoration:none; }
        .menu-item { display: flex; align-items: center; padding: 14px 18px; color: #64748b; text-decoration: none; border-radius: 12px; margin-bottom: 8px; transition: 0.2s; }
        .menu-item:hover { background: #f8fafc; color: #0f172a; }
        .menu-item.active { background: #f0f9ff; color: var(--blue); font-weight: 600; }
        .main { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        .card-custom { background: white; border-radius: 24px; padding: 30px; box-shadow: 0 10px 15px rgba(0,0,0,0.04); margin-bottom: 30px; }
        .question-item { background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 15px; border-left: 6px solid; }
    </style>
</head>
<body>

<div class="sidebar">
<div style="font-size:28px; font-weight:800; margin-bottom:40px; color:#111827; letter-spacing:-1px;">
    <span style="color:#ef4444;">❤</span> wellX
</div>
    <a href="panel.php" class="menu-item <?php echo isActive('panel.php', $current_page); ?>">🏠 Özet Paneli</a>
    <a href="beslenme.php" class="menu-item <?php echo isActive('beslenme.php', $current_page); ?>">🥗 Beslenme</a>
    <a href="egzersiz.php" class="menu-item <?php echo isActive('egzersiz.php', $current_page); ?>">🏋️ Egzersiz</a>
    <a href="sorularim.php" class="menu-item active">📩 Uzmana Sorular</a>
    <a href="gelisim.php" class="menu-item <?php echo isActive('gelisim.php', $current_page); ?>">📈 Gelişim</a>
    <a href="rozetlerim.php" class="menu-item <?php echo isActive('rozetlerim.php', $current_page); ?>">🏆 Rozetlerim</a>
    <a href="profil.php" class="menu-item">👤 Profil</a>
    <a href="cikis.php" class="menu-item" style="color:#ef4444; margin-top: 40px;">🚪 Çıkış Yap</a>
</div>

<div class="main">
    <h1>📩 Uzman İletişim Merkezi</h1>
    <p class="text-muted">Buradan uzmanlarınıza yeni sorular sorabilir ve yanıtlarını takip edebilirsiniz.</p>

    <div class="card-custom" style="border-left: 6px solid var(--orange);">
        <h3 style="font-size: 18px; color: #1e293b; margin-bottom: 20px;"><i class="fas fa-paper-plane" style="color: var(--orange);"></i> Yeni Soru Gönder</h3>
        
        <?php if ($is_premium == 1): ?>
            <form action="islem_v2.php?is=soru_sor" method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Uzman Seçin</label>
                        <select name="uzman_id" class="form-select" required style="border-radius: 12px; padding: 12px;">
                            <option value="">Seçiniz...</option>
                            <?php
                            $es_sorgu = $conn->prepare("SELECT ude.uzman_id, k.ad_soyad, k.rol FROM uzman_danisan_eslesmeleri ude JOIN kullanicilar k ON ude.uzman_id = k.id WHERE ude.danisan_id = ?");
                            $es_sorgu->execute([$user_id]);
                            while($uzman = $es_sorgu->fetch()) {
                                echo "<option value='".$uzman['uzman_id']."'>".$uzman['ad_soyad']." (".ucfirst($uzman['rol']).")</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Sorunuz</label>
                        <div class="input-group">
                            <input type="text" name="soru_metni" class="form-control" placeholder="Diyet veya antrenman hakkında ne sormak istersiniz?" required style="border-radius: 12px 0 0 12px; padding: 12px;">
                            <button type="submit" class="btn btn-warning text-white fw-bold" style="border-radius: 0 12px 12px 0; padding: 0 25px;">Gönder</button>
                        </div>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="premium-lock-overlay text-center p-4" style="background: #fffbeb; border-radius: 16px; border: 1px dashed var(--orange);">
                <div style="font-size: 30px; margin-bottom: 10px;">🔒</div>
                <h4 style="font-size: 16px; font-weight: 600; color: #854d0e;">Uzman Desteği Premium Üyelere Özeldir</h4>
                <p class="small text-muted mb-3">Diyetisyen ve antrenörlerimize soru sormak için planlarımızı inceleyin.</p>
                <a href="premium_planlar.php" class="btn btn-warning text-white btn-sm fw-bold" style="border-radius: 10px; padding: 8px 20px;">
                    Premium Edinin
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-custom">
        <h3 style="font-size: 18px; color: #1e293b; margin-bottom: 20px;"><i class="fas fa-history" style="color: var(--blue);"></i> Geçmiş Sorular ve Yanıtlar</h3>
        
        <?php 
        $soru_cek = $conn->prepare("
            SELECT s.*, k.ad_soyad as uzman_adi, k.rol as uzman_rolü 
            FROM uzman_sorulari s 
            JOIN kullanicilar k ON s.uzman_id = k.id 
            WHERE s.danisan_id = ? 
            ORDER BY s.soru_tarihi DESC
        ");
        $soru_cek->execute([$user_id]);
        $sorular = $soru_cek->fetchAll(PDO::FETCH_ASSOC);

        if (count($sorular) == 0): ?>
            <p class="text-muted small">Henüz bir soru sormadınız.</p>
        <?php else: 
            foreach($sorular as $s): ?>
                <div class="question-item" style="border-left-color: <?php echo ($s['durum'] == 'cevaplandi') ? '#10b981' : '#f59e0b'; ?>;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-white text-dark border shadow-sm" style="border-radius: 8px;">
                            <i class="fas fa-user-md me-1 text-primary"></i> <?php echo htmlspecialchars($s['uzman_adi']); ?> (<?php echo ucfirst($s['uzman_rolü']); ?>)
                        </span>
                        <small class="text-muted" style="font-size: 11px;"><?php echo date('d.m.Y H:i', strtotime($s['soru_tarihi'])); ?></small>
                    </div>
                    
                    <p class="mb-1" style="font-size: 14px; color: #1e293b;"><strong>Soru:</strong> <?php echo htmlspecialchars($s['soru_metni']); ?></p>

                    <?php if ($s['durum'] == 'cevaplandi'): ?>
                        <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <strong style="color: #10b981; font-size: 13px;"><i class="fas fa-comment-check"></i> Uzmanın Yanıtı:</strong>
                            <p class="mb-0 mt-1" style="font-size: 14px; color: #334155;"><?php echo htmlspecialchars($s['cevap_metni']); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="mt-2">
                            <span class="badge bg-light text-warning" style="font-size: 11px; border: 1px solid #fef3c7;">⏳ Yanıt bekleniyor...</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; 
        endif; ?>
    </div>
</div>

</body>
</html>