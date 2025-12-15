<?php
session_start();
$con = mysqli_connect("localhost", "root", "", "moviedb");

$user_id = $_POST['user_id'];
$password = $_POST['password'];

$sql = "SELECT * FROM members WHERE user_id = '$user_id' AND is_admin = 1";
$result = mysqli_query($con, $sql);

if(mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_array($result);
    // 과제용으로 간단히 처리
    if($password === '1234') {
        $_SESSION['admin'] = true;
        $_SESSION['admin_id'] = $user_id;
        $_SESSION['admin_name'] = $row['name'];
        header("Location: manager_home.php");
        exit();
    }
}

mysqli_close($con);
echo "<script>
        alert('관리자 계정이 아니거나 비밀번호가 일치하지 않습니다.');
        history.back();
      </script>";
?>