<?php
// logout.php
session_start(); // Ensure session is started
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
header("Location: index.php"); // Redirect to login page
exit();
?>