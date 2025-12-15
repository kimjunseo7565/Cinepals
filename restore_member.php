<?php
require_once("inc/session.php");
require_once("inc/db.php");

$member_id = '';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['member_id'])) {
        $member_id = $_GET['member_id'];
    }
}

if ($member_id == '') {
    echo "<script>alert('잘못된 요청'); history.back();</script>";
    exit;
}

// 🔥 직접 mysqli 사용
$con = mysqli_connect("localhost", "root", "", "moviedb");

if (!$con) {
    echo "<script>alert('DB 연결 실패'); history.back();</script>";
    exit;
}

// 🔥 상태를 다시 active로
$query = "UPDATE members SET status = 'active' WHERE member_id = '$member_id'";
$result = mysqli_query($con, $query);

mysqli_close($con);

if ($result) {
    echo "<script>
            alert('회원이 복구되었습니다.');
            location.href='manager_members.php';
          </script>";
} else {
    echo "<script>alert('복구 실패'); history.back();</script>";
}
