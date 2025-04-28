<?php
session_start();

// 硬编码管理员密码（生产环境应使用数据库）
$adminPass = password_hash('admin', PASSWORD_DEFAULT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (password_verify($_POST['password'], $adminPass)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "密码错误";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>管理员登录</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <form method="post">
            <h2>管理员登录</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            <input type="password" name="password" 
                   placeholder="输入管理密码" required>
            <button type="submit">登录</button>
        </form>
    </div>
</body>
</html>