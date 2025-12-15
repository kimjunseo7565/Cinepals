<?php
$con = mysqli_connect("localhost", "root", "", "moviedb");

if (!$con) {
    die("연결 실패: " . mysqli_connect_error());
}

// status 컬럼에 'deleted' 값 추가
$sql = "ALTER TABLE members 
        MODIFY COLUMN status ENUM('active', 'banned', 'deleted') DEFAULT 'active'";

if (mysqli_query($con, $sql)) {
    echo "✅ status 컬럼이 수정되었습니다!<br>";
    echo "✅ 이제 'active', 'banned', 'deleted' 값을 사용할 수 있습니다!<br>";
    echo "<br><a href='manager_members.php'>회원 관리로 가기</a>";
} else {
    echo "❌ 에러: " . mysqli_error($con);
}

mysqli_close($con);
