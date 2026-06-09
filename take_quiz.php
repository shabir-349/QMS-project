<?php
session_start();
require 'db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: index.php"); exit();
}

$quiz_id    = (int)$_GET['id'];
$student_id = $_SESSION['user_id'];

// Check already submitted
$check = $conn->query("SELECT * FROM submissions WHERE student_id=$student_id AND quiz_id=$quiz_id");
if ($check->num_rows > 0) {
    header("Location: student_dashboard.php"); exit();
}

$quiz      = $conn->query("SELECT * FROM quizzes WHERE quiz_id=$quiz_id AND is_active=1")->fetch_assoc();
if (!$quiz) { header("Location: student_dashboard.php"); exit(); }

$questions = $conn->query("SELECT * FROM questions WHERE quiz_id=$quiz_id ORDER BY question_id");

// Handle submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $total = $questions->num_rows;
    $score = 0;
    $questions->data_seek(0);
    while ($q = $questions->fetch_assoc()) {
        $ans = isset($_POST['answers'][$q['question_id']]) ? $_POST['answers'][$q['question_id']] : '';
        if ($ans == $q['correct_answer']) $score++;
    }
    $pct = $total > 0 ? round(($score / $total) * 100, 1) : 0;
    $conn->query("INSERT INTO submissions (student_id, quiz_id, calculated_score, submitted_at)
                  VALUES ($student_id, $quiz_id, $pct, NOW())");
    header("Location: result.php?quiz_id=$quiz_id&score=$pct&total=$total&got=$score");
    exit();
}

