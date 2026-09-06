<?php
session_start();
include "../config.php";
if (isset($_POST['login'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];
    $res = $db->query("SELECT * FROM users WHERE username='$u'")->fetch_assoc();
    if ($res && password_verify($p, $res['password'])) {
        $_SESSION['user'] = $res;
        header("Location: dashboard.php");
    } else echo "Wrong login";
}
?>
<form method="POST">
    <input name="username" placeholder="username" required>
    <input name="password" type="password" placeholder="password" required>
    <button name="login">Login</button>
</form>