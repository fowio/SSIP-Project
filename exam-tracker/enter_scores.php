<?php
include 'db.php';
session_start();

// access to teachers only
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// checkexam_id
if (empty($_GET['exam_id']) || !ctype_digit($_GET['exam_id'])) {
    header('Location: teacher.php');
    exit;
}
$exam_id = (int)$_GET['exam_id'];

// form submissions handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scores'])) {
    $insertStmt = $pdo->prepare(
        'INSERT INTO student_exams (student_id, exam_id, score) VALUES (?, ?, ?)'
    );
    $updateStmt = $pdo->prepare(
        'UPDATE student_exams SET score = ? WHERE student_id = ? AND exam_id = ?'
    );
    foreach ($_POST['scores'] as $student_id => $score) {
        if ($score === '' || !is_numeric($score)) continue;
        $check = $pdo->prepare(
            'SELECT entry_id FROM student_exams WHERE student_id = ? AND exam_id = ?'
        );
        $check->execute([$student_id, $exam_id]);
        if ($check->fetch()) {
            $updateStmt->execute([$score, $student_id, $exam_id]);
        } else {
            $insertStmt->execute([$student_id, $exam_id, $score]);
        }
    }
    header("Location: teacher.php");
    exit;
}

// get exam details
$examStmt = $pdo->prepare(
    'SELECT c.course_name, e.exam_date
     FROM exams e
     JOIN courses c ON e.course_id = c.course_id
     WHERE e.exam_id = ?'
);
$examStmt->execute([$exam_id]);
$exam = $examStmt->fetch();
if (!$exam) {
    header('Location: teacher.php');
    exit;
}

$studentsStmt = $pdo->prepare(
    'SELECT u.user_id, u.name
       FROM users u
       JOIN enrollments en ON en.student_id = u.user_id
       JOIN exams e       ON e.course_id    = en.course_id
      WHERE e.exam_id = ?
      ORDER BY u.name'
);
$studentsStmt->execute([$exam_id]);
$students = $studentsStmt->fetchAll();

$scoresStmt = $pdo->prepare(
    'SELECT student_id, score FROM student_exams WHERE exam_id = ?'
);
$scoresStmt->execute([$exam_id]);
$existing = [];
foreach ($scoresStmt->fetchAll() as $row) {
    $existing[$row['student_id']] = $row['score'];
}
?>

<style>
    body {
    font-family: Arial, sans-serif;
  }
  .card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    margin: 20px 0;
  }
  .card-header {
    padding: 16px;
    border-bottom: 1px solid #eee;
    background: #f7f7f7;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }
  .card-header h2 {
    margin: 0;
    color: #007BFF;
    font-size: 1.2rem;
  }
  .card-body {
    padding: 20px;
  }
  .table-responsive {
    overflow-x: auto;
  }
  .exam-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
  }
  .exam-table th,
  .exam-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
  }
  .exam-table th {
    background: #f1f1f1;
  }
  .exam-table tr:nth-child(odd) {
    background: #fafafa;
  }
  input.form-control {
    width: 70px;
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 4px;
    text-align: center;
  }
  button.btn {
    padding: 8px 16px;
    background: #007BFF;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
  }
  button.btn:hover {
    background: #0056b3;
  }
</style>

<div class="container">
  <div class="card">
    <div class="card-header">
      <h2>Enter Scores for <?= htmlspecialchars($exam['course_name']); ?> (<?= htmlspecialchars($exam['exam_date']); ?>)</h2>
    </div>
    <div class="card-body">
      <form method="post">
        <div class="table-responsive">
          <table class="exam-table">
            <thead>
              <tr>
                <th>Student</th>
                <th>Score</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $stu): ?>
              <tr>
                <td><?= htmlspecialchars($stu['name']); ?></td>
                <td>
                  <input
                    type="text"
                    class="form-control"
                    name="scores[<?= $stu['user_id']; ?>]"
                    value="<?= isset($existing[$stu['user_id']]) ? htmlspecialchars($existing[$stu['user_id']]) : ''; ?>"
                  >
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <button type="submit" class="btn">Save Scores</button>
      </form>
    </div>
  </div>
</div>
