<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

//1. premium kontrolü 
$user_sorgu = $conn->prepare("SELECT is_premium, ad_soyad FROM kullanicilar WHERE id = ?");
$user_sorgu->execute([$user_id]);
$user_data = $user_sorgu->fetch(PDO::FETCH_ASSOC);
$is_premium = $user_data['is_premium'] ?? 0;

//2. tarihe göre fotoğrafları çek
$foto_sorgu = $conn->prepare("SELECT * FROM gelisim_fotograflari WHERE user_id = ? ORDER BY yuklenme_tarihi DESC");
$foto_sorgu->execute([$user_id]);
$fotograflar = $foto_sorgu->fetchAll(PDO::FETCH_ASSOC);

//3. grafik verilerini çek
$sorgu = $conn->prepare("SELECT * FROM aktivite_kayitlari WHERE user_id = ? ORDER BY kayit_tarihi DESC LIMIT 10");
$sorgu->execute([$user_id]);
$veriler = array_reverse($sorgu->fetchAll(PDO::FETCH_ASSOC));

$tarihler = []; $kilolar = []; $sular = [];
foreach($veriler as $v) {
    $tarihler[] = date('d/m', strtotime($v['kayit_tarihi']));
    $kilolar[] = $v['guncel_kilo'];
    $sular[] = $v['su_miktari'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gelişim Analizi | WellX</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root { 
            --blue: #3b82f6; --orange: #f59e0b; --green: #10b981; 
            --text-main: #1e293b; --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.6);
        }
        
        body { 
            font-family: 'Poppins', sans-serif; margin: 0; display: flex; color: var(--text-main); 
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            background-attachment: fixed; min-height: 100vh;
        }

        #particles-js { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; pointer-events: none; }
        
        /* sidebar tasarımı */
        .sidebar { 
            width: 260px; height: 100vh; padding: 30px 20px; position: fixed; z-index: 100;
            background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border); box-shadow: 10px 0 30px rgba(0,0,0,0.03);
            display: flex; flex-direction: column;
        }
        
        .sidebar h2 { font-size: 28px; font-weight: 800; color: #111827; margin-bottom: 10px; letter-spacing: -1px; display: flex; align-items: center; gap: 10px;}
        .sidebar h2 i { color: #ef4444; filter: drop-shadow(0 0 8px rgba(239,68,68,0.4)); }

        .menu-item { display: flex; align-items: center; padding: 14px 18px; color: var(--text-muted); text-decoration: none; border-radius: 16px; margin-bottom: 8px; transition: all 0.3s ease; font-weight: 500; border: 1px solid transparent; }
        .menu-item i { transition: 0.3s; width: 25px; }
        .menu-item:hover { background: rgba(255, 255, 255, 0.9); color: var(--blue); transform: translateX(5px); border-color: rgba(255,255,255,0.8); box-shadow: 0 4px 15px rgba(59,130,246,0.05); }
        .menu-item.active { background: linear-gradient(135deg, #dbeafe, #eff6ff); color: var(--blue); font-weight: 700; box-shadow: 0 8px 20px rgba(59,130,246,0.1); border-color: white; }

        .menu-item:nth-of-type(1) i { color: #3b82f6; }
        .menu-item:nth-of-type(2) i { color: #10b981; }
        .menu-item:nth-of-type(3) i { color: #8b5cf6; }
        .menu-item:nth-of-type(4) i { color: #f59e0b; }
        .menu-item:nth-of-type(5) i { color: #06b6d4; }
        .menu-item:nth-of-type(6) i { color: #ec4899; }
        .menu-item:nth-of-type(7) i { color: #f97316; }
        .menu-item:nth-of-type(8) i { color: #6366f1; }
        
        .sidebar .logout-btn { margin-top: auto !important; background: rgba(254, 226, 226, 0.6); color: #ef4444 !important; font-weight: 600; }
        .sidebar .logout-btn:hover { background: #fee2e2; color: #dc2626 !important; transform: translateX(0); }

        .main { margin-left: 260px; padding: 40px 50px; width: calc(100% - 260px); position: relative; z-index: 10; box-sizing: border-box;}
        
        .page-header { display: flex; align-items: center; gap: 15px; margin-bottom: 40px; }
        .header-icon { background: linear-gradient(135deg, #0ea5e9, #0284c7); width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; box-shadow: 0 8px 20px rgba(14,165,233,0.3);}
        .page-header h1 { font-size: 32px; font-weight: 800; margin: 0; letter-spacing: -1px; color: #0f172a;}

        /*cam kart görünümü */
        .glass-card {
            background: var(--glass-bg); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border-radius: 24px; border: 1px solid var(--glass-border);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.03); padding: 30px; margin-bottom: 30px;
            transition: 0.3s ease;
        }
        .glass-card h4 { margin-top: 0; margin-bottom: 20px; font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        
        .top-grid { display: grid; grid-template-columns: 1.2fr 1.2fr 0.8fr; gap: 25px; margin-bottom: 30px; }
        .chart-box { height: 240px; width: 100%; position: relative; }

        /* fotoğraf alanı tasarımı */
        .btn-upload-trigger { background: linear-gradient(135deg, #1e293b, #0f172a); color: white; border: none; padding: 14px; border-radius: 16px; font-weight: 600; width: 100%; transition: 0.3s; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);}
        .btn-upload-trigger:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(0,0,0,0.2); }

        .foto-galeri { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 12px; margin-top: 20px; max-height: 250px; overflow-y: auto; padding-right: 5px; }
        .foto-item { position: relative; border-radius: 14px; overflow: hidden; border: 2px solid white; transition: 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .foto-item:hover { transform: scale(1.05); z-index: 5; }
        .foto-item img { width: 100%; height: 100px; object-fit: cover; display: block; }
        .foto-tarih { position: absolute; bottom: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); color: white; width: 100%; font-size: 10px; padding: 5px 0; text-align: center; font-weight: 600; }

        .delete-btn { position: absolute; top: 5px; right: 5px; background: #ef4444; color: white; width: 22px; height: 22px; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 10px; text-decoration: none; z-index: 10; border: 2px solid white; }
        .foto-item:hover .delete-btn { display: flex; }

        
        .premium-lock { background: rgba(254, 243, 199, 0.8); border: 2px dashed #fcd34d; padding: 25px 20px; border-radius: 20px; text-align: center; height: 100%; display: flex; flex-direction: column; justify-content: center; }

        /* tablo */
        .table-wrapper { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 13px; font-weight: 700; color: var(--text-muted); border-bottom: 2px solid rgba(0,0,0,0.05); text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid rgba(0,0,0,0.05); font-size: 14px; font-weight: 500; }
        .weight-cell { font-weight: 700; color: var(--blue); background: rgba(59,130,246,0.1); padding: 6px 12px; border-radius: 10px; }

        .modal-content { border-radius: 24px; border: none; background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); box-shadow: 0 25px 50px rgba(0,0,0,0.1);}
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="sidebar">
    <h2><i class="fas fa-heartbeat"></i> wellX </h2>
    
    <?php if ($is_premium): ?>
        <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 8px 14px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 10px rgba(245,158,11,0.15); color: #d97706; font-size: 12px; font-weight: bold; margin-bottom: 20px;">
            <i class="fas fa-crown"></i> PREMIUM
        </div>
    <?php endif; ?>
    
    <?php 
        $current_page = basename($_SERVER['PHP_SELF']); 
        if(!function_exists('isActive')){
            function isActive($page, $current) { return ($page == $current) ? 'active' : ''; }
        }
    ?>

    <nav style="flex-grow: 1;">
        <a href="panel.php" class="menu-item <?php echo isActive('panel.php', $current_page); ?>"><i class="fas fa-home"></i> Özet Paneli</a>
        <a href="beslenme.php" class="menu-item <?php echo isActive('beslenme.php', $current_page); ?>"><i class="fas fa-apple-alt"></i> Beslenme</a>
        <a href="egzersiz.php" class="menu-item <?php echo isActive('egzersiz.php', $current_page); ?>"><i class="fas fa-dumbbell"></i> Egzersiz</a>
        <a href="sorularim.php" class="menu-item <?php echo isActive('sorularim.php', $current_page); ?>"><i class="fas fa-envelope-open-text"></i> Uzmana Sorular</a>
        <a href="gelisim.php" class="menu-item <?php echo isActive('gelisim.php', $current_page); ?>"><i class="fas fa-chart-line"></i> Gelişim</a>
        <a href="rozetlerim.php" class="menu-item <?php echo isActive('rozetlerim.php', $current_page); ?>"><i class="fas fa-medal"></i> Rozetlerim</a>
        <a href="turnuva.php" class="menu-item <?php echo isActive('turnuva.php', $current_page); ?>"><i class="fas fa-trophy"></i> Turnuva</a>
        <a href="profil.php" class="menu-item <?php echo isActive('profil.php', $current_page); ?>"><i class="fas fa-user"></i> Profil</a>
    </nav>
    <a href="cikis.php" class="menu-item logout-btn"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
</div>

<div class="main">
    <div class="page-header">
        <div class="header-icon"><i class="fas fa-chart-pie"></i></div>
        <h1>Gelişim Analizi</h1>
    </div>

    <div class="top-grid">
        <div class="glass-card">
            <h4><i class="fas fa-weight-scale" style="color: #6366f1;"></i> Kilo Değişim Grafiği</h4>
            <div class="chart-box"><canvas id="weightChart"></canvas></div>
        </div>

        <div class="glass-card">
            <h4><i class="fas fa-droplets" style="color: #0ea5e9;"></i> Su Tüketimi</h4>
            <div class="chart-box"><canvas id="waterChart"></canvas></div>
        </div>

        <div class="glass-card">
            <h4><i class="fas fa-camera-retro" style="color: var(--orange);"></i> Görsel Gelişim</h4>
            <?php if ($is_premium == 1): ?>
                <button class="btn-upload-trigger" data-bs-toggle="modal" data-bs-target="#fotoYukleModal">
                    <i class="fas fa-plus"></i> Yeni Form Ekle
                </button>
                
                <div class="foto-galeri">
                    <?php if (count($fotograflar) > 0): ?>
                        <?php foreach ($fotograflar as $foto): ?>
                            <div class="foto-item">
                                <a href="islem_v2.php?is=foto_sil&id=<?php echo $foto['id']; ?>" class="delete-btn" onclick="return confirm('Bu görseli silmek istediğinize emin misiniz?')"><i class="fas fa-trash-can"></i></a>
                                <a href="<?php echo $foto['foto_yolu']; ?>" target="_blank">
                                    <img src="<?php echo $foto['foto_yolu']; ?>" alt="Gelişim">
                                    <div class="foto-tarih"><?php echo date('d.m.Y', strtotime($foto['yuklenme_tarihi'])); ?></div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center mt-4 text-muted small">Henüz fotoğraf yüklemediniz.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="premium-lock">
                    <i class="fas fa-crown mb-2" style="font-size: 32px; color: var(--orange);"></i>
                    <h5 style="font-weight: 700; font-size: 15px; color:#854d0e;">Premium Özellik</h5>
                    <p style="font-size: 12px; color: #b45309; margin-bottom:10px;">Form fotoğraflarınızı yükleyerek değişiminizi kaydedin.</p>
                    <a href="premium_planlar.php" class="btn btn-warning btn-sm fw-bold" style="border-radius: 10px;">Yükselt</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="glass-card">
        <h4><i class="fas fa-table-list" style="color:var(--text-muted);"></i> Son 10 Günlük Detaylı Veriler</h4>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Güncel Kilo</th>
                        <th>Su (Litre)</th>
                        <th>Uyku (Saat)</th>
                        <th>Alınan Kalori</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach(array_reverse($veriler) as $row): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><i class="far fa-calendar-alt me-2"></i><?php echo date('d.m.Y', strtotime($row['kayit_tarihi'])); ?></td>
                        <td><span class="weight-cell"><?php echo $row['guncel_kilo']; ?> kg</span></td>
                        <td><i class="fas fa-droplet me-2" style="color: #38bdf8;"></i><?php echo $row['su_miktari']; ?> L</td>
                        <td><i class="fas fa-bed me-2" style="color: #818cf8;"></i><?php echo $row['uyku_suresi']; ?> S</td>
                        <td><strong><?php echo round($row['alinan_kalori']); ?> kcal</strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="fotoYukleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-2">
      <div class="modal-header border-0 pb-0">
        <h5 class="fw-bold m-0 text-primary">Yeni Form Fotoğrafı Ekle</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form action="islem_v2.php?is=foto_yukle" method="POST" enctype="multipart/form-data">
            <div style="border: 2px dashed #cbd5e1; border-radius: 20px; padding: 30px; text-align: center; margin-bottom: 20px;">
                <i class="fas fa-cloud-upload-alt fs-1 text-primary opacity-50 mb-3"></i>
                <input type="file" name="form_foto" class="form-control bg-light" required style="border-radius: 12px;">
                <div class="mt-2 text-muted" style="font-size: 11px;">JPG, PNG veya WebP dosyaları kabul edilir.</div>
            </div>
            <button type="submit" class="btn btn-primary w-100 p-3 fw-bold" style="border-radius: 16px; font-size: 15px;">
                <i class="fas fa-check-circle me-2"></i>Gelişimi Kaydet
            </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
const commonOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { 
        x: { grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11, weight: '600' } } },
        y: { grid: { color: 'rgba(0,0,0,0.05)' }, border: { display: false }, ticks: { font: { family: 'Poppins', size: 11 } } } 
    }
};

new Chart(document.getElementById('weightChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($tarihler); ?>,
        datasets: [{
            data: <?php echo json_encode($kilolar); ?>,
            borderColor: '#0f172a', borderWidth: 3, tension: 0.4,
            pointRadius: 4, pointBackgroundColor: '#fff', pointBorderWidth: 2, fill: false
        }]
    },
    options: commonOptions
});

new Chart(document.getElementById('waterChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($tarihler); ?>,
        datasets: [{
            data: <?php echo json_encode($sular); ?>,
            backgroundColor: '#0ea5e9', borderRadius: 10, barThickness: 18
        }]
    },
    options: commonOptions
});
</script>
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": { "number": { "value": 40 }, "color": { "value": "#3b82f6" }, "opacity": { "value": 0.2 }, "size": { "value": 4 }, "line_linked": { "enable": true, "color": "#3b82f6", "opacity": 0.15 }, "move": { "enable": true, "speed": 1.5 } }
    });
</script>
</body>
</html>