<?php
/**
 * Proje: saglik_portali
 * Dosya: sorularim.php
 * Açıklama: Premium üyeler için Soru Sorma ve Cevap Takip sayfası (Glassmorphism & Chat UI).
 */

session_start(); 
include 'baglan.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Kullanıcı Verilerini Çek
$sorgu = $conn->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$sorgu->execute([$user_id]);
$user_data = $sorgu->fetch(PDO::FETCH_ASSOC);

$is_premium = $user_data['is_premium'] ?? 0;

$current_page = basename($_SERVER['PHP_SELF']);
function isActive($page, $current) { return ($page == $current) ? 'active' : ''; }
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Uzmana Sorular | WellX</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { 
            --blue: #3b82f6; --orange: #f59e0b; --green: #10b981; 
            --text-main: #1e293b; --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.6);
        }
        
        body { 
            font-family: 'Poppins', sans-serif; margin: 0; display: flex; color: var(--text-main); 
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            background-attachment: fixed; min-height: 100vh;
        }

        #particles-js { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; pointer-events: none; }
        
        /* Modern Sidebar (Glassmorphism) */
        .sidebar { 
            width: 260px; height: 100vh; padding: 30px 20px; position: fixed; z-index: 100;
            background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border); box-shadow: 10px 0 30px rgba(0,0,0,0.03);
            display: flex; flex-direction: column;
        }
        
        .sidebar h2 { font-size: 28px; font-weight: 800; color: #111827; margin-bottom: 10px; letter-spacing: -1px; display: flex; align-items: center; gap: 10px;}
        .sidebar h2 i { color: #ef4444; filter: drop-shadow(0 0 8px rgba(239,68,68,0.4)); }

        .menu-item { display: flex; align-items: center; padding: 14px 18px; color: var(--text-muted); text-decoration: none; border-radius: 16px; margin-bottom: 8px; transition: all 0.3s ease; font-weight: 500; border: 1px solid transparent; }
        .menu-item i { transition: 0.3s; width: 25px; }
        .menu-item:hover { background: rgba(255, 255, 255, 0.9); color: var(--blue); transform: translateX(5px); border-color: rgba(255,255,255,0.8); box-shadow: 0 4px 15px rgba(59,130,246,0.05); }
        .menu-item.active { background: linear-gradient(135deg, #dbeafe, #eff6ff); color: var(--blue); font-weight: 700; box-shadow: 0 8px 20px rgba(59,130,246,0.1); border-color: white; }

        .menu-item:nth-of-type(1) i { color: #3b82f6; }
        .menu-item:nth-of-type(2) i { color: #10b981; }
        .menu-item:nth-of-type(3) i { color: #8b5cf6; }
        .menu-item:nth-of-type(4) i { color: #f59e0b; }
        .menu-item:nth-of-type(5) i { color: #06b6d4; }
        .menu-item:nth-of-type(6) i { color: #ec4899; }
        .menu-item:nth-of-type(7) i { color: #f97316; }
        .menu-item:nth-of-type(8) i { color: #6366f1; }
        
        .sidebar .logout-btn { margin-top: auto !important; background: rgba(254, 226, 226, 0.6); color: #ef4444 !important; font-weight: 600; }
        .sidebar .logout-btn:hover { background: #fee2e2; color: #dc2626 !important; transform: translateX(0); }

        /* Main Content */
        .main { margin-left: 260px; padding: 40px 50px; width: calc(100% - 260px); position: relative; z-index: 10; box-sizing: border-box;}
        
        .page-header { display: flex; align-items: center; gap: 15px; margin-bottom: 40px; }
        .header-icon { background: linear-gradient(135deg, #f59e0b, #d97706); width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; box-shadow: 0 8px 20px rgba(245,158,11,0.3);}
        .page-header h1 { font-size: 32px; font-weight: 800; margin: 0; letter-spacing: -1px; color: #0f172a;}
        .page-header p { margin: 0; color: var(--text-muted); font-size: 15px; font-weight: 500;}

        /* Glass Cards */
        .glass-card {
            background: var(--glass-bg); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border-radius: 24px; border: 1px solid var(--glass-border);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.03); padding: 35px; margin-bottom: 30px;
        }
        
        /* Form Elementleri (Gönderim Kartı) */
        .form-select, .form-control {
            border-radius: 14px; border: 1px solid #e2e8f0; background: rgba(255,255,255,0.9);
            font-size: 15px; padding: 15px; color: var(--text-main); transition: 0.3s;
        }
        .form-select:focus, .form-control:focus { background: white; border-color: var(--orange); box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1); outline: none;}
        
        .btn-send-message {
            background: linear-gradient(135deg, var(--orange) 0%, #d97706 100%); color: white; border: none;
            border-radius: 0 14px 14px 0; padding: 0 30px; font-weight: 700; font-size: 16px; transition: 0.3s; box-shadow: 0 10px 20px rgba(245, 158, 11, 0.2);
        }
        .btn-send-message:hover { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(245, 158, 11, 0.3); color: white;}

        .premium-lock { background: rgba(254, 243, 199, 0.8); border-radius: 20px; border: 1px dashed #fcd34d; padding: 40px; text-align: center; }

        /* ====== MODERN CHAT (SOHBET) TASARIMI ====== */
        .chat-container { display: flex; flex-direction: column; gap: 25px; }
        
        .chat-bubble-wrapper { display: flex; flex-direction: column; gap: 10px; }
        
        /* Benim Sorum (Sağa yaslı, mavi baloncuk) */
        .my-question { align-self: flex-end; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 15px 20px; border-radius: 20px 20px 0 20px; max-width: 80%; box-shadow: 0 10px 20px rgba(59,130,246,0.15);}
        .my-question-info { align-self: flex-end; font-size: 11px; color: var(--text-muted); font-weight: 600; display: flex; align-items: center; gap: 5px;}
        
        /* Uzmanın Cevabı (Sola yaslı, beyaz/cam baloncuk) */
        .expert-answer-wrapper { display: flex; gap: 15px; max-width: 85%; }
        .expert-avatar { width: 45px; height: 45px; border-radius: 14px; background: linear-gradient(135deg, #10b981, #059669); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; box-shadow: 0 5px 15px rgba(16,185,129,0.2);}
        .expert-answer { background: rgba(255,255,255,0.9); padding: 15px 20px; border-radius: 0 20px 20px 20px; border: 1px solid #e2e8f0; color: var(--text-main); font-size: 14px; line-height: 1.6; box-shadow: 0 10px 25px rgba(0,0,0,0.03);}
        .expert-info { font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center;}
        
        /* Bekleyen Soru (Sarı rozet) */
        .waiting-badge { align-self: flex-end; background: rgba(254, 243, 199, 0.8); color: #d97706; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid #fde68a;}
        
        .empty-chat { text-align: center; padding: 40px; color: var(--text-muted); }
        .empty-chat i { font-size: 50px; color: #cbd5e1; margin-bottom: 15px;}
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="sidebar">
    <h2><i class="fas fa-heartbeat"></i> wellX </h2>
    
    <?php if ($is_premium): ?>
        <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 8px 14px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 10px rgba(245,158,11,0.15); color: #d97706; font-size: 12px; font-weight: bold; margin-bottom: 20px;">
            <i class="fas fa-crown"></i> PREMIUM
        </div>
    <?php endif; ?>

    <nav style="flex-grow: 1;">
        <a href="panel.php" class="menu-item <?php echo isActive('panel.php', $current_page); ?>"><i class="fas fa-home"></i> Özet Paneli</a>
        <a href="beslenme.php" class="menu-item <?php echo isActive('beslenme.php', $current_page); ?>"><i class="fas fa-apple-alt"></i> Beslenme</a>
        <a href="egzersiz.php" class="menu-item <?php echo isActive('egzersiz.php', $current_page); ?>"><i class="fas fa-dumbbell"></i> Egzersiz</a>
        <a href="sorularim.php" class="menu-item <?php echo isActive('sorularim.php', $current_page); ?>"><i class="fas fa-envelope-open-text"></i> Uzmana Sorular</a>
        <a href="gelisim.php" class="menu-item <?php echo isActive('gelisim.php', $current_page); ?>"><i class="fas fa-chart-line"></i> Gelişim</a>
        <a href="rozetlerim.php" class="menu-item <?php echo isActive('rozetlerim.php', $current_page); ?>"><i class="fas fa-medal"></i> Rozetlerim</a>
        <a href="turnuva.php" class="menu-item <?php echo isActive('turnuva.php', $current_page); ?>"><i class="fas fa-trophy"></i> Turnuva</a>
        <a href="profil.php" class="menu-item <?php echo isActive('profil.php', $current_page); ?>"><i class="fas fa-user"></i> Profil</a>
    </nav>
    
    <a href="cikis.php" class="menu-item logout-btn"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
</div>

<div class="main">
    <div class="page-header">
        <div class="header-icon"><i class="fas fa-paper-plane"></i></div>
        <div>
            <h1>Uzman İletişim Merkezi</h1>
            <p>Diyetisyeninize veya hocanıza aklınıza takılanları anında sorun.</p>
        </div>
    </div>

    <div class="glass-card" style="border-left: 5px solid var(--orange); padding: 30px;">
        <h3 style="font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 25px;"><i class="fas fa-comment-dots" style="color: var(--orange);"></i> Yeni Soru Gönder</h3>
        
        <?php if ($is_premium == 1): ?>
            <form action="islem_v2.php?is=soru_sor" method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Muhatap Uzman</label>
                        <select name="uzman_id" class="form-select" required>
                            <option value="">Listeden Seçiniz...</option>
                            <?php
                            $es_sorgu = $conn->prepare("SELECT ude.uzman_id, k.ad_soyad, k.rol FROM uzman_danisan_eslesmeleri ude JOIN kullanicilar k ON ude.uzman_id = k.id WHERE ude.danisan_id = ?");
                            $es_sorgu->execute([$user_id]);
                            while($uzman = $es_sorgu->fetch()) {
                                $rolIcon = ($uzman['rol'] == 'diyetisyen') ? "🥗 " : "🏋️ ";
                                echo "<option value='".$uzman['uzman_id']."'>".$rolIcon.$uzman['ad_soyad']." (".ucfirst($uzman['rol']).")</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mesajınız</label>
                        <div class="input-group" style="box-shadow: 0 4px 15px rgba(0,0,0,0.02); border-radius: 14px;">
                            <input type="text" name="soru_metni" class="form-control" placeholder="Örn: Bu akşamki yemeğimi neyle değiştirebilirim?" required style="border-radius: 14px 0 0 14px; border-right:none;">
                            <button type="submit" class="btn btn-send-message"><i class="fas fa-paper-plane me-2"></i> İlet</button>
                        </div>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="premium-lock">
                <div style="font-size: 40px; margin-bottom: 15px; color:#d97706;"><i class="fas fa-lock"></i></div>
                <h4 style="font-size: 18px; font-weight: 800; color: #854d0e;">Uzman Desteği Premium Üyelere Özeldir</h4>
                <p style="color: #92400e; margin-bottom: 20px;">Diyetisyen ve antrenörlerimize soru sormak için planlarımızı inceleyin.</p>
                <a href="premium_planlar.php" class="btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 12px 25px; border-radius: 12px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);">Premium Edinin</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card">
        <h3 style="font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 30px;"><i class="fas fa-history" style="color: var(--blue);"></i> Sohbet Geçmişi</h3>
        
        <div class="chat-container">
            <?php 
            $soru_cek = $conn->prepare("
                SELECT s.*, k.ad_soyad as uzman_adi, k.rol as uzman_rolü 
                FROM uzman_sorulari s 
                JOIN kullanicilar k ON s.uzman_id = k.id 
                WHERE s.danisan_id = ? 
                ORDER BY s.soru_tarihi DESC
            ");
            $soru_cek->execute([$user_id]);
            $sorular = $soru_cek->fetchAll(PDO::FETCH_ASSOC);

            if (count($sorular) == 0): ?>
                <div class="empty-chat">
                    <i class="fas fa-comments"></i>
                    <h4 style="font-weight: 700; color:#475569;">Henüz bir mesajınız yok.</h4>
                    <p>Aklınıza takılan her şeyi uzmanlarınıza sorabilirsiniz.</p>
                </div>
            <?php else: 
                foreach($sorular as $s): 
                    $uzmanIcon = ($s['uzman_rolü'] == 'diyetisyen') ? "fa-user-md" : "fa-dumbbell";
                    $uzmanColor = ($s['uzman_rolü'] == 'diyetisyen') ? "linear-gradient(135deg, #10b981, #059669)" : "linear-gradient(135deg, #3b82f6, #2563eb)";
            ?>
                    <div class="chat-bubble-wrapper">
                        <div class="my-question-info">Ben <i class="fas fa-check-double text-primary"></i> <?php echo date('d.m.Y H:i', strtotime($s['soru_tarihi'])); ?></div>
                        <div class="my-question">
                            <?php echo htmlspecialchars($s['soru_metni']); ?>
                        </div>

                        <?php if ($s['durum'] == 'cevaplandi'): ?>
                            <div class="expert-answer-wrapper mt-2">
                                <div class="expert-avatar" style="background: <?php echo $uzmanColor; ?>;">
                                    <i class="fas <?php echo $uzmanIcon; ?>"></i>
                                </div>
                                <div class="expert-answer">
                                    <div class="expert-info">
                                        <span><?php echo htmlspecialchars($s['uzman_adi']); ?> <span style="font-weight:400; color:var(--text-muted);">yazıyor...</span></span>
                                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                    </div>
                                    <?php echo nl2br(htmlspecialchars($s['cevap_metni'])); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="waiting-badge mt-1"><i class="fas fa-clock"></i> Uzmanınızın yanıtı bekleniyor...</div>
                        <?php endif; ?>
                    </div>
                    <hr style="border-color: rgba(0,0,0,0.05); margin: 10px 0;">
                <?php endforeach; 
            endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": { "number": { "value": 40 }, "color": { "value": "#3b82f6" }, "opacity": { "value": 0.2 }, "size": { "value": 4 }, "line_linked": { "enable": true, "color": "#3b82f6", "opacity": 0.15 }, "move": { "enable": true, "speed": 1.5 } }
    });
</script>
</body>
</html>