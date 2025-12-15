<?php
require_once("inc/session.php");
require_once("inc/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'];

    $con = mysqli_connect("localhost", "root", "", "moviedb");

    // 신고 상태를 "대기"로 변경
    $update_report = "UPDATE reports SET status = '대기' WHERE report_id = '$report_id'";
    mysqli_query($con, $update_report);

    mysqli_close($con);

    echo "success";
} else {
    echo "error";
}
?>