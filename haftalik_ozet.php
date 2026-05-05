<?php
// PHP ile veritabanından verileri çektiğini varsayıyoruz
$veriler = [
    ['tarih' => '28.04.2026', 'kilo' => 2, 'spor' => '1\'', 'uyku' => '1s', 'su' => '1L'],
    ['tarih' => '27.04.2026', 'kilo' => 1, 'spor' => '2\'', 'uyku' => '2s', 'su' => '1L'],
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <style>
        body { background: #f0f0f0; font-family: 'Poppins', sans-serif; padding: 40px; }
        .container { max-width: 700px; margin: auto; }
        .card { background: white; border-radius: 40px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .back-link { text-decoration: none; color: #5a6778; font-weight: 600; }
        
        /* Özet Kutuları */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
        .stat-box { background: #f8f9fa; padding: 15px 20px; border-radius: 20px; }
        .stat-val { font-weight: 600; font-size: 18px; display: block; }
        .stat-lbl { color: #999; font-size: 12px; }

        /* Tablo */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; color: #ccc; font-weight: 400; font-size: 12px; padding-bottom: 15px; }
        td { padding: 15px 0; border-bottom: 1px solid #f5f5f5; font-size: 14px; }
        .edit-icon { color: #ff7675; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <a href="#" class="back-link">⬅ Seçime Dön</a>
            <h2 style="margin: 20px 0;">📊 Haftalık Değerler</h2>
            
            <div style="margin-bottom:15px; font-weight:600;">27 Apr - 03 May <span style="color:#999; font-weight:400; font-size:12px;">+1 kg (Alınan)</span></div>

            <div class="stats-grid">
                <div class="stat-box"><span class="stat-val">4 dk</span><span class="stat-lbl">Toplam Spor</span></div>
                <div class="stat-box"><span class="stat-val">1.3 sa</span><span class="stat-lbl">Ort. Uyku</span></div>
                <div class="stat-box"><span class="stat-val">4.7 L</span><span class="stat-lbl">Ort. Su</span></div>
                <div class="stat-box"><span class="stat-val">2 kg</span><span class="stat-lbl">Güncel Kilo</span></div>
            </div>

            <table>
                <thead>
                    <tr><th>Tarih</th><th>Kilo</th><th>Spor</th><th>Uyku</th><th>Su</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach($veriler as $v): ?>
                    <tr>
                        <td><b><?= $v['tarih'] ?></b></td>
                        <td><b><?= $v['kilo'] ?></b></td>
                        <td><?= $v['spor'] ?></td>
                        <td><?= $v['uyku'] ?></td>
                        <td><?= $v['su'] ?></td>
                        <td><span class="edit-icon">✎</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>