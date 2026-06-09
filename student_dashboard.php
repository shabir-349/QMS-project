<?php
session_start();
require 'db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: index.php"); exit();
}

$student_id = $_SESSION['user_id'];

// Available quizzes (not yet taken)
$quizzes = $conn->query("
    SELECT q.* FROM quizzes q
    WHERE q.is_active = 1
    AND q.quiz_id NOT IN (
        SELECT quiz_id FROM submissions WHERE student_id = $student_id
    )
");

// Completed quizzes
$done = $conn->query("
    SELECT q.title, s.calculated_score, s.submitted_at
    FROM submissions s
    JOIN quizzes q ON s.quiz_id = q.quiz_id
    WHERE s.student_id = $student_id
    ORDER BY s.submitted_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QMS — Student Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  body { background: #0f0e17; color: #e8e8e8; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
  .topnav {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    border-bottom: 1px solid rgba(255,255,255,0.07);
    padding: 15px 30px; display: flex;
    align-items: center; justify-content: space-between;
  }
  .brand { display: flex; align-items: center; gap: 12px; }
  .brand-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px; width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; color: white;
  }
  .brand h5 { color: #fff; margin: 0; font-weight: 700; }
  .user-pill {
    background: rgba(102,126,234,0.15); border: 1px solid rgba(102,126,234,0.25);
    border-radius: 20px; padding: 6px 14px; color: #a0aec0; font-size: 0.85rem;
    display: flex; align-items: center; gap: 8px;
  }
  .content { padding: 30px; max-width: 1000px; margin: 0 auto; }
  .welcome-banner {
    background: linear-gradient(135deg, rgba(102,126,234,0.15), rgba(118,75,162,0.15));
    border: 1px solid rgba(102,126,234,0.2); border-radius: 16px; padding: 25px 30px;
    margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between;
  }
  .welcome-banner h4 { color: #fff; margin: 0; font-weight: 700; }
  .welcome-banner p { color: #a0aec0; margin: 5px 0 0; }
  .section-title { color: #fff; font-weight: 600; margin-bottom: 15px; }
  .quiz-card {
    background: #1a1a2e; border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px; padding: 20px; margin-bottom: 15px;
    transition: all 0.2s;
  }
  .quiz-card:hover { border-color: rgba(102,126,234,0.4); transform: translateY(-2px); }
  .quiz-card h6 { color: #fff; font-weight: 600; margin-bottom: 5px; }
  .quiz-card small { color: #718096; }
  .btn-start {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none; border-radius: 8px; color: white;
    padding: 8px 20px; font-size: 0.85rem; font-weight: 600;
    transition: all 0.2s; text-decoration: none;
  }
  .btn-start:hover {
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    color: white; transform: translateY(-1px);
  }
  .result-card {
    background: #1a1a2e; border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px; padding: 15px 20px; margin-bottom: 10px;
    display: flex; align-items: center; justify-content: space-between;
  }
  .score-chip {
    border-radius: 20px; padding: 4px 14px;
    font-weight: 700; font-size: 0.9rem;
  }
  .score-pass { background: rgba(72,199,142,0.15); color: #48c78e; }
  .score-fail { background: rgba(252,129,129,0.15); color: #fc8181; }
  .empty-state { text-align: center; padding: 40px; color: #4a5568; }
</style>
</head>
<body>

<nav class="topnav">
  <div class="brand">
    <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
    <h5>QMS</h5>
  </div>
  <div class="d-flex align-items: center; gap: 15px">
    <div class="user-pill">
      <i class="fas fa-user-circle" style="color:#667eea"></i>
      <?= htmlspecialchars($_SESSION['user_name']) ?>
    </div>
    <a href="logout.php" style="color:#fc8181; text-decoration:none; font-size:0.85rem; margin-left:15px;">
      <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
  </div>
</nav>

<div class="content">
  <div class="welcome-banner">
    <div>
      <h4>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>! 👋</h4>
      <p>AUST — Department of Computer Science &bull; 4th Semester</p>
    </div>
    <div style="text-align:right">
      <div style="font-size:2rem; font-weight:700; color:#667eea"><?= $done->num_rows ?></div>
      <div style="color:#a0aec0; font-size:0.8rem">Quizzes Completed</div>
    </div>
  </div>

  <!-- Available Quizzes -->
  <div class="section-title"><i class="fas fa-play-circle me-2" style="color:#667eea"></i>Available Quizzes</div>
  <?php if ($quizzes->num_rows == 0): ?>
    <div class="empty-state">
      <i class="fas fa-check-double" style="font-size:2rem; margin-bottom:10px; color:#48c78e"></i>
      <p>All quizzes completed! Check your results below.</p>
    </div>
  <?php else: ?>
    <?php while ($qz = $quizzes->fetch_assoc()): 
      $qcount = $conn->query("SELECT COUNT(*) as c FROM questions WHERE quiz_id={$qz['quiz_id']}")->fetch_assoc();
    ?>
    <div class="quiz-card d-flex align-items-center justify-content-between">
      <div>
        <h6><?= htmlspecialchars($qz['title']) ?></h6>
        <small><i class="fas fa-question-circle me-1"></i><?= $qcount['c'] ?> Questions &nbsp;&bull;&nbsp;
               <i class="fas fa-clock me-1"></i><?= $qz['duration_minutes'] ?> minutes</small>
      </div>
      <a href="take_quiz.php?id=<?= $qz['quiz_id'] ?>" class="btn-start">
        <i class="fas fa-play me-1"></i>Start Quiz
      </a>
    </div>
    <?php endwhile; ?>
  <?php endif; ?>

  <!-- Results -->
  <div class="section-title mt-4"><i class="fas fa-history me-2" style="color:#667eea"></i>My Results</div>
  <?php $done->data_seek(0); if ($done->num_rows == 0): ?>
    <div class="empty-state">
      <i class="fas fa-clipboard-list" style="font-size:2rem; margin-bottom:10px"></i>
      <p>No quizzes taken yet. Start a quiz above!</p>
    </div>
  <?php else: ?>
    <?php while ($r = $done->fetch_assoc()): ?>
    <div class="result-card">
      <div>
        <div style="color:#fff; font-weight:600"><?= htmlspecialchars($r['title']) ?></div>
        <small style="color:#718096"><?= date('d M Y, h:i A', strtotime($r['submitted_at'])) ?></small>
      </div>
      <span class="score-chip <?= $r['calculated_score'] >= 60 ? 'score-pass' : 'score-fail' ?>">
        <?= round($r['calculated_score'],1) ?>%
      </span>
    </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

</body>
</html>
