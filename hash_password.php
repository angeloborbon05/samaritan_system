<?php
$new_admin_password = 'admin21'; // Replace with the actual password
$hashed_password = password_hash($new_admin_password, PASSWORD_DEFAULT);
echo "Hashed Password: " . $hashed_password;
?>
