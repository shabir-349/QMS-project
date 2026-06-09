<?php
session_start();
require 'db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); exit();
}

// Handle new quiz creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_quiz'])) {
    $title    = $conn->real_escape_string($_POST['title']);
    $duration = (int)$_POST['duration'];
    $adminId  = $_SESSION['user_id'];

    $conn->query("INSERT INTO quizzes (creator_id, title, duration_minutes, is_active)
                  VALUES ($adminId, '$title', $duration, 1)");
    $quiz_id = $conn->insert_id;

    foreach ($_POST['q_text'] as $i => $qtext) {
        if (trim($qtext) == '') continue;
        $qtext   = $conn->real_escape_string($qtext);
        $opt_a   = $conn->real_escape_string($_POST['opt_a'][$i]);
        $opt_b   = $conn->real_escape_string($_POST['opt_b'][$i]);
        $opt_c   = $conn->real_escape_string($_POST['opt_c'][$i]);
        $opt_d   = $conn->real_escape_string($_POST['opt_d'][$i]);
        $correct = $conn->real_escape_string($_POST['correct'][$i]);

        $conn->query("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer)
                      VALUES ($quiz_id, '$qtext', '$opt_a', '$opt_b', '$opt_c', '$opt_d', '$correct')");
    }
    $success = "Quiz created successfully!";
}

// Fetch data
$quizzes    = $conn->query("SELECT q.*, COUNT(qu.question_id) as q_count FROM quizzes q LEFT JOIN questions qu ON q.quiz_id = qu.quiz_id GROUP BY q.quiz_id ORDER BY q.created_at DESC");
$students   = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='student'")->fetch_assoc();
$total_subs = $conn->query("SELECT COUNT(*) as cnt FROM submissions")->fetch_assoc();
$avg_score  = $conn->query("SELECT AVG(calculated_score) as avg FROM submissions")->fetch_assoc();

// Analytics
$analytics = $conn->query("
    SELECT u.name, COUNT(s.sub_id) AS total_quizzes,
           ROUND(AVG(s.calculated_score),1) AS avg_score
    FROM users u
    JOIN submissions s ON u.user_id = s.student_id
    WHERE u.role = 'student'
    GROUP BY u.user_id, u.name
    ORDER BY avg_score DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QMS — Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  :root { --purple: #667eea; --dark-purple: #764ba2; }
  body { background: #0f0e17; color: #e8e8e8; font-family: 'Segoe UI', sans-serif; }
  .sidebar {
    background: linear-gradient(180deg, #1a1a2e, #16213e);
    width: 260px; min-height: 100vh; position: fixed;
    border-right: 1px solid rgba(255,255,255,0.05);
    padding: 25px 20px;
  }
  .sidebar .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 35px; }
  .sidebar .brand-icon {
    background: linear-gradient(135deg, var(--purple), var(--dark-purple));
    border-radius: 12px; width: 45px; height: 45px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: white;
  }
  .sidebar .brand-text h5 { color: #fff; margin: 0; font-weight: 700; font-size: 1.1rem; }
  .sidebar .brand-text small { color: #718096; font-size: 0.75rem; }
  .nav-item-custom {
    padding: 12px 15px; border-radius: 10px; color: #a0aec0;
    cursor: pointer; margin-bottom: 5px; transition: all 0.2s;
    display: flex; align-items: center; gap: 10px; font-size: 0.9rem;
    text-decoration: none;
  }
  .nav-item-custom:hover, .nav-item-custom.active {
    background: rgba(102,126,234,0.15); color: var(--purple);
  }
  .main-content { margin-left: 260px; padding: 30px; }
  .topbar {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 30px;
  }
  .topbar h4 { color: #fff; font-weight: 600; margin: 0; }
  .user-badge {
    background: rgba(102,126,234,0.15); border: 1px solid rgba(102,126,234,0.3);
    border-radius: 20px; padding: 8px 16px;
    color: var(--purple); font-size: 0.85rem;
  }
  .stat-card {
    background: linear-gradient(135deg, rgba(102,126,234,0.1), rgba(118,75,162,0.1));
    border: 1px solid rgba(102,126,234,0.2); border-radius: 16px; padding: 25px;
  }
  .stat-card .stat-num { font-size: 2.2rem; font-weight: 700; color: var(--purple); }
  .stat-card .stat-label { color: #a0aec0; font-size: 0.85rem; margin-top: 5px; }
  .stat-card .stat-icon { font-size: 2rem; opacity: 0.3; float: right; margin-top: -40px; }
  .section-card {
    background: #1a1a2e; border: 1px solid rgba(255,255,255,0.07);
    border-radius: 16px; padding: 25px; margin-bottom: 25px;
  }
  .section-title { color: #fff; font-weight: 600; margin-bottom: 20px; font-size: 1rem; }
  .form-control, .form-select {
    background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
    color: #e8e8e8; border-radius: 8px;
  }
  .form-control:focus, .form-select:focus {
    background: rgba(255,255,255,0.1); border-color: var(--purple);
    color: #fff; box-shadow: none;
  }
  .form-control::placeholder { color: #4a5568; }
  .form-label { color: #a0aec0; font-size: 0.85rem; }
  .btn-primary-custom {
    background: linear-gradient(135deg, var(--purple), var(--dark-purple));
    border: none; border-radius: 8px; color: white;
    padding: 10px 20px; font-weight: 600; transition: all 0.2s;
  }
  .btn-primary-custom:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(102,126,234,0.35); color: white;
  }
  .quiz-badge {
    background: rgba(72,199,142,0.15); color: #48c78e;
    border-radius: 20px; padding: 3px 10px; font-size: 0.75rem;
  }
  .quiz-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 15px; background: rgba(255,255,255,0.03);
    border-radius: 10px; margin-bottom: 10px;
    border: 1px solid rgba(255,255,255,0.05);
  }
  .quiz-row:hover { background: rgba(102,126,234,0.07); }
  .table-dark-custom { color: #e8e8e8; }
  .table-dark-custom th { color: #a0aec0; font-size: 0.8rem; font-weight: 600; border-color: rgba(255,255,255,0.07); }
  .table-dark-custom td { border-color: rgba(255,255,255,0.05); vertical-align: middle; }
  .score-bar-bg { background: rgba(255,255,255,0.1); border-radius: 10px; height: 8px; }
  .score-bar { background: linear-gradient(90deg, var(--purple), var(--dark-purple)); border-radius: 10px; height: 8px; }
  .question-block {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
    border-radius: 10px; padding: 15px; margin-bottom: 15px;
  }
  .section-tab { display: none; }
  .section-tab.active { display: block; }
  .alert-success-custom {
    background: rgba(72,199,142,0.15); border: 1px solid rgba(72,199,142,0.3);
    color: #48c78e; border-radius: 10px; padding: 12px 15px;
  }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="brand">
    <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
    <div class="brand-text">
      <h5>QMS</h5>
      <small>Admin Panel</small>
    </div>
  </div>
  <a class="nav-item-custom active" onclick="showTab('dashboard')">
    <i class="fas fa-th-large"></i> Dashboard
  </a>
  <a class="nav-item-custom" onclick="showTab('create')">
    <i class="fas fa-plus-circle"></i> Create Quiz
  </a>
  <a class="nav-item-custom" onclick="showTab('quizzes')">
    <i class="fas fa-list"></i> All Quizzes
  </a>
  <a class="nav-item-custom" onclick="showTab('analytics')">
    <i class="fas fa-chart-bar"></i> Analytics
  </a>
  <div style="position:absolute; bottom:25px; width:220px;">
    <a href="logout.php" class="nav-item-custom" style="color:#fc8181;">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="topbar">
    <h4><i class="fas fa-th-large me-2" style="color:var(--purple)"></i>Admin Dashboard</h4>
    <div class="user-badge"><i class="fas fa-user-shield me-2"></i><?= $_SESSION['user_name'] ?></div>
  </div>

  <?php if (isset($success)): ?>
  <div class="alert-success-custom mb-3"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
  <?php endif; ?>

  <!-- Dashboard Tab -->
  <div id="tab-dashboard" class="section-tab active">
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-num"><?= $students['cnt'] ?></div>
          <div class="stat-label"><i class="fas fa-users me-1"></i>Total Students</div>
          <div class="stat-icon"><i class="fas fa-users"></i></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <?php $qcount = $conn->query("SELECT COUNT(*) as c FROM quizzes")->fetch_assoc(); ?>
          <div class="stat-num"><?= $qcount['c'] ?></div>
          <div class="stat-label"><i class="fas fa-file-alt me-1"></i>Total Quizzes</div>
          <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-num"><?= $total_subs['cnt'] ?></div>
          <div class="stat-label"><i class="fas fa-check-circle me-1"></i>Submissions</div>
          <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-num"><?= $avg_score['avg'] ? round($avg_score['avg'],1).'%' : 'N/A' ?></div>
          <div class="stat-label"><i class="fas fa-star me-1"></i>Class Average</div>
          <div class="stat-icon"><i class="fas fa-star"></i></div>
        </div>
      </div>
    </div>

    <div class="section-card">
      <div class="section-title"><i class="fas fa-bolt me-2" style="color:var(--purple)"></i>Recent Quizzes</div>
      <?php
      $recent = $conn->query("SELECT q.*, COUNT(qu.question_id) as q_count FROM quizzes q LEFT JOIN questions qu ON q.quiz_id=qu.quiz_id GROUP BY q.quiz_id ORDER BY q.created_at DESC LIMIT 5");
      while ($qz = $recent->fetch_assoc()):
      ?>
      <div class="quiz-row">
        <div>
          <div style="color:#fff; font-weight:600"><?= htmlspecialchars($qz['title']) ?></div>
          <small style="color:#718096"><?= $qz['q_count'] ?> Questions &bull; <?= $qz['duration_minutes'] ?> min</small>
        </div>
        <span class="quiz-badge"><i class="fas fa-circle me-1" style="font-size:0.5rem"></i>Active</span>
      </div>
      <?php endwhile; ?>
    </div>
  </div>

  <!-- Create Quiz Tab -->
  <div id="tab-create" class="section-tab">
    <div class="section-card">
      <div class="section-title"><i class="fas fa-plus-circle me-2" style="color:var(--purple)"></i>Create New Quiz</div>
      <form method="POST">
        <div class="row g-3 mb-4">
          <div class="col-md-8">
            <label class="form-label">Quiz Title</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. COAL Mid-Term Quiz" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Duration (minutes)</label>
            <input type="number" name="duration" class="form-control" value="30" min="5" max="180" required>
          </div>
        </div>

        <div class="section-title mt-2">Questions</div>
        <div id="questions-container">
          <?php for ($i = 0; $i < 3; $i++): ?>
          <div class="question-block">
            <label class="form-label">Question <?= $i+1 ?></label>
            <input type="text" name="q_text[]" class="form-control mb-2" placeholder="Enter question text" required>
            <div class="row g-2 mb-2">
              <div class="col-md-6"><input type="text" name="opt_a[]" class="form-control" placeholder="Option A" required></div>
              <div class="col-md-6"><input type="text" name="opt_b[]" class="form-control" placeholder="Option B" required></div>
              <div class="col-md-6"><input type="text" name="opt_c[]" class="form-control" placeholder="Option C" required></div>
              <div class="col-md-6"><input type="text" name="opt_d[]" class="form-control" placeholder="Option D" required></div>
            </div>
            <label class="form-label">Correct Answer</label>
            <select name="correct[]" class="form-select" style="width:120px;" required>
              <option value="A">A</option>
              <option value="B">B</option>
              <option value="C">C</option>
              <option value="D">D</option>
            </select>
          </div>
          <?php endfor; ?>
        </div>

        <div class="d-flex gap-2 mt-2">
          <button type="button" class="btn btn-outline-secondary" onclick="addQuestion()" style="border-radius:8px;">
            <i class="fas fa-plus me-1"></i> Add Question
          </button>
          <button type="submit" name="create_quiz" class="btn btn-primary-custom">
            <i class="fas fa-save me-2"></i>Save Quiz
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- All Quizzes Tab -->
  <div id="tab-quizzes" class="section-tab">
    <div class="section-card">
      <div class="section-title"><i class="fas fa-list me-2" style="color:var(--purple)"></i>All Quizzes</div>
      <?php $quizzes->data_seek(0); while ($qz = $quizzes->fetch_assoc()): ?>
      <div class="quiz-row">
        <div>
          <div style="color:#fff; font-weight:600"><?= htmlspecialchars($qz['title']) ?></div>
          <small style="color:#718096"><?= $qz['q_count'] ?> Questions &bull; <?= $qz['duration_minutes'] ?> min &bull; Created: <?= date('d M Y', strtotime($qz['created_at'])) ?></small>
        </div>
        <span class="quiz-badge"><i class="fas fa-circle me-1" style="font-size:0.5rem"></i>Active</span>
      </div>
      <?php endwhile; ?>
    </div>
  </div>

  <!-- Analytics Tab -->
  <div id="tab-analytics" class="section-tab">
    <div class="section-card">
      <div class="section-title"><i class="fas fa-chart-bar me-2" style="color:var(--purple)"></i>Student Performance Analytics</div>
      <?php if ($analytics->num_rows > 0): ?>
      <table class="table table-dark-custom">
        <thead>
          <tr>
            <th>STUDENT NAME</th>
            <th>QUIZZES TAKEN</th>
            <th>AVG SCORE</th>
            <th>PERFORMANCE</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $analytics->fetch_assoc()): ?>
          <tr>
            <td><i class="fas fa-user me-2" style="color:var(--purple)"></i><?= htmlspecialchars($row['name']) ?></td>
            <td><?= $row['total_quizzes'] ?></td>
            <td style="color:<?= $row['avg_score'] >= 75 ? '#48c78e' : '#fc8181' ?>;font-weight:600">
              <?= $row['avg_score'] ?>%
            </td>
            <td style="width:200px">
              <div class="score-bar-bg">
                <div class="score-bar" style="width:<?= min($row['avg_score'],100) ?>%"></div>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p style="color:#718096; text-align:center; padding:30px">No submissions yet. Students need to take quizzes first.</p>
      <?php endif; ?>
    </div>
  </div>

</div><!-- end main-content -->

<script>
function showTab(name) {
  document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.nav-item-custom').forEach(n => n.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  event.target.closest('.nav-item-custom').classList.add('active');
}

let qCount = 3;
function addQuestion() {
  qCount++;
  const container = document.getElementById('questions-container');
  const div = document.createElement('div');
  div.className = 'question-block';
  div.innerHTML = `
    <label class="form-label">Question ${qCount}</label>
    <input type="text" name="q_text[]" class="form-control mb-2" placeholder="Enter question text" required>
    <div class="row g-2 mb-2">
      <div class="col-md-6"><input type="text" name="opt_a[]" class="form-control" placeholder="Option A" required></div>
      <div class="col-md-6"><input type="text" name="opt_b[]" class="form-control" placeholder="Option B" required></div>
      <div class="col-md-6"><input type="text" name="opt_c[]" class="form-control" placeholder="Option C" required></div>
      <div class="col-md-6"><input type="text" name="opt_d[]" class="form-control" placeholder="Option D" required></div>
    </div>
    <label class="form-label">Correct Answer</label>
    <select name="correct[]" class="form-select" style="width:120px;" required>
      <option value="A">A</option><option value="B">B</option>
      <option value="C">C</option><option value="D">D</option>
    </select>`;
  container.appendChild(div);
}
</script>
</body>
</html>
