<?php
include 'db.php';
session_start();

// teachers and students can access
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id   = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if (empty($_GET['exam_id']) || !ctype_digit($_GET['exam_id'])) {
    header('Location: teacher.php');
    exit;
}
$exam_id = (int)$_GET['exam_id'];

// getting exam & course
$examStmt = $pdo->prepare(
    'SELECT e.course_id, c.course_name, e.exam_date
     FROM exams e
     JOIN courses c ON e.course_id=c.course_id
     WHERE e.exam_id=?'
);
$examStmt->execute([$exam_id]);
$exam = $examStmt->fetch();
if (!$exam) {
    header('Location: teacher.php'); exit;
}
$course_id = $exam['course_id'];

// handing comments by teachers (POST METHOD)
if ($user_type === 'teacher' && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['comments'])) {
    $upsertStmt = $pdo->prepare(
        'INSERT INTO feedback (exam_id, student_id, teacher_id, comment)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE comment = VALUES(comment), created_at = CURRENT_TIMESTAMP'
    );
    foreach ($_POST['comments'] as $student_id => $comment) {
        $trimmed = trim($comment);
        if ($trimmed === '') continue;
        $upsertStmt->execute([$exam_id, $student_id, $user_id, $trimmed]);
    }
    header("Location: feedback.php?exam_id={$exam_id}&status=saved"); exit;
}

// getting enrolled students
$studentsStmt = $pdo->prepare(
    'SELECT u.user_id, u.name
     FROM users u
     JOIN enrollments en ON en.student_id = u.user_id
     WHERE en.course_id = ?
     ORDER BY u.name'
);
$studentsStmt->execute([$course_id]);
$students = $studentsStmt->fetchAll();

// getexisting feedback for this exam
$fbStmt = $pdo->prepare(
    'SELECT student_id, comment FROM feedback WHERE exam_id = ?'
);
$fbStmt->execute([$exam_id]);
$existing = [];
foreach ($fbStmt->fetchAll() as $row) {
    $existing[$row['student_id']] = $row['comment'];
}

// display
include 'includes/header.php';
?>
<div class="container">
  <h2>Feedback for <?= htmlspecialchars($exam['course_name']); ?> (<?= htmlspecialchars($exam['exam_date']); ?>)</h2>

  <?php if (isset($_GET['status']) && $_GET['status']==='saved'): ?>
    <div class="alert" style="background:#d1ecf1;color:#0c5460;padding:10px;border-radius:4px;">
      Comments saved successfully.
    </div>
  <?php endif; ?>

  <?php if ($user_type === 'teacher'): ?>
    <form method="post">
      <table class="exam-table">
        <thead><tr><th>Student</th><th>Comment</th></tr></thead>
        <tbody>
          <?php foreach ($students as $stu): ?>
          <tr>
            <td><?= htmlspecialchars($stu['name']); ?></td>
            <td>
              <textarea name="comments[<?= $stu['user_id']; ?>]" rows="2" style="width:100%; padding:6px;"><?= htmlspecialchars($existing[$stu['user_id']] ?? ''); ?></textarea>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <button type="submit" class="btn">Save Comments</button>
    </form>
  <?php else: ?>
    <table class="exam-table">
      <thead><tr><th>Student</th><th>Comment</th></tr></thead>
      <tbody>
        <?php foreach ($students as $stu): ?>
        <tr>
          <td><?= htmlspecialchars($stu['name']); ?></td>
          <td><?= nl2br(htmlspecialchars($existing[$stu['user_id']] ?? '—')); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
