<?php
session_start();
require_once 'config.php';

// 🔐 CEK LOGIN ADMIN
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Auto logout setelah 2 jam tidak aktif
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['login_time'] = time();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM saran_kata WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin_saran.php?msg=deleted');
    exit;
}

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("SELECT kata_banjar, arti_indonesia FROM saran_kata WHERE id = ?");
        $stmt->execute([$id]);
        $saran = $stmt->fetch();
        
        if ($saran) {
            // Check if word already exists
            $check = $pdo->prepare("SELECT id FROM kamus_banjar WHERE kata_banjar = ?");
            $check->execute([$saran['kata_banjar']]);
            
            if (!$check->fetch()) {
                $insert = $pdo->prepare("INSERT INTO kamus_banjar (kata_banjar, arti_indonesia, abjad) VALUES (?, ?, ?)");
                $insert->execute([
                    $saran['kata_banjar'],
                    $saran['arti_indonesia'],
                    strtoupper(substr($saran['kata_banjar'], 0, 1))
                ]);
            }
        }
        $update = $pdo->prepare("UPDATE saran_kata SET status = 'approved' WHERE id = ?");
        $update->execute([$id]);
        header('Location: admin_saran.php?msg=approved');
        exit;
        
    } elseif ($action === 'reject') {
        $update = $pdo->prepare("UPDATE saran_kata SET status = 'rejected' WHERE id = ?");
        $update->execute([$id]);
        header('Location: admin_saran.php?msg=rejected');
        exit;
    }
}

// Get stats
$totalStmt = $pdo->query("SELECT COUNT(*) as total FROM saran_kata");
$totalSaran = $totalStmt->fetch()['total'];

$pendingStmt = $pdo->query("SELECT COUNT(*) as pending FROM saran_kata WHERE status = 'pending'");
$pendingCount = $pendingStmt->fetch()['pending'];

$approvedStmt = $pdo->query("SELECT COUNT(*) as approved FROM saran_kata WHERE status = 'approved'");
$approvedCount = $approvedStmt->fetch()['approved'];

$rejectedStmt = $pdo->query("SELECT COUNT(*) as rejected FROM saran_kata WHERE status = 'rejected'");
$rejectedCount = $rejectedStmt->fetch()['rejected'];

// Get all suggestions
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($filter !== 'all') {
    $where[] = "status = ?";
    $params[] = $filter;
}

