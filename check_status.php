<?php
$con = mysqli_connect("localhost", "root", "", "moviedb");

if (!$con) {
    die("연결 실패: " . mysqli_connect_error());
}

echo "<h3>📌 현재 status 컬럼 구조:</h3>";
$sql = "SHOW COLUMNS FROM members LIKE 'status'";
$result = mysqli_query($con, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "status 컬럼을 찾을 수 없습니다.";
}

echo "<hr>";
echo "<h3>🔧 강제로 수정 시도:</h3>";

// 강제로 ALTER 실행
$alter_sql = "ALTER TABLE members MODIFY COLUMN status ENUM('active', 'banned', 'deleted') DEFAULT 'active'";
if (mysqli_query($con, $alter_sql)) {
    echo "✅ 수정 완료!<br>";
} else {
    echo "❌ 에러: " . mysqli_error($con) . "<br>";
}

echo "<hr>";
echo "<h3>✅ 수정 후 status 컬럼 구조:</h3>";
$sql2 = "SHOW COLUMNS FROM members LIKE 'status'";
$result2 = mysqli_query($con, $sql2);

if ($result2 && mysqli_num_rows($result2) > 0) {
    $row2 = mysqli_fetch_assoc($result2);
    echo "<pre>";
    print_r($row2);
    echo "</pre>";
}

mysqli_close($con);
