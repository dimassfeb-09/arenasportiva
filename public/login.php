<?php
// Redirect to the main page since authentication is now handled through the offcanvas forms
header('Location: index.php');
exit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"  
        content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Login – Booking Lapangan</title>
  <link 
    rel="stylesheet" 
    href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" 
    crossorigin="anonymous">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="card-auth text-center">
    <img src="assets/img/logo.png" alt="Logo" class="mb-4" style="width:80px;">
    <h4 class="mb-4">Login Pelanggan</h4>

    <?php if(!empty($error)): ?>
      <div class="alert alert-danger small">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group text-left">
        <label>Username</label>
        <input name="username" type="text" class="form-control" placeholder="Masukkan username Anda" required>
      </div>
      <div class="form-group text-left">
        <label>Password</label>
        <input name="password" type="password" class="form-control" placeholder="Password" required>
      </div>
      <button type="submit" class="btn btn-auth btn-block">Login</button>
    </form>
    
    <p class="mt-3 small">
      <a href="forgot_password.php">Lupa Password?</a>
    </p>

    <p class="mt-3 small">
      <a href="forgot_password.php">Lupa Password?</a>
    </p>

    <p class="mt-3 small">
      Belum punya akun? <a href="register.php">Daftar di sini</a>
    </p>
  </div>

  <!-- Popup notification -->
  <div id="popup-notification" class="popup-notification" style="display: none;">
    <div class="popup-content">
      <span class="close-btn" onclick="closePopup()">&times;</span>
      <p id="popup-message"></p>
    </div>
  </div>

  <script>
    <?php if(!empty($error)): ?>
    document.addEventListener('DOMContentLoaded', function() {
      showPopup('<?= htmlspecialchars($error) ?>');
    });
    <?php endif; ?>
    
    <?php if(!empty($message)): ?>
    document.addEventListener('DOMContentLoaded', function() {
      showPopup('<?= htmlspecialchars($message) ?>');
    });
    <?php endif; ?>

    function showPopup(message) {
      document.getElementById('popup-message').textContent = message;
      document.getElementById('popup-notification').style.display = 'block';
    }

    function closePopup() {
      document.getElementById('popup-notification').style.display = 'none';
    }

    // Close popup when clicking outside
    window.onclick = function(event) {
      var popup = document.getElementById('popup-notification');
      if (event.target == popup) {
        popup.style.display = 'none';
      }
    }
  </script>

  <style>
    .popup-notification {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      z-index: 1000;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .popup-content {
      background-color: #fff;
      padding: 20px;
      border-radius: 5px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      max-width: 400px;
      width: 90%;
      text-align: center;
      position: relative;
    }

    .close-btn {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 24px;
      cursor: pointer;
    }
  </style>
</body>
</html>
