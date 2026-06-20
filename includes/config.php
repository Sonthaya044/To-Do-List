<?php
$host = "fdb1032.awardspace.net"; 
$user = "4681536_todoapp";      
$pass = "Sontaya123";           
$db   = "4681536_todoapp";       

// สร้างการเชื่อมต่อ
$conn = new mysqli($host, $user, $pass, $db);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่า Timezone และ Character Set (เหมือนเดิม)
$conn->set_charset("utf8");
mysqli_query($conn, "SET time_zone = '+07:00'");

?>