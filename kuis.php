<?php
require_once 'config.php';

// Ambil semua kata untuk kuis
try {
    $allWordsStmt = $pdo->query("SELECT id, kata_banjar, arti_indonesia, abjad FROM kamus_banjar ORDER BY RAND()");
    $allWords = $allWordsStmt->fetchAll();
} catch (PDOException $e) {
    $allWords = [];
}

// Pengaturan kuis
$mode = $_GET['mode'] ?? 'pilihan_ganda'; // pilihan_ganda, ketik, huruf
$huruf_filter = $_GET['huruf'] ?? '';
$difficulty = $_GET['difficulty'] ?? 'mudah'; // mudah, sedang, sulit

// Filter berdasarkan huruf jika ada
if ($huruf_filter) {
    $filteredWords = array_filter($allWords, function($w) use ($huruf_filter) {
        return strtoupper($w['abjad']) === strtoupper($huruf_filter);
    });
    $filteredWords = array_values($filteredWords);
} else {
    $filteredWords = $allWords;
}

// Batasi jumlah soal
$maxSoal = $_GET['jumlah'] ?? 10;
$quizWords = array_slice($filteredWords, 0, min($maxSoal, count($filteredWords)));
shuffle($quizWords);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuis Bahasa Banjar - Tes Pengetahuanmu!</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #f5576c 0%, #ff6b6b 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --bg: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #7e22ce 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text: #1f2937;
            --text-light: #6b7280;
            --border: rgba(255, 255, 255, 0.2);
            --shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        body.dark {
            --bg: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            --card-bg: rgba(30, 41, 59, 0.95);
            --text: #f1f5f9;
            --text-light: #94a3b8;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            background-attachment: fixed;
            min-height: 100vh;
            padding: 20px;
            color: var(--text);
        }
        
        .container {
            max-width: 900px;
            margin: auto;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            padding: 20px;
            border-radius: 15px;
            color: white;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .stat-card:nth-child(1) { background: var(--primary-gradient); }
        .stat-card:nth-child(2) { background: var(--success-gradient); }
        .stat-card:nth-child(3) { background: var(--danger-gradient); }
        .stat-card:nth-child(4) { background: var(--warning-gradient); }
        
        .stat-card h3 { font-size: 2em; margin-bottom: 5px; }
        .stat-card p { font-size: 0.9em; opacity: 0.9; }
        
        /* Setup Quiz */
        .quiz-setup {
            background: rgba(102, 126, 234, 0.1);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        
        .quiz-setup h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text);
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
        }
        
        .btn {
            padding: 14px 32px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1em;
            font-family: 'Poppins', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }
        
        .btn-success { background: var(--success-gradient); }
        .btn-danger { background: var(--danger-gradient); }
        .btn-warning { background: var(--warning-gradient); }
        
        /* Quiz Area */
        .quiz-area {
            display: none;
        }
        
        .quiz-progress {
            background: rgba(128, 128, 128, 0.1);
            height: 10px;
            border-radius: 5px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .quiz-progress-bar {
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 5px;
            transition: width 0.5s ease;
        }
        
        .question-card {
            background: rgba(102, 126, 234, 0.05);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }
        
        .question-number {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.95em;
        }
        
        .question-text {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text);
        }
        
        .question-hint {
            color: var(--text-light);
            font-size: 0.95em;
            font-style: italic;
        }
        
        /* Pilihan Ganda */
        .choices {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .choice-btn {
            padding: 18px 20px;
            background: var(--card-bg);
            border: 2px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            font-weight: 500;
            color: var(--text);
            text-align: left;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .choice-btn:hover:not(.disabled) {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        .choice-btn.correct {
            background: var(--success-gradient);
            color: white;
            border-color: #43e97b;
            animation: pulse 0.5s;
        }
        
        .choice-btn.wrong {
            background: var(--danger-gradient);
            color: white;
            border-color: #f5576c;
            animation: shake 0.5s;
        }
        
        .choice-btn.disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        /* Input Jawaban */
        .answer-input {
            width: 100%;
            padding: 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 1.2em;
            margin-bottom: 15px;
        }
        
        .answer-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        /* Feedback */
        .feedback {
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            font-weight: 600;
            display: none;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .feedback.correct {
            background: var(--success-gradient);
            color: white;
        }
        
        .feedback.wrong {
            background: var(--danger-gradient);
            color: white;
        }
        
        /* Results */
        .quiz-results {
            display: none;
            text-align: center;
        }
        
        .results-card {
            background: var(--primary-gradient);
            color: white;
            padding: 40px;
            border-radius: 25px;
            margin-bottom: 30px;
        }
        
        .results-card h2 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 30px auto;
            font-size: 3em;
            font-weight: 800;
            border: 5px solid rgba(255, 255, 255, 0.5);
        }
        
        .results-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }
        
        .result-stat {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
        }
        
        .result-stat h3 {
            font-size: 2em;
            margin-bottom: 5px;
        }
        
        .result-stat p {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        /* Timer */
        .timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--warning-gradient);
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            font-size: 1.5em;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(250, 112, 154, 0.4);
            z-index: 100;
            display: none;
        }
        
        .timer.warning {
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            padding: 10px 20px;
            border: 2px solid #667eea;
            border-radius: 12px;
        }
        
        .back-link:hover {
            background: #667eea;
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 25px; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); }
            .choices { grid-template-columns: 1fr; }
            .results-stats { grid-template-columns: 1fr; }
            .header h1 { font-size: 1.8em; }
        }
    </style>
