<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
    <style>
        body { background: #6c5ce7; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin:0; }
        .card { background: white; padding: 40px; border-radius: 25px; width: 350px; }
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2d3436; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="text-align:center">Kayıt Ol</h2>
        <form action="islem_v2.php?is=kayit_ol" method="POST">
            <input type="text" name="ad_soyad" placeholder="Ad Soyad" required>
            <input type="text" name="kullanici_adi" placeholder="Kullanıcı Adı" required>
            <input type="email" name="email" placeholder="E-posta" required>
            <input type="password" name="sifre" placeholder="Şifre" required>
            <button type="submit">Hesabı Oluştur</button>
        </form>
        <center><br><a href="index.php" style="color:#666; font-size:12px; text-decoration:none;">Zaten hesabım var</a></center>
    </div>
</body>
</html>