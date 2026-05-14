<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol | WellX Sağlık Portalı</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --primary: #3b82f6; 
            --primary-dark: #1d4ed8;
            --glass: rgba(255, 255, 255, 0.85);
            --text-dark: #0f172a;
        }

        body, html {
            margin: 0; padding: 0; width: 100%; min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            background-attachment: fixed;
        }

        #particles-js {
            position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 1;
        }

        .register-box {
            position: relative; z-index: 10;
            width: 100%; max-width: 450px;
            padding: 50px 40px; margin: 40px 20px;
            background: var(--glass);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 35px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: 0.3s;
        }

        .register-box h2 {
            color: var(--text-dark);
            font-weight: 700; font-size: 30px;
            margin-bottom: 8px; letter-spacing: -1px;
        }

        .register-box p {
            color: #64748b; font-size: 14px; margin-bottom: 30px;
        }

        .input-group { position: relative; margin-bottom: 20px; text-align: left; }
        
        .input-group label {
            display: block; font-size: 13px; font-weight: 600; 
            color: #475569; margin-bottom: 8px; margin-left: 5px;
        }

        .input-wrapper { position: relative; }
        
        .input-wrapper i {
            position: absolute; left: 18px; top: 50%; transform: translateY(-50%);
            color: var(--primary); font-size: 16px;
        }

        .input-wrapper input {
            width: 100%; padding: 15px 15px 15px 50px;
            border-radius: 18px; border: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-dark); font-size: 14px; outline: none; box-sizing: border-box; transition: 0.3s;
            font-family: inherit;
        }

        .input-wrapper input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            background: white;
        }

        .btn-register {
            width: 100%; padding: 16px; border-radius: 18px; border: none;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s;
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2); margin-top: 10px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(59, 130, 246, 0.3);
        }

        .footer-links { margin-top: 25px; font-size: 14px; color: #64748b; }
        .footer-links a { color: var(--primary); text-decoration: none; font-weight: 600; transition: 0.3s; }
        .footer-links a:hover { text-decoration: underline; }
        
        .expert-apply { margin-top: 25px; padding-top: 20px; border-top: 1px solid #f1f5f9; font-size: 13px; color: #94a3b8;}
        .expert-apply a { color: #64748b; font-weight: 500; text-decoration: none;}
        .expert-apply a:hover { color: var(--primary); }
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="register-box">
    <h2>Yeni Hesap Oluştur 🚀</h2>
    <p>Sağlıklı yaşam yolculuğuna ilk adımı at.</p>

    <form action="islem_v2.php?is=kayit_ol" method="POST">
        <div class="input-group">
            <label>Ad Soyad</label>
            <div class="input-wrapper">
                <i class="fas fa-id-card"></i>
                <input type="text" name="ad_soyad" placeholder="İsminiz ve Soyisminiz" required>
            </div>
        </div>

        <div class="input-group">
            <label>Kullanıcı Adı</label>
            <div class="input-wrapper">
                <i class="fas fa-at"></i>
                <input type="text" name="kullanici_adi" placeholder="Kullanıcı adınızı belirleyin" required>
            </div>
        </div>

        <div class="input-group">
            <label>E-posta Adresi</label>
            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="ornek@mail.com" required>
            </div>
        </div>

        <div class="input-group">
            <label>Şifre</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" name="sifre" placeholder="Güçlü bir şifre girin" required>
            </div>
        </div>

        <button type="submit" class="btn-register">Hesabı Oluştur</button>
    </form>

    <div class="footer-links">
        Zaten hesabın var mı? <a href="index.php">Giriş Yap</a>
    </div>

    <div class="expert-apply">
        Sağlık uzmanı mısın? <br>
        <a href="diyetisyen_basvuru.php">Diyetisyen</a> | <a href="hoca_basvuru.php">Eğitmen</a> başvurusu yap.
    </div>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": {
            "number": { "value": 50, "density": { "enable": true, "value_area": 800 } },
            "color": { "value": "#3b82f6" },
            "shape": { "type": "circle" },
            "opacity": { "value": 0.5 },
            "size": { "value": 3 },
            "line_linked": { "enable": true, "distance": 150, "color": "#3b82f6", "opacity": 0.2, "width": 1 },
            "move": { "enable": true, "speed": 1.5 }
        },
        "interactivity": {
            "detect_on": "canvas",
            "events": { "onhover": { "enable": true, "mode": "grab" } }
        },
        "retina_detect": true
    });
</script>
</body>
</html>