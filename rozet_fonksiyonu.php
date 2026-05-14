<?php
// rozet_fonksiyonu.php
function rozetKontrolEt($conn, $user_id, $kategori, $mevcut_deger) {
   $yeni_rozet = null;
   // Bu kategoriye ait rozetleri bul

    $sorgu = $conn->prepare("SELECT * FROM rozetler WHERE kategori = ?");
    $sorgu->execute([$kategori]);
    $rozetler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rozetler as $rozet) {
        // Kullanıcı hedefe ulaştıysa
        if ($mevcut_deger >= $rozet['hedef_deger']) {
            $rozet_id = $rozet['id'];

            // DÜZELTİLEN KISIM: Kullanıcı bu rozeti BUGÜN almış mı kontrol et
            $kontrol = $conn->prepare("SELECT id FROM kullanici_rozetleri WHERE user_id = ? AND rozet_id = ? AND DATE(kazanma_tarihi) = CURDATE()");
            $kontrol->execute([$user_id, $rozet_id]);

            if (!$kontrol->fetch()) {
                // BUGÜN daha önce almamışsa rozeti ver
                $ekle = $conn->prepare("INSERT INTO kullanici_rozetleri (user_id, rozet_id) VALUES (?, ?)");
                $ekle->execute([$user_id, $rozet_id]);
                
                // Animasyonun tetiklenmesi için rozet adını değişkene atıyoruz
                $yeni_rozet = $rozet['rozet_adi'];
            }
        }
    }
    
    // Kazanılan en son rozeti islem_v2.php'ye gönder (Oradan da URL ile panel.php'ye gidip animasyonu çıkaracak)
    return $yeni_rozet;
}
?>