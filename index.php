<?php
require_once 'config.php';

// ========================================
// TRACKING PENCARIAN (Word Cloud Stats)
// ========================================
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $searchTerm = trim($_GET['q']);
    try {
        // Buat tabel search_stats jika belum ada
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS search_stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                kata_kunci VARCHAR(255) NOT NULL,
                jumlah_cari INT DEFAULT 1,
                terakhir_dicari TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_kata (kata_kunci)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Track search
        $stmt = $pdo->prepare("
            INSERT INTO search_stats (kata_kunci, jumlah_cari) 
            VALUES (?, 1)
            ON DUPLICATE KEY UPDATE 
            jumlah_cari = jumlah_cari + 1,
            terakhir_dicari = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$searchTerm]);
    } catch (PDOException $e) {
        error_log("Error tracking search: " . $e->getMessage());
    }
}

// ========================================
// STATISTIK PENGUNJUNG (REAL COUNT)
// ========================================
session_start();

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stats_pengunjung (
            id INT PRIMARY KEY DEFAULT 1,
            jumlah_kunjungan INT DEFAULT 0,
            terakhir_diakses TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $checkStmt = $pdo->query("SELECT COUNT(*) FROM stats_pengunjung");
    if ($checkStmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO stats_pengunjung (id, jumlah_kunjungan) VALUES (1, 0)");
    }
    
    if (!isset($_SESSION['visited'])) {
        $pdo->exec("UPDATE stats_pengunjung SET jumlah_kunjungan = jumlah_kunjungan + 1 WHERE id = 1");
        $_SESSION['visited'] = true;
    }
    
    $statStmt = $pdo->query("SELECT jumlah_kunjungan FROM stats_pengunjung WHERE id = 1");
    $visits = $statStmt->fetch()['jumlah_kunjungan'];
} catch (PDOException $e) {
    $visits = 0;
}

// ========================================
// GET DATA FOR WORD CLOUD
// ========================================
$wordCloudData = [];