</head>
<body>
<div class="timer" id="timer">️ <span id="timerDisplay">00:00</span></div>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-brain"></i> Kuis Bahasa Banjar</h1>
        <p style="color: var(--text-light);">Tes pengetahuanmu tentang bahasa daerah!</p>
    </div>

    <!-- Quiz Setup -->
    <div id="quizSetup" class="quiz-setup">
        <h2><i class="fas fa-cog"></i> Pengaturan Kuis</h2>
        
        <div class="form-group">
            <label><i class="fas fa-gamepad"></i> Mode Kuis</label>
            <select id="quizMode">
                <option value="pilihan_ganda"> Pilihan Ganda (Mudah)</option>
                <option value="ketik">️ Ketik Jawaban (Sedang)</option>
                <option value="tebak_arti">🤔 Tebak Arti (Sulit)</option>
            </select>
        </div>

        <div class="form-group">
            <label><i class="fas fa-filter"></i> Filter Huruf (Opsional)</label>
            <select id="hurufFilter">
                <option value="">Semua Huruf</option>
                <?php foreach(range('A', 'Z') as $h): ?>
                <option value="<?= $h ?>"><?= $h ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label><i class="fas fa-list-ol"></i> Jumlah Soal</label>
            <select id="jumlahSoal">
                <option value="5">5 Soal (Cepat)</option>
                <option value="10" selected>10 Soal (Normal)</option>
                <option value="20">20 Soal (Tantangan)</option>
                <option value="50">50 Soal (Marathon)</option>
            </select>
        </div>

        <div class="form-group">
            <label><i class="fas fa-clock"></i> Timer (Opsional)</label>
            <select id="timerSetting">
                <option value="0">Tidak Ada Timer</option>
                <option value="30">30 Detik / Soal</option>
                <option value="60">60 Detik / Soal</option>
                <option value="120">120 Detik / Soal</option>
            </select>
        </div>

        <button class="btn" onclick="startQuiz()" style="width: 100%; justify-content: center;">
            <i class="fas fa-play"></i> Mulai Kuis!
        </button>
    </div>

    <!-- Quiz Area -->
    <div id="quizArea" class="quiz-area">
        <div class="quiz-progress">
            <div class="quiz-progress-bar" id="progressBar" style="width: 0%"></div>
        </div>

        <div class="stats-bar">
            <div class="stat-card">
                <h3 id="statSoal">1/10</h3>
                <p>Soal</p>
            </div>
            <div class="stat-card">
                <h3 id="statBenar">0</h3>
                <p>Benar</p>
            </div>
            <div class="stat-card">
                <h3 id="statSalah">0</h3>
                <p>Salah</p>
            </div>
            <div class="stat-card">
                <h3 id="statSkor">0</h3>
                <p>Skor</p>
            </div>
        </div>

        <div class="question-card">
            <div class="question-number" id="questionNumber">Soal 1 dari 10</div>
            <div class="question-text" id="questionText"></div>
            <div class="question-hint" id="questionHint"></div>
        </div>

        <div id="choicesArea" class="choices"></div>
        
        <div id="inputArea" style="display: none;">
            <input type="text" id="answerInput" class="answer-input" placeholder="Ketik jawabanmu di sini...">
            <button class="btn" onclick="checkTypedAnswer()" style="width: 100%; justify-content: center;">
                <i class="fas fa-check"></i> Periksa Jawaban
            </button>
        </div>

        <div id="feedback" class="feedback"></div>

        <div style="text-align: center; margin-top: 20px;">
            <button id="nextBtn" class="btn" onclick="nextQuestion()" style="display: none;">
                Soal Berikutnya <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- Results -->
    <div id="quizResults" class="quiz-results">
        <div class="results-card">
            <h2><i class="fas fa-trophy"></i> Kuis Selesai!</h2>
            <div class="score-circle" id="finalScore">0%</div>
            <p id="finalMessage">Kerja bagus!</p>
        </div>

        <div class="results-stats">
            <div class="result-stat">
                <h3 id="resultBenar">0</h3>
                <p>Jawaban Benar</p>
            </div>
            <div class="result-stat">
                <h3 id="resultSalah">0</h3>
                <p>Jawaban Salah</p>
            </div>
            <div class="result-stat">
                <h3 id="resultWaktu">0s</h3>
                <p>Waktu Total</p>
            </div>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <button class="btn" onclick="restartQuiz()">
                <i class="fas fa-redo"></i> Kuis Ulang
            </button>
            <a href="index.php" class="back-link">
                <i class="fas fa-home"></i> Kembali ke Kamus
            </a>
        </div>
    </div>