if ($search !== '') {
    $where[] = "(kata_banjar LIKE ? OR arti_indonesia LIKE ? OR sumber LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$saranStmt = $pdo->prepare("SELECT * FROM saran_kata $whereClause ORDER BY created_at DESC");
$saranStmt->execute($params);
$saranList = $saranStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - UjarBanjar</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --bg: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            padding: 30px 20px;
            color: var(--text);
        }
        
        .container {
            max-width: 1400px;
            margin: auto;
            background: var(--card-bg);
            padding: 40px;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255,255,255,0.8);
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 2px solid var(--border);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .header-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            color: white;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        
        .header h1 {
            font-size: 2em;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 12px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .btn-logout {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9em;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            padding: 28px;
            border-radius: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: var(--shadow);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card.total { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .stat-card.pending { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .stat-card.approved { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-card.rejected { background: linear-gradient(135deg, #ef4444, #f87171); }
        
        .stat-card h3 {
            font-size: 2.5em;
            font-weight: 800;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        
        .stat-card p {
            font-size: 0.95em;
            opacity: 0.95;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        
        /* Search & Filters */
        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 14px 20px 14px 48px;
            border: 2px solid var(--border);
            border-radius: 14px;
            background: var(--card-bg);
            font-size: 0.95em;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Table */
        .table-wrapper {
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
            border-bottom: 2px solid var(--border);
        }
        
        th {
            padding: 18px 24px;
            text-align: left;
            font-weight: 700;
            color: var(--text);
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }
        
        tbody tr {
            transition: all 0.2s;
        }
        
        tbody tr:hover {
            background: rgba(99, 102, 241, 0.03);
            transform: scale(1.005);
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        .word-cell {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.05em;
        }
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 8px 14px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-approve {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-reject {
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        
        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 1.3em;
            margin-bottom: 8px;
            color: var(--text);
        }
        
        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 25px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            background: rgba(99, 102, 241, 0.1);
            transform: translateX(-5px);
        }
        
        /* Delete Confirmation Modal */
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
            z-index: 2000;
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 24px;
            max-width: 450px;
            width: 90%;
            text-align: center;
            animation: modalSlide 0.3s ease;
        }
        
        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .modal-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--danger), #dc2626);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5em;
            color: white;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
        }
        
        .modal h2 {
            color: var(--text);
            margin-bottom: 12px;
            font-size: 1.5em;
        }
        
        .modal p {
            color: var(--text-light);
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        
        .btn-cancel {
            padding: 12px 24px;
            background: var(--card-bg);
            color: var(--text);
            border: 2px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background: var(--border);
        }
        
        .btn-confirm {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            transition: all 0.3s;
        }
        
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }
        
        @media (max-width: 768px) {
            .container { padding: 25px; }
            .header { flex-direction: column; text-align: center; gap: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            .controls { flex-direction: column; }
            .search-box { min-width: 100%; }
            table { font-size: 0.9em; }
            th, td { padding: 12px 16px; }
            .actions { flex-direction: column; }
            .btn-action { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="header-icon">📋</div>
            <div>
                <h1>Admin Panel</h1>
                <p style="color: var(--text-light); font-size: 0.95em;">UjarBanjar - Kelola Saran Kata</p>
            </div>
        </div>
        <div class="admin-info">
            <div class="admin-user">
                <i class="fas fa-user-circle"></i>
                <span><?= htmlspecialchars($_SESSION['admin_username']) ?></span>
            </div>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card total">
            <h3><?= $totalSaran ?></h3>
            <p><i class="fas fa-inbox"></i> Total Saran</p>
        </div>
        <div class="stat-card pending">
            <h3><?= $pendingCount ?></h3>
            <p><i class="fas fa-clock"></i> Menunggu Review</p>
        </div>
        <div class="stat-card approved">
            <h3><?= $approvedCount ?></h3>
            <p><i class="fas fa-check-circle"></i> Disetujui</p>
        </div>
        <div class="stat-card rejected">
            <h3><?= $rejectedCount ?></h3>
            <p><i class="fas fa-times-circle"></i> Ditolak</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <?php
        $messages = [
            'approved' => ['success', '✅ Kata berhasil disetujui dan ditambahkan ke kamus!'],
            'rejected' => ['danger', '❌ Saran kata berhasil ditolak.'],
            'deleted' => ['danger', '🗑️ Data saran berhasil dihapus permanen.'],
            'success' => ['success', '✅ Operasi berhasil dilakukan.']
        ];
        $msgType = $_GET['msg'];
        if (isset($messages[$msgType])):
        ?>
        <div class="alert alert-<?= $messages[$msgType][0] ?>">
            <i class="fas fa-<?= $messages[$msgType][0] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $messages[$msgType][1] ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Search & Filters -->
    <div class="controls">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cari kata, arti, atau sumber..." 
                   value="<?= htmlspecialchars($search) ?>" 
                   onchange="window.location.href='?filter=<?= $filter ?>&search='+this.value">
        </div>
        <div class="filter-buttons">
            <a href="?filter=all&search=<?= urlencode($search) ?>" 
               class="filter-btn <?= $filter==='all'?'active':'' ?>">
                <i class="fas fa-layer-group"></i> Semua
            </a>
            <a href="?filter=pending&search=<?= urlencode($search) ?>" 
               class="filter-btn <?= $filter==='pending'?'active':'' ?>">
                <i class="fas fa-clock"></i> Pending
            </a>
            <a href="?filter=approved&search=<?= urlencode($search) ?>" 
               class="filter-btn <?= $filter==='approved'?'active':'' ?>">
                <i class="fas fa-check"></i> Approved
            </a>
            <a href="?filter=rejected&search=<?= urlencode($search) ?>" 
               class="filter-btn <?= $filter==='rejected'?'active':'' ?>">
                <i class="fas fa-times"></i> Rejected
            </a>
        </div>
    </div>

    <!-- Table -->
    <?php if (count($saranList) > 0): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th width="20%">Kata Banjar</th>
                    <th width="25%">Arti</th>
                    <th width="15%">Sumber</th>
                    <th width="15%">Status</th>
                    <th width="15%">Tanggal</th>
                    <th width="10%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($saranList as $s): ?>
                <tr>
                    <td class="word-cell"><?= htmlspecialchars($s['kata_banjar']) ?></td>
                    <td><?= htmlspecialchars($s['arti_indonesia']) ?></td>
                    <td style="color: var(--text-light);"><?= htmlspecialchars($s['sumber']) ?></td>
                    <td>
                        <span class="status-badge <?= $s['status'] ?>">
                            <i class="fas fa-<?= $s['status']==='pending'?'clock':($s['status']==='approved'?'check':'times') ?>"></i>
                            <?= ucfirst($s['status']) ?>
                        </span>
                    </td>
                    <td style="font-size: 0.9em; color: var(--text-light);">
                        <?= date('d M Y H:i', strtotime($s['created_at'])) ?>
                    </td>
                    <td>
                        <div class="actions">
                            <?php if ($s['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn-action btn-approve" title="Setujui">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn-action btn-reject" title="Tolak">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <button onclick="confirmDelete(<?= $s['id'] ?>)" 
                                    class="btn-action btn-delete" title="Hapus Permanen">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Tidak Ada Data</h3>
        <p>Tidak ada saran kata yang ditemukan dengan filter saat ini.</p>
    </div>
    <?php endif; ?>

    <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Kembali ke Kamus
    </a>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-icon">
            <i class="fas fa-trash"></i>
        </div>
        <h2>Hapus Data Saran?</h2>
        <p>Apakah Anda yakin ingin menghapus saran ini? Tindakan ini tidak dapat dibatalkan dan data akan hilang permanen.</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteId">
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn-confirm">
                    <i class="fas fa-trash"></i> Ya, Hapus
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.remove();
        }, 500);
    });
}, 5000);
</script>
</body>
</html>