<?php
session_start();
include 'baglan.php';

// kullanıcı id alalım
$user_id = $_SESSION['user_id'];

// 1. Veritabanında kullanıcıyı premium yapalım

$guncelle = $conn->prepare("UPDATE kullanicilar SET is_premium = 1 WHERE id = ?");
$sonuc = $guncelle->execute([$user_id]);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ödeme Başarılı!</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
    </style>
</head>
<body>

<script>
    Swal.fire({
        title: 'Ödeme Başarıyla Sağlandı!',
        text: 'Artık Premium üyesiniz. Tüm özelliklere erişebilirsiniz.',
        icon: 'success',
        confirmButtonText: 'Panele Git',
        confirmButtonColor: '#0ea5e9'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'panel.php';
        }
    });
</script>

</body>
</html>