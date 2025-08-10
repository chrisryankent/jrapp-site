<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "config/config.php"; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['pass'];

    // 1) Fetch user by email
    $stmt = $conn->prepare("SELECT id,name,password_hash,role_id,branch_id,designation 
                              FROM tbl_user 
                             WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // 2) Verify password
    if ($result && $user = $result->fetch_assoc()) {
        if ($user['password_hash'] === $password) {
            // 3) Set session vars
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['userlogin']   = $user['name'];
            $_SESSION['role_id']     = $user['role_id'];
            $_SESSION['branch_id']   = $user['branch_id'];
            $_SESSION['designation'] = $user['designation'];

            // 4) Write audit log
            $entity      = 'user';
            $entity_id   = $user['id'];
            $changed_by  = $user['id'];
            $change_type = 'login';
            $change_data = json_encode([
                'ip'         => $_SERVER['REMOTE_ADDR']    ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            $auditSql = "INSERT INTO tbl_audit_log
                           (entity, entity_id, changed_by, change_type, change_data)
                         VALUES
                           (?,       ?,         ?,          ?,           ?)";
            $auditSt = $conn->prepare($auditSql);
            $auditSt->bind_param(
                "siiss",
                $entity,
                $entity_id,
                $changed_by,
                $change_type,
                $change_data
            );
            $auditSt->execute();
            $auditSt->close();

            // 5) Redirect to dashboard
            header("Location: index.php");
            exit();
        }
    }

    // Bad credentials
    $error = "Invalid email or password.";
    $stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sign in</title>
  <link href="assets/css/bootstrap.css" rel="stylesheet">
  <link href="assets/css/signin.css" rel="stylesheet">
</head>
<body>
  <div class="container">
    <form class="form-signin" action="" method="POST">
      <div class="text-center mb-4">
        <img src="images/brac.jpg" width="220" height="72" alt="">
      </div>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?php echo $error; ?></div>
      <?php endif; ?>
      <div class="form-label-group">
        <input type="email" name="email" class="form-control" placeholder="Email address" required autofocus>
        <label>Email address</label>
      </div>
      <div class="form-label-group">
        <input type="password" name="pass" class="form-control" placeholder="Password" required>
        <label>Password</label>
      </div>
      <button class="btn btn-lg btn-primary btn-block" name="submit" type="submit">Sign in</button>
      <p class="mt-3 text-uppercase font-weight-bold text-center">
        For account creation, contact the developer:<br>
        <a href="mailto:chrisryankent@gmail.com">chrisryankent@gmail.com</a> or 0775850082
      </p>
    </form>
    <div class="row mt-4">
      <div class="col-md-12 text-center text-muted">
        Developed by &copy; <a href="https://github.com/Aklilu-Mandefro">chris ryan kent</a> – <?php echo date('Y'); ?>
      </div>
    </div>
  </div>
</body>
</html>