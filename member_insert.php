<?php
$con = mysqli_connect("localhost", "root", "", "moviedb");

// 🔥 보안 처리 추가
$email = mysqli_real_escape_string($con, $_POST["email"]);
$pass = mysqli_real_escape_string($con, $_POST["pass"]);
$nickname = mysqli_real_escape_string($con, $_POST["nickname"]);

// 간단한 중복 체크
$check_sql = "select * from members where user_id='$email' or email='$email' or nickname='$nickname'";
$result = mysqli_query($con, $check_sql);

if(mysqli_num_rows($result) > 0) {
    mysqli_close($con);
    echo "<script>
            alert('이미 사용중인 이메일이나 닉네임입니다.');
            history.back();
          </script>";
    exit();
}

// 비밀번호 평문 저장 (간단하게)
$sql = "insert into members(user_id, password, nickname, email, name) ";
$sql .= "values('$email', '$pass', '$nickname', '$email', '$nickname')";

if(mysqli_query($con, $sql)) {
    mysqli_close($con);
    echo "<script>
            location.href = 'success.php';
          </script>";
    exit();
} else {
    mysqli_close($con);
    echo "<script>
            alert('회원가입 중 오류가 발생했습니다.');
            history.back();
          </script>";
}
?>