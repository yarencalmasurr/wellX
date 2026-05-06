<?php
// rozet_fonksiyonu.php
function rozetKontrolEt($conn, $user_id, $kategori, $mevcut_deger) {
    // Bu kategoriye ait rozetleri bul
    $sorgu = $conn->prepare("SELECT * FROM rozetler WHERE kategori = ?");
    $sorgu->execute([$kategori]);
    $rozetler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rozetler as $rozet) {
        // Kullanıcı hedefe ulaştıysa
        if ($mevcut_deger >= $rozet['hedef_deger']) {
            $rozet_id = $rozet['id'];

            // Daha önce bu rozeti almış mı kontrol et
            $kontrol = $conn->prepare("SELECT id FROM kullanici_rozetleri WHERE user_id = ? AND rozet_id = ?");
            $kontrol->execute([$user_id, $rozet_id]);

            if (!$kontrol->fetch()) {
                // Daha önce almamışsa rozeti ver
                $ekle = $conn->prepare("INSERT INTO kullanici_rozetleri (user_id, rozet_id) VALUES (?, ?)");
                $ekle->execute([$user_id, $rozet_id]);
            }
        }
    }
}