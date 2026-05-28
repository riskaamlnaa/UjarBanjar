<?php
require_once 'config.php';

// Get statistics
try {
    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM kamus_banjar");
    $totalKata = $totalStmt->fetch()['total'];
    
    $abjadStmt = $pdo->query("SELECT COUNT(DISTINCT abjad) as huruf FROM kamus_banjar");
    $totalHuruf = $abjadStmt->fetch()['huruf'];
    
    $visitStmt = $pdo->query("SELECT jumlah_kunjungan FROM stats_pengunjung WHERE id = 1");
    $visits = $visitStmt->fetch()['jumlah_kunjungan'];
} catch (PDOException $e) {
    $totalKata = 1782;
    $totalHuruf = 26;
    $visits = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang - Kamus Bahasa Banjar</title>
    <style>
        :root {
            --bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card: #ffffff;
            --text: #333333;
            --text-light: #666666;
            --border: #e0e0e0;
            --primary: #667eea;
            --primary-hover: #5568d3;
            --shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        body.dark {
            --bg: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --card: #1e293b;
            --text: #f1f5f9;
            --text-light: #94a3b8;
            --border: #334155;
            --primary: #818cf8;
            --primary-hover: #6366f1;
            --shadow: 0 10px 40px rgba(0,0,0,0.4);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; transition: background 0.3s, color 0.3s; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: var(--bg);
            min-height: 100vh;
            padding: 20px;
            color: var(--text);
        }
        .container { 
            max-width: 900px; 
            margin: auto; 
            background: var(--card); 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: var(--shadow);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
        }
        h1 { 
            color: var(--primary); 
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        .subtitle {
            color: var(--text-light);
            font-size: 1.1em;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102,126,234,0.3);
        }
        .stat-card h3 {
            font-size: 2em;
            margin-bottom: 5px;
        }
        .stat-card p {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .section {
            margin: 30px 0;
            padding: 25px;
            background: rgba(128,128,128,0.05);
            border-radius: 12px;
            border-left: 4px solid var(--primary);
        }
        h2 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        p, li {
            line-height: 1.8;
            margin-bottom: 10px;
            color: var(--text);
        }
        ul {
            margin-left: 20px;
        }
        li {
            margin: 8px 0;
        }
        .feature-list {
            list-style: none;
            margin-left: 0;
        }
        .feature-list li {
            padding-left: 30px;
            position: relative;
        }
        .feature-list li::before {
            content: "✅";
            position: absolute;
            left: 0;
            top: 0;
        }
        .tech-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .tech-badge {
            background: var(--primary);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .copyright {
            background: rgba(102,126,234,0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            font-size: 0.9em;
            color: var(--text-light);
            border-left: 4px solid var(--primary);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border: 2px solid var(--primary);
            border-radius: 8px;
            transition: all 0.3s;
        }
        .back-link:hover {
            background: var(--primary);
            color: white;
            transform: translateX(-5px);
        }
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--card);
            border: 2px solid var(--border);
            color: var(--text);
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2em;
        }
        @media (max-width: 768px) {
            .container { padding: 25px; }
            h1 { font-size: 1.7em; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<button class="theme-toggle" onclick="toggleTheme()" title="Mode Gelap/Terang">🌓</button>

<div class="container">
    <div class="header">
        <h1>📖 Tentang Kamus Bahasa Banjar</h1>
        <p class="subtitle">Melestarikan Bahasa Daerah untuk Generasi Mendatang</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= number_format($totalKata) ?></h3>
            <p>Total Kata</p>
        </div>
        <div class="stat-card">
            <h3><?= $totalHuruf ?></h3>
            <p>Huruf A-Z</p>
        </div>
        <div class="stat-card">
            <h3><?= number_format($visits) ?></h3>
            <p>Pengunjung</p>
        </div>
    </div>

    <div class="section">
        <h2>📚 Tentang Website</h2>
        <p>
            Website ini merupakan <strong>arsip digital</strong> yang bertujuan melestarikan <strong>Bahasa Banjar</strong> 
            sebagai warisan budaya Nusantara. Data kamus diarsipkan dari sumber 
            <em>Kamus Bahasa Banjar (Copyright 2009)</em> yang sebelumnya tersedia di 
            <code>urangbanua.com</code>.
        </p>
        <div class="copyright">
            <strong>📜 Sumber Data:</strong> Kamus Bahasa Banjar - Copyright 2009 | www.urangbanua.com<br>
            <small>Data diarsipkan untuk tujuan pelestarian dan pendidikan bahasa daerah</small>
        </div>
    </div>

    <div class="section">
        <h2>✨ Fitur Utama</h2>
        <ul class="feature-list">
            <li><strong>Pencarian Cepat & Filter A-Z</strong> - Cari kata dengan mudah dan filter berdasarkan huruf</li>
            <li><strong>Mode Gelap (Dark Mode)</strong> - Nyaman dibaca di malam hari</li>
            <li><strong>Tombol Audio Pengucapan (TTS)</strong> - Dengarkan cara pengucapan kata</li>
            <li><strong>Form Saran Kata Baru</strong> - Bantu kami melengkapi kamus</li>
            <li><strong>Statistik Pengunjung Real-time</strong> - Pantau penggunaan kamus</li>
            <li><strong>Responsive Design</strong> - Akses dari smartphone, tablet, atau desktop</li>
            <li><strong>Copy to Clipboard</strong> - Salin kata dan arti dengan mudah</li>
        </ul>
    </div>

    <div class="section">
        <h2>🛠️ Teknologi</h2>
        <p>Dibangun dengan teknologi modern dan ringan:</p>
        <div class="tech-stack">
            <span class="tech-badge">PHP Native</span>
            <span class="tech-badge">MySQL/MariaDB</span>
            <span class="tech-badge">Vanilla JavaScript</span>
            <span class="tech-badge">CSS3</span>
            <span class="tech-badge">PDO</span>
            <span class="tech-badge">Responsive Design</span>
        </div>
        <p style="margin-top: 15px;">
            Tidak menggunakan framework berat agar <strong>ringan, cepat, dan mudah dipelajari</strong> 
            untuk pemula yang ingin memahami dasar-dasar pemrograman web.
        </p>
    </div>

    <div class="section">
        <h2>🤝 Kontribusi</h2>
        <p>
            Kami sangat menghargai kontribusi Anda untuk melengkapi kamus ini. Jika menemukan:
        </p>
        <ul>
            <li>Kesalahan arti atau penulisan</li>
            <li>Kata yang belum ada dalam kamus</li>
            <li>Saran perbaikan fitur</li>
        </ul>
        <p>
            Silakan gunakan tombol <strong>"💡 Sarankan Kata Baru"</strong> di halaman utama. 
            Tim admin akan mereview setiap saran sebelum data ditambahkan ke database untuk 
            memastikan kualitas dan akurasi kamus.
        </p>
    </div>

    <div class="section">
        <h2>📖 Tentang Bahasa Banjar</h2>
        <p>
            <strong>Bahasa Banjar</strong> adalah bahasa yang dituturkan oleh suku Banjar di 
            Kalimantan Selatan dan sebagian Kalimantan Tengah. Bahasa ini memiliki kekayaan 
            kosakata yang sangat beragam dan unik, mencerminkan budaya dan kearifan lokal 
            masyarakat Banjar.
        </p>
        <p>
            Pelestarian bahasa daerah sangat penting untuk menjaga identitas budaya dan 
            warisan leluhur agar tidak punah di era globalisasi ini.
        </p>
    </div>

    <div class="section">
        <h2>📞 Kontak & Informasi</h2>
        <p>
            Website ini dibuat dengan ❤️ untuk pelestarian bahasa daerah.<br>
            Untuk pertanyaan atau kerjasama, silakan hubungi melalui form saran yang tersedia.
        </p>
    </div>

    <a href="index.php" class="back-link">← Kembali ke Kamus</a>
</div>

<script>
// Dark Mode Toggle
function toggleTheme() {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
}

// Load saved theme
if(localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
}
</script>
</body>
</html>