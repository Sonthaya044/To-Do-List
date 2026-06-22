<<<<<<< HEAD
<?php
// delete.php
session_start();
include "includes/config.php";

// ตรวจสอบว่า login อยู่หรือไม่ และมี id ส่งมาหรือไม่
if (!isset($_SESSION['username']) || !isset($_GET['id'])) {
    header('Location: home.php');
    exit;
}

$note_id = $_GET['id'];
$username = $_SESSION['username'];

// เตรียม SQL เพื่อลบข้อมูล โดยตรวจสอบให้แน่ใจว่าโน้ตเป็นของผู้ใช้ที่ login อยู่
// เพื่อความปลอดภัยสูงสุด!
$stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND username = ?");
$stmt->bind_param("is", $note_id, $username);

// สั่งให้ SQL ทำงาน
if ($stmt->execute()) {
    // ลบสำเร็จ กลับไปหน้าแรก
    header("Location: home.php");
} else {
    echo "Error deleting record: " . $conn->error;
}

$stmt->close();
$conn->close();
=======
<?php
// delete.php
session_start();
require_once __DIR__ . "/includes/config.php";

// ตรวจสอบว่า login อยู่หรือไม่ และมี id ส่งมาหรือไม่
if (!isset($_SESSION['username']) || !isset($_GET['id'])) {
    header('Location: home.php');
    exit;
}

$note_id = $_GET['id'];
$username = $_SESSION['username'];

// เตรียม SQL เพื่อลบข้อมูล โดยตรวจสอบให้แน่ใจว่าโน้ตเป็นของผู้ใช้ที่ login อยู่
// เพื่อความปลอดภัยสูงสุด!
$stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND username = ?");
$stmt->bind_param("is", $note_id, $username);

// สั่งให้ SQL ทำงาน
if ($stmt->execute()) {
    // ลบสำเร็จ กลับไปหน้าแรก
    header("Location: home.php");
} else {
    echo "Error deleting record: " . $conn->error;
}

$stmt->close();
$conn->close();
>>>>>>> 9d61535 (Prepare Vercel PHP deploy: add vercel.json and fix config includes)
?>