<?php
require_once 'config.php';

// Ambil semua kata dari kamus
try {
    $stmt = $pdo->query("SELECT kata_banjar, arti_indonesia FROM kamus_banjar ORDER BY RAND() LIMIT 200");
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $words = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Cloud - UjarBanjar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            overflow-x: hidden;
        }

        .container {
            max-width: 1400px;
            margin: auto;
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 3em;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .header p {
            color: #64748b;
            font-size: 1.1em;
        }

        .wordcloud-container {
            position: relative;
            min-height: 600px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 15px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            border-radius: 20px;
            overflow: hidden;
        }

        .word-item {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 700;
            text-decoration: none;
            color: white;
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            animation: float 3s ease-in-out infinite;
        }

        .word-item:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            z-index: 100;
        }

        .word-item::after {
            content: attr(data-arti);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(-10px);
            background: rgba(30, 41, 59, 0.95);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .word-item:hover::after {
            opacity: 1;
            transform: translateX(-50%) translateY(-15px);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        /* Warna-warni untuk word cloud */
        .color-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .color-2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .color-3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .color-4 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .color-5 { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .color-6 { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        .color-7 { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #1e293b !important; }
        .color-8 { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: #1e293b !important; }
        .color-9 { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #1e293b !important; }
        .color-10 { background: linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%); color: #1e293b !important; }

        /* Ukuran berbeda */
        .size-1 { font-size: 14px; padding: 6px 14px; }
        .size-2 { font-size: 16px; padding: 8px 16px; }
        .size-3 { font-size: 18px; padding: 10px 18px; }
        .size-4 { font-size: 20px; padding: 12px 20px; }
        .size-5 { font-size: 24px; padding: 14px 24px; }
        .size-6 { font-size: 28px; padding: 16px 28px; }
        .size-7 { font-size: 32px; padding: 18px 32px; }
        .size-8 { font-size: 36px; padding: 20px 36px; }

        .back-btn {
            display: inline-block;
            margin-top: 30px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }

        @media (max-width: 768px) {
            .header h1 { font-size: 2em; }
            .wordcloud-container { min-height: 400px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌈 Word Cloud Bahasa Banjar</h1>
            <p>Kosakata Bahasa Banjar yang Indah dan Berwarna</p>
        </div>

        <div class="wordcloud-container" id="wordcloud">
            <?php 
            $colors = ['color-1', 'color-2', 'color-3', 'color-4', 'color-5', 
                       'color-6', 'color-7', 'color-8', 'color-9', 'color-10'];
            
            foreach ($words as $index => $word): 
                $size = rand(1, 8);
                $color = $colors[$index % count($colors)];
                $rotation = rand(-10, 10);
                $delay = $index * 0.05;
            ?>
            <a href="index.php?q=<?= urlencode($word['kata_banjar']) ?>" 
               class="word-item <?= $color ?> size-<?= $size ?>"
               data-arti="<?= htmlspecialchars($word['arti_indonesia']) ?>"
               style="animation-delay: <?= $delay ?>s; transform: rotate(<?= $rotation ?>deg);"
               title="<?= htmlspecialchars($word['kata_banjar']) ?> - <?= htmlspecialchars($word['arti_indonesia']) ?>">
                <?= htmlspecialchars($word['kata_banjar']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center;">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Kamus
            </a>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/your-code.js"></script>
    <script>
        // Tambahkan efek interaktif
        document.addEventListener('DOMContentLoaded', function() {
            const words = document.querySelectorAll('.word-item');
            
            words.forEach(word => {
                word.addEventListener('mouseenter', function() {
                    this.style.zIndex = '1000';
                });
                
                word.addEventListener('mouseleave', function() {
                    this.style.zIndex = '1';
                });
            });
        });
    </script>
</body>
</html>