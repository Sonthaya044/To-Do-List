<?php
// อ่านการตั้งค่า DB จาก environment variables (สำหรับ Vercel)
$host = getenv('DB_HOST') ?: "fdb1032.awardspace.net";
$user = getenv('DB_USER') ?: "4681536_todoapp";
$pass = getenv('DB_PASS') ?: "Sontaya123";
$db   = getenv('DB_NAME') ?: "4681536_todoapp";

// สร้างการเชื่อมต่อ
$conn = new mysqli($host, $user, $pass, $db);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่า Timezone และ Character Set
$conn->set_charset("utf8");
mysqli_query($conn, "SET time_zone = '+07:00'");

?>