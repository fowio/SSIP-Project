<?php

include 'db.php';           
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$errors = [];
$editUser   = false;
$editCourse = false;

if (isset($_POST['add_user'])) {
    $name      = trim($_POST['name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $role      = $_POST['user_type'];
    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $hash, $role]);
        header('Location: admin.php'); exit;
    } else {
        $errors[] = 'Provide valid user details (6+ char password).';
    }
}

if (isset($_GET['edit_user'])) {
    $uid = (int) $_GET['edit_user'];
    $stmt = $pdo->prepare('SELECT user_id, name, email, user_type FROM users WHERE user_id = ?');
    $stmt->execute([$uid]);
    if ($user = $stmt->fetch()) {
        $editUser   = true;
        $uId        = $user['user_id'];
        $uName      = $user['name'];
        $uEmail     = $user['email'];
        $uRole      = $user['user_type'];
    }
}

if (isset($_POST['edit_user'])) {
    $uId   = $_POST['user_id'];
    $uRole = $_POST['user_type'];
    $stmt = $pdo->prepare('UPDATE users SET user_type = ? WHERE user_id = ?');
    $stmt->execute([$uRole, $uId]);
    header('Location: admin.php'); exit;
}

if (isset($_GET['delete_user'])) {
    $uid = (int) $_GET['delete_user'];
    $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
    $stmt->execute([$uid]);
    header('Location: admin.php'); exit;
}

if (isset($_POST['add_course'])) {
    $cName = trim($_POST['course_name']);
    $code  = trim($_POST['code']);
    if ($cName && $code) {
        $stmt = $pdo->prepare('INSERT INTO courses (course_name, code) VALUES (?, ?)');
        $stmt->execute([$cName, $code]);
        header('Location: admin.php'); exit;
    } else {
        $errors[] = 'Provide valid course name and code.';
    }
}

if (isset($_GET['edit_course'])) {
    $cid = (int) $_GET['edit_course'];
    $stmt = $pdo->prepare('SELECT course_id, course_name, code FROM courses WHERE course_id = ?');
    $stmt->execute([$cid]);
    if ($course = $stmt->fetch()) {
        $editCourse = true;
        $cId         = $course['course_id'];
        $cName       = $course['course_name'];
        $cCode       = $course['code'];
    }
}

if (isset($_POST['edit_course'])) {
    $cId   = $_POST['course_id'];
    $cName = trim($_POST['course_name']);
    $cCode = trim($_POST['code']);
    $stmt = $pdo->prepare('UPDATE courses SET course_name = ?, code = ? WHERE course_id = ?');
    $stmt->execute([$cName, $cCode, $cId]);
    header('Location: admin.php'); exit;
}

if (isset($_GET['delete_course'])) {
    $cid = (int) $_GET['delete_course'];
    $stmt = $pdo->prepare('DELETE FROM courses WHERE course_id = ?');
    $stmt->execute([$cid]);
    header('Location: admin.php'); exit;
}

$usersStmt   = $pdo->query('SELECT user_id, name, email, user_type FROM users');
$allUsers    = $usersStmt->fetchAll();

$coursesStmt = $pdo->query('SELECT course_id, course_name, code FROM courses');
$allCourses  = $coursesStmt->fetchAll();
?>

<div class="container">
    <h2>User Management</h2>
    <?php if ($errors): ?><ul class="errors"><?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach;?></ul><?php endif; ?>
    <form method="post">
        <label>Name</label>
        <input name="name" type="text" value="<?= $uName ?? '';?>" required>
        <label>Email</label>
        <input name="email" type="email" value="<?= $uEmail ?? '';?>" required <?= $editUser ? 'readonly' : '';?>>
        <?php if (!$editUser): ?>
            <label>Password</label>
            <input name="password" type="password" required>
        <?php endif; ?>
        <label>Role</label>
        <select name="user_type">
            <option value="student" <?= (!isset($uRole) || $uRole=='student')?'selected':'';?>>Student</option>
            <option value="teacher" <?= (isset($uRole)&&$uRole=='teacher')?'selected':'';?>>Teacher</option>
            <option value="admin"   <?= (isset($uRole)&&$uRole=='admin')?'selected':'';?>>Admin</option>
        </select>
        <?php if ($editUser): ?>
            <input type="hidden" name="user_id" value="<?= $uId;?>">
            <button name="edit_user" class="btn">Update User</button>
            <a href="admin.php" class="btn">Cancel</a>
        <?php else: ?>
            <button name="add_user" class="btn">Add User</button>
        <?php endif; ?>
    </form>

    <table>
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
        <?php foreach ($allUsers as $user): ?>
            <tr>
                <td><?=htmlspecialchars($user['name']);?></td>
                <td><?=htmlspecialchars($user['email']);?></td>
                <td><?=htmlspecialchars($user['user_type']);?></td>
                <td>
                    <a href="?edit_user=<?= $user['user_id'];?>" class="btn">Edit</a>
                    <a href="?delete_user=<?= $user['user_id'];?>" class="btn" onclick="return confirm('Delete this user?');">Delete</a>
                </td>
            </tr>
        <?php endforeach;?>
    </table>

    <h2>Course Management</h2>
    <form method="post">
        <label>Course Name</label>
        <input name="course_name" type="text" value="<?= $cName ?? '';?>" required>
        <label>Code</label>
        <input name="code" type="text" value="<?= $cCode ?? '';?>" required>
        <?php if ($editCourse): ?>
            <input type="hidden" name="course_id" value="<?= $cId;?>">
            <button name="edit_course" class="btn">Update Course</button>
            <a href="admin.php" class="btn">Cancel</a>
        <?php else: ?>
            <button name="add_course" class="btn">Add Course</button>
        <?php endif; ?>
    </form>

    <table>
        <tr><th>Course</th><th>Code</th><th>Actions</th></tr>
        <?php foreach ($allCourses as $course): ?>
            <tr>
                <td><?=htmlspecialchars($course['course_name']);?></td>
                <td><?=htmlspecialchars($course['code']);?></td>
                <td>
                    <a href="?edit_course=<?= $course['course_id'];?>" class="btn">Edit</a>
                    <a href="?delete_course=<?= $course['course_id'];?>" class="btn" onclick="return confirm('Delete this course?');">Delete</a>
                </td>
            </tr>
        <?php endforeach;?>
    </table>
</div>