</div>

<script>
// Data Kuis dari PHP
const quizData = <?= json_encode($quizWords) ?>;
let currentQuestion = 0;
let benar = 0;
let salah = 0;
let skor = 0;
let timerInterval;
let timeLeft;
let startTime;

function startQuiz() {
    const mode = document.getElementById('quizMode').value;
    const huruf = document.getElementById('hurufFilter').value;
    const jumlah = parseInt(document.getElementById('jumlahSoal').value);
    const timerSec = parseInt(document.getElementById('timerSetting').value);
    
    if (quizData.length === 0) {
        alert('Tidak ada data untuk kuis!');
        return;
    }
    
    // Shuffle and slice
    const shuffled = [...quizData].sort(() => 0.5 - Math.random());
    window.quizQuestions = shuffled.slice(0, Math.min(jumlah, shuffled.length));
    window.quizMode = mode;
    window.timerSetting = timerSec;
    
    // Reset stats
    currentQuestion = 0;
    benar = 0;
    salah = 0;
    skor = 0;
    startTime = Date.now();
    
    // Show quiz area
    document.getElementById('quizSetup').style.display = 'none';
    document.getElementById('quizArea').style.display = 'block';
    document.getElementById('quizResults').style.display = 'none';
    
    if (timerSec > 0) {
        document.getElementById('timer').style.display = 'block';
    }
    
    showQuestion();
}

function showQuestion() {
    if (currentQuestion >= window.quizQuestions.length) {
        showResults();
        return;
    }
    
    const q = window.quizQuestions[currentQuestion];
    const mode = window.quizMode;
    
    // Update progress
    const progress = ((currentQuestion + 1) / window.quizQuestions.length) * 100;
    document.getElementById('progressBar').style.width = progress + '%';
    document.getElementById('statSoal').textContent = `${currentQuestion + 1}/${window.quizQuestions.length}`;
    document.getElementById('questionNumber').textContent = `Soal ${currentQuestion + 1} dari ${window.quizQuestions.length}`;
    
    // Reset feedback
    document.getElementById('feedback').style.display = 'none';
    document.getElementById('nextBtn').style.display = 'none';
    
    // Timer
    if (window.timerSetting > 0) {
        timeLeft = window.timerSetting;
        updateTimerDisplay();
        startTimer();
    }
    
    if (mode === 'pilihan_ganda') {
        showMultipleChoice(q);
    } else if (mode === 'ketik') {
        showTypedInput(q);
    } else if (mode === 'tebak_arti') {
        showTebakArti(q);
    }
}

