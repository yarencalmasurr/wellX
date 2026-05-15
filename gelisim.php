<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// --- 1. PREMIUM KONTROLÜ ---
$user_sorgu = $conn->prepare("SELECT is_premium, ad_soyad FROM kullanicilar WHERE id = ?");
$user_sorgu->execute([$user_id]);
$user_data = $user_sorgu->fetch(PDO::FETCH_ASSOC);
$is_premium = $user_data['is_premium'] ?? 0;

// --- 2. FOTOĞRAFLARI ÇEK (Tarihe Göre) ---
$foto_sorgu = $conn->prepare("SELECT * FROM gelisim_fotograflari WHERE user_id = ? ORDER BY yuklenme_tarihi DESC");
$foto_sorgu->execute([$user_id]);
$fotograflar = $foto_sorgu->fetchAll(PDO::FETCH_ASSOC);

// --- 3. GRAFİK VERİLERİNİ ÇEK ---
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
    <title>Gelişim Analizi | Sağlık Portalı</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --bg: #f8fafc;
            --accent: #0ea5e9;
            --premium: #f59e0b;
            --text-dark: #0f172a;
            --text-light: #64748b;
            --white: #ffffff;
            --card-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.04), 0 10px 10px -5px rgba(0, 0, 0, 0.01);
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg); 
            color: var(--text-dark); 
            margin: 0; 
            padding: 40px 0;
            line-height: 1.6;
        }

        .container { max-width: 1200px; padding: 0 20px; }

        /* Navigation */
        .top-nav { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 40px; 
        }
        .top-nav h2 { font-size: 28px; font-weight: 700; margin: 0; letter-spacing: -0.8px; }
        .back-btn { 
            background: var(--white);
            color: var(--text-light); 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 14px; 
            padding: 10px 20px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            transition: 0.3s all;
        }
        .back-btn:hover { background: #f1f5f9; color: var(--accent); transform: translateX(-5px); }

        /* Dashboard Grid */
        .top-grid { 
            display: grid; 
            grid-template-columns: 1.2fr 1.2fr 0.8fr; 
            gap: 25px; 
            margin-bottom: 30px; 
        }

        .card { 
            background: var(--white); 
            padding: 28px; 
            border-radius: 30px; 
            box-shadow: var(--card-shadow); 
            border: 1px solid rgba(226, 232, 240, 0.7);
            height: 100%;
            transition: 0.3s ease;
        }

        .card h4 { 
            margin-top: 0; 
            margin-bottom: 20px; 
            font-size: 13px; 
            font-weight: 800; 
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-box { height: 240px; width: 100%; position: relative; }

        /* Fotoğraf Alanı & Galeri */
        .btn-upload-trigger { 
            background: var(--text-dark); 
            color: var(--white); 
            border: none; 
            padding: 14px; 
            border-radius: 16px; 
            font-weight: 700; 
            width: 100%; 
            transition: 0.3s; 
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-upload-trigger:hover { background: var(--accent); transform: scale(1.02); }

        .foto-galeri { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); 
            gap: 12px; 
            margin-top: 25px; 
            max-height: 280px; 
            overflow-y: auto; 
            padding-right: 5px;
        }

        .foto-item { 
            position: relative; 
            border-radius: 18px; 
            overflow: hidden; 
            border: 2px solid #f1f5f9; 
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        }
        .foto-item:hover { transform: scale(1.05); z-index: 5; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .foto-item img { width: 100%; height: 110px; object-fit: cover; display: block; cursor: zoom-in; }
        
        .foto-tarih { 
            position: absolute; 
            bottom: 0; 
            background: rgba(15, 23, 42, 0.8); 
            backdrop-filter: blur(4px);
            color: white; 
            width: 100%; 
            font-size: 10px; 
            padding: 6px 0; 
            text-align: center; 
            font-weight: 600;
        }

        .delete-btn { 
            position: absolute; 
            top: 8px; 
            right: 8px; 
            background: #ef4444; 
            color: white; 
            width: 24px; 
            height: 24px; 
            border-radius: 50%; 
            display: none; 
            align-items: center; 
            justify-content: center; 
            font-size: 11px; 
            text-decoration: none; 
            z-index: 10;
            border: 2px solid white;
        }
        .foto-item:hover .delete-btn { display: flex; }

        /* Premium Lock Design */
        .premium-lock { 
            background: linear-gradient(135deg, #fffcf5 0%, #fffbeb 100%); 
            border: 2px dashed #fcd34d; 
            padding: 30px 20px; 
            border-radius: 24px; 
            text-align: center; 
            height: 100%; 
            display: flex; 
            flex-direction: column; 
            justify-content: center;
        }

        /* Data Table */
        .table-card { padding: 0; overflow: hidden; border-radius: 30px; }
        .table-header { padding: 28px 28px 10px 28px; }
        .table-wrapper { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { text-align: left; background: #fafcfe; padding: 20px; font-size: 12px; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f1f5f9; }
        td { padding: 20px; border-bottom: 1px solid #f1f5f9; font-size: 15px; font-weight: 500; }
        .weight-cell { font-weight: 700; color: var(--accent); background: #f0f9ff; padding: 6px 12px; border-radius: 10px; }

        /* Modal Design */
        .modal-content { border-radius: 35px; border: none; overflow: hidden; }
        .modal-header { background: #f8fafc; padding: 30px; }
        .modal-body { padding: 40px; }
        .upload-area { border: 2px dashed #e2e8f0; border-radius: 20px; padding: 30px; text-align: center; transition: 0.3s; }
        .upload-area:hover { border-color: var(--accent); background: #f0f9ff; }

        @media (max-width: 1100px) { .top-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <div class="top-nav">
        <h2><i class="fas fa-chart-pie" style="color: var(--accent); margin-right: 12px;"></i>Gelişim Analizi</h2>
        <a href="panel.php" class="back-btn"><i class="fas fa-arrow-left me-2"></i>Panele Dön</a>
    </div>

    <div class="top-grid">
        <div class="card">
            <h4><i class="fas fa-weight-scale" style="color: #6366f1;"></i> Kilo Değişim Grafiği</h4>
            <div class="chart-box"><canvas id="weightChart"></canvas></div>
        </div>

        <div class="card">
            <h4><i class="fas fa-droplets" style="color: #0ea5e9;"></i> Su Tüketim Analizi</h4>
            <div class="chart-box"><canvas id="waterChart"></canvas></div>
        </div>

        <div class="card">
            <h4><i class="fas fa-camera-retro" style="color: var(--premium);"></i> Görsel Gelişim</h4>
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
                        <div class="text-center mt-4">
                            <p style="font-size: 13px; color: var(--text-light); font-style: italic;">Henüz fotoğraf yüklemediniz.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="premium-lock">
                    <i class="fas fa-crown mb-3" style="font-size: 32px; color: var(--premium);"></i>
                    <h5 style="font-weight: 700; font-size: 16px;">Premium Özellik</h5>
                    <p style="font-size: 12px; color: #b45309;">Form fotoğraflarınızı yükleyerek değişiminizi kanıtlayın.</p>
                    <a href="premium_planlar.php" class="btn btn-warning btn-sm fw-bold mt-2" style="border-radius: 10px;">Yükselt</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card table-card">
        <div class="table-header">
            <h4><i class="fas fa-table-list"></i> Son 10 Günlük Detaylı Veriler</h4>
        </div>
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
                        <td style="color: var(--text-light);"><?php echo date('d.m.Y', strtotime($row['kayit_tarihi'])); ?></td>
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

<div class="modal fade" id="fotoYukleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header border-0">
        <h5 class="fw-bold m-0">Yeni Form Fotoğrafı Ekle</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="islem_v2.php?is=foto_yukle" method="POST" enctype="multipart/form-data">
            <div class="upload-area mb-4">
                <i class="fas fa-images fs-1 text-primary opacity-50 mb-3"></i>
                <input type="file" name="form_foto" class="form-control border-0 bg-light p-3" required style="border-radius: 15px;">
                <div class="mt-3 text-muted small">JPG, PNG veya WebP dosyaları kabul edilir.</div>
            </div>
            <button type="submit" class="btn btn-primary w-100 p-3 fw-bold" style="border-radius: 18px; font-size: 16px;">
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
        x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' } } },
        y: { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } } } 
    }
};

// Kilo Chart
new Chart(document.getElementById('weightChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($tarihler); ?>,
        datasets: [{
            data: <?php echo json_encode($kilolar); ?>,
            borderColor: '#0f172a',
            borderWidth: 3,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderWidth: 2,
            fill: false
        }]
    },
    options: commonOptions
});

// Water Chart
new Chart(document.getElementById('waterChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($tarihler); ?>,
        datasets: [{
            data: <?php echo json_encode($sular); ?>,
            backgroundColor: '#0ea5e9',
            borderRadius: 10,
            barThickness: 18
        }]
    },
    options: commonOptions
});
</script>

</body>
</html>