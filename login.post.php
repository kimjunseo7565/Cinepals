<?php
$email = $_POST['email'];
$pass = $_POST['password'];

if ($email == null || $pass == null) {
    echo ("<script>alert('모두 입력하여 주세요.');</script>");
    echo ("<script>history.back();</script>");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "moviedb");
$sql = "select * from members where email='$email'";
$result = mysqli_query($con, $sql);

if (mysqli_num_rows($result) == 0) {
    echo ("<script>alert('회원가입을 먼저하세요');</script>");
    echo ("<script>history.back();</script>");
    exit();
}

$row = mysqli_fetch_array($result);

// 간단한 비밀번호 비교 (평문 또는 간단한 암호화)
if ($pass != $row['password']) {
    echo ("<script>alert('비밀번호가 틀립니다.');</script>");
    echo ("<script>history.back();</script>");
    exit();
}

// 회원 상태 확인 - 정지/삭제된 회원 차단
$member_status = isset($row['status']) ? $row['status'] : 'active';

if ($member_status == 'deleted') {
    echo ("<script>alert('이 계정은 관리자에 의해 삭제되었습니다.\\n자세한 사항은 고객센터로 문의해주세요.');</script>");
    echo ("<script>history.back();</script>");
    exit();
}

if ($member_status == 'banned') {
    echo ("<script>alert('정지된 계정입니다. 관리자에게 문의하세요.');</script>");
    echo ("<script>history.back();</script>");
    exit();
}

session_start();
$_SESSION['member_id'] = $row['member_id'];
$_SESSION['name'] = $row['name'];

// 최근 로그인 시간 업데이트
$update_sql = "UPDATE members SET last_login = NOW() WHERE member_id = " . $row['member_id'];
mysqli_query($con, $update_sql);

if (isset($_POST['keep_login'])) {
    setcookie("email", $row['email'], time() + (10), "/");
}

mysqli_close($con);
header("Location: index.php");
