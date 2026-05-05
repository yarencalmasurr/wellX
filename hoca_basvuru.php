<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ekiplerimize Katılın - Spor Hocası</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { background: #6c5ce7; font-family: 'Poppins', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .form-card { background: white; padding: 40px; border-radius: 40px; width: 100%; max-width: 450px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .badge { background: #ebf5ff; color: #3498db; padding: 6px 18px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; display: inline-block; margin-bottom: 10px; }
        h1 { font-size: 24px; margin: 10px 0 20px 0; color: #2d3436; text-align: center; }
        label { display: block; margin-top: 15px; margin-bottom: 5px; font-size: 13px; color: #636e72; font-weight: 600; }
        input, textarea { width: 100%; padding: 14px; margin-bottom: 10px; border: 1px solid #eee; background: #f8f9fb; border-radius: 12px; box-sizing: border-box; font-family: inherit; }
        input:focus, textarea:focus { outline: none; border-color: #3498db; background: #fff; }
        .btn-submit { width: 100%; padding: 15px; background: #2d3436; color: white; border: none; border-radius: 12px; font-size: 16px; cursor: pointer; font-weight: 600; transition: 0.3s; margin-top: 15px; }
        .btn-submit:hover { background: #000; transform: translateY(-2px); }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #6c5ce7; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>
    <div class="form-card">
        <center><span class="badge">💪 SPOR HOCASI PANELİ</span></center>
        <h1>Ekiplerimize Katılın</h1>
        
        <form action="islem.php?is=hoca_kayit" method="POST" enctype="multipart/form-data">
            <label>Ad Soyad</label>
            <input type="text" name="ad_soyad" placeholder="Adınız Soyadınız" required>
            
            <label>E-posta Adresi</label>
            <input type="email" name="email" placeholder="ornek@mail.com" required>
            
            <label>Uzmanlık Alanı</label>
            <input type="text" name="uzmanlik" placeholder="Örn: Fitness, Pilates, Crossfit" required>
            
            <label>Tecrübe / Özgeçmiş</label>
            <textarea name="tecrube" placeholder="Eğitmenlik geçmişinizden kısaca bahsedin..." rows="3"></textarea>
            
            <label>Sertifika / Diploma (Seçmeli)</label>
            <input type="file" name="belge">
            
            <button type="submit" class="btn-submit">Başvuruyu Gönder</button>
        </form>
        
        <a href="index.php" class="back-link">← Giriş Ekranına Dön</a>
    </div>
</body>
</html>