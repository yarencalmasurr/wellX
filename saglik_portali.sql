-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 06 May 2026, 13:09:20
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `saglik_portali`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `aktivite_kayitlari`
--

CREATE TABLE `aktivite_kayitlari` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `alinan_kalori` int(11) DEFAULT NULL,
  `yakilan_kalori` int(11) DEFAULT NULL,
  `su_miktari` decimal(4,2) DEFAULT NULL,
  `uyku_suresi` decimal(4,2) DEFAULT NULL,
  `guncel_kilo` decimal(5,2) DEFAULT NULL,
  `spor_suresi` int(11) DEFAULT NULL,
  `kayit_tarihi` date DEFAULT NULL,
  `yemek_detay` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `aktivite_kayitlari`
--

INSERT INTO `aktivite_kayitlari` (`id`, `user_id`, `alinan_kalori`, `yakilan_kalori`, `su_miktari`, `uyku_suresi`, `guncel_kilo`, `spor_suresi`, `kayit_tarihi`, `yemek_detay`) VALUES
(47, 5, 3, 4, 3.00, 2.00, 6.00, 10, '2026-04-29', NULL),
(49, 1, 500, 50, 0.50, 7.00, 60.00, 10, '2026-05-06', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `beslenme_planlari`
--

CREATE TABLE `beslenme_planlari` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `diyetisyen_id` int(11) DEFAULT NULL,
  `plan_metni` text DEFAULT NULL,
  `durum_notu` varchar(255) DEFAULT NULL,
  `kayit_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `okundu` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `beslenme_planlari`
--

INSERT INTO `beslenme_planlari` (`id`, `user_id`, `diyetisyen_id`, `plan_metni`, `durum_notu`, `kayit_tarihi`, `okundu`) VALUES
(1, 1, 2, 'az ye ', NULL, '2026-04-29 04:21:27', 1),
(2, 1, 2, 'pırasa ye çok yemişsin yeşillik artır', NULL, '2026-04-29 04:26:18', 1),
(3, 5, 2, 'yemek ye cılız', NULL, '2026-04-29 04:35:28', 1),
(4, 1, 2, 'az ye', NULL, '2026-04-29 05:44:11', 1),
(5, 1, 2, '', NULL, '2026-04-29 05:44:14', 1),
(6, 5, 2, 'pırt', NULL, '2026-04-29 07:17:48', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `egzersiz_planlari`
--

CREATE TABLE `egzersiz_planlari` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `hoca_id` int(11) DEFAULT NULL,
  `antrenman_notu` text DEFAULT NULL,
  `kayit_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `okundu` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `egzersiz_planlari`
--

INSERT INTO `egzersiz_planlari` (`id`, `user_id`, `hoca_id`, `antrenman_notu`, `kayit_tarihi`, `okundu`) VALUES
(1, 1, 3, 'az yap', '2026-04-29 04:44:18', 1),
(2, 1, 3, 'az yap', '2026-04-29 04:44:21', 1),
(3, 5, 4, 'zort', '2026-04-29 07:18:32', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `gunluk_veriler`
--

CREATE TABLE `gunluk_veriler` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `kilo` float DEFAULT NULL,
  `uyku` float DEFAULT NULL,
  `spor` int(11) DEFAULT NULL,
  `su` float DEFAULT NULL,
  `tarih` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `gunun_antrenmani`
--

CREATE TABLE `gunun_antrenmani` (
  `id` int(11) NOT NULL,
  `hoca_id` int(11) DEFAULT NULL,
  `duyuru_baslik` varchar(255) DEFAULT NULL,
  `duyuru_icerik` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `gunun_antrenmani`
--

INSERT INTO `gunun_antrenmani` (`id`, `hoca_id`, `duyuru_baslik`, `duyuru_icerik`) VALUES
(1, 4, 'a', 'a'),
(2, 4, 'a', 'a'),
(3, 4, 'sanasch', 'sasacas');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `gunun_tarifi`
--

CREATE TABLE `gunun_tarifi` (
  `id` int(11) NOT NULL,
  `diyetisyen_id` int(11) NOT NULL,
  `tarif_baslik` varchar(255) NOT NULL,
  `tarif_icerik` text NOT NULL,
  `ekleme_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `gunun_tarifi`
--

INSERT INTO `gunun_tarifi` (`id`, `diyetisyen_id`, `tarif_baslik`, `tarif_icerik`, `ekleme_tarihi`) VALUES
(1, 2, 'edeemnfdhskndd', 'dmjnjdsnjsd', '2026-05-06 10:47:44'),
(2, 2, 'yaren', 'çalmaşur', '2026-05-06 10:51:42');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL,
  `ad_soyad` varchar(100) DEFAULT NULL,
  `kullanici_adi` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `sifre` varchar(255) NOT NULL,
  `rol` enum('danışan','hoca','diyetisyen','admin') DEFAULT 'danışan',
  `diyetisyen_id` int(11) DEFAULT NULL,
  `hoca_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`id`, `ad_soyad`, `kullanici_adi`, `email`, `sifre`, `rol`, `diyetisyen_id`, `hoca_id`) VALUES
(1, 'asli', 'asli', 'ascel1903@gmail.com', '123456', 'danışan', NULL, NULL),
(2, 'aslihani', 'd', 'ascel1903@gmail.com', 'd', 'diyetisyen', NULL, NULL),
(3, 'aslihaniş', 'aslihaniş43', 'ascel1903@gmail.com', '571437', 'hoca', NULL, NULL),
(4, 'd', 's', 'ascel1903@gmail.com', 's', 'hoca', NULL, NULL),
(5, 'a', 'a', 'ascel1903@gmail.com', 'a', 'danışan', NULL, NULL),
(7, 'aa', 'aa29', 'ascel1903@gmail.com', '598993', 'hoca', NULL, NULL),
(8, 'a', 'a52', 'ascel1903@gmail.com', '759653', 'diyetisyen', NULL, NULL),
(9, 'Admin', 'admin', 'admin@admin.com', '12345', 'admin', NULL, NULL),
(10, 'd', 'd10', 'deneme@gmail.com', '840585', 'diyetisyen', NULL, NULL),
(11, 's', 's67', 'sidarxx9@hotmail.com', '246582', 'hoca', NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanici_rozetleri`
--

CREATE TABLE `kullanici_rozetleri` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rozet_id` int(11) NOT NULL,
  `kazanma_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kullanici_rozetleri`
--

INSERT INTO `kullanici_rozetleri` (`id`, `user_id`, `rozet_id`, `kazanma_tarihi`) VALUES
(1, 5, 2, '2026-04-29 08:11:42'),
(2, 5, 1, '2026-04-29 08:20:50'),
(3, 5, 3, '2026-04-29 08:20:50');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tarif_puanlari`
--

CREATE TABLE `tarif_puanlari` (
  `id` int(11) NOT NULL,
  `tarif_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `puan` int(1) NOT NULL,
  `tarih` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `tarif_puanlari`
--

INSERT INTO `tarif_puanlari` (`id`, `tarif_id`, `user_id`, `puan`, `tarih`) VALUES
(1, 2, 1, 5, '2026-05-06 10:58:23');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `uzman_basvurulari`
--

CREATE TABLE `uzman_basvurulari` (
  `id` int(11) NOT NULL,
  `ad_soyad` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `uzmanlik` varchar(100) NOT NULL,
  `tecrube` text DEFAULT NULL,
  `belge` varchar(255) DEFAULT NULL,
  `rol` enum('diyetisyen','hoca') NOT NULL,
  `durum` enum('beklemede','onaylandi','reddedildi') DEFAULT 'beklemede',
  `tarih` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Tablo döküm verisi `uzman_basvurulari`
--

INSERT INTO `uzman_basvurulari` (`id`, `ad_soyad`, `email`, `uzmanlik`, `tecrube`, `belge`, `rol`, `durum`, `tarih`) VALUES
(1, 'aslihaniş', 'ascel1903@gmail.com', 'ahsasjak', 'ajkshkjahsk', '1777384816_WhatsApp Image 2026-04-27 at 18.30.04.jpeg', 'hoca', 'onaylandi', '2026-04-28 11:00:16'),
(2, 'd', 'ascel1903@gmail.com', 'd', 'd', '', 'hoca', 'onaylandi', '2026-04-28 11:14:34'),
(3, 'a', 'ascel1903@gmail.com', 's', 's', '', 'diyetisyen', 'reddedildi', '2026-04-28 11:17:45'),
(4, 'a', 'ascel1903@gmail.com', 'a', 'a', '', 'diyetisyen', 'onaylandi', '2026-04-28 11:27:19'),
(5, 'aa', 'ascel1903@gmail.com', 'a', 'a', '', 'hoca', 'onaylandi', '2026-04-28 11:27:29'),
(6, 'serkan', 'serkan@gmail.com', 'ahsakjs', 'ajkdhakjdh', 'WhatsApp Image 2026-04-27 at 18.30.04.jpeg', 'hoca', 'reddedildi', '2026-04-29 04:36:48'),
(7, 'd', 'deneme@gmail.com', 'd', 'd', '', 'diyetisyen', 'onaylandi', '2026-04-29 09:24:53'),
(8, 's', 'sidarxx9@hotmail.com', 's', 's', '', 'hoca', 'onaylandi', '2026-04-29 09:25:14');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `aktivite_kayitlari`
--
ALTER TABLE `aktivite_kayitlari`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `beslenme_planlari`
--
ALTER TABLE `beslenme_planlari`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `egzersiz_planlari`
--
ALTER TABLE `egzersiz_planlari`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `gunluk_veriler`
--
ALTER TABLE `gunluk_veriler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `gunun_antrenmani`
--
ALTER TABLE `gunun_antrenmani`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `gunun_tarifi`
--
ALTER TABLE `gunun_tarifi`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `kullanici_rozetleri`
--
ALTER TABLE `kullanici_rozetleri`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `tarif_puanlari`
--
ALTER TABLE `tarif_puanlari`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `uzman_basvurulari`
--
ALTER TABLE `uzman_basvurulari`
  ADD PRIMARY KEY (`id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `aktivite_kayitlari`
--
ALTER TABLE `aktivite_kayitlari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Tablo için AUTO_INCREMENT değeri `beslenme_planlari`
--
ALTER TABLE `beslenme_planlari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `egzersiz_planlari`
--
ALTER TABLE `egzersiz_planlari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `gunluk_veriler`
--
ALTER TABLE `gunluk_veriler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `gunun_antrenmani`
--
ALTER TABLE `gunun_antrenmani`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `gunun_tarifi`
--
ALTER TABLE `gunun_tarifi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Tablo için AUTO_INCREMENT değeri `kullanici_rozetleri`
--
ALTER TABLE `kullanici_rozetleri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `tarif_puanlari`
--
ALTER TABLE `tarif_puanlari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `uzman_basvurulari`
--
ALTER TABLE `uzman_basvurulari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `aktivite_kayitlari`
--
ALTER TABLE `aktivite_kayitlari`
  ADD CONSTRAINT `aktivite_kayitlari_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `kullanicilar` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;