<?php
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin.php');
        exit();
    } else {
        header('Location: student.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HelpHive - University Campus Help Desk Platform">
    <title>HelpHive - University Help Desk</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <section id="login-view" class="view active">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="logo">
                        <i class="fas fa-hexagon-vertical-nft"></i>
                        <span>HelpHive</span>
                    </div>
                    <h1>Welcome Back</h1>
                    <p>Sign in to access your help desk</p>
                </div>
                <form id="login-form">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    <div id="login-error" class="error-message hidden">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Invalid credentials. Please try again.</span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
                <div class="login-footer">
                    <p>Demo Credentials:</p>
                    <div class="demo-creds">
                        <span><strong>Student:</strong> student / 123</span>
                        <span><strong>Admin:</strong> admin / 123</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div id="toast-container"></div>
    <script src="js/common.js"></script>
    <script src="js/login.js"></script>
</body>

</html>

