<<<<<<< HEAD
<?php
// update.php
session_start();
include "includes/config.php";

// ตรวจสอบว่าเป็น POST request และ login อยู่หรือไม่
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_SESSION['username'])) {
    header('Location: home.php');
    exit;
}

// รับข้อมูลจากฟอร์ม
$note_id = $_POST['note_id'];
$title = $_POST['title'];
$content = $_POST['content'];
$color = $_POST['color'];
$username = $_SESSION['username'];

// --- ส่วนที่เพิ่มเข้ามา ---
$due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
$priority = $_POST['priority'];
// --- สิ้นสุดส่วนที่เพิ่มเข้ามา ---


// --- แก้ไข SQL ---
// เตรียม SQL เพื่ออัปเดตข้อมูลทั้งหมด และตรวจสอบว่าเป็นเจ้าของโน้ตจริง
$stmt = $conn->prepare("UPDATE notes SET title=?, content=?, color=?, due_date=?, priority=? WHERE id=? AND username=?");

// --- แก้ไข bind_param ---
$stmt->bind_param("sssssis", $title, $content, $color, $due_date, $priority, $note_id, $username);


// สั่งให้ SQL ทำงาน
if ($stmt->execute()) {
    // อัปเดตสำเร็จ กลับไปหน้าแรก
    header("Location: home.php");
} else {
    echo "Error updating record: " . $conn->error;
}

$stmt->close();
$conn->close();
=======
<?php
// update.php
session_start();
require_once __DIR__ . "/includes/config.php";

// ตรวจสอบว่าเป็น POST request และ login อยู่หรือไม่
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_SESSION['username'])) {
    header('Location: home.php');
    exit;
}

// รับข้อมูลจากฟอร์ม
$note_id = $_POST['note_id'];
$title = $_POST['title'];
$content = $_POST['content'];
$color = $_POST['color'];
$username = $_SESSION['username'];

// --- ส่วนที่เพิ่มเข้ามา ---
$due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
$priority = $_POST['priority'];
// --- สิ้นสุดส่วนที่เพิ่มเข้ามา ---


// --- แก้ไข SQL ---
// เตรียม SQL เพื่ออัปเดตข้อมูลทั้งหมด และตรวจสอบว่าเป็นเจ้าของโน้ตจริง
$stmt = $conn->prepare("UPDATE notes SET title=?, content=?, color=?, due_date=?, priority=? WHERE id=? AND username=?");

// --- แก้ไข bind_param ---
$stmt->bind_param("sssssis", $title, $content, $color, $due_date, $priority, $note_id, $username);


// สั่งให้ SQL ทำงาน
if ($stmt->execute()) {
    // อัปเดตสำเร็จ กลับไปหน้าแรก
    header("Location: home.php");
} else {
    echo "Error updating record: " . $conn->error;
}

$stmt->close();
$conn->close();
>>>>>>> 9d61535 (Prepare Vercel PHP deploy: add vercel.json and fix config includes)
?>