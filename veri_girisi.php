<?php
session_start();
// Giriş kontrolü: Oturum açılmamışsa giriş sayfasına yönlendir
if(!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Günlük Veri Girişi | Sağlık Portalı</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f5f6fa;
            --card-bg: #ffffff;
            --accent-color: #6c5ce7; /* Giriş ekranındaki ana mor tonu */
            --input-bg: #f8f9fb;
            --text-dark: #2d3436;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background: var(--card-bg);
            padding: 45px;
            border-radius: 50px; /* Görsellerdeki gibi geniş kavisler */
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            text-align: center;
        }

        h2 {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 30px;
            font-size: 26px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: #888;
            margin-bottom: 8px;
            display: block;
            padding-left: 10px;
        }

        input {
            width: 100%;
            padding: 18px;
            border: none;
            background: var(--input-bg);
            border-radius: 20px;
            font-size: 15px;
            font-family: inherit;
            box-sizing: border-box;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            background: #f0f1f5;
            box-shadow: 0 0 0 2px var(--accent-color);
        }

        .btn-save {
            width: 100%;
            padding: 20px;
            background: var(--text-dark); /* Görseldeki buton stili */
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-save:hover {
            background: #000;
            transform: translateY(-2px);
        }

        .skip-link {
            display: block;
            margin-top: 20px;
            color: #aaa;
            text-decoration: none;
            font-size: 13px;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Bugün Nasıl Geçti?</h2>
        
        <form action="islem.php?is=kaydet" method="POST">
            <div class="form-group">
                <label>Güncel Kilo (kg)</label>
                <input type="number" step="0.1" name="kilo" placeholder="Örn: 70.5" required>
            </div>

            <div class="form-group">
                <label>Uyku Süresi (saat)</label>
                <input type="number" step="0.5" name="uyku" placeholder="Örn: 7.5" required>
            </div>

            <div class="form-group">
                <label>Spor Süresi (dakika)</label>
                <input type="number" name="spor" placeholder="Örn: 45" required>
            </div>

            <div class="form-group">
                <label>İçilen Su (Litre)</label>
                <input type="number" step="0.1" name="su" placeholder="Örn: 2.5" required>
            </div>

            <button type="submit" class="btn-save">Verileri Kaydet ve İlerle ➔</button>
        </form>

        <a href="haftalik_ozet.php" class="skip-link">Veri girmeden devam et</a>
    </div>

</body>
</html>