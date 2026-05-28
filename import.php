<?php
require_once 'config.php';

$totalStmt = $pdo->query("SELECT COUNT(*) as total FROM kamus_banjar");
$totalKata = $totalStmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data - Kamus Bahasa Banjar</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 { color: #667eea; text-align: center; }
        .stats {
            background: #e7e9ff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }
        .info {
            background: #d1ecf1;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #0c5460;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>📊 Status Database</h1>
    
    <div class="stats">
        <h2><?= number_format($totalKata) ?> Kata</h2>
        <p>Data Kamus Bahasa Banjar tersimpan di database</p>
    </div>

    <div class="info">
        <strong>ℹ️ Informasi:</strong><br>
        Data kamus sudah berhasil diimport (1782 kata).<br><br>
        Untuk menambah data baru, Anda bisa:<br>
        1. Import via phpMyAdmin (format CSV)<br>
        2. Insert manual via phpMyAdmin
    </div>

    <div style="text-align: center;">
        <a href="index.php" class="btn">← Kembali ke Kamus</a>
        <a href="http://localhost/phpmyadmin" class="btn" target="_blank">Buka phpMyAdmin</a>
    </div>
</div>
</body>
</html