try {
    // Cek apakah ada parameter refresh di URL
    $forceRandom = isset($_GET['refresh_wc']) ? true : false;
    
    // 1. Ambil dari statistik pencarian (prioritas utama) - hanya jika tidak ada force refresh
    if (!$forceRandom) {
        $searchStmt = $pdo->query("
            SELECT kata_kunci as kata, jumlah_cari as freq 
            FROM search_stats 
            WHERE jumlah_cari > 0
            ORDER BY jumlah_cari DESC 
            LIMIT 50
        ");
        $searchData = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $searchData = [];
    }
    
    // 2. Jika belum ada data pencarian ATAU dipaksa refresh, ambil kata-kata acak dari kamus
    if (empty($searchData) || $forceRandom) {
        $commonWordsStmt = $pdo->query("
            SELECT kata_banjar as kata, 
                   CASE 
                       WHEN LENGTH(kata_banjar) <= 4 THEN 15
                       WHEN LENGTH(kata_banjar) <= 6 THEN 12
                       ELSE 8
                   END as freq
            FROM kamus_banjar 
            ORDER BY RAND() 
            LIMIT 50
        ");
        $wordCloudData = $commonWordsStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $wordCloudData = $searchData;
    }
} catch (PDOException $e) {
    $wordCloudData = [
        ['kata' => 'banyu', 'freq' => 15],
        ['kata' => 'rumah', 'freq' => 14],
        ['kata' => 'makan', 'freq' => 13],
        ['kata' => 'kucing', 'freq' => 12],
        ['kata' => 'hayam', 'freq' => 11],
        ['kata' => 'kampung', 'freq' => 10],
        ['kata' => 'jalan', 'freq' => 10],
        ['kata' => 'anak', 'freq' => 9],
        ['kata' => 'handak', 'freq' => 8],
        ['kata' => 'banyu', 'freq' => 8],
    ];
}

// Parameter
$keyword = trim($_GET['q'] ?? '');
$abjad = strtoupper($_GET['abjad'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Query
$sql = "SELECT kata_banjar, arti_indonesia, abjad FROM kamus_banjar WHERE 1=1";
$params = [];

if ($keyword !== '') {
    $sql .= " AND (kata_banjar LIKE ? OR arti_indonesia LIKE ?)";
    $params[] = "%$keyword%"; $params[] = "%$keyword%";
} elseif ($abjad !== '') {
    $sql .= " AND abjad = ?";
    $params[] = $abjad;
}

$sql .= " ORDER BY kata_banjar ASC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

$countSql = "SELECT COUNT(*) FROM kamus_banjar WHERE 1=1";
$countParams = [];
if ($keyword !== '') {
    $countSql .= " AND (kata_banjar LIKE ? OR arti_indonesia LIKE ?)";
    $countParams[] = "%$keyword%"; $countParams[] = "%$keyword%";
} elseif ($abjad !== '') {
    $countSql .= " AND abjad = ?";
    $countParams[] = $abjad;
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Stats
try {
    $totalKataStmt = $pdo->query("SELECT COUNT(*) as total FROM kamus_banjar");
    $totalKata = $totalKataStmt->fetch()['total'];
    
    $abjadStmt = $pdo->query("SELECT COUNT(DISTINCT abjad) as huruf FROM kamus_banjar");
    $totalHuruf = $abjadStmt->fetch()['huruf'];
} catch (PDOException $e) {
    $totalKata = 1782;
    $totalHuruf = 26;
}

// Word of the Day
try {
    $randomStmt = $pdo->query("SELECT kata_banjar, arti_indonesia FROM kamus_banjar ORDER BY RAND() LIMIT 1");
    $randomWord = $randomStmt->fetch();
} catch (PDOException $e) {
    $randomWord = null;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UjarBanjar - Kamus Digital Bahasa Banjar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --accent: #06b6d4;
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 12px;
            --radius-lg: 16px;
        }
        
        body.dark {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --card-bg: rgba(30, 41, 59, 0.95);
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border: #334155;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            padding: 20px;
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .container { 
            max-width: 1200px; 
            margin: auto; 
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 40px; 
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid var(--border);
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .logo-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            color: white;
            box-shadow: var(--shadow);
        }
        
        .header h1 { 
            font-size: 2em;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }
        
        .subtitle {
            color: var(--text-secondary);
            font-size: 0.95em;
            font-weight: 400;
            margin-top: 4px;
        }
        
        .theme-toggle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: var(--card-bg);
            color: var(--text-primary);
            cursor: pointer;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .theme-toggle:hover {
            transform: rotate(180deg);
            border-color: var(--primary);
            box-shadow: var(--shadow);
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            padding: 24px;
            border-radius: var(--radius-lg);
            color: white;
            text-align: center;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card:nth-child(1) { 
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }
        .stat-card:nth-child(2) { 
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
        }
        .stat-card:nth-child(3) { 
            background: linear-gradient(135deg, #06b6d4 0%, #22d3ee 100%);
        }
        
        .stat-card h3 {
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }
        
        .stat-card p {
            font-size: 0.9em;
            opacity: 0.95;
            font-weight: 500;
        }
        
        .word-of-day {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 28px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            border: 1px solid #fcd34d;
        }
        
        body.dark .word-of-day {
            background: linear-gradient(135deg, #451a03 0%, #78350f 100%);
            border: 1px solid #92400e;
        }
        
        .word-of-day::before {
            content: '🎲';
            position: absolute;
            right: 28px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 3.5em;
            opacity: 0.3;
        }
        
        .word-of-day h2 {
            font-size: 1.1em;
            margin-bottom: 12px;
            color: #92400e;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        body.dark .word-of-day h2 {
            color: #fcd34d;
        }
        
        .word-of-day .word {
            font-size: 2em;
            font-weight: 700;
            margin: 10px 0;
            color: #78350f;
            letter-spacing: -0.5px;
        }
        
        body.dark .word-of-day .word {
            color: #fde68a;
        }
        
        .word-of-day .meaning {
            font-size: 1.1em;
            color: #92400e;
            font-style: italic;
            opacity: 0.9;
        }
        
        body.dark .word-of-day .meaning {
            color: #fbbf24;
        }
        
        .search-container {
            margin-bottom: 30px;
        }
        
        .search-box { 
            display: flex; 
            gap: 12px;
        }
        
        .search-box input { 
            flex: 1; 
            padding: 16px 20px; 
            font-size: 1em; 
            border: 2px solid var(--border);
            border-radius: var(--radius);
            background: var(--card-bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .search-box input::placeholder {
            color: var(--text-muted);
        }
        
        .btn { 
            padding: 16px 28px; 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white; 
            border: none; 
            border-radius: var(--radius); 
            cursor: pointer; 
            font-weight: 600;
            font-size: 1em;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }
        
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .abjad-nav { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 8px; 
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(241, 245, 249, 0.5);
            border-radius: var(--radius-lg);
            justify-content: center;
            border: 1px solid var(--border);
        }
        
        body.dark .abjad-nav {
            background: rgba(30, 41, 59, 0.5);
        }
        
        .abjad-nav a { 
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--card-bg);
            color: var(--text-secondary);
            text-decoration: none; 
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.9em;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }
        
        .abjad-nav a:hover, 
        .abjad-nav a.active { 
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .results-info {
            background: rgba(99, 102, 241, 0.05);
            padding: 14px 18px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            border-left: 4px solid var(--primary);
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.95em;
        }
        
        .table-container {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
            border: 1px solid var(--border);
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse;
        }
        
        th { 
            background: rgba(99, 102, 241, 0.05);
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85em;
            padding: 16px 20px;
            text-align: left;
            border-bottom: 2px solid var(--border);
        }
        
        td { 
            padding: 16px 20px; 
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
        }
        
        tr {
            transition: all 0.2s ease;
        }
        
        tr:hover { 
            background: rgba(99, 102, 241, 0.03);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .kata {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.05em;
        }
        
        .arti {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        .aksi { 
            display: flex; 
            gap: 8px;
        }
        
        .btn-action {
            width: 38px;
            height: 38px;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 1em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .btn-speak {
            background: linear-gradient(135deg, var(--accent) 0%, #0891b2 100%);
            color: white;
        }
        
        .btn-copy {
            background: var(--card-bg);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            padding: 10px 16px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover,
        .pagination .active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Word Cloud Section - ENHANCED VISUAL STYLE */
        .wordcloud-section {
            margin-top: 50px;
            padding: 40px;
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            border-radius: 20px;
            margin-bottom: 30px;
            animation: fadeIn 0.8s ease;
        }
        
        body.dark .wordcloud-section {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }
        
        .wordcloud-section h2 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 40px;
            font-size: 2em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .wordcloud-container {
            position: relative;
            min-height: 500px;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 40px 20px;
            perspective: 1000px;
        }
        
        .wordcloud-item {
            position: relative;
            display: inline-block;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            color: white !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            animation: float 6s ease-in-out infinite;
            z-index: 1;
            margin: 5px;
        }
        
        .wordcloud-item:hover {
            transform: translateY(-10px) scale(1.15) rotate(0deg) !important;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            z-index: 100;
            filter: brightness(1.1);
        }
        
        .wordcloud-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 50px;
            background: inherit;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: -1;
        }
        
        .wordcloud-item:hover::before {
            opacity: 0.3;
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0) rotate(var(--rotation, 0deg)); 
            }
            33% { 
                transform: translateY(-10px) rotate(calc(var(--rotation, 0deg) + 5deg)); 
            }
            66% { 
                transform: translateY(-5px) rotate(calc(var(--rotation, 0deg) - 5deg)); 
            }
        }
        
        /* Gradient Colors for Word Cloud */
        .wc-color-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .wc-color-2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .wc-color-3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .wc-color-4 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .wc-color-5 { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .wc-color-6 { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        .wc-color-7 { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #1e293b !important; }
        .wc-color-8 { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: #1e293b !important; }
        .wc-color-9 { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #1e293b !important; }
        .wc-color-10 { background: linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%); color: #1e293b !important; }
        
        /* Ukuran berbeda untuk visual yang dinamis */
        .wc-size-1 { font-size: 14px; padding: 6px 14px; }
        .wc-size-2 { font-size: 16px; padding: 8px 16px; }
        .wc-size-3 { font-size: 18px; padding: 10px 18px; }
        .wc-size-4 { font-size: 20px; padding: 12px 20px; }
        .wc-size-5 { font-size: 24px; padding: 14px 24px; }
        .wc-size-6 { font-size: 28px; padding: 16px 28px; }
        .wc-size-7 { font-size: 32px; padding: 18px 32px; }
        .wc-size-8 { font-size: 36px; padding: 20px 36px; }
        
        .wordcloud-info {
            text-align: center;
            color: var(--text-secondary);
            margin-top: 30px;
            font-style: italic;
            font-size: 0.95em;
        }
        
        .wordcloud-info i {
            margin-right: 5px;
            color: var(--accent);
        }
        
        /* Tooltip */
        .wordcloud-tooltip {
            position: absolute;
            background: rgba(30, 41, 59, 0.95);
            color: white;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            pointer-events: none;
            z-index: 1000;
            white-space: nowrap;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            animation: tooltipFade 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        @keyframes tooltipFade {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Ripple Effect */
        @keyframes ripple {
            to {
                width: 200px;
                height: 200px;
                opacity: 0;
                margin-left: -100px;
                margin-top: -100px;
            }
        }
        
        .partner-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid var(--border);
        }
        .partner-title {
            color: var(--text-secondary);
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .partner-card {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(128, 128, 128, 0.08);
            padding: 15px 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            width: 100%;
            max-width: 400px;
            transition: transform 0.3s, background 0.3s;
        }
        .partner-card:hover {
            transform: translateY(-3px);
            background: rgba(128, 128, 128, 0.12);
        }
        .partner-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            background: #fff;
            padding: 2px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .partner-info h4 {
            margin: 0;
            font-size: 0.85em;
            color: var(--text-muted);
            font-weight: 500;
        }
        .partner-info p {
            margin: 2px 0 0 0;
            font-size: 1em;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid var(--border);
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9em;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: var(--radius);
            transition: all 0.3s ease;
        }
        
        .footer a:hover {
            background: rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: var(--card-bg);
            padding: 32px;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 480px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .modal-header h2 {
            color: var(--text-primary);
            font-size: 1.5em;
            font-weight: 700;
        }
        
        .close-modal {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .close-modal:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
            transform: rotate(90deg);
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9em;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--card-bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.95em;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 14px 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            display: none;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            z-index: 2000;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            background: rgba(241, 245, 249, 0.5);
            border-radius: var(--radius-lg);
            margin: 20px 0;
            border: 1px dashed var(--border);
        }
        
        body.dark .empty {
            background: rgba(30, 41, 59, 0.5);
        }
        
        .empty-icon {
            font-size: 3.5em;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 24px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .header h1 {
                font-size: 1.6em;
            }
            
            .stats-bar {
                grid-template-columns: 1fr;
            }
            
            .search-box {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .abjad-nav a {
                width: 38px;
                height: 38px;
                font-size: 0.85em;
            }
            
            table {
                font-size: 0.9em;
            }
            
            th, td {
                padding: 12px 14px;
            }
            
            .aksi {
                flex-direction: column;
            }
            
            .wordcloud-container {
                min-height: 400px;
                gap: 8px 12px;
                padding: 20px 10px;
            }
            
            .wordcloud-item {
                padding: 8px 16px;
                margin: 3px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo-section">
            <div class="logo-icon">🗣️</div>
            <div>
                <h1>UjarBanjar</h1>
                <p class="subtitle">Lengkap dengan Arti dan Penjelasan</p>
            </div>
        </div>
        <button class="theme-toggle" onclick="toggleTheme()" title="Mode Gelap/Terang">
            <i class="fas fa-moon"></i>
        </button>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <h3><?= number_format($totalKata) ?></h3>
            <p><i class="fas fa-book"></i> Total Kata</p>
        </div>
        <div class="stat-card">
            <h3><?= $totalHuruf ?></h3>
            <p><i class="fas fa-font"></i> Huruf A-Z</p>
        </div>
        <div class="stat-card">
            <h3><?= number_format($visits) ?></h3>
            <p><i class="fas fa-users"></i> Pengunjung</p>
        </div>
    </div>

    <?php if ($randomWord): ?>
    <div class="word-of-day">
        <h2><i class="fas fa-dice"></i> Kata Hari Ini</h2>
        <div class="word">"<?= htmlspecialchars($randomWord['kata_banjar']) ?>"</div>
        <div class="meaning"><?= htmlspecialchars($randomWord['arti_indonesia']) ?></div>
    </div>
    <?php endif; ?>

    <div class="search-container">
        <form class="search-box" method="GET" action="">
            <input type="text" name="q" placeholder="Cari kata Banjar atau artinya..." 
                   value="<?= htmlspecialchars($keyword) ?>" autocomplete="off" id="searchInput">
            <button type="submit" class="btn">
                <i class="fas fa-search"></i>
                <span>Cari</span>
            </button>
        </form>
    </div>

    <div class="abjad-nav">
        <a href="?" class="<?= $abjad=='' && $keyword=='' ? 'active' : '' ?>">
            <span>Semua</span>
        </a>
        <?php foreach(range('A','Z') as $h): ?>
            <a href="?abjad=<?= $h ?>" class="<?= $abjad==$h ? 'active' : '' ?>">
                <span><?= $h ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="results-info">
        <i class="fas fa-info-circle"></i>
        <span>
            Menampilkan <strong><?= count($results) ?></strong> dari <strong><?= $totalRows ?></strong> kata
            <?= $keyword ? "untuk pencarian '<b>".htmlspecialchars($keyword)."</b>'" : '' ?>
            <?= $abjad ? "huruf <b>".htmlspecialchars($abjad)."</b>" : '' ?>
        </span>
    </div>

    <?php if(count($results) > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th width="30%"><i class="fas fa-bookmark"></i> Kata Banjar</th>
                    <th><i class="fas fa-info-circle"></i> Arti</th>
                    <th width="120"><i class="fas fa-cogs"></i> Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($results as $r): ?>
                <tr>
                    <td class="kata"><?= htmlspecialchars($r['kata_banjar']) ?></td>
                    <td class="arti"><?= nl2br(htmlspecialchars($r['arti_indonesia'])) ?></td>
                    <td class="aksi">
                        <button class="btn-action btn-speak" onclick="speak('<?= addslashes($r['kata_banjar']) ?>')" title="Dengarkan">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <button class="btn-action btn-copy" onclick="copyText('<?= addslashes($r['kata_banjar']) ?> - <?= addslashes($r['arti_indonesia']) ?>')" title="Salin">
                            <i class="fas fa-copy"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if($totalPages > 1): ?>
    <div class="pagination">
        <?php if($page>1): ?>
            <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>">
                <i class="fas fa-chevron-left"></i> Prev
            </a>
        <?php endif; ?>
        
        <?php for($i=max(1,$page-2); $i<=min($totalPages,$page+2); $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>" 
               class="<?= $i==$page?'active':'' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php if($page<$totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty">
        <div class="empty-icon"></div>
        <h3>Tidak ada data ditemukan</h3>
        <p>Coba kata kunci lain atau pilih huruf di atas</p>
    </div>
    <?php endif; ?>

    <div class="partner-section">
    <h3 class="partner-title">Mitra Kerjasama</h3>
    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
        <!-- Mitra 1: SMP Negeri 21 Banjarmasin -->
        <div class="partner-card">
            <img src="smp21.png" alt="SMP Negeri 21 Banjarmasin" class="partner-logo">
            <div class="partner-info">
                <h4>Mitra</h4>
                <p>SMP Negeri 21 Banjarmasin</p>
            </div>
        </div>
        
        <!-- Mitra 2: SMA PGRI 7 Banjarmasin -->
        <div class="partner-card">
            <img src="pgri 7.png" alt="SMA PGRI 7 Banjarmasin" class="partner-logo">
            <div class="partner-info">
                <h4>Mitra</h4>
                <p>SMA PGRI 7 Banjarmasin</p>
            </div>
        </div>
    </div>
</div>

    <!-- Word Cloud Section - ENHANCED VERSION WITH REFRESH -->
    <div class="wordcloud-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin-bottom: 0;"><i class="fas fa-cloud"></i> Kata Paling Sering Dicari</h2>
            <a href="?refresh_wc=1" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: var(--primary); color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
        </div>
        
        <?php if (!empty($wordCloudData)): ?>
        <div class="wordcloud-container" id="wordcloud">
            <?php 
            $colors = [
                'wc-color-1', 'wc-color-2', 'wc-color-3', 'wc-color-4', 'wc-color-5', 
                'wc-color-6', 'wc-color-7', 'wc-color-8', 'wc-color-9', 'wc-color-10',
                'wc-color-1', 'wc-color-2'
            ];
            $maxFreq = max(array_column($wordCloudData, 'freq'));
            $totalWords = count($wordCloudData);
            
            foreach ($wordCloudData as $index => $word): 
                // Calculate size based on frequency (1-8)
                $sizeRatio = ($word['freq'] / $maxFreq);
                $sizeNum = min(8, max(1, round($sizeRatio * 8)));
                
                // Random rotation between -15 and 15 degrees
                $rotation = rand(-15, 15);
                
                // Random color from gradient palette
                $color = $colors[$index % count($colors)];
                
                // Random delay for staggered animation
                $delay = $index * 0.05;
            ?>
            <a href="?q=<?= htmlspecialchars($word['kata']) ?>" 
               class="wordcloud-item <?= $color ?> wc-size-<?= $sizeNum ?>"
               style="--rotation: <?= $rotation ?>deg; animation-delay: <?= $delay ?>s;"
               data-kata="<?= htmlspecialchars($word['kata']) ?>"
               title="<?= htmlspecialchars($word['kata']) ?>">
                <?= htmlspecialchars($word['kata']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <p class="wordcloud-info">
            <i class="fas fa-info-circle"></i> Klik kata untuk mencari • Klik tombol Refresh untuk mengacak kata
        </p>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
            <i class="fas fa-cloud" style="font-size: 3em; margin-bottom: 15px; opacity: 0.5;"></i>
            <p>Belum ada data pencarian. Jadilah yang pertama mencari kata!</p>
            <p style="font-size: 0.9em; margin-top: 10px;">Gunakan kolom pencarian di atas untuk mulai menjelajahi kamus.</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>&copy; <?= date('Y') ?> <strong>UjarBanjar</strong> | Dibuat dengan <i class="fas fa-heart" style="color: #ef4444;"></i> untuk pelestarian bahasa daerah</p>
        <div class="footer-links">
            <a href="about.php"><i class="fas fa-info-circle"></i> Tentang</a>
            <a href="kuis.php"><i class="fas fa-brain"></i> Kuis</a>
            <button class="btn" onclick="openModal()" style="padding: 8px 16px; font-size: 0.9em;">
                <i class="fas fa-lightbulb"></i> Sarankan Kata
            </button>
            <a href="admin_saran.php" class="admin-link" title="Khusus Admin" style="background: #fbbf24; color: #78350f; padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #f59e0b;">
                <i class="fas fa-cog"></i> Admin
            </a>
        </div>
    </div>
</div>

<div id="saranModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-lightbulb"></i> Sarankan Kata Baru</h2>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <p style="margin-bottom:20px; color: var(--text-secondary);">
            Bantu kami melengkapi kamus! Data akan direview sebelum ditampilkan.
        </p>
        <form action="proses_saran.php" method="POST">
            <div class="form-group">
                <label><i class="fas fa-font"></i> Kata Banjar</label>
                <input type="text" name="kata" required placeholder="Contoh: handak">
            </div>
            <div class="form-group">
                <label><i class="fas fa-book"></i> Arti / Penjelasan</label>
                <textarea name="arti" rows="3" required placeholder="Contoh: mau / ingin"></textarea>
            </div>
            <div class="form-group">
                <label><i class="fas fa-user"></i> Nama / Sumber (Opsional)</label>
                <input type="text" name="sumber" placeholder="Nama Anda">
            </div>
            <button type="submit" class="btn" style="width: 100%;">
                <i class="fas fa-paper-plane"></i> Kirim Saran
            </button>
        </form>
    </div>
</div>

<div class="toast" id="toast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Berhasil disalin!</span>
</div>

<script>
    // Dark Mode
    function toggleTheme() {
        document.body.classList.toggle('dark');
        const icon = document.querySelector('.theme-toggle i');
        if (document.body.classList.contains('dark')) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
            localStorage.setItem('theme', 'dark');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
            localStorage.setItem('theme', 'light');
        }
    }
    
    if(localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        const icon = document.querySelector('.theme-toggle i');
        if (icon) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    }

    // Audio TTS
    function speak(text) {
        if (!('speechSynthesis' in window)) {
            console.warn('Browser tidak mendukung Web Speech API');
            return;
        }
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID';
        utterance.rate = 0.9;
        utterance.pitch = 1;
        const voices = window.speechSynthesis.getVoices();
        const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
        if (idVoice) utterance.voice = idVoice;
        window.speechSynthesis.speak(utterance);
    }
    if ('speechSynthesis' in window) window.speechSynthesis.getVoices();

    // Copy function
    function copyText(t) {
        navigator.clipboard.writeText(t).then(() => {
            showToast('✅ Berhasil disalin!');
        }).catch(() => {
            const textarea = document.createElement('textarea');
            textarea.value = t;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast('✅ Berhasil disalin!');
        });
    }

    // Toast notification
    function showToast(message) {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        toastMessage.textContent = message;
        toast.style.display = 'flex';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    // Modal functions
    function openModal() { document.getElementById('saranModal').style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    function closeModal() { document.getElementById('saranModal').style.display = 'none'; document.body.style.overflow = 'auto'; }
    window.onclick = function(e) { const modal = document.getElementById('saranModal'); if(e.target == modal) closeModal(); }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); document.getElementById('searchInput').focus(); }
        if (e.key === 'Escape') closeModal();
    });

    // Auto-focus search
    window.addEventListener('load', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput && !searchInput.value) setTimeout(() => { searchInput.focus(); }, 500);
    });

    // Enhanced Word Cloud Interactions
    document.addEventListener('DOMContentLoaded', function() {
        const wordItems = document.querySelectorAll('.wordcloud-item');
        
        wordItems.forEach((item, index) => {
            // Set staggered animation delay
            item.style.animationDelay = `${index * 0.05}s`;
            
            item.addEventListener('mouseenter', function(e) {
                const kata = this.dataset.kata;
                
                // Create tooltip
                const tooltip = document.createElement('div');
                tooltip.className = 'wordcloud-tooltip';
                tooltip.textContent = `Cari: ${kata}`;
                tooltip.style.left = (e.pageX + 15) + 'px';
                tooltip.style.top = (e.pageY - 10) + 'px';
                document.body.appendChild(tooltip);
                
                this.tooltip = tooltip;
                
                // Pause float animation on hover
                this.style.animationPlayState = 'paused';
            });
            
            item.addEventListener('mousemove', function(e) {
                if (this.tooltip) {
                    this.tooltip.style.left = (e.pageX + 15) + 'px';
                    this.tooltip.style.top = (e.pageY - 10) + 'px';
                }
            });
            
            item.addEventListener('mouseleave', function() {
                if (this.tooltip) {
                    this.tooltip.remove();
                    this.tooltip = null;
                }
                // Resume float animation
                this.style.animationPlayState = 'running';
            });
            
            // Click ripple effect
            item.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    width: 20px;
                    height: 20px;
                    background: rgba(255,255,255,0.6);
                    border-radius: 50%;
                    transform: translate(-50%, -50%);
                    left: ${e.offsetX}px;
                    top: ${e.offsetY}px;
                    animation: ripple 0.6s ease-out;
                    pointer-events: none;
                    z-index: 10;
                `;
                
                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
        });
    });
</script>
</body>
</html>