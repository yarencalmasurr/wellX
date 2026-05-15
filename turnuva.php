<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];

// Turnuva verilerini View üzerinden çekiyoruz
$sorgu = $conn->prepare("SELECT * FROM turnuva_puan_durumu LIMIT 15");
$sorgu->execute();
$siralamalar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sağlık Takip | Turnuva</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --blue: #0ea5e9; --gold: #f59e0b; --silver: #94a3b8; --bronze: #b45309; --bg: #f8fafc; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; display: flex; color: #1e293b; }
        
        .sidebar { width: 260px; background: white; height: 100vh; padding: 30px 20px; box-shadow: 4px 0 24px rgba(0,0,0,0.03); position: fixed; }
        .main { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        
        .leaderboard-container { background: white; border-radius: 24px; padding: 20px; box-shadow: 0 10px 15px rgba(0,0,0,0.04); }
        
        .user-row { 
            display: flex; align-items: center; padding: 18px 25px; 
            margin-bottom: 10px; border-radius: 20px; 
            background: #fff; border: 1px solid #f1f5f9; transition: 0.2s;
        }
        .user-row:hover { transform: scale(1.01); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        .rank-circle {
            width: 40px; height: 40px; border-radius: 50%; display: flex;
            align-items: center; justify-content: center; font-weight: 700;
            margin-right: 20px; font-size: 14px;
        }
        
        .first { background: #fef3c7; color: #d97706; border: 2px solid #fcd34d; }
        .second { background: #f1f5f9; color: #475569; border: 2px solid #cbd5e1; }
        .third { background: #ffedd5; color: #9a3412; border: 2px solid #fed7aa; }
        .others { background: #f8fafc; color: #64748b; }

        .xp-badge { background: var(--blue); color: white; padding: 8px 16px; border-radius: 14px; font-weight: 600; font-size: 14px; }
        .current-user { border: 2px solid var(--blue) !important; background: #f0f9ff !important; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="font-size:28px; font-weight:800; margin-bottom:40px; color:#111827; letter-spacing:-1px;">
    <span style="color:#ef4444;">❤</span> wellX
</div>
    <a href="panel.php" class="btn w-100 text-start mb-2"><i class="fas fa-home me-2"></i> Özet Paneli</a>
    <a href="turnuva.php" class="btn btn-primary w-100 text-start mb-2"><i class="fas fa-trophy me-2"></i> Turnuva</a>
</div>

<div class="main">
    <h1 class="mb-2">🏆 Turnuva Sıralaması</h1>
    <p class="text-muted mb-4">Su, Spor, Uyku ve Düzenli Veri Girişi ile puanını yükselt!</p>

    <div class="leaderboard-container">
        <?php foreach ($siralamalar as $index => $row): 
            $rank = $index + 1;
            $rankClass = 'others';
            if($rank == 1) $rankClass = 'first';
            elseif($rank == 2) $rankClass = 'second';
            elseif($rank == 3) $rankClass = 'third';
            
            $is_me = ($row['id'] == $user_id);
        ?>
            <div class="user-row <?php echo $is_me ? 'current-user' : ''; ?>">
                <div class="rank-circle <?php echo $rankClass; ?>">
                    <?php echo $rank; ?>
                </div>
                
                <div style="flex-grow: 1;">
                    <span style="font-weight: 600; font-size: 16px;">
                        <?php echo htmlspecialchars($row['ad_soyad']); ?>
                        <?php if($is_me) echo ' <small class="text-primary">(Sen)</small>'; ?>
                        <?php if($rank == 1) echo ' 👑'; ?>
                    </span>
                    <div style="font-size: 12px; color: #94a3b8;">
                        <i class="fas fa-check-circle"></i> <?php echo $row['kayit_sayisi']; ?> Günlük Veri Girişi
                    </div>
                </div>

                <div class="xp-badge">
                    <?php echo number_format($row['toplam_puan'], 0, ',', '.'); ?> XP
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>