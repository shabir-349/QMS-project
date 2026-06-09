<?php
session_start();
require 'db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QMS — Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  body {
    background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
  }
  .login-card {
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 20px;
    padding: 40px;
    width: 420px;
    box-shadow: 0 25px 45px rgba(0,0,0,0.3);
  }
  .logo-area { text-align: center; margin-bottom: 30px; }
  .logo-area h1 { color: #fff; font-weight: 700; font-size: 2rem; }
  .logo-area p  { color: #a0aec0; font-size: 0.9rem; }
  .logo-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 16px;
    width: 70px; height: 70px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 15px;
    font-size: 1.8rem; color: white;
  }
  .form-control {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff; border-radius: 10px; padding: 12px 15px;
  }
  .form-control:focus {
    background: rgba(255,255,255,0.12);
    border-color: #667eea; color: #fff;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.2);
  }
  .form-control::placeholder { color: #718096; }
  .form-label { color: #cbd5e0; font-size: 0.9rem; }
  .btn-login {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none; border-radius: 10px;
    padding: 12px; font-size: 1rem; font-weight: 600;
    width: 100%; color: white; transition: all 0.3s;
  }
  .btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102,126,234,0.4);
    color: white;
  }
  .demo-info {
    background: rgba(102,126,234,0.15);
    border: 1px solid rgba(102,126,234,0.3);
    border-radius: 10px; padding: 15px; margin-top: 20px;
  }
  .demo-info p { color: #a0aec0; font-size: 0.82rem; margin: 2px 0; }
  .demo-info strong { color: #667eea; }
  .dept-badge {
    background: rgba(102,126,234,0.2);
    color: #667eea; border-radius: 20px;
    padding: 3px 12px; font-size: 0.75rem;
    display: inline-block; margin-bottom: 10px;
  }
</style>
</head>
<body>
<div class="login-card">
  <div class="logo-area">
    <span class="dept-badge">DEPARTMENT OF COMPUTER SCIENCE</span>
    <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
    <h1>QMS</h1>
    <p>Quiz Management System — AUST</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger" style="border-radius:10px; font-size:0.9rem;">
    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
  </div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label"><i class="fas fa-envelope me-2"></i>Email Address</label>
      <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
    </div>
    <div class="mb-4">
      <label class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
      <input type="password" name="password" class="form-control" placeholder="Enter password" required>
    </div>
    <button type="submit" class="btn btn-login">
      <i class="fas fa-sign-in-alt me-2"></i>Login to QMS
    </button>
  </form>

  <div class="demo-info">
    <p><i class="fas fa-info-circle me-1"></i> <strong>Demo Credentials:</strong></p>
    <p><strong>Admin:</strong> admin@qms.com / password</p>
    <p><strong>Student:</strong> sami@qms.com / password</p>
  </div>
</div>
</body>
</html>
