<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diyetisyen Başvurusu | WellX Elite</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --neon-teal: #2dd4bf; --dark-bg: #0f172a; --card-glass: rgba(30, 41, 59, 0.7); --text-light: #f1f5f9; }
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle at top right, #1e293b, #0f172a); background-attachment: fixed; color: var(--text-light); }
        #particles-js { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 1; }
        .apply-container { position: relative; z-index: 10; width: 100%; max-width: 600px; padding: 50px; margin: 40px 20px; background: var(--card-glass); backdrop-filter: blur(20px); border-radius: 24px; border: 1px solid rgba(45, 212, 191, 0.2); box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4); }
        .apply-container h2 { color: var(--neon-teal); font-weight: 800; font-size: 32px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .apply-container p { color: #94a3b8; font-size: 14px; margin-bottom: 35px; }
        .form-group { text-align: left; margin-bottom: 25px; }
        label { display: block; font-size: 12px; font-weight: 700; color: var(--neon-teal); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 18px; top: 18px; color: var(--neon-teal); font-size: 16px; }
        input, textarea, .form-control { width: 100%; padding: 16px 16px 16px 52px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(15, 23, 42, 0.6); color: white; font-size: 14px; outline: none; box-sizing: border-box; transition: 0.3s; }
        textarea { height: 120px; padding-top: 18px; resize: none; }
        input:focus, textarea:focus { border-color: var(--neon-teal); background: rgba(15, 23, 42, 0.9); box-shadow: 0 0 15px rgba(45, 212, 191, 0.2); }
        .btn-send { width: 100%; padding: 18px; border-radius: 12px; border: none; background: linear-gradient(135deg, #2dd4bf 0%, #0d9488 100%); color: white; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 2px; box-shadow: 0 10px 20px rgba(45, 212, 191, 0.2); margin-top: 15px; }
        .btn-send:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(45, 212, 191, 0.4); filter: brightness(1.1); }
        .back-link { display: inline-block; margin-top: 25px; color: #64748b; text-decoration: none; font-size: 13px; transition: 0.3s; }
        .back-link:hover { color: var(--neon-teal); }
        .apply-container::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, transparent, var(--neon-teal), transparent); border-radius: 24px 24px 0 0; }
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="apply-container">
    <h2>Beslenme Uzmanı 🍎</h2>
    <p>Beslenme ekolünüzü WellX Elite üyeleriyle paylaşın.</p>
    
    <form action="islem_v2.php?is=uzman_basvuru" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="uzmanlik_turu" value="diyetisyen">

        <div class="form-group">
            <label>Ad Soyad</label>
            <div class="input-wrapper">
                <i class="fas fa-user-md"></i>
                <input type="text" name="ad_soyad" placeholder="İsminiz ve Soyisminiz" required>
            </div>
        </div>

        <div class="form-group">
            <label>E-Posta Adresi</label>
            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="iletisim@mail.com" required>
            </div>
        </div>

        <div class="form-group">
            <label>Beslenme Ekolü / Branş</label>
            <div class="input-wrapper">
                <i class="fas fa-leaf"></i>
                <input type="text" name="uzmanlik" placeholder="Keto, Klinik, Fonksiyonel Tıp vb." required>
            </div>
        </div>

        <div class="form-group">
            <label>Akademik ve Klinik Geçmiş</label>
            <div class="input-wrapper">
                <i class="fas fa-graduation-cap"></i>
                <textarea name="ozgecmis" placeholder="Eğitiminiz ve uzmanlık tecrübeleriniz..." required></textarea>
            </div>
        </div>

        <div class="form-group">
            <label>Diploma / Sertifika (PDF/JPG)</label>
            <input type="file" name="belge" class="form-control" style="padding-left: 15px; padding-top: 12px; height: 55px;">
        </div>

        <button type="submit" class="btn-send">Başvuruyu Onayla</button>
    </form>

    <a href="index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> Panele Dön</a>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": { "number": { "value": 60 }, "color": { "value": "#2dd4bf" }, "opacity": { "value": 0.3 }, "size": { "value": 2 }, "line_linked": { "enable": true, "color": "#2dd4bf", "opacity": 0.2 }, "move": { "enable": true, "speed": 2 } }
    });
</script>
</body>
</html>