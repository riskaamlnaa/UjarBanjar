<?php
session_start();
require_once 'config.php';

// Jika sudah login, redirect ke admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_saran.php');
    exit;
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // ⚠️ GANTI USERNAME & PASSWORD DI SINI ⚠️
    $admin_user = 'admin';
    $admin_pass = '123456';
    
    if ($username === $admin_user && $password === $admin_pass) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        
        header('Location: admin_saran.php');
        exit;
    } else {
        $error = '❌ Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - UjarBanjar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        body.dark {
            --bg: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --card-bg: #1e293b;
            --text: #f1f5f9;
            --text-light: #94a3b8;
            --border: #334155;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text);
        }
        .login-card {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: var(--primary);
            font-size: 1.8em;
            margin-bottom: 8px;
        }
        .login-header p {
            color: var(--text-light);
            font-size: 0.9em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.9em;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 10px;
            background: var(--card-bg);
            color: var(--text);
            font-size: 1em;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        .error {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
            border: 1px solid #fecaca;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9em;
        }
        .back-link:hover { text-decoration: underline; }
        
        @media (max-width: 480px) {
            .login-card { padding: 30px 25px; }
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <h1>🔐 Admin UjarBanjar</h1>
        <p>Silakan login untuk mengakses panel admin</p>
    </div>
    
    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required placeholder="Masukkan username">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Masukkan password">
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
    
    <a href="index.php" class="back-link">← Kembali ke Kamus</a>
</div>

<script>
// Dark mode detection
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
}
</script>
</body>
</html>