<?php
session_start();
include 'baglan.php';

// Güvenlik
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];

// Hem diyetisyenin attığı notları hem de gönderilen tarifleri buradan çekeceğiz
$sorgu = $conn->prepare("SELECT * FROM beslenme_planlari WHERE user_id = ? ORDER BY id DESC");
$sorgu->execute([$user_id]);
$mesajlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Uzman Notlarım</title>
    <style>
        body { font-family: sans-serif; background: #f8fafc; padding: 40px; }
        .card { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 15px; border-left: 5px solid #3498db; }
    </style>
</head>
<body>

<div style="max-width:800px; margin:auto;">
    <h2>📩 Uzman Notlarım ve Tariflerim</h2>
    <a href="panel.php" style="display:block; margin-bottom:20px;">⬅️ Panele Dön</a>

    <?php if(empty($mesajlar)): ?>
        <p>Henüz uzmanınızdan gelen bir not bulunmuyor.</p>
    <?php else: ?>
        <?php foreach($mesajlar as $m): ?>
            <div class="card">
                <div style="white-space: pre-wrap; font-size: 15px; color: #334155;"><?php echo htmlspecialchars($m['plan_metni']); ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>