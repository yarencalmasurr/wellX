<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eğitmen Başvurusu | WellX </title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --neon-blue: #00d2ff;
            --dark-bg: #0f172a;
            --card-glass: rgba(30, 41, 59, 0.7);
            --text-light: #f1f5f9;
        }

        body, html {
            margin: 0; padding: 0; font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            /* Koyu ve Güçlü Arka Plan */
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            background-attachment: fixed;
            color: var(--text-light);
        }

        #particles-js {
            position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 1;
        }

        /* Daha Keskin ve Maskülen Kart Tasarımı */
        .apply-container {
            position: relative; z-index: 10;
            width: 100%; max-width: 600px;
            padding: 50px; margin: 40px 20px;
            background: var(--card-glass);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(0, 210, 255, 0.2);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
        }

        .apply-container h2 {
            color: var(--neon-blue);
            font-weight: 800; font-size: 32px;
            margin-bottom: 10px; text-transform: uppercase;
            letter-spacing: 1px;
        }

        .apply-container p {
            color: #94a3b8; font-size: 14px; margin-bottom: 35px;
        }

        .form-group { text-align: left; margin-bottom: 25px; }
        
        label { 
            display: block; font-size: 12px; font-weight: 700; 
            color: var(--neon-blue); margin-bottom: 8px; 
            text-transform: uppercase; letter-spacing: 1px;
        }

        .input-wrapper { position: relative; }
        
        .input-wrapper i {
            position: absolute; left: 18px; top: 18px;
            color: var(--neon-blue); font-size: 16px;
        }

        input, textarea, .form-control {
            width: 100%; padding: 16px 16px 16px 52px;
            border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(15, 23, 42, 0.6);
            color: white; font-size: 14px; outline: none; box-sizing: border-box; transition: 0.3s;
        }

        textarea { height: 120px; padding-top: 18px; resize: none; }

        input:focus, textarea:focus {
            border-color: var(--neon-blue);
            background: rgba(15, 23, 42, 0.9);
            box-shadow: 0 0 15px rgba(0, 210, 255, 0.2);
        }

        .btn-send {
            width: 100%; padding: 18px; border-radius: 12px; border: none;
            background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%);
            color: white; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s;
            text-transform: uppercase; letter-spacing: 2px;
            box-shadow: 0 10px 20px rgba(0, 210, 255, 0.2);
            margin-top: 15px;
        }

        .btn-send:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 210, 255, 0.4);
            filter: brightness(1.1);
        }

        .back-link { 
            display: inline-block; margin-top: 25px; color: #64748b; 
            text-decoration: none; font-size: 13px; transition: 0.3s;
        }
        .back-link:hover { color: var(--neon-blue); }

        /* Neon Çizgi Efekti */
        .apply-container::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, transparent, var(--neon-blue), transparent);
            border-radius: 24px 24px 0 0;
        }
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="apply-container">
    <h2>SPOR EĞİTMENİ 🏋️‍♂️</h2>
    <p>Profesyonel eğitmen kadromuza katılarak fark yaratın.</p>
    
    <form action="islem_v2.php?is=uzman_basvuru" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="uzmanlik_turu" value="hoca">

        <div class="form-group">
            <label>Ad Soyad</label>
            <div class="input-wrapper">
                <i class="fas fa-user-shield"></i>
                <input type="text" name="ad_soyad" placeholder="İsminiz ve Soyisminiz" required>
            </div>
        </div>

        <div class="form-group">
            <label>Branş / Uzmanlık Alanı</label>
            <div class="input-wrapper">
                <i class="fas fa-dumbbell"></i>
                <input type="text" name="uzmanlik" placeholder="Fitness, Bodybuilding, Yoga vb." required>
            </div>
        </div>

        <div class="form-group">
            <label>Eğitmenlik Geçmişi</label>
            <div class="input-wrapper">
                <i class="fas fa-history"></i>
                <textarea name="ozgecmis" placeholder="Sertifikalarınız ve deneyimleriniz..." required></textarea>
            </div>
        </div>

        <div class="form-group">
            <label>Sertifika Yükle (PDF/JPG)</label>
            <input type="file" name="belge" class="form-control" style="padding-left: 15px; padding-top: 12px; height: 55px;">
        </div>

        <button type="submit" class="btn-send">Başvuruyu Onayla</button>
    </form>

    <a href="index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> Panele Dön</a>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": {
            "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
            "color": { "value": "#00d2ff" },
            "shape": { "type": "circle" },
            "opacity": { "value": 0.3 },
            "size": { "value": 2 },
            "line_linked": { "enable": true, "distance": 150, "color": "#00d2ff", "opacity": 0.2, "width": 1 },
            "move": { "enable": true, "speed": 2 }
        },
        "interactivity": {
            "detect_on": "canvas",
            "events": { "onhover": { "enable": true, "mode": "repulse" } }
        },
        "retina_detect": true
    });
</script>
</body>
</html>