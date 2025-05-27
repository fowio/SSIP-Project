<?php
include 'db.php';
include 'includes/header.php';
// TEACHERS ONLY (duhhhh)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit;
}
$errors = [];
$editing = false;

// adding exam handler
if (isset($_POST['add_exam'])) {
    $course_id = $_POST['course_id'];
    $exam_date = $_POST['exam_date'];
    $location  = trim($_POST['location']);
    if ($course_id && $exam_date) {
        $stmt = $pdo->prepare('INSERT INTO exams (course_id, exam_date, location) VALUES (?, ?, ?)');
        $stmt->execute([$course_id, $exam_date, $location]);
        header('Location: teacher.php');
        exit;
    }
}

// deleting exam handling
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM exams WHERE exam_id = ?');
    $stmt->execute([$del_id]);
    header('Location: teacher.php');
    exit;
}

// edit request handling
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM exams WHERE exam_id = ?');
    $stmt->execute([$edit_id]);
    $exam = $stmt->fetch();
    if ($exam) {
        $editing = true;
        $exam_id = $exam['exam_id'];
        $course_id = $exam['course_id'];
        $exam_date = $exam['exam_date'];
        $location  = $exam['location'];
    }
}

// updating exams
if (isset($_POST['edit_exam'])) {
    $exam_id   = $_POST['exam_id'];
    $course_id = $_POST['course_id'];
    $exam_date = $_POST['exam_date'];
    $location  = trim($_POST['location']);
    $stmt = $pdo->prepare('UPDATE exams SET course_id = ?, exam_date = ?, location = ? WHERE exam_id = ?');
    $stmt->execute([$course_id, $exam_date, $location, $exam_id]);
    header('Location: teacher.php');
    exit;
}

// get courses
$stmt = $pdo->query('SELECT course_id, course_name FROM courses');
$courses = $stmt->fetchAll();

// get all exams
$stmt = $pdo->prepare('SELECT e.exam_id, c.course_name, e.exam_date, e.location FROM exams e JOIN courses c ON e.course_id = c.course_id ORDER BY e.exam_date');
$stmt->execute();
$exams = $stmt->fetchAll();
?>
<div class="container">
    <h2><?php echo $editing ? 'Edit Exam' : 'Add New Exam'; ?></h2>
    <?php if ($errors): ?><ul class="errors"><?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul><?php endif; ?>
    <form method="post">
        <label for="course_id">Course</label>
        <select id="course_id" name="course_id" required>
            <option value="">Select a course</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= $c['course_id']; ?>" <?= (isset($course_id) && $course_id == $c['course_id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($c['course_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="exam_date">Date</label>
        <input id="exam_date" name="exam_date" type="date" value="<?= isset($exam_date) ? htmlspecialchars($exam_date) : ''; ?>" required>
        <label for="location">Location</label>
        <input id="location" name="location" type="text" value="<?= isset($location) ? htmlspecialchars($location) : ''; ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="exam_id" value="<?= $exam_id; ?>">
            <button type="submit" name="edit_exam" class="btn">Update Exam</button>
            <a href="teacher.php" class="btn">Cancel</a>
        <?php else: ?>
            <button type="submit" name="add_exam" class="btn">Add Exam</button>
        <?php endif; ?>
    </form>
    <h2>Existing Exams</h2>
    <?php if ($exams): ?><table>
        <tr><th>Course</th><th>Date</th><th>Location</th><th>Actions</th></tr>
        <?php foreach ($exams as $ex): ?><tr><td><?=htmlspecialchars($ex['course_name']);?></td>
            <td><?=htmlspecialchars($ex['exam_date']);?></td><td><?=htmlspecialchars($ex['location']);?></td>
            <td>
            <a href="?edit=<?= $ex['exam_id'];?>" class="btn">Edit</a>
            <a href="?delete=<?= $ex['exam_id'];?>" class="btn" onclick="return confirm('Delete this exam?');">Delete</a>
            <a href="enter_scores.php?exam_id=<?= $ex['exam_id'];?>" class="btn">Enter Scores</a>
            <a href="feedback.php?exam_id=<?= $ex['exam_id'] ?>" class="btn">Feedback</a>
            <a href="seating.php?exam_id=<?= $ex['exam_id'] ?>" class="btn">Seating</a>
        </td></tr><?php endforeach;?></table><?php else:?><p>No exams found.</p><?php endif; ?></div>
        <?php include 'includes/footer.php'; ?>
