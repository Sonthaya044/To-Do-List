<<<<<<< HEAD
<?php
session_start();
include "includes/config.php";

if (!isset($_SESSION['username'])) {
    header("Location: auth.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username']);

// --- ส่วนดึงข้อมูลจากฐานข้อมูล (แก้ไขแล้ว) ---
$notes = [];
// แก้ไข: ดึงข้อมูลทุกคอลัมน์ที่จำเป็นเพื่อให้ข้อมูลสมบูรณ์
$stmt = $conn->prepare("SELECT id, username, note_type, title, content, due_date, priority, is_completed, color, created_at FROM notes WHERE username = ? ORDER BY id DESC");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notes[] = $row;
}
$stmt->close();
$conn->close();


$high_priority_count = 0;
$medium_priority_count = 0;
$low_priority_count = 0;

// 2. วนลูปใน array $notes ที่เราดึงมาแล้ว
foreach ($notes as $note) {
    // 3. เช็กว่างานนั้น "ยังไม่เสร็จ" ใช่หรือไม่
    if ($note['is_completed'] == 0) {
        // 4. เพิ่มค่าในตัวนับตามระดับความสำคัญของงานนั้นๆ
        if ($note['priority'] === 'High') {
            $high_priority_count++;
        } elseif ($note['priority'] === 'Medium') {
            $medium_priority_count++;
        } elseif ($note['priority'] === 'Low') {
            $low_priority_count++;
        }
    }
}

// --- สิ้นสุดส่วนดึงข้อมูล ---

?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Sticky Wall - To Do App</title>
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
    <h1>Sticky Wall</h1>
  
    <div class="grid">
      
      <?php foreach ($notes as $note): ?>
      <div class="note <?= $note['is_completed'] ? 'completed' : '' ?>" style="background-color: <?= htmlspecialchars($note['color']); ?>;">
        <h3><?= htmlspecialchars($note['title']); ?></h3>
        <p>
          <?= nl2br(htmlspecialchars($note['content'])); ?>
        </p>
        
        <div class="note-meta">
          <span>
            <?php if (!empty($note['due_date'])): ?>
              📅 <?= date("j M Y", strtotime($note['due_date'])); ?>
            <?php endif; ?>
          </span>
          <?php if (!empty($note['priority'])): ?>
            <span class="priority-tag priority-<?= htmlspecialchars($note['priority']) ?>">
              <?= htmlspecialchars($note['priority']) ?>
            </span>
          <?php endif; ?>
        </div>
        <div class="note-actions">
            <a href="#" class="edit-btn" onclick='openEditForm(<?= json_encode($note) ?>)'>แก้ไข</a>
            <a href="delete.php?id=<?= $note['id'] ?>" class="delete-btn" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบโน้ตนี้?')">ลบ</a>
        </div>
      </div>
      <?php endforeach; ?>
      
      
      <div class="note add-note" onclick="openForm()" >+</div>
    </div>

  <div  id="noteForm" class="modal-overlay"> <form action="save.php" method="post" class="modal-form"> <h3>เพิ่ม Note</h3>
        
        <label for="title-input">หัวข้อ</label>
        <input type="text" name="title" id="title-input" placeholder="Title" required>
        
        <label for="content-input">เนื้อหา</label>
        <textarea name="content" id="content-input" placeholder="Content"></textarea>
        
        <div class="form-row">
            <div>
                <label for="due_date">วันที่:</label>
                <input type="date" name="due_date" id="due_date">
            </div>
            <div>
                <label for="priority">ความสำคัญ:</label>
                <select name="priority" id="priority">
                    <option value="Low">น้อย</option>
                    <option value="Medium" selected>ปานกลาง</option>
                    <option value="High">สูง</option>
                </select>
            </div>
        </div>
        
        <label for="color-input">สี:</label>
        <input type="color" name="color" id="color-input" value="#fff7b0" style="width: 100%; height: 40px; padding: 0;">

        <div class="form-actions">
            <button type="button" onclick="closeForm()">ยกเลิก</button>
            <button type="submit">บันทึก</button>
        </div>
    </form>
</div>
<div id="editNoteForm" class="modal-overlay">
    <form action="update.php" method="post" class="modal-form">
        <h3>แก้ไข Note</h3>
        
        <input type="hidden" id="edit_note_id" name="note_id">
        
        <label for="edit_title">หัวข้อ</label>
        <input type="text" id="edit_title" name="title" placeholder="Title" required>
        
        <label for="edit_content">เนื้อหา</label>
        <textarea id="edit_content" name="content" placeholder="Content"></textarea>
        
        <div class="form-row">
            <div>
                <label for="edit_due_date">วันที่:</label>
                <input type="date" name="due_date" id="edit_due_date">
            </div>
            <div>
                <label for="edit_priority">ความสำคัญ:</label>
                <select name="priority" id="edit_priority">
                    <option value="Low">น้อย</option>
                    <option value="Medium">ปานกลาง</option>
                    <option value="High">สูง</option>
                </select>
            </div>
        </div>
        
        <label for="edit_color">สี:</label>
        <input type="color" id="edit_color" name="color" style="width: 100%; height: 40px; padding: 0;">

        <div class="form-actions">
            <button type="button" onclick="closeEditForm()">ยกเลิก</button>
            <button type="submit">บันทึกการเปลี่ยนแปลง</button>
        </div>
    </form>
</div>

  </div>
  <script>
    function openForm(){document.getElementById('noteForm').style.display='flex';}
    function closeForm(){document.getElementById('noteForm').style.display='none';}
    function closeEditForm() {document.getElementById('editNoteForm').style.display = 'none';}

    function openEditForm(note) {
      document.getElementById('edit_note_id').value = note.id;
      document.getElementById('edit_title').value = note.title;
      document.getElementById('edit_content').value = note.content;
      document.getElementById('edit_color').value = note.color;
      document.getElementById('edit_due_date').value = note.due_date;
      document.getElementById('edit_priority').value = note.priority;
      
      document.getElementById('editNoteForm').style.display = 'flex';
    }
  </script>

</body>
=======
<?php
session_start();
require_once __DIR__ . "/includes/config.php";

if (!isset($_SESSION['username'])) {
    header("Location: auth.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username']);

// --- ส่วนดึงข้อมูลจากฐานข้อมูล (แก้ไขแล้ว) ---
$notes = [];
// แก้ไข: ดึงข้อมูลทุกคอลัมน์ที่จำเป็นเพื่อให้ข้อมูลสมบูรณ์
$stmt = $conn->prepare("SELECT id, username, note_type, title, content, due_date, priority, is_completed, color, created_at FROM notes WHERE username = ? ORDER BY id DESC");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notes[] = $row;
}
$stmt->close();
$conn->close();


$high_priority_count = 0;
$medium_priority_count = 0;
$low_priority_count = 0;

// 2. วนลูปใน array $notes ที่เราดึงมาแล้ว
foreach ($notes as $note) {
    // 3. เช็กว่างานนั้น "ยังไม่เสร็จ" ใช่หรือไม่
    if ($note['is_completed'] == 0) {
        // 4. เพิ่มค่าในตัวนับตามระดับความสำคัญของงานนั้นๆ
        if ($note['priority'] === 'High') {
            $high_priority_count++;
        } elseif ($note['priority'] === 'Medium') {
            $medium_priority_count++;
        } elseif ($note['priority'] === 'Low') {
            $low_priority_count++;
        }
    }
}

// --- สิ้นสุดส่วนดึงข้อมูล ---

?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Sticky Wall - To Do App</title>
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
    <h1>Sticky Wall</h1>
  
    <div class="grid">
      
      <?php foreach ($notes as $note): ?>
      <div class="note <?= $note['is_completed'] ? 'completed' : '' ?>" style="background-color: <?= htmlspecialchars($note['color']); ?>;">
        <h3><?= htmlspecialchars($note['title']); ?></h3>
        <p>
          <?= nl2br(htmlspecialchars($note['content'])); ?>
        </p>
        
        <div class="note-meta">
          <span>
            <?php if (!empty($note['due_date'])): ?>
              📅 <?= date("j M Y", strtotime($note['due_date'])); ?>
            <?php endif; ?>
          </span>
          <?php if (!empty($note['priority'])): ?>
            <span class="priority-tag priority-<?= htmlspecialchars($note['priority']) ?>">
              <?= htmlspecialchars($note['priority']) ?>
            </span>
          <?php endif; ?>
        </div>
        <div class="note-actions">
            <a href="#" class="edit-btn" onclick='openEditForm(<?= json_encode($note) ?>)'>แก้ไข</a>
            <a href="delete.php?id=<?= $note['id'] ?>" class="delete-btn" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบโน้ตนี้?')">ลบ</a>
        </div>
      </div>
      <?php endforeach; ?>
      
      
      <div class="note add-note" onclick="openForm()" >+</div>
    </div>

  <div  id="noteForm" class="modal-overlay"> <form action="save.php" method="post" class="modal-form"> <h3>เพิ่ม Note</h3>
        
        <label for="title-input">หัวข้อ</label>
        <input type="text" name="title" id="title-input" placeholder="Title" required>
        
        <label for="content-input">เนื้อหา</label>
        <textarea name="content" id="content-input" placeholder="Content"></textarea>
        
        <div class="form-row">
            <div>
                <label for="due_date">วันที่:</label>
                <input type="date" name="due_date" id="due_date">
            </div>
            <div>
                <label for="priority">ความสำคัญ:</label>
                <select name="priority" id="priority">
                    <option value="Low">น้อย</option>
                    <option value="Medium" selected>ปานกลาง</option>
                    <option value="High">สูง</option>
                </select>
            </div>
        </div>
        
        <label for="color-input">สี:</label>
        <input type="color" name="color" id="color-input" value="#fff7b0" style="width: 100%; height: 40px; padding: 0;">

        <div class="form-actions">
            <button type="button" onclick="closeForm()">ยกเลิก</button>
            <button type="submit">บันทึก</button>
        </div>
    </form>
</div>
<div id="editNoteForm" class="modal-overlay">
    <form action="update.php" method="post" class="modal-form">
        <h3>แก้ไข Note</h3>
        
        <input type="hidden" id="edit_note_id" name="note_id">
        
        <label for="edit_title">หัวข้อ</label>
        <input type="text" id="edit_title" name="title" placeholder="Title" required>
        
        <label for="edit_content">เนื้อหา</label>
        <textarea id="edit_content" name="content" placeholder="Content"></textarea>
        
        <div class="form-row">
            <div>
                <label for="edit_due_date">วันที่:</label>
                <input type="date" name="due_date" id="edit_due_date">
            </div>
            <div>
                <label for="edit_priority">ความสำคัญ:</label>
                <select name="priority" id="edit_priority">
                    <option value="Low">น้อย</option>
                    <option value="Medium">ปานกลาง</option>
                    <option value="High">สูง</option>
                </select>
            </div>
        </div>
        
        <label for="edit_color">สี:</label>
        <input type="color" id="edit_color" name="color" style="width: 100%; height: 40px; padding: 0;">

        <div class="form-actions">
            <button type="button" onclick="closeEditForm()">ยกเลิก</button>
            <button type="submit">บันทึกการเปลี่ยนแปลง</button>
        </div>
    </form>
</div>

  </div>
  <script>
    function openForm(){document.getElementById('noteForm').style.display='flex';}
    function closeForm(){document.getElementById('noteForm').style.display='none';}
    function closeEditForm() {document.getElementById('editNoteForm').style.display = 'none';}

    function openEditForm(note) {
      document.getElementById('edit_note_id').value = note.id;
      document.getElementById('edit_title').value = note.title;
      document.getElementById('edit_content').value = note.content;
      document.getElementById('edit_color').value = note.color;
      document.getElementById('edit_due_date').value = note.due_date;
      document.getElementById('edit_priority').value = note.priority;
      
      document.getElementById('editNoteForm').style.display = 'flex';
    }
  </script>

</body>
>>>>>>> 9d61535 (Prepare Vercel PHP deploy: add vercel.json and fix config includes)
</html>