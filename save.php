<?php
session_start();
include "includes/config.php";

// ตรวจสอบว่าเป็น POST request และ login อยู่หรือไม่
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_SESSION['username'])) {
    header("Location: home.php");
    exit;
}

// รับค่าจากฟอร์ม
$username = $_SESSION['username'];
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$color = $_POST['color'] ?? '#fff7b0';

// รับค่าที่เพิ่มเข้ามา (วันที่ และ ความสำคัญ)
$due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
$priority = $_POST['priority'] ?? 'Medium';

// --- เปลี่ยนมาใช้ Prepared Statements เพื่อป้องกัน SQL Injection ---

// 1. เตรียมคำสั่ง SQL โดยใช้เครื่องหมาย '?' เป็น placeholder
$stmt = $conn->prepare("INSERT INTO notes (username, title, content, color, due_date, priority) VALUES (?, ?, ?, ?, ?, ?)");

// 2. ผูกตัวแปรเข้ากับ placeholder (s = string)
// "ssssss" หมายถึงตัวแปรทั้ง 6 ตัวเป็นชนิด string
$stmt->bind_param("ssssss", $username, $title, $content, $color, $due_date, $priority);

// 3. สั่งให้ SQL ทำงาน
if ($stmt->execute()) {
    // บันทึกสำเร็จ → กลับไป home.php
    header("Location: home.php");
} else {
    echo "Error: " . $stmt->error;
}

// 4. ปิดการเชื่อมต่อ
$stmt->close();
$conn->close();
?>