<?php

function rozetKontrolEt($conn, $user_id, $kategori, $mevcut_deger) {
   $yeni_rozet = null;
   // bu kategoriye ait rozetleri bul

    $sorgu = $conn->prepare("SELECT * FROM rozetler WHERE kategori = ?");
    $sorgu->execute([$kategori]);
    $rozetler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rozetler as $rozet) {
        // kullanıcı hedefe ulaştıysa
        if ($mevcut_deger >= $rozet['hedef_deger']) {
            $rozet_id = $rozet['id'];

            //  kullanıcı bu rozeti BUGÜN almış mı kontrol
            $kontrol = $conn->prepare("SELECT id FROM kullanici_rozetleri WHERE user_id = ? AND rozet_id = ? AND DATE(kazanma_tarihi) = CURDATE()");
            $kontrol->execute([$user_id, $rozet_id]);

            if (!$kontrol->fetch()) {
                // BUGÜN daha önce almamışsa rozeti ver
                $ekle = $conn->prepare("INSERT INTO kullanici_rozetleri (user_id, rozet_id) VALUES (?, ?)");
                $ekle->execute([$user_id, $rozet_id]);
                
                // animasyonun tetiklenmesi için rozet adını değişkene atıyoruz
                $yeni_rozet = $rozet['rozet_adi'];
            }
        }
    }
    
    // Kazanılan en son rozeti islem_v2.php e gönderme
    return $yeni_rozet;
}
?>