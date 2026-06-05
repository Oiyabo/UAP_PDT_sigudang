<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "db_gudang");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>