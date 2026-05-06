<?php
session_start();
// Seçilen planı URL'den güvenli bir şekilde alıyoruz
$plan = isset($_GET['plan']) ? htmlspecialchars($_GET['plan']) : 'yetişkin';
$fiyat = ($plan == 'ogrenci') ? '49.90' : '99.90';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Güvenli Ödeme | Sağlık Takip</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --blue: #0ea5e9; 
            --green: #10b981; 
            --bg: #f8fafc; 
            --text: #1e293b; 
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background: var(--bg); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
        }

        .payment-card {
            background: white;
            max-width: 500px;
            width: 90%;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
        }

        .payment-card h3 {
            margin-top: 0;
            color: var(--text);
            font-size: 22px;
            font-weight: 600;
        }

        .text-muted {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .card-input {
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            box-sizing: border-box;
            transition: 0.2s;
        }

        .card-input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: var(--green);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            filter: brightness(0.9);
            transform: translateY(-1px);
        }

        .plan-info {
            background: #f0f9ff;
            color: #0369a1;
            padding: 10px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="payment-card">
    <div class="plan-info">
        <i class="fas fa-tag"></i> ₺<?php echo $fiyat; ?> / Aylık Abonelik
    </div>
    
    <h3>💳 Ödeme Bilgileri</h3>
    <p class="text-muted"><strong><?php echo ucfirst($plan); ?> Planı</strong> ödemesini güvenle tamamlayın.</p>
    
    <form action="onay_sayfasi.php" method="POST">
        <!-- Plan bilgisini gizli olarak gönderiyoruz -->
        <input type="hidden" name="secilen_plan" value="<?php echo $plan; ?>">
        
        <input type="text" placeholder="Kart Üzerindeki İsim" class="card-input" required>
        <input type="text" placeholder="0000 0000 0000 0000" class="card-input" maxlength="19" required>
        
        <div style="display: flex; gap: 10px;">
            <input type="text" placeholder="AA/YY" class="card-input" maxlength="5" required>
            <input type="text" placeholder="CVC" class="card-input" maxlength="3" required>
        </div>
        
        <button type="submit" class="btn-submit">Ödemeyi Tamamla</button>
    </form>

    <div style="margin-top: 20px;">
        <a href="premium_planlar.php" style="color: #94a3b8; text-decoration: none; font-size: 13px;">
            <i class="fas fa-chevron-left"></i> Vazgeç ve Geri Dön
        </a>
    </div>
</div>

</body>
</html>