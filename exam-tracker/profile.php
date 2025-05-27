<?php
include 'db.php';
session_start();

// Restrict to logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$errors = [];
$success = '';

// form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // email validation ( cannot be empty and need to be correct formatted )
    if (!$name) {
        $errors[] = 'Name cannot be empty.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    // check the email's uniqueness
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = 'Email already in use.';
    }

    // handling password changes
    $changePass = false;
    if ($new || $confirm) {
        if (!$current) {
            $errors[] = 'Please enter your current password to change it.';
        } else {
            $hashStmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
            $hashStmt->execute([$user_id]);
            $hash = $hashStmt->fetchColumn();
            if (!password_verify($current, $hash)) {
                $errors[] = 'Current password is incorrect.';
            }
        }
        if ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        }
        if (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
        $changePass = empty($errors);
    }

    // update if there are no errors
    if (empty($errors)) {
        // updating name and email
        $upd = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE user_id = ?');
        $upd->execute([$name, $email, $user_id]);
        // updating password (if needed)
        if ($changePass) {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?')
                ->execute([$newHash, $user_id]);
        }
        $success = 'Profile updated successfully.';
    }
}

// current user is:
$stmt = $pdo->prepare('SELECT name, email FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="max-width:500px; margin-top:30px;">
    <?php if ($success): ?>
      <div class="alert" style="background:#d4edda;color:#155724;padding:10px;border-radius:4px;margin-bottom:20px;">
        <?= htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert" style="background:#f8d7da;color:#721c24;padding:10px;border-radius:4px;margin-bottom:20px;">
        <ul style="margin:0;padding-left:20px;">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <h2 style="color:#007BFF;">Profile & Settings</h2>
    <form method="post">
        <label for="name">Full Name</label>
        <input id="name" name="name" type="text" value="<?= htmlspecialchars($user['name']); ?>" required style="width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= htmlspecialchars($user['email']); ?>" required style="width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;">

        <hr>
        <h2 style="color:#007BFF;">Change Password</h2>
        <label for="current_password">Current Password</label>
        <input id="current_password" name="current_password" type="password" style="width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;">

        <label for="new_password">New Password</label>
        <input id="new_password" name="new_password" type="password" style="width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;">

        <label for="confirm_password">Confirm New Password</label>
        <input id="confirm_password" name="confirm_password" type="password" style="width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;">

        <button type="submit" class="btn" style="background:#007BFF; color:#fff; padding:10px 20px; border:none; border-radius:4px; cursor:pointer;">Save Changes</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>