function showMultipleChoice(q) {
    document.getElementById('questionText').innerHTML = `Apa arti dari kata "<strong style="color: #667eea;">${q.kata_banjar}</strong>"?`;
    document.getElementById('questionHint').textContent = `Kata dimulai dengan huruf ${q.abjad}`;
    document.getElementById('choicesArea').style.display = 'grid';
    document.getElementById('inputArea').style.display = 'none';
    
    // Generate wrong answers
    const wrongAnswers = quizData
        .filter(w => w.kata_banjar !== q.kata_banjar)
        .sort(() => 0.5 - Math.random())
        .slice(0, 3)
        .map(w => w.arti_indonesia);
    
    const choices = [...wrongAnswers, q.arti_indonesia].sort(() => 0.5 - Math.random());
    
    const choicesArea = document.getElementById('choicesArea');
    choicesArea.innerHTML = '';
    
    choices.forEach(choice => {
        const btn = document.createElement('button');
        btn.className = 'choice-btn';
        btn.textContent = choice;
        btn.onclick = () => checkMultipleChoice(btn, choice === q.arti_indonesia, q);
        choicesArea.appendChild(btn);
    });
}

function showTypedInput(q) {
    document.getElementById('questionText').innerHTML = `Apa arti dari kata "<strong style="color: #667eea;">${q.kata_banjar}</strong>"?`;
    document.getElementById('questionHint').textContent = `Ketik arti yang benar (huruf kapital tidak diperhitungkan)`;
    document.getElementById('choicesArea').style.display = 'none';
    document.getElementById('inputArea').style.display = 'block';
    document.getElementById('answerInput').value = '';
    document.getElementById('answerInput').focus();
    
    window.correctAnswer = q.arti_indonesia.toLowerCase();
}

function showTebakArti(q) {
    document.getElementById('questionText').innerHTML = `Kata Banjar apa yang artinya "<strong style="color: #667eea;">${q.arti_indonesia}</strong>"?`;
    document.getElementById('questionHint').textContent = `Jawaban dimulai dengan huruf ${q.abjad} dan memiliki ${q.kata_banjar.length} huruf`;
    document.getElementById('choicesArea').style.display = 'none';
    document.getElementById('inputArea').style.display = 'block';
    document.getElementById('answerInput').value = '';
    document.getElementById('answerInput').focus();
    
    window.correctAnswer = q.kata_banjar.toLowerCase();
}

function checkMultipleChoice(btn, isCorrect, q) {
    clearInterval(timerInterval);
    
    // Disable all buttons
    document.querySelectorAll('.choice-btn').forEach(b => b.classList.add('disabled'));
    
    if (isCorrect) {
        btn.classList.add('correct');
        showFeedback(true, q);
        benar++;
        skor += window.timerSetting > 0 ? 150 : 100;
    } else {
        btn.classList.add('wrong');
        // Highlight correct answer
        document.querySelectorAll('.choice-btn').forEach(b => {
            if (b.textContent === q.arti_indonesia) b.classList.add('correct');
        });
        showFeedback(false, q);
        salah++;
    }
    
    updateStats();
    document.getElementById('nextBtn').style.display = 'inline-flex';
}

function checkTypedAnswer() {
    const input = document.getElementById('answerInput').value.trim().toLowerCase();
    const correct = window.correctAnswer;
    
    if (!input) {
        alert('Silakan isi jawaban terlebih dahulu!');
        return;
    }
    
    clearInterval(timerInterval);
    
    // Check similarity (allow small typos)
    const isCorrect = input === correct || similarity(input, correct) > 0.85;
    
    if (isCorrect) {
        showFeedback(true, window.quizQuestions[currentQuestion]);
        benar++;
        skor += window.timerSetting > 0 ? 150 : 100;
    } else {
        showFeedback(false, window.quizQuestions[currentQuestion], correct);
        salah++;
    }
    
    updateStats();
    document.getElementById('nextBtn').style.display = 'inline-flex';
    document.getElementById('inputArea').style.display = 'none';
}

