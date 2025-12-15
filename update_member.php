<?php
require_once("inc/session.php");

$name = $_POST["name"];
$phone = $_POST["phone"];
$bio = $_POST["bio"];

if ($name == null) {    
    echo("<script>alert('이름을 입력하여 주세요.');</script>");
    echo("<script>history.back();</script>");
    exit();
}

// 실제로는 DB 업데이트가 들어갈 자리
// $sql = "update members set name='$name', phone='$phone', bio='$bio' where member_id=$member_id";

echo("<script>alert('수정이 완료되었습니다.');</script>");
echo("<script>location.href='mypage.php';</script>");
?>