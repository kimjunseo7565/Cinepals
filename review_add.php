<?php
require_once("inc/session.php");

// POST로 전송된 데이터 받기
$movie_id = $_POST['movie_id'];
$rating = $_POST['rating'];
$content = $_POST['content'];

// 실제로는 여기서 DB에 저장
// 지금은 임시로 성공 메시지만 출력

echo("<script>
    alert('리뷰가 등록되었습니다.');
    location.href = 'movie_detail.php?id=" . $movie_id . "';
</script>");
?>