<?php
session_start();
<<<<<<< HEAD
include "includes/config.php";
=======
require_once __DIR__ . "/includes/config.php";
>>>>>>> 9d61535 (Prepare Vercel PHP deploy: add vercel.json and fix config includes)

if (!isset($_SESSION['username'])) {
    header("Location: auth.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username']);

// --- ส่วนที่ 1: ดึงข้อมูลสำหรับแสดงผล "Upcoming" ---
$upcoming_notes = [];
$stmt_upcoming = $conn->prepare("SELECT id, title, content, color, due_date, priority, is_completed 
                                 FROM notes 
                                 WHERE username = ? 
                                   AND due_date > CURDATE() 
                                   AND due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) 
                                   AND is_completed = 0
                                 ORDER BY due_date ASC, FIELD(priority, 'High', 'Medium', 'Low'), id DESC");
$stmt_upcoming->bind_param("s", $_SESSION['username']);
$stmt_upcoming->execute();
$result_upcoming = $stmt_upcoming->get_result();

// จัดกลุ่ม notes ตาม due_date
$grouped_notes = [];
while ($row = $result_upcoming->fetch_assoc()) {
    $date = $row['due_date'];
    if (!isset($grouped_notes[$date])) {
        $grouped_notes[$date] = [];
    }
    $grouped_notes[$date][] = $row;
}
$stmt_upcoming->close();


// --- ส่วนที่ 2: ดึงข้อมูล "ทั้งหมด" มาเพื่อนับจำนวนใน Sidebar ---
$all_notes = [];
$stmt_all = $conn->prepare("SELECT priority, is_completed FROM notes WHERE username = ?");
$stmt_all->bind_param("s", $_SESSION['username']);
$stmt_all->execute();
$result_all = $stmt_all->get_result();
while ($row = $result_all->fetch_assoc()) {
    $all_notes[] = $row;
}
$stmt_all->close();

$high_priority_count = 0;
$medium_priority_count = 0;
$low_priority_count = 0;

foreach ($all_notes as $note) {
    if ($note['is_completed'] == 0) {
        if ($note['priority'] === 'High') {
            $high_priority_count++;
        } elseif ($note['priority'] === 'Medium') {
            $medium_priority_count++;
        } elseif ($note['priority'] === 'Low') {
            $low_priority_count++;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Upcoming - To Do App</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <aside>
      <div>
        <h2>Menu</h2>
        <hr>
        <ul class="menu">
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
    <h1>🗓️ Upcoming (3 Days)</h1>

    <div class="today-list">
      <?php if (empty($grouped_notes)): ?>
        <p style="text-align:center; margin-top: 20px;">✨ ยอดเยี่ยม! ไม่มีงานสำหรับ 3 วันข้างหน้า</p>
      <?php else: ?>
        <?php foreach ($grouped_notes as $date => $notes_on_date): ?>
            <div class="date-group">
                <h2><?= date("l, j F Y", strtotime($date)); ?></h2>
                <?php foreach ($notes_on_date as $note): ?>
                    <div class="today-note <?= $note['is_completed'] ? 'completed' : '' ?>" style="border-left-color: <?= htmlspecialchars($note['color']); ?>;">
                        
                        <div class="note-info">
                            <h3><?= htmlspecialchars($note['title']); ?></h3>
                            <?php if(!empty($note['content'])): ?>
                              <p><?= nl2br(htmlspecialchars($note['content'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="note-priority">
                            <span class="priority-tag priority-<?= htmlspecialchars($note['priority']) ?>">
                              <?= htmlspecialchars($note['priority']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  </body>
</html>