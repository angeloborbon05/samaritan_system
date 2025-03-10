<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <div class="container">
    <h1 class="form-title">Sign In</h1>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-main" id="error-message">
            <p><?php echo $_SESSION['error']; ?></p>
        </div>
        <script>
            setTimeout(function() {
                document.getElementById('error-message').style.display = 'none';
            }, 1000); // Hide after 1 second
        </script>
        <?php unset($_SESSION['error']); // Remove error message after displaying ?>
    <?php endif; ?>

    <form method="POST" action="login.php"> <!-- Change action to login.php -->
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" id="email" placeholder="Email" required>
        </div>

        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="password_signin" placeholder="Password" required>
            <i class="fa fa-eye-slash" id="eye_signin" onclick="togglePasswordVisibility('password_signin', 'eye_signin')"></i>
        </div>

        <input type="submit" value="Sign In" class="btn">
    </form>

    <p class="or">----------or---------</p>

    <div class="icons">
        <i class="fab fa-google"></i>
        <i class="fab fa-facebook"></i>
    </div>

    <div class="links">
        <p>Don't have an account yet? <a href="register.php">Sign Up</a></p>
    </div>
  </div>

  <script src="script.js"></script>
</body>

</html>