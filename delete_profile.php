<?php
require_once("inc/session.php");
require_once("inc/db.php");

$member_id = $_SESSION['member_id'];

// 현재 프로필 이미지 경로 가져오기
$sql = "SELECT profile_image FROM members WHERE member_id = ?";
$current_image = db_select($sql, [$member_id])[0]['profile_image'];

// 실제 파일 삭제
if($current_image && file_exists($current_image)) {
    unlink($current_image);
}

// DB에서 프로필 이미지 정보 삭제
$sql = "UPDATE members SET profile_image = NULL WHERE member_id = ?";
$result = db_update_delete($sql, [$member_id]);

if($result) {
    echo "<script>
            alert('프로필 이미지가 삭제되었습니다.');
            location.href='mypage.php';
          </script>";
} else {
    echo "<script>
            alert('삭제 중 오류가 발생했습니다.');
            history.back();
          </script>";
}
?>