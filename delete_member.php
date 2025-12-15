<?php
require_once("inc/session.php");
require_once("inc/db.php");

$member_id = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['member_id'])) {
        $member_id = $_POST['member_id'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['member_id'])) {
        $member_id = $_GET['member_id'];
    }
}

if ($member_id == '') {
    echo "<script>alert('잘못된 요청'); history.back();</script>";
    exit;
}

$con = mysqli_connect("localhost", "root", "", "moviedb");
$check_admin_sql = "SELECT is_admin FROM members WHERE member_id = '$member_id'";
$check_result = mysqli_query($con, $check_admin_sql);

if ($check_result && mysqli_num_rows($check_result) > 0) {
    $member = mysqli_fetch_assoc($check_result);

    if ($member['is_admin'] == 1) {
        echo "<script>
                alert('관리자 계정은 삭제할 수 없습니다.');
                history.back();
              </script>";
        mysqli_close($con);
        exit;
    }
}

if (!$con) {
    echo "<script>alert('DB 연결 실패'); history.back();</script>";
    exit;
}

// 회원 삭제 (상태만 변경)
$query = "UPDATE members SET status = 'deleted' WHERE member_id = '$member_id'";
$result = mysqli_query($con, $query);

mysqli_close($con);

if ($result) {
    echo "<script>
            alert('회원이 삭제되었습니다.');
            location.href='manager_members.php';
          </script>";
} else {
    echo "<script>alert('삭제 실패'); history.back();</script>";
}