function similarity(s1, s2) {
    const longer = s1.length > s2.length ? s1 : s2;
    const shorter = s1.length > s2.length ? s2 : s1;
    if (longer.length === 0) return 1.0;
    return (longer.length - editDistance(longer, shorter)) / longer.length;
}

function editDistance(s1, s2) {
    s1 = s1.toLowerCase();
    s2 = s2.toLowerCase();
    const costs = [];
    for (let i = 0; i <= s1.length; i++) {
        let lastValue = i;
        for (let j = 0; j <= s2.length; j++) {
            if (i === 0) costs[j] = j;
            else if (j > 0) {
                let newValue = costs[j - 1];
                if (s1.charAt(i - 1) !== s2.charAt(j - 1))
                    newValue = Math.min(Math.min(newValue, lastValue), costs[j]) + 1;
                costs[j - 1] = lastValue;
                lastValue = newValue;
            }
        }
        if (i > 0) costs[s2.length] = lastValue;
    }
    return costs[s2.length];
}

function showFeedback(isCorrect, q, correctAnswer = null) {
    const feedback = document.getElementById('feedback');
    feedback.style.display = 'block';
    
    if (isCorrect) {
        feedback.className = 'feedback correct';
        feedback.innerHTML = `<i class="fas fa-check-circle"></i> <strong>Benar!</strong> "${q.kata_banjar}" artinya "${q.arti_indonesia}"`;
    } else {
        feedback.className = 'feedback wrong';
        const jawaban = correctAnswer || q.arti_indonesia;
        feedback.innerHTML = `<i class="fas fa-times-circle"></i> <strong>Salah!</strong> Jawaban yang benar: "${jawaban}"`;
    }
}

function updateStats() {
    document.getElementById('statBenar').textContent = benar;
    document.getElementById('statSalah').textContent = salah;
    document.getElementById('statSkor').textContent = skor;
}

function nextQuestion() {
    currentQuestion++;
    showQuestion();
}

function startTimer() {
    updateTimerDisplay();
    timerInterval = setInterval(() => {
        timeLeft--;
        updateTimerDisplay();
        
        if (timeLeft <= 10) {
            document.getElementById('timer').classList.add('warning');
        }
        
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            showFeedback(false, window.quizQuestions[currentQuestion]);
            salah++;
            updateStats();
            document.getElementById('nextBtn').style.display = 'inline-flex';
        }
    }, 1000);
}

function updateTimerDisplay() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    document.getElementById('timerDisplay').textContent = 
        `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

function showResults() {
    clearInterval(timerInterval);
    document.getElementById('timer').style.display = 'none';
    document.getElementById('quizArea').style.display = 'none';
    document.getElementById('quizResults').style.display = 'block';
    
    const total = window.quizQuestions.length;
    const percentage = Math.round((benar / total) * 100);
    const waktuTotal = Math.round((Date.now() - startTime) / 1000);
    
    document.getElementById('finalScore').textContent = percentage + '%';
    document.getElementById('resultBenar').textContent = benar;
    document.getElementById('resultSalah').textContent = salah;
    document.getElementById('resultWaktu').textContent = waktuTotal + 's';
    
    let message = '';
    if (percentage >= 90) message = ' Luar Biasa! Kamu ahli bahasa Banjar!';
    else if (percentage >= 70) message = '👏 Bagus! Pengetahuanmu sangat baik!';
    else if (percentage >= 50) message = ' Lumayan! Terus belajar ya!';
    else message = '📚 Jangan menyerah! Coba lagi untuk meningkatkan skor!';
    
    document.getElementById('finalMessage').textContent = message;
}

function restartQuiz() {
    document.getElementById('quizResults').style.display = 'none';
    document.getElementById('quizSetup').style.display = 'block';
}

// Enter key untuk submit jawaban
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && document.getElementById('inputArea').style.display !== 'none') {
        checkTypedAnswer();
    }
});
</script>
</body>
</html>