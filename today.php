<?php
session_start();
include "includes/config.php";

if (!isset($_SESSION['username'])) {
    header("Location: auth.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username']);

// --- ส่วนที่ 1: ดึงข้อมูลสำหรับแสดงผล "วันนี้" ---
$today_notes = [];
$completed_count = 0; // สร้างตัวแปรสำหรับนับงานที่เสร็จแล้ว
$stmt_today = $conn->prepare("SELECT id, title, content, color, due_date, priority, is_completed 
                             FROM notes 
                             WHERE username = ? AND due_date = CURDATE() 
                             ORDER BY is_completed ASC, FIELD(priority, 'High', 'Medium', 'Low'), id DESC");
$stmt_today->bind_param("s", $_SESSION['username']);
$stmt_today->execute();
$result_today = $stmt_today->get_result();
while ($row = $result_today->fetch_assoc()) {
    $today_notes[] = $row;
    // --- เพิ่มเข้ามา: นับจำนวนงานที่เสร็จแล้วสำหรับปุ่มลบ ---
    if ($row['is_completed']) {
        $completed_count++;
    }
}
$stmt_today->close();


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
  <title>Today - To Do App</title>
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
    <h1>📅 Today <span style="font-weight:normal; color:#888;"><?= date("l, j F") ?></span></h1>
    
    <?php if ($completed_count > 0): ?>
    <div class="delete-completed-wrapper">
        <form action="delete_completed.php" method="POST" onsubmit="return confirm('คุณต้องการลบงานที่เสร็จแล้วทั้งหมดใช่หรือไม่?');">
            <button type="submit" class="btn-delete-completed">
                ลบงานที่เสร็จแล้ว (<?= $completed_count ?>)
            </button>
        </form>
    </div>
    <?php endif; ?>

    <div class="today-list">
      <?php if (empty($today_notes)): ?>
        <p style="text-align:center; margin-top: 20px;">🎉 ยอดเยี่ยม! ไม่มีรายการสำหรับวันนี้</p>
      <?php else: ?>
        <?php foreach ($today_notes as $note): ?>
            <div class="today-note <?= $note['is_completed'] ? 'completed' : '' ?>" style="border-left-color: <?= htmlspecialchars($note['color']); ?>;">
                <div class="note-status">
                    <input type="checkbox" class="task-checkbox" data-note-id="<?= $note['id'] ?>" <?= $note['is_completed'] ? 'checked' : '' ?>>
                </div>
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
      <?php endif; ?>
    </div>
  </div>
  
  <script>
    document.querySelectorAll('.task-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const noteId = this.dataset.noteId;
            const isCompleted = this.checked;
            
            // ส่งข้อมูลไปอัปเดตสถานะ (ไม่ต้องรอหน้าโหลดใหม่)
            fetch('toggle_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ note_id: noteId, status: isCompleted })
            })
            .then(response => {
                if(response.ok) {
                    // ถ้าสำเร็จ ให้โหลดหน้าเว็บใหม่เพื่ออัปเดตปุ่ม "ลบ" และการจัดเรียง
                    window.location.reload(); 
                } else {
                    alert('เกิดข้อผิดพลาดในการอัปเดตสถานะ');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
  </script>
</body>
</html>