<?php
include 'db.php';
session_start();

// teachers only
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// getting exam_id
if (empty($_GET['exam_id']) || !ctype_digit($_GET['exam_id'])) {
    header('Location: teacher.php');
    exit;
}
$exam_id = (int)$_GET['exam_id'];

// assigning seats based on POST form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seats'])) {
    $upsert = $pdo->prepare(
        'INSERT INTO seating (exam_id, student_id, seat_label)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE seat_label = VALUES(seat_label)'
    );
    foreach ($_POST['seats'] as $student_id => $label) {
        $label = trim($label);
        if ($label === '') continue;
        $upsert->execute([$exam_id, (int)$student_id, $label]);
    }
    header("Location: seating.php?exam_id={$exam_id}&status=saved");
    exit;
}

// getting exam's information
$examStmt = $pdo->prepare(
    'SELECT c.course_name, e.exam_date FROM exams e JOIN courses c ON e.course_id=c.course_id WHERE e.exam_id=?'
);
$examStmt->execute([$exam_id]);
$exam = $examStmt->fetch();
if (!$exam) {
    header('Location: teacher.php');
    exit;
}

// enrolled students in course?
$stuStmt = $pdo->prepare(
    'SELECT u.user_id, u.name
     FROM users u
     JOIN enrollments en ON en.student_id=u.user_id
     JOIN exams e       ON e.course_id=en.course_id
     WHERE e.exam_id = ?
     ORDER BY u.name'
);
$stuStmt->execute([$exam_id]);
$students = $stuStmt->fetchAll();

// existing seating
$seatStmt = $pdo->prepare('SELECT student_id, seat_label FROM seating WHERE exam_id = ?');
$seatStmt->execute([$exam_id]);
$existing = [];
foreach ($seatStmt->fetchAll() as $row) {
    $existing[$row['student_id']] = $row['seat_label'];
}

include 'includes/header.php';
?>
<div class="container">
  <h2>Seating for <?= htmlspecialchars($exam['course_name']) ?> (<?= htmlspecialchars($exam['exam_date']) ?>)</h2>
  <?php if (isset($_GET['status']) && $_GET['status']==='saved'): ?>
    <div style="background:#d4edda;color:#155724;padding:10px;border-radius:4px;margin-bottom:20px;">
      Seat assignments saved.
    </div>
  <?php endif; ?>
  <form method="post">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr>
          <th style="border:1px solid #ddd;padding:8px;">Student</th>
          <th style="border:1px solid #ddd;padding:8px;">Seat Label</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $stu): ?>
        <tr style="background:<?= ($loopIndex++ % 2 ? '#fafafa' : '#fff') ?>;">
          <td style="border:1px solid #ddd;padding:8px;"><?= htmlspecialchars($stu['name']) ?></td>
          <td style="border:1px solid #ddd;padding:8px;">
            <input
              type="text"
              name="seats[<?= $stu['user_id'] ?>]"
              value="<?= htmlspecialchars($existing[$stu['user_id']] ?? '') ?>"
              style="width:100%;padding:6px;border:1px solid #ccc;border-radius:4px;"
            >
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button type="submit" class="btn">Save Seating</button>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
