<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

start_app_session();
if (!empty($_SESSION['user_id'])) {
    header('Location: user.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email and password.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, username, email, password, role FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $passwordOk = $user && (password_verify($password, $user['password'])
            || (!str_starts_with($user['password'], '$2y$') && hash_equals($user['password'], $password)));
        if ($passwordOk) {
            if (!password_get_info($user['password'])['algo']) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $update = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
                mysqli_stmt_bind_param($update, 'si', $hash, $user['id']);
                mysqli_stmt_execute($update);
                mysqli_stmt_close($update);
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            header('Location: user.php');
            exit();
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Log in</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"></head>
<body><div class="container" style="max-width:520px;padding-top:100px">
<h1>Log in</h1>
<?php if ($error !== ''): ?><p class="text-danger" role="alert"><?php echo h($error); ?></p><?php endif; ?>
<form action="login.php" method="post">
<div class="form-group"><label for="email">Email address</label><input type="email" class="form-control" name="email" id="email" autocomplete="username" required></div>
<div class="form-group"><label for="password">Password</label><input type="password" class="form-control" name="password" id="password" autocomplete="current-password" required></div>
<button type="submit" class="btn btn-primary">Log in</button>
</form></div></body></html>
