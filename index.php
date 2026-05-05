<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap | Sağlık Portalı</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            font-family: 'Poppins', sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
        }
        .login-card { 
            background: white; 
            padding: 40px; 
            border-radius: 40px; 
            width: 100%; 
            max-width: 400px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
            text-align: center; 
        }
        .header h2 { color: #2d3436; margin-bottom: 30px; font-weight: 600; }
        
        .form-group { text-align: left; margin-bottom: 20px; }
        label { display: block; font-size: 14px; font-weight: 600; color: #333; margin-bottom: 8px; }
        
        input { 
            width: 100%; 
            padding: 15px; 
            border: 1px solid #e0e0e0; 
            border-radius: 12px; 
            font-size: 15px; 
            box-sizing: border-box; 
            transition: 0.3s;
        }
        input:focus { border-color: #667eea; outline: none; box-shadow: 0 0 8px rgba(102,126,234,0.1); }
        
        .btn-login { 
            width: 100%; 
            padding: 16px; 
            background: #6c5ce7; 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.3s;
            font-size: 16px;
        }
        .btn-login:hover { background: #5a4bcf; transform: translateY(-2px); }
        
        .footer-links { margin-top: 25px; font-size: 13px; color: #636e72; }
        .footer-links a { color: #667eea; text-decoration: none; font-weight: 600; }
        
        .alert { background: #ff7675; color: white; padding: 10px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="header">
            <h2>👋 Tekrar Hoş Geldin</h2>
        </div>

        <?php if(isset($_GET['hata'])): ?>
            <div class="alert">Kullanıcı adı veya şifre hatalı!</div>
        <?php endif; ?>

        <form action="islem_v2.php?is=login" method="POST">
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="kullanici" placeholder="Kullanıcı adınızı girin" required>
            </div>

            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="sifre" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-login">Giriş Yap</button>
        </form>

        <div class="footer-links">
            Henüz hesabın yok mu? <a href="kayit_v2.php">Kayıt Ol</a><br><br>
            <a href="hoca_basvuru.php">Spor Hocası Başvurusu</a> | 
            <a href="diyetisyen_basvuru.php">Diyetisyen Başvurusu</a>
        </div>
    </div>

</body>
</html>