<?php
session_start();
if($_SESSION['rol'] != 'danışan') { header("Location: index.php"); exit(); }
?>
<!DOCTYPE html>
<html>
<head><title>Günlük Takip</title></head>
<body>
    <h2>Günlük Takip Formu</h2>
    <form action="islem_v2.php?is=gunluk_kaydet" method="POST">
        <input type="number" name="alinan" placeholder="Alınan Kalori"><br>
        <input type="number" name="yakilan" placeholder="Yakılan Kalori"><br>
        <input type="number" step="0.1" name="su" placeholder="Su"><br>
        <input type="number" step="0.1" name="uyku" placeholder="Uyku"><br>
        <input type="number" step="0.1" name="kilo" placeholder="Kilo"><br>
        <input type="number" name="spor" placeholder="Spor Süresi"><br>
        <button type="submit">Kaydet</button>
    </form>
</body>
</html>