<?php
session_start();
include "includes/config.php";

// ถ้ามีการ Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: auth.php");
    exit;
}

// ถ้า login แล้วไม่ควรเข้ามาหน้านี้
if (isset($_SESSION['username'])) {
    header("Location: home.php");
    exit;
}

$message = "";

// ✅ Register
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $email    = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    if (empty($email) || empty($username) || empty($password) || empty($confirm)) {
        $message = "❌ กรุณากรอกข้อมูลให้ครบถ้วน";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ อีเมลไม่ถูกต้อง";
    } elseif (strlen($username) < 4) {
        $message = "❌ ชื่อผู้ใช้ต้องมีอย่างน้อย 4 ตัวอักษร";
    } elseif (strlen($password) < 8) {
        $message = "❌ รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";
    } elseif ($password !== $confirm) {
        $message = "❌ รหัสผ่านไม่ตรงกัน";
    } else {
        // ตรวจสอบซ้ำ
        $check = $conn->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $check->bind_param("ss", $email, $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "❌ อีเมลหรือชื่อผู้ใช้นี้ถูกใช้แล้ว";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (email, username, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $email, $username, $hashed);

            if ($stmt->execute()) {
                $message = "✅ สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
            } else {
                $message = "❌ เกิดข้อผิดพลาด: " . htmlspecialchars($conn->error);
            }
        }
    }
}

// ✅ Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username=? OR email=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['username'];

            header("Location: home.php");
            exit;
        } else {
            $message = "❌ รหัสผ่านไม่ถูกต้อง!";
        }
    } else {
        $message = "❌ ไม่พบผู้ใช้นี้!";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Login & Register</title>
    <link rel="stylesheet" href="style.css"><link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script>
        function toggleForm(id) {
            document.getElementById('loginForm').style.display = id === 'login' ? 'block' : 'none';
            document.getElementById('registerForm').style.display = id === 'register' ? 'block' : 'none';
        }
    </script>
</head>
<body data-aos="fade-right" class="auth-page">

    <a href="index.php" class="auth-back-button-top-left">← กลับไปหน้าแรก</a>

    <div class="auth-container">
        <h2>To-Do List</h2>

        <?php if (!empty($message)): ?>
            <?php $message_class = strpos($message, '✅') !== false ? 'success' : 'error'; ?>
            <div class="auth-message <?= $message_class ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" class="auth-form" method="POST" style="display:block;">
            <h3>เข้าสู่ระบบ</h3>
            <input type="text" name="username" placeholder="ชื่อผู้ใช้ หรือ อีเมล" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <button type="submit" name="login">เข้าสู่ระบบ</button>
            <p>ยังไม่มีบัญชี? <span class="auth-toggle" onclick="toggleForm('register')">สมัครสมาชิก</span></p>
        </form>

        <form id="registerForm" class="auth-form" method="POST" style="display:none;">
            <h3>สมัครสมาชิก</h3>
            <input type="email" name="email" placeholder="อีเมล" required>
            <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" required>
            <button type="submit" name="register">สมัครสมาชิก</button>
            <p>มีบัญชีแล้ว? <span class="auth-toggle" onclick="toggleForm('login')">เข้าสู่ระบบ</span></p>
        </form>
    </div>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init();
</script>
</body>
</html>
