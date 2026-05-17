<?php

session_start();
include 'baglan.php';

if(!isset($_SESSION['user_id'])){
    header("Location:index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$kontrol = $conn->prepare("
    SELECT is_premium
    FROM kullanicilar
    WHERE id = ?
");

$kontrol->execute([$user_id]);

$user = $kontrol->fetch(PDO::FETCH_ASSOC);

if($user['is_premium'] != 1){
    exit("Premium gerekli.");
}
// premium kullanıcıların gelişim fotoğraflarını yükleyebilmesi için

if(isset($_FILES['gelisim_foto'])){

    $dosya = $_FILES['gelisim_foto'];

    $uzanti = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));

    $izinli = ['jpg','jpeg','png','webp'];

    if(!in_array($uzanti, $izinli)){
        exit("Geçersiz dosya.");
    }
// aynı isimde iki fotoğraf olursa diğeri silineceği için rastgele bir sayıyıla birleştirilir.
    $yeni_ad = time() . "_" . rand(1000,9999) . "." . $uzanti;

    $hedef = $yeni_ad;

    move_uploaded_file($dosya['tmp_name'], $hedef);

    $aciklama = $_POST['aciklama'] ?? '';

    $ekle = $conn->prepare("
        INSERT INTO gelisim_fotograflari
        (user_id, foto_yolu, aciklama)
        VALUES (?, ?, ?)
    ");

    $ekle->execute([
        $user_id,
        $hedef,
        $aciklama
    ]);

}

header("Location: gelisim.php");
exit();
?>