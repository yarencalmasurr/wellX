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
    $sorgu = $conn->prepare("
        SELECT k.id, k.ad_soyad, k.email,
        (SELECT SUM(spor_suresi) FROM aktivite_kayitlari WHERE user_id = k.id AND kayit_tarihi = ?) as bugunku_spor
        FROM kullanicilar k 
        JOIN uzman_danisan_eslesmeleri ude ON k.id = ude.danisan_id
        WHERE ude.uzman_id = ? AND ude.uzman_rol = 'hoca'
    ");
    $sorgu->execute([$bugun, $hoca_id]);
    $danisanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    $soru_sorgu = $conn->prepare("
        SELECT us.*, k.ad_soyad as danisan_adi 
        FROM uzman_sorulari us 
        JOIN kullanicilar k ON us.danisan_id = k.id 
        WHERE us.uzman_id = ? AND us.durum = 'beklemede'
        ORDER BY us.soru_tarihi DESC
    ");
    $soru_sorgu->execute([$hoca_id]);
    $gelen_sorular = $soru_sorgu->fetchAll(PDO::FETCH_ASSOC);

    $son_duyuru_sorgu = $conn->prepare("
        SELECT * 
        FROM gunun_antrenmani 
        WHERE hoca_id = ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $son_duyuru_sorgu->execute([$hoca_id]);
    $son_duyuru = $son_duyuru_sorgu->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veritabanı hatası oluştu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hoca Yönetim Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --primary: #1e293b; 
            --accent: #3b82f6; 
            --danger: #ef4444; 
            --bg: #f8fafc; 
            --card-bg: #ffffff;
            --text-main: #334155;
            --text-muted: #94a3b8;
        }
        body { background: var(--bg); font-family: 'Poppins', sans-serif; margin: 0; display: flex; color: var(--text-main); }
        .sidebar { width: 260px; background: var(--primary); height: 100vh; color: white; padding: 40px 30px; position: fixed; box-shadow: 4px 0 24px rgba(0,0,0,0.06); display: flex; flex-direction: column; box-sizing: border-box; }
        .sidebar .logo { font-size: 24px; font-weight: 700; margin-bottom: 40px; color: white; display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .sidebar .logo i { color: #f59e0b; }
        .user-info { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px; margin-bottom: 30px; }
        .user-info p { margin: 0; font-size: 14px; color: #cbd5e1; }
        .user-info strong { font-size: 16px; color: white; display: block; margin-top: 4px; }
        .sidebar .logout-btn { margin-top: auto; color: #fca5a5; display: flex; align-items: center; gap: 10px; text-decoration: none; padding: 12px; border-radius: 12px; transition: 0.3s; font-weight: 500;}
        .sidebar .logout-btn:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .main { margin-left: 260px; padding: 50px; width: calc(100% - 260px); box-sizing: border-box; }
        .page-title { font-size: 28px; font-weight: 700; margin-top: 0; margin-bottom: 30px; color: #0f172a; }
        .card { background: var(--card-bg); padding: 30px; border-radius: 24px; box-shadow: 0 10px 20px -5px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid rgba(226,232,240,0.8); transition: transform 0.2s; }
        .card h3 { margin-top: 0; display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px; }
        input[type="text"], textarea { width: 100%; padding: 16px; border-radius: 14px; border: 1px solid #e2e8f0; background: #f8fafc; margin-bottom: 15px; box-sizing: border-box; font-family: inherit; font-size: 14px; transition: all 0.3s; }
        input[type="text"]:focus, textarea:focus { outline: none; border-color: var(--accent); background: white; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        textarea { resize: vertical; min-height: 100px; }
        .btn { background: var(--accent); color: white; border: none; padding: 14px 24px; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 15px; width: 100%; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        .alert-msg { background: #dcfce7; color: #166534; padding: 15px 20px; border-radius: 12px; font-weight: 500; font-size: 14px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .badge-warning { background: #fef3c7; color: #d97706; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block;}
        .tarif-box { background: linear-gradient(to right, #ffffff, #f0f9ff); border: 2px dashed #bae6fd; }
        .students-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        .student-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .student-name { display: flex; align-items: center; gap: 12px; }
        .student-avatar { width: 45px; height: 45px; background: #eff6ff; color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; }
        .son-kayit { background: #f8fafc; padding: 15px; border-radius: 10px; margin-top: 10px; border-left: 4px solid var(--accent); font-size: 14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo"><i class="fas fa-trophy"></i> Hoca Paneli</div>
    
    <div class="user-info">
        <p>Hoş Geldiniz,</p>
        <strong><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></strong>
    </div>

    <a href="cikis.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
</div>

<div class="main">
    <?php if(isset($_GET['durum']) && $_GET['durum'] == 'ok'): ?>
        <div class="alert-msg">
            <i class="fas fa-check-circle"></i> İşlem başarıyla tamamlandı.
        </div>
    <?php elseif(isset($_GET['durum']) && $_GET['durum'] == 'cevaplandi'): ?>
        <div class="alert-msg">
            <i class="fas fa-check-circle"></i> Soruya verdiğiniz cevap sporcuya iletildi.
        </div>
    <?php endif; ?>

    <div class="card tarif-box">
        <h3><i class="fas fa-bullhorn text-primary"></i> Günün Antrenman Duyurusunu Paylaş</h3>
        <form action="islem_v2.php?is=antrenman_duyuru_kaydet" method="POST" style="margin-top: 20px;">
            <input type="text" name="duyuru_baslik" placeholder="Antrenman Başlığı (Örn: Kardiyo Günü)" required>
            <textarea name="duyuru_icerik" placeholder="Hareketler ve set sayıları..." required></textarea>
            <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Duyuruyu Yayınla</button>
        </form>

        <?php if($son_duyuru): ?>
            <div class="son-kayit">
                <strong>Paylaşılan Son Duyuru:</strong><br>
                <em><?php echo htmlspecialchars($son_duyuru['antrenman_baslik']); ?></em>: 
                <?php echo htmlspecialchars($son_duyuru['antrenman_icerik']); ?>
            </div>
        <?php endif; ?>
    </div>

    <h2 class="page-title">Size Bağlı Sporcular</h2>

    <div class="students-grid">
        <?php if($danisanlar): ?>
            <?php foreach($danisanlar as $d): ?>
                <div class="card">
                    <div class="student-header">
                        <div class="student-name">
                            <div class="student-avatar"><i class="fas fa-user"></i></div>
                            <div>
                                <h4 style="margin:0; font-size: 16px; color:#1e293b;"><?php echo htmlspecialchars($d['ad_soyad']); ?></h4>
                                <span style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($d['email']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 14px; font-weight: 500;">Bugünkü Spor:</span>
                        <div>
                            <strong style="font-size: 16px; color: var(--accent);"><?php echo $d['bugunku_spor'] ?? 0; ?> dk</strong> 
                            <?php if(($d['bugunku_spor'] ?? 0) < 30): ?>
                                <span class="badge-warning" style="margin-left: 8px;">Hedef Altı</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <form action="islem_v2.php?is=egzersiz_yaz" method="POST">
                        <input type="hidden" name="danisan_id" value="<?php echo $d['id']; ?>">
                        <textarea name="antrenman_notu" placeholder="<?php echo htmlspecialchars($d['ad_soyad']); ?> için özel antrenman programı..." required style="min-height: 80px;"></textarea>
                        <button type="submit" class="btn"><i class="fas fa-check"></i> Notu Gönder</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                <i class="fas fa-users" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                <p style="color: var(--text-muted); font-size: 16px; margin: 0;">Henüz size atanmış bir sporcu bulunmuyor.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="border-top: 5px solid #f59e0b; margin-top: 30px;">
        <h3><i class="fas fa-question-circle" style="color: #f59e0b;"></i> Sporculardan Gelen Sorular (Cevap Bekleyenler)</h3>
        
        <?php if($gelen_sorular): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 20px;">
                <?php foreach($gelen_sorular as $soru): ?>
                    <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <strong style="color: #1e293b;"><i class="fas fa-user-circle text-muted"></i> <?php echo htmlspecialchars($soru['danisan_adi']); ?></strong>
                            <small style="color: #64748b;"><i class="far fa-clock"></i> <?php echo date('d.m.Y H:i', strtotime($soru['soru_tarihi'])); ?></small>
                        </div>
                        <div style="color: #334155; font-size: 15px; margin-bottom: 15px; padding: 15px; background: white; border-radius: 12px; border-left: 4px solid #f59e0b;">
                            "<?php echo nl2br(htmlspecialchars($soru['soru_metni'])); ?>"
                        </div>
                        <form action="islem_v2.php?is=cevapla" method="POST">
                            <input type="hidden" name="soru_id" value="<?php echo $soru['id']; ?>">
                            <textarea name="cevap_metni" placeholder="Sporcunuza cevabınızı yazın..." required style="min-height: 80px; margin-bottom: 15px;"></textarea>
                            <button type="submit" class="btn" style="padding: 12px; background: #f59e0b; color: white;"><i class="fas fa-paper-plane"></i> Cevabı Gönder</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; background: #f8fafc; border-radius: 16px; border: 1px dashed #cbd5e1;">
                <i class="fas fa-check-circle" style="font-size: 30px; color: #3b82f6; margin-bottom: 10px;"></i>
                <p style="color: var(--text-muted); margin: 0;">Harika! Şu an cevap bekleyen hiçbir soru yok.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>