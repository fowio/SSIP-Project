<?php
include 'db.php';
session_start();

//if not logged in -> send back
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// based on user role
switch ($_SESSION['user_type']) {
    case 'teacher':
        header('Location: teacher.php');
        break;
    case 'admin':
        header('Location: admin.php');
        break;
    default:
        header('Location: student.php');
        break;
}
exit;
?>
