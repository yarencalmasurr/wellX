<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sorgu = $conn->prepare("SELECT is_premium, ad_soyad FROM kullanicilar WHERE id = ?");
$sorgu->execute([$user_id]);
$user = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['is_premium'] == 0) {
    header("Location: panel.php");
    exit();
}

if (isset($_POST['iptal_et'])) {

    $iptal = $conn->prepare("UPDATE kullanicilar SET is_premium = 0 WHERE id = ?");
    $iptal->execute([$user_id]);

    header("Location: panel.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Premium Yönetimi</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f8fafc;
    font-family:Poppins,sans-serif;
}

.card-box{
    max-width:550px;
    margin:100px auto;
    background:white;
    border-radius:24px;
    padding:40px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
}

.premium-badge{
    background:linear-gradient(135deg,#f59e0b,#d97706);
    color:white;
    padding:10px 18px;
    border-radius:14px;
    display:inline-block;
    font-weight:600;
    margin-bottom:20px;
}

.btn-danger{
    border-radius:14px;
    padding:12px;
    font-weight:600;
}

.btn-secondary{
    border-radius:14px;
    padding:12px;
    font-weight:600;
}
</style>
</head>

<body>

<div class="card-box text-center">

    <div class="premium-badge">
        👑 Premium Üyelik Aktif
    </div>

    <h2><?php echo htmlspecialchars($user['ad_soyad']); ?></h2>

    
    <p class="text-muted mt-3" style="font-size:15px; line-height:1.8;">

        Premium üyeliğini iptal etmen durumunda:

        <br><br>

        ❌ Kişisel uzman desteğine erişemezsin.<br>
        ❌ Özel beslenme ve egzersiz planların devre dışı kalır.<br>
        ❌ Gelişmiş analiz ve detaylı grafik özellikleri kapanır.<br>
        ❌ Premium tarifler ve özel içerikler görüntülenemez.<br>

    </p>

    <form method="POST">

        <button type="submit"
                name="iptal_et"
                class="btn btn-danger w-100 mt-4"
                onclick="return confirm('Premium üyeliğini iptal etmek istediğine emin misin?')">

            Premium Üyeliği İptal Et

        </button>

    </form>

    <a href="panel.php" class="btn btn-secondary w-100 mt-3">
        Panele Geri Dön
    </a>

</div>

</body>
</html>