<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefNote — Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="auth-card">
        <div class="auth-logo">🍳 Chef<span>Note</span></div>
        <h2 class="auth-title">Create Account</h2>
        <p class="auth-sub">Start your culinary journey</p>

        <?php
        session_start();
        $error = $success = '';
        $name = $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'db.php';
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $pw = $_POST['password'] ?? '';
            $pw2 = $_POST['confirm'] ?? '';

            if (!$name || !$email || !$pw) $error = 'All fields are required.';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Invalid email.';
            elseif (strlen($pw) < 6) $error = 'Password must be at least 6 characters.';
            elseif ($pw !== $pw2) $error = 'Passwords do not match.';
            else {
                $q = $conn->prepare("SELECT id FROM users WHERE email=?");
                $q->bind_param('s', $email); $q->execute(); $q->store_result();
                if ($q->num_rows > 0) $error = 'Email already registered.';
                else {
                    $hash = password_hash($pw, PASSWORD_DEFAULT);
                    $ins = $conn->prepare("INSERT INTO users (full_name,email,password) VALUES (?,?,?)");
                    $ins->bind_param('sss', $name, $email, $hash);
                    $success = $ins->execute() ? 'Account created! Redirecting…' : 'Something went wrong.';
                    $ins->close();
                }
                $q->close(); $conn->close();
            }
        }

        if ($error): ?><div class="auth-alert auth-alert-error"><?=htmlspecialchars($error)?></div><?php endif;
        if ($success): ?><div class="auth-alert auth-alert-success"><?=htmlspecialchars($success)?></div><script>setTimeout(()=>location.href='login.php',1500)</script><?php endif; ?>

        <form method="POST">
            <div class="auth-field"><i class="bi bi-person"></i><input type="text" name="name" placeholder="Full Name" required value="<?=htmlspecialchars($name)?>"></div>
            <div class="auth-field"><i class="bi bi-envelope"></i><input type="email" name="email" placeholder="Email" required value="<?=htmlspecialchars($email)?>"></div>
            <div class="auth-field"><i class="bi bi-lock"></i><input type="password" name="password" placeholder="Password (min 6)" required minlength="6"></div>
            <div class="auth-field"><i class="bi bi-shield-lock"></i><input type="password" name="confirm" placeholder="Confirm Password" required></div>
            <button type="submit" class="auth-btn">Create Account</button>
        </form>
        <div class="auth-footer">Already have an account? <a href="login.php">Sign In</a></div>
    </div>
</body>
</html>
