<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: pages/index.php");
} else {
    header("Location: pages/login.php");
}
exit;
?>