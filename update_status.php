<?php
require_once("inc/session.php");
require_once("inc/db.php");

// GET 방식과 POST 방식 모두 받기 - 쉬운 방법
$member_id = '';
$status = '';

// POST 방식으로 온 경우
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['member_id'])) {
        $member_id = $_POST['member_id'];
    }
    if (isset($_POST['status'])) {
        $status = $_POST['status'];
    }
}

// GET 방식으로 온 경우
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['member_id'])) {
        $member_id = $_GET['member_id'];
    }
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
    }
}

// 값이 있는지 확인
if ($member_id == '' || $status == '') {
    echo "<script>alert('잘못된 요청입니다.'); history.back();</script>";
    exit;
}

// 허용된 상태값인지 확인 - 쉬운 방법
if ($status != 'active' && $status != 'banned') {
    echo "<script>alert('잘못된 상태값입니다.'); history.back();</script>";
    exit;
}

// 회원 상태 업데이트
$con = mysqli_connect("localhost", "root", "", "moviedb");

// 회원이 존재하는지 확인
$check_sql = "SELECT nickname, is_admin FROM members WHERE member_id = $member_id";
$check_result = mysqli_query($con, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    echo "<script>alert('존재하지 않는 회원입니다.'); history.back();</script>";
    exit;
}

$member_info = mysqli_fetch_assoc($check_result);
$member_name = $member_info['nickname'];
if ($member_info['is_admin'] == 1) {
    echo "<script>
            alert('관리자 계정은 상태를 변경할 수 없습니다.');
            history.back();
          </script>";
    mysqli_close($con);
    exit;
}
// 상태 업데이트
$query = "UPDATE members SET status = '$status' WHERE member_id = $member_id";
$result = mysqli_query($con, $query);

if ($result) {
    // 성공 메시지
    $status_text = ($status == 'active') ? '활성화' : '정지';
    echo "<script>
            alert('$member_name 회원이 {$status_text}되었습니다.');
            location.href = 'manager_members.php';
          </script>";
} else {
    echo "<script>
            alert('상태 변경 실패: " . mysqli_error($con) . "');
            history.back();
          </script>";
}

mysqli_close($con);
