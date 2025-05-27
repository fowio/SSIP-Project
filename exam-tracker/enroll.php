<?php
include 'db.php';
session_start();

//students only
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login.php');
    exit;
}
$student_id = $_SESSION['user_id'];
$errors = [];

//enrollment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['courses'])) {
    // Insert each selected course
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO enrollments (student_id, course_id) VALUES (?, ?)'
    );
    foreach ($_POST['courses'] as $cid) {
        $stmt->execute([$student_id, (int)$cid]);
    }
    header('Location: student.php');
    exit;
}

// get all courses
$courses = $pdo->query(
    'SELECT course_id, course_name FROM courses ORDER BY course_name'
)->fetchAll();
// get courses already enrolled
$enStmt = $pdo->prepare(
    'SELECT course_id FROM enrollments WHERE student_id = ?'
);
$enStmt->execute([$student_id]);
$enrolled = array_column($enStmt->fetchAll(), 'course_id');

include 'includes/header.php';
?>
<div class="container">
  <h2>Enroll in Courses</h2>
  <?php if ($errors): ?>
    <ul class="errors">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <form method="post">
    <?php foreach ($courses as $course): ?>
      <div>
        <label>
          <input type="checkbox"
                 name="courses[]"
                 value="<?= $course['course_id'] ?>"
                 <?= in_array($course['course_id'], $enrolled) ? 'checked' : '' ?>>
          <?= htmlspecialchars($course['course_name']) ?>
        </label>
      </div>
    <?php endforeach; ?>
    <button type="submit" class="btn">Save Enrollment</button>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
