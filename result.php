<?php
session_start();
if (!isset($_SESSION['role'])) { header("Location: index.php"); exit(); }

$score = isset($_GET['score']) ? (float)$_GET['score'] : 0;
$total = isset($_GET['total']) ? (int)$_GET['total'] : 0;
$got   = isset($_GET['got'])   ? (int)$_GET['got']   : 0;
$pass  = $score >= 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QMS — Result</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  body {
    background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    font-family: 'Segoe UI', sans-serif;
  }
  .result-card {
    background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1); border-radius: 24px;
    padding: 50px 40px; text-align: center; width: 450px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.4);
  }
  .result-icon {
    width: 100px; height: 100px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem; margin: 0 auto 25px;
  }
  .icon-pass { background: rgba(72,199,142,0.2); color: #48c78e; border: 2px solid #48c78e; }
  .icon-fail { background: rgba(252,129,129,0.2); color: #fc8181; border: 2px solid #fc8181; }
  .score-big { font-size: 4rem; font-weight: 800; }
  .score-pass { color: #48c78e; }
  .score-fail { color: #fc8181; }
  .result-label { color: #a0aec0; margin-bottom: 5px; font-size: 0.9rem; }
  .stat-row { display: flex; justify-content: center; gap: 40px; margin: 25px 0; }
  .stat-item { text-align: center; }
  .stat-item .val { font-size: 1.8rem; font-weight: 700; color: #667eea; }
  .stat-item .lbl { color: #718096; font-size: 0.8rem; }
  .btn-home {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none; border-radius: 12px; color: white;
    padding: 13px 35px; font-weight: 600; font-size: 1rem;
    text-decoration: none; display: inline-block; transition: all 0.2s;
  }
  .btn-home:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.4); color:white; }
  .pass-badge {
    padding: 6px 20px; border-radius: 20px; font-weight: 700;
    display: inline-block; margin-bottom: 20px;
  }
  .pass-badge-pass { background: rgba(72,199,142,0.2); color: #48c78e; border: 1px solid #48c78e; }
  .pass-badge-fail { background: rgba(252,129,129,0.2); color: #fc8181; border: 1px solid #fc8181; }
</style>
</head>
<body>
<div class="result-card">
  <div class="result-icon <?= $pass ? 'icon-pass' : 'icon-fail' ?>">
    <i class="fas fa-<?= $pass ? 'trophy' : 'times' ?>"></i>
  </div>

  <span class="pass-badge <?= $pass ? 'pass-badge-pass' : 'pass-badge-fail' ?>">
    <?= $pass ? '✅ PASSED' : '❌ FAILED' ?>
  </span>

  <div class="result-label">YOUR SCORE</div>
  <div class="score-big <?= $pass ? 'score-pass' : 'score-fail' ?>"><?= $score ?>%</div>

  <div class="stat-row">
    <div class="stat-item">
      <div class="val"><?= $got ?></div>
      <div class="lbl">Correct</div>
    </div>
    <div class="stat-item">
      <div class="val"><?= $total - $got ?></div>
      <div class="lbl">Wrong</div>
    </div>
    <div class="stat-item">
      <div class="val"><?= $total ?></div>
      <div class="lbl">Total</div>
    </div>
  </div>

  <p style="color:#a0aec0; font-size:0.9rem; margin-bottom:25px">
    <?php if ($pass): ?>
      Great work, <?= htmlspecialchars($_SESSION['user_name']) ?>! Results sent to instructor.
    <?php else: ?>
      Keep practicing! Your instructor has been notified.
    <?php endif; ?>
  </p>

  <a href="student_dashboard.php" class="btn-home">
    <i class="fas fa-home me-2"></i>Back to Dashboard
  </a>
</div>
</body>
</html>
