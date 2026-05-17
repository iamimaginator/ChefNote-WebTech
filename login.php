<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefNote — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="auth-card">
        <div class="auth-logo">🍳 Chef<span>Note</span></div>
        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-sub">Sign in to your recipes</p>

        <?php
        session_start();
        if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
        $error = ''; $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'db.php';
            $email = trim($_POST['email'] ?? '');
            $pw = $_POST['password'] ?? '';

            if (!$email || !$pw) $error = 'Please fill all fields.';
            else {
                $q = $conn->prepare("SELECT id,full_name,password FROM users WHERE email=?");
                $q->bind_param('s', $email); $q->execute();
                $r = $q->get_result();
                if ($row = $r->fetch_assoc()) {
                    if (password_verify($pw, $row['password'])) {
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['user_name'] = $row['full_name'];
                        header('Location: index.php'); exit;
                    } else $error = 'Incorrect password.';
                } else $error = 'No account with that email.';
                $q->close(); $conn->close();
            }
        }

        if ($error): ?><div class="auth-alert auth-alert-error"><?=htmlspecialchars($error)?></div><?php endif; ?>

        <form method="POST">
            <div class="auth-field"><i class="bi bi-envelope"></i><input type="email" name="email" placeholder="Email" required value="<?=htmlspecialchars($email)?>"></div>
            <div class="auth-field"><i class="bi bi-lock"></i><input type="password" name="password" placeholder="Password" required></div>
            <button type="submit" class="auth-btn">Sign In</button>
        </form>
        <div class="auth-footer">Don't have an account? <a href="signup.php">Create one</a></div>
    </div>
</body>
</html>
