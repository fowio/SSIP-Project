<?php
include 'db.php';
include 'includes/header.php';

// students only
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
?>
<?php



// grab filter inputs
$searchCourse = $_GET['search_course'] ?? '';
$fromDate     = $_GET['from_date']     ?? '';
$toDate       = $_GET['to_date']       ?? '';
?>

<div class="container">
  <?php if ($_SESSION['user_type'] === 'student'): ?>
    <a href="enroll.php" class="btn" style="margin-bottom: 20px;">
      Enroll in Courses
    </a>
  <?php endif; ?>

<div class="container">
  <h2>Upcoming Exams</h2>

  <!-- filtering form -->
  <form method="get" class="mb-3">
    <label>
      Course:
      <input
        type="text"
        name="search_course"
        value="<?= htmlspecialchars($searchCourse) ?>"
        placeholder="e.g. Math"
      >
    </label>
    <label>
      From:
      <input type="date" name="from_date" value="<?= $fromDate ?>">
    </label>
    <label>
      To:
      <input type="date" name="to_date" value="<?= $toDate ?>">
    </label>
    <button type="submit" class="btn">Filter</button>
    <a href="student.php" class="btn">Clear</a>
  </form>

  <?php
// get upcoming exams
$stmt = $pdo->prepare(
    'SELECT e.exam_id, c.course_name, e.exam_date, e.location
       FROM exams e
       JOIN courses c ON e.course_id = c.course_id
       JOIN enrollments en ON en.course_id = c.course_id
      WHERE en.student_id = ?
        AND e.exam_date >= CURDATE()
      ORDER BY e.exam_date ASC'
  );
  $stmt->execute([$_SESSION['user_id']]);
  $upcoming = $stmt->fetchAll();
  
  // find any exams in next 3 days
  $soon = [];
  $today     = new DateTime();
  $threshold = (new DateTime())->modify('+3 days');
  foreach ($upcoming as $exam) {
      $d = new DateTime($exam['exam_date']);
      if ($d >= $today && $d <= $threshold) {
          $soon[] = $exam;
      }
  }
  ?>

<?php
// course name filter
if ($searchCourse !== '') {
    $where[]    = 'c.course_name LIKE ?';
    $params[]   = "%{$searchCourse}%";
}

// date range filter
if ($fromDate !== '') {
    $where[]    = 'e.exam_date >= ?';
    $params[]   = $fromDate;
}
if ($toDate !== '') {
    $where[]    = 'e.exam_date <= ?';
    $params[]   = $toDate;
}

$sql = "
  SELECT e.exam_id, c.course_name, e.exam_date, e.location
    FROM exams e
    JOIN courses c       ON e.course_id = c.course_id
    JOIN enrollments en  ON en.course_id = c.course_id
   WHERE en.student_id = ?      -- only exams for courses they’re in
     AND e.exam_date  >= CURDATE()
   ORDER BY e.exam_date ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$upcoming = $stmt->fetchAll();

// get past exams with scores
$stmt = $pdo->prepare(
    'SELECT c.course_name, e.exam_date, se.score
     FROM student_exams se
     JOIN exams e ON se.exam_id = e.exam_id
     JOIN courses c ON e.course_id = c.course_id
     WHERE se.student_id = ?
     ORDER BY e.exam_date DESC'
);
$stmt->execute([$user_id]);
$past = $stmt->fetchAll();
?>

<?php if (!empty($soon)): ?>
  <div style="
    background: #fff3cd;
    border: 1px solid #ffeeba;
    padding: 12px 20px;
    border-radius: 4px;
    margin-bottom: 20px;
    color: #856404;
    font-family: Arial, sans-serif;
  ">
    <strong>Heads up!</strong>
    You have <?= count($soon) ?> exam<?= count($soon)>1?'s':'' ?> coming up 
    <?php if (count($soon) === 1): ?>
      on <?= htmlspecialchars($soon[0]['exam_date']) ?>: 
      <?= htmlspecialchars($soon[0]['course_name']) ?>.
    <?php else: ?>
      between today and <?= $threshold->format('Y-m-d') ?>.
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="container">
    <h2>Upcoming Exams</h2>
    <?php if ($upcoming): ?>
        <table>
            <tr><th>Course</th><th>Date</th><th>Location</th></tr>
            <?php foreach ($upcoming as $exam): ?>
                <tr>
                    <td><?= htmlspecialchars($exam['course_name']) ?></td>
                    <td><?= htmlspecialchars($exam['exam_date']) ?></td>
                    <td><?= htmlspecialchars($exam['location']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No upcoming exams.</p>
    <?php endif; ?>

    <h2>Past Results</h2>
    <?php if ($past): ?>
        <table>
            <tr><th>Course</th><th>Date</th><th>Score</th></tr>
            <?php foreach ($past as $res): ?>
                <tr>
                    <td><?= htmlspecialchars($res['course_name']) ?></td>
                    <td><?= htmlspecialchars($res['exam_date']) ?></td>
                    <td><?= htmlspecialchars($res['score']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No past results found.</p>
    <?php endif; ?>
</div>


<?php include 'includes/footer.php'; ?>