<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kata = trim($_POST['kata']);
    $arti = trim($_POST['arti']);
    $sumber = trim($_POST['sumber']) ?: 'Anonim';

    if (!empty($kata) && !empty($arti)) {
        $stmt = $pdo->prepare("INSERT INTO saran_kata (kata_banjar, arti_indonesia, sumber) VALUES (?, ?, ?)");
        $stmt->execute([$kata, $arti, $sumber]);
        echo "<script>alert('✅ Terima kasih! Saran Anda telah terkirim dan akan direview.'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('❌ Harap isi kata dan arti.'); history.back();</script>";
    }
} else {
    header('Location: index.php');
}
?>