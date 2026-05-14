<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap | WellX </title>
    
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
            margin: 0; padding: 0; width: 100%; height: 100%;
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            /* Ferah Mavi Arka Plan */
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
        }

        /* Particles Arka Plan Alanı */
        #particles-js {
            position: absolute; width: 100%; height: 100%; top: 0; left: 0; z-index: 1;
        }

        /* Modern Cam Efektli Kart */
        .login-box {
            position: relative; z-index: 10;
            width: 100%; max-width: 420px;
            padding: 50px 40px;
            background: var(--glass);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 35px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: 0.3s;
        }

        .login-box h2 {
            color: var(--text-dark);
            font-weight: 700; font-size: 32px;
            margin-bottom: 8px; letter-spacing: -1px;
        }

        .login-box p {
            color: #64748b; font-size: 14px; margin-bottom: 35px;
        }

        .input-group { position: relative; margin-bottom: 20px; text-align: left; }
        
        label { 
            display: block; font-size: 13px; font-weight: 600; 
            color: #475569; margin-bottom: 8px; margin-left: 5px;
        }

        .input-wrapper { position: relative; }
        
        .input-wrapper i {
            position: absolute; left: 18px; top: 50%; transform: translateY(-50%);
            color: var(--primary); font-size: 18px;
        }

        .input-wrapper input {
            width: 100%; padding: 16px 16px 16px 52px;
            border-radius: 18px; border: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-dark); font-size: 15px; outline: none; box-sizing: border-box; transition: 0.3s;
            font-family: inherit;
        }

        .input-wrapper input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            background: white;
        }

        .btn-login {
            width: 100%; padding: 16px; border-radius: 18px; border: none;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s;
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2); margin-top: 10px;
        }

        .btn-login:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 25px rgba(59, 130, 246, 0.3);
        }

        .footer-links { margin-top: 30px; font-size: 14px; color: #64748b; }
        .footer-links a { color: var(--primary); text-decoration: none; font-weight: 600; }
        
        .expert-apply { 
            margin-top: 25px; padding-top: 20px; border-top: 1px solid #f1f5f9; 
            display: flex; justify-content: center; gap: 15px; 
        }
        
        .expert-apply a { font-size: 12px; color: #94a3b8; text-decoration: none; transition: 0.3s; }
        .expert-apply a:hover { color: var(--primary); text-decoration: underline; }

        .alert-error {
            background: #fee2e2; color: #ef4444; padding: 12px; 
            border-radius: 15px; font-size: 13px; margin-bottom: 20px;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="login-box" id="loginCard">
    <h2>WellX </h2>
    <p>Sağlıklı yaşam serüvenine yeniden hoş geldin.</p>

    <?php if(isset($_GET['hata'])): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle me-1"></i> Kullanıcı adı veya şifre hatalı!
        </div>
    <?php endif; ?>

    <form action="islem_v2.php?is=login" method="POST">
        <div class="input-group">
            <label>Kullanıcı Adı</label>
            <div class="input-wrapper">
                <i class="fas fa-user"></i>
                <input type="text" name="kullanici" placeholder="Kullanıcı adınızı girin" required>
            </div>
        </div>

        <div class="input-group">
            <label>Şifre</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" name="sifre" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" class="btn-login">Giriş Yap</button>
    </form>

    <div class="footer-links">
        Henüz hesabın yok mu? <a href="kayit_v2.php">Kayıt Ol</a>
    </div>
    
    <div class="expert-apply">
        <a href="hoca_basvuru.php">Spor Hocası Başvurusu</a>
        <span style="color: #e2e8f0;">|</span>
        <a href="diyetisyen_basvuru.php">Diyetisyen Başvurusu</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": {
            "number": { "value": 50, "density": { "enable": true, "value_area": 800 } },
            "color": { "value": "#3b82f6" },
            "shape": { "type": "circle" },
            "opacity": { "value": 0.5, "random": false },
            "size": { "value": 3, "random": true },
            "line_linked": { "enable": true, "distance": 150, "color": "#3b82f6", "opacity": 0.2, "width": 1 },
            "move": { "enable": true, "speed": 1.5, "direction": "none", "random": false, "straight": false, "out_mode": "out", "bounce": false }
        },
        "interactivity": {
            "detect_on": "canvas",
            "events": { "onhover": { "enable": true, "mode": "grab" }, "onclick": { "enable": true, "mode": "push" }, "resize": true }
        },
        "retina_detect": true
    });

    // Hafif Tilt (Eğilme) Efekti
    const card = document.getElementById('loginCard');
    document.addEventListener('mousemove', (e) => {
        const xAxis = (window.innerWidth / 2 - e.pageX) / 40;
        const yAxis = (window.innerHeight / 2 - e.pageY) / 40;
        card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
    });
</script>

</body>
</html>