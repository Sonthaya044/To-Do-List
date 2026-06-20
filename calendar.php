<?php
session_start();
include "includes/config.php";

if (!isset($_SESSION['username'])) {
    header("Location: auth.php");
    exit;
}

// --- ส่วนที่ 1: การคำนวณปฏิทิน ---
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// สร้าง timestamp ของวันแรกในเดือนที่เลือก
$first_day_timestamp = mktime(0, 0, 0, $month, 1, $year);
$month_name = date('F', $first_day_timestamp);
$days_in_month = date('t', $first_day_timestamp);
// 'w' ให้วันของสัปดาห์ (0=Sun, 1=Mon, ..., 6=Sat)
$first_day_of_week = date('w', $first_day_timestamp);

// คำนวณเดือนก่อนหน้าและเดือนถัดไป
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// --- ส่วนที่ 2: ดึงข้อมูล Notes สำหรับเดือนที่แสดงผล ---
$events = [];
$stmt_calendar = $conn->prepare("SELECT title, due_date, priority, is_completed 
                                 FROM notes 
                                 WHERE username = ? AND MONTH(due_date) = ? AND YEAR(due_date) = ?");
$stmt_calendar->bind_param("sii", $_SESSION['username'], $month, $year);
$stmt_calendar->execute();
$result_calendar = $stmt_calendar->get_result();
while ($row = $result_calendar->fetch_assoc()) {
    // จัดกลุ่ม event ตามวันที่
    $day = date('j', strtotime($row['due_date'])); // 'j' คือวันที่แบบไม่มี 0 นำหน้า (1-31)
    $events[$day][] = $row;
}
$stmt_calendar->close();


// --- ส่วนที่ 3: ดึงข้อมูลสำหรับ Sidebar (เหมือนหน้าอื่น) ---
$high_priority_count = $medium_priority_count = $low_priority_count = 0;
$stmt_all = $conn->prepare("SELECT priority, is_completed FROM notes WHERE username = ?");
$stmt_all->bind_param("s", $_SESSION['username']);
$stmt_all->execute();
$result_all = $stmt_all->get_result();
while ($row = $result_all->fetch_assoc()) {
    if ($row['is_completed'] == 0) {
        if ($row['priority'] === 'High') $high_priority_count++;
        elseif ($row['priority'] === 'Medium') $medium_priority_count++;
        elseif ($row['priority'] === 'Low') $low_priority_count++;
    }
}
$stmt_all->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Calendar - To Do App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<aside>
    <div>
        <h2>Menu</h2>
        <hr>
        <ul>
<li><a href="today.php">📍 Today</a></li>
    <li><a href="upcoming.php">⏳ Upcoming</a></li>
    <li><a href="calendar.php">🗓️ Calendar</a></li>
    <li><a href="home.php">📝 Sticky Wall</a></li>
        </ul>
        <div class="lists">
            <h3>Lists</h3>
            <ul>
                <li>🔴 High <span class="task-count"><?= $high_priority_count ?></span></li>
                <li>🟠 Medium <span class="task-count"><?= $medium_priority_count ?></span></li>
                <li>🟢 Low <span class="task-count"><?= $low_priority_count ?></span></li>
            </ul>
        </div>
    </div>
    <div class="bottom-links">
        <a href="auth.php?logout=1">🚪 Sign out</a>
    </div>
</aside>

<div class="main">
    <h1>📆 Calendar</h1>

    <div class="calendar-container">
        <div class="calendar-nav">
            <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="nav-arrow">&lt;</a>
            <span class="current-month"><?= $month_name ?> <?= $year ?></span>
            <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="nav-arrow">&gt;</a>
        </div>

        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php
                    // ช่องว่างก่อนวันที่ 1
                    for ($i = 0; $i < $first_day_of_week; $i++) {
                        echo "<td></td>";
                    }

                    // วันที่ในเดือน
                    $current_day = 1;
                    while ($current_day <= $days_in_month) {
                        // ถ้าเป็นวันอาทิตย์ (และไม่ใช่ช่องแรก) ให้ขึ้นแถวใหม่
                        if (($current_day + $first_day_of_week - 1) % 7 == 0 && $current_day != 1) {
                            echo "</tr><tr>";
                        }
                        
                        // ตรวจสอบว่าเป็นวันปัจจุบันหรือไม่
                        $today_class = (date('Y-m-d') == sprintf('%04d-%02d-%02d', $year, $month, $current_day)) ? ' today' : '';

                        echo "<td class='day-cell{$today_class}'>";
                        echo "<div class='day-number'>{$current_day}</div>";

                        // แสดง events ของวันนี้
                        if (isset($events[$current_day])) {
                            echo "<div class='events'>";
                            foreach ($events[$current_day] as $event) {
                                $completed_class = $event['is_completed'] ? ' completed' : '';
                                $priority_class = 'priority-' . strtolower($event['priority']);
                                echo "<div class='event {$priority_class}{$completed_class}'>" . htmlspecialchars($event['title']) . "</div>";
                            }
                            echo "</div>";
                        }

                        echo "</td>";
                        $current_day++;
                    }

                    // ช่องว่างหลังวันสุดท้าย
                    $remaining_cells = 7 - (($first_day_of_week + $days_in_month) % 7);
                    if($remaining_cells < 7) {
                        for ($i = 0; $i < $remaining_cells; $i++) {
                            echo "<td></td>";
                        }
                    }
                    ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>