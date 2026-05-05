<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Danışan Kaydı</title>
    <style>
        body { background: #6c5ce7; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 25px; width: 350px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #706fd3; color: white; border: none; border-radius: 10px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h3>Yeni Danışan Kaydı</h3>
        <form action="islem.php?is=danisan_kayit" method="POST">
            <input type="text" name="ad_soyad" placeholder="Ad Soyad" required>
            <input type="text" name="kullanici" placeholder="Kullanıcı Adı" required>
            <input type="email" name="email" placeholder="E-posta" required>
            <input type="password" name="sifre" placeholder="Şifre" required>
            <button type="submit">Kayıt Ol</button>
        </form>
        <br><a href="index.php" style="color:#666; font-size:12px;">Geri Dön</a>
    </div>
</body>
</html>