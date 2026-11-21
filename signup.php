<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) <= 8) {
        $error = 'Enter a username, valid email, and password with at least 8 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, 'INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, \'user\')');
        mysqli_stmt_bind_param($stmt, 'sss', $username, $email, $hash);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header('Location: login.php');
            exit();
        }
        $error = mysqli_stmt_errno($stmt) === 1062 ? 'That email is already registered.' : 'Sign up failed.';
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign up</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"></head>
<body><div class="container" style="max-width:520px;padding-top:100px">
<h1>Sign up</h1>
<?php if ($error !== ''): ?><p class="text-danger" role="alert"><?php echo h($error); ?></p><?php endif; ?>
<form action="signup.php" method="post">
<div class="form-group"><label for="username">Username</label><input type="text" class="form-control" name="username" id="username" maxlength="255" required></div>
<div class="form-group"><label for="email">Email address</label><input type="email" class="form-control" name="email" id="email" maxlength="255" autocomplete="email" required></div>
<div class="form-group"><label for="password">Password (at least 8 characters)</label><input type="password" class="form-control" name="password" id="password" minlength="8" autocomplete="new-password" required></div>
<button type="submit" class="btn btn-primary">Sign up</button>
</form></div></body></html>
