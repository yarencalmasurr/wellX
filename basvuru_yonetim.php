<?php
// 1. Oturumu başlatıyoruz
session_start();

// GÜVENLİK DUVARI: Sadece adminler girebilir
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit();
}

// 2. Veritabanı bağlantısını dahil ediyoruz
include 'baglan.php';

// 3. Bekleyen başvuruları veritabanından çekiyoruz
try {
    $sorgu = $conn->query("SELECT * FROM uzman_basvurulari WHERE durum = 'beklemede'");
    $basvurular = $sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Başvuru Yönetim Paneli</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h2 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #edf2f7; }
        th { background-color: #f1f5f9; color: #4a5568; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #f7fafc; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-diyetisyen { background: #e3f9e5; color: #1f9d55; }
        .badge-hoca { background: #e0f2fe; color: #0369a1; }
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; color: white; font-size: 14px; transition: 0.2s; }
        .btn-onay { background-color: #10b981; border: none; }
        .btn-onay:hover { background-color: #059669; }
        .btn-red { background-color: #ef4444; margin-left: 5px; }
        .btn-red:hover { background-color: #dc2626; }
        .empty { text-align: center; color: #718096; padding: 40px; }
    </style>
</head>
<body>

<div class="container">
    <h2>📩 Gelen Uzman Başvuruları</h2>
    
    <table>
        <thead>
            <tr>
                <th>Uzman Bilgileri</th>
                <th>Branş</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($basvurular) > 0): ?>
                <?php foreach ($basvurular as $row): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($row['ad_soyad']); ?></strong><br>
                        <small style="color: #666;"><?php echo htmlspecialchars($row['email']); ?></small>
                    </td>
                    <td>
                        <?php 
                            $class = ($row['uzmanlik'] == 'diyetisyen') ? 'badge-diyetisyen' : 'badge-hoca';
                            echo "<span class='badge $class'>" . strtoupper($row['uzmanlik']) . "</span>";
                        ?>
                    </td>
                    <td>
                        <a href="islem.php?is=onayla&id=<?php echo $row['id']; ?>" 
                           class="btn btn-onay" 
                           onclick="return confirm('Bu uzmanı onaylıyor musunuz? Otomatik hesap oluşturulacaktır.')">
                           Onayla
                        </a>
                        <a href="islem.php?is=reddet&id=<?php echo $row['id']; ?>" 
                           class="btn btn-red">
                           Reddet
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="empty">Şu an bekleyen herhangi bir başvuru bulunmuyor.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 20px; text-align: right;">
        <a href="index.php" style="color: #4a5568; text-decoration: none; font-size: 14px;">← Ana Sayfaya Dön</a>
    </div>
</div>

</body>
</html>