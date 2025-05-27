<?php
include 'db.php';
include 'includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // get user
    $stmt = $pdo->prepare('SELECT user_id, password, user_type FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // success logging in
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['user_type'] = $user['user_type'];

        // based on role -> show page
        switch ($user['user_type']) {
            case 'teacher': header('Location: teacher.php'); break;
            case 'admin':   header('Location: admin.php');   break;
            default:        header('Location: student.php'); break;
        }
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>

<div class="container">
    <h2>Login</h2>
    <?php if ($error): ?>
        <p class="errors"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <button type="submit" class="btn">Login</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>