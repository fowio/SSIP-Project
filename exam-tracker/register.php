<?php
include 'db.php';
include 'includes/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // get data that user entered and validating
    $name      = trim($_POST['name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $user_type = $_POST['user_type'];

    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $errors[] = 'Please enter a valid name, email and a password of at least 6 characters.';
    }

    // check if existed already
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'Email already registered.';
    }

    // make a new user finally
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, $user_type]);
        header('Location: login.php');
        exit;
    }
}
?>

<div class="container">
  <h2>Register</h2>
  <?php if ($errors): ?>
    <ul class="errors">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" action="">
    <label for="name">Full Name</label>
    <input id="name" name="name" type="text" required>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" required>

    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>

    <label for="user_type">Role</label>
    <select id="user_type" name="user_type">
      <option value="student">Student</option>
      <option value="teacher">Teacher</option>
      <option value="admin">Admin</option>
    </select>

    <button type="submit" class="btn">Register</button>
  </form>
</div>

<?php include 'includes/footer.php'; ?>
