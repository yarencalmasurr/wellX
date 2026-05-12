<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Premium Plan Seçimi | Sağlık Takip</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --blue: #0ea5e9; --orange: #f59e0b; --green: #10b981; --bg: #f8fafc; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: #1e293b; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        
        .container { max-width: 900px; width: 95%; text-align: center; padding: 20px; }
        .header { margin-bottom: 40px; }
        .header h1 { font-size: 32px; color: #0f172a; margin-bottom: 10px; }
        .header p { color: #64748b; }

        .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        
        .plan-card { 
            background: white; 
            padding: 40px 30px; 
            border-radius: 24px; 
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); 
            transition: 0.3s; 
            border: 2px solid transparent;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .plan-card:hover { transform: translateY(-10px); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.1); }
        .plan-card.active { border-color: var(--blue); }

        .plan-icon { font-size: 40px; margin-bottom: 20px; }
        .plan-name { font-size: 22px; font-weight: 600; margin-bottom: 10px; }
        .plan-price { font-size: 36px; font-weight: 700; color: #0f172a; margin-bottom: 20px; }
        .plan-price span { font-size: 16px; color: #64748b; font-weight: 400; }

        .file-upload { margin: 20px 0; text-align: left; }
        .file-upload label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 8px; color: #64748b; }
        .file-upload input { 
            width: 100%; 
            padding: 10px; 
            border: 1px dashed #cbd5e1; 
            border-radius: 12px; 
            font-size: 12px; 
            background: #f1f5f9;
        }

        .btn-select { 
            width: 100%; 
            padding: 16px; 
            background: #f1f5f9; 
            color: #475569; 
            border: none; 
            border-radius: 14px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.2s;
            margin-top: auto;
        }
        .plan-card:hover .btn-select { background: var(--blue); color: white; }

        .back-link { margin-top: 30px; display: inline-block; color: #64748b; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: var(--blue); }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Sizin İçin En Uygun Planı Seçin</h1>
        <p>Sağlıklı yaşam yolculuğunuzda bir adım öne geçin.</p>
    </div>

    <div class="plans-grid">
        <div class="plan-card">
            <div class="plan-icon">🎓</div>
            <div class="plan-name">Öğrenci Planı</div>
            <div class="plan-price">₺49.90 <span>/ay</span></div>
            
            <form action="premium_islem.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="plan_turu" value="ogrenci">
                <div class="file-upload">
                    <label><i class="fas fa-file-upload"></i> Öğrenci Belgesi (PDF/JPG)</label>
                    <input type="file" name="ogrenci_belgesi" required>
                </div>
                <button type="submit" name="premium_onay" class="btn-select">Hemen Başla</button>
            </form>
        </div>

        <div class="plan-card active">
            <div style="position: absolute; top: 15px; right: 20px; background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">En Popüler</div>
            <div class="plan-icon">🚀</div>
            <div class="plan-name">Yetişkin Planı</div>
            <div class="plan-price">₺99.90 <span>/ay</span></div>
            
            <form action="premium_islem.php" method="POST">
                <input type="hidden" name="plan_turu" value="yetiskin">
                <div style="margin: 20px 0; text-align: left; min-height: 70px;">
                    <p style="font-size: 13px; color: #64748b;"><i class="fas fa-check" style="color: #10b981;"></i> Anında Aktivasyon</p>
                    <p style="font-size: 13px; color: #64748b;"><i class="fas fa-check" style="color: #10b981;"></i> Tüm Özelliklere Erişim</p>
                </div>
                <button type="submit" name="premium_onay" class="btn-select">Hemen Başla</button>
            </form>
        </div>
    </div>

    <a href="panel.php" class="back-link"><i class="fas fa-arrow-left"></i> Panele Geri Dön</a>
</div>

</body>
</html>