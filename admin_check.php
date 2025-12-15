<?php
session_start();
$con = mysqli_connect("localhost", "root", "", "moviedb");

// 세션 체크
if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// DB에서 관리자 권한 재확인
$user_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM members WHERE user_id = '$user_id' AND is_admin = 1";
$result = mysqli_query($con, $sql);

if(mysqli_num_rows($result) == 0) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

mysqli_close($con);
?>