$questions->data_seek(0);
$all_questions = [];
while ($q = $questions->fetch_assoc()) $all_questions[] = $q;
$total_q = count($all_questions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QMS — <?= htmlspecialchars($quiz['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  body { background: #0f0e17; color: #e8e8e8; font-family: 'Segoe UI', sans-serif; }
  .quiz-header {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    border-bottom: 1px solid rgba(255,255,255,0.07);
    padding: 15px 30px; position: sticky; top: 0; z-index: 100;
    display: flex; align-items: center; justify-content: space-between;
  }
  .quiz-header h5 { color: #fff; margin: 0; font-size: 1rem; font-weight: 600; }
  .timer-box {
    background: rgba(252,129,129,0.15); border: 1px solid rgba(252,129,129,0.3);
    border-radius: 10px; padding: 8px 20px;
    font-size: 1.2rem; font-weight: 700; color: #fc8181;
    font-family: monospace;
  }
  .timer-box.warn { color: #fc8181; animation: pulse 1s infinite; }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.6} }
  .content { max-width: 800px; margin: 0 auto; padding: 30px 20px; }
  .q-card {
    background: #1a1a2e; border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px; padding: 25px; margin-bottom: 20px;
  }
  .q-num { color: #667eea; font-size: 0.8rem; font-weight: 600; margin-bottom: 8px; }
  .q-text { color: #fff; font-size: 1rem; font-weight: 500; margin-bottom: 20px; }
  .option-label {
    display: flex; align-items: center; gap: 12px;
    background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
    border-radius: 10px; padding: 12px 15px; margin-bottom: 10px;
    cursor: pointer; transition: all 0.15s;
  }
  .option-label:hover { background: rgba(102,126,234,0.1); border-color: rgba(102,126,234,0.3); }
  input[type="radio"]:checked + .option-label {
    background: rgba(102,126,234,0.2); border-color: #667eea;
  }
  input[type="radio"] { display: none; }
  .opt-badge {
    background: rgba(102,126,234,0.2); color: #667eea;
    border-radius: 6px; width: 28px; height: 28px; min-width:28px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.85rem;
  }
  .progress-bar-custom {
    background: rgba(255,255,255,0.1); border-radius: 10px; height: 6px; margin-bottom: 20px;
  }
  .progress-fill {
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 10px; height: 6px; transition: width 0.3s;
  }
  .btn-submit {
    background: linear-gradient(135deg, #48c78e, #06d6a0);
    border: none; border-radius: 12px; color: white;
    padding: 14px 40px; font-size: 1rem; font-weight: 700;
    width: 100%; transition: all 0.2s; margin-top: 10px;
  }
  .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(72,199,142,0.3); color:white; }
  .warning-bar {
    background: rgba(252,129,129,0.1); border: 1px solid rgba(252,129,129,0.3);
    border-radius: 10px; padding: 10px 15px; margin-bottom: 20px;
    display: none; color: #fc8181; font-size: 0.85rem;
  }
</style>
</head>
<body>

<div class="quiz-header">
  <h5><i class="fas fa-file-alt me-2" style="color:#667eea"></i><?= htmlspecialchars($quiz['title']) ?></h5>
  <div class="timer-box" id="timer">
    <?= str_pad($quiz['duration_minutes'],2,'0',STR_PAD_LEFT) ?>:00
  </div>
</div>

<div class="content">
  <div id="cheat-warning" class="warning-bar">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <span id="warn-msg">Warning: Tab switching detected!</span>
  </div>

  <div class="progress-bar-custom">
    <div class="progress-fill" id="progress-fill" style="width:0%"></div>
  </div>

  <form method="POST" id="quiz-form">
    <?php foreach ($all_questions as $idx => $q): ?>
    <div class="q-card">
      <div class="q-num">QUESTION <?= $idx+1 ?> OF <?= $total_q ?></div>
      <div class="q-text"><?= htmlspecialchars($q['question_text']) ?></div>

      <?php foreach (['A','B','C','D'] as $opt): ?>
        <?php $opt_val = $q['option_'.strtolower($opt)]; ?>
        <div>
          <input type="radio" name="answers[<?= $q['question_id'] ?>]"
                 id="q<?= $q['question_id'] ?>_<?= $opt ?>"
                 value="<?= $opt ?>"
                 onchange="updateProgress()">
          <label class="option-label" for="q<?= $q['question_id'] ?>_<?= $opt ?>">
            <div class="opt-badge"><?= $opt ?></div>
            <span><?= htmlspecialchars($opt_val) ?></span>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <button type="submit" class="btn-submit">
      <i class="fas fa-paper-plane me-2"></i>Submit Quiz
    </button>
  </form>
</div>

<script>
// Countdown Timer
let timeLeft = <?= $quiz['duration_minutes'] * 60 ?>;
const timerEl = document.getElementById('timer');
const interval = setInterval(() => {
  timeLeft--;
  const m = Math.floor(timeLeft / 60);
  const s = timeLeft % 60;
  timerEl.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
  if (timeLeft <= 60) timerEl.classList.add('warn');
  if (timeLeft <= 0) {
    clearInterval(interval);
    alert('Time is up! Quiz auto-submitting...');
    document.getElementById('quiz-form').submit();
  }
}, 1000);

// Anti-Cheat: Tab Switch Detection
let warnCount = 0;
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    warnCount++;
    const bar = document.getElementById('cheat-warning');
    const msg = document.getElementById('warn-msg');
    bar.style.display = 'block';
    if (warnCount >= 3) {
      msg.textContent = '3 violations detected. Quiz auto-submitting!';
      setTimeout(() => document.getElementById('quiz-form').submit(), 1500);
    } else {
      msg.textContent = `Warning ${warnCount}/3: Tab switching detected! Quiz will auto-submit on 3rd violation.`;
    }
  }
});

// Anti-Cheat: Disable Copy-Paste
document.addEventListener('copy',  e => e.preventDefault());
document.addEventListener('paste', e => e.preventDefault());
document.addEventListener('contextmenu', e => e.preventDefault());

// Progress Bar
function updateProgress() {
  const total = <?= $total_q ?>;
  const answered = document.querySelectorAll('input[type="radio"]:checked').length;
  document.getElementById('progress-fill').style.width = (answered / total * 100) + '%';
}
</script>
</body>
</html>
