<?php
require_once("inc/session.php");
require_once("inc/db.php");

$movie_id = $_POST['movie_id'];
$content = $_POST['content'];
$member_id = $_SESSION['member_id'];

// 닉네임 가져오기
$member = db_select("SELECT nickname FROM members WHERE member_id = ?", [$member_id])[0];
$nickname = $member['nickname'];

// 댓글 저장
$result = db_insert("INSERT INTO comments (movie_id, member_id, nickname, content) VALUES (?, ?, ?, ?)", 
    [$movie_id, $member_id, $nickname, $content]
);

if($result) {
    echo "<script>
            alert('댓글이 등록되었습니다.');
            location.href='movie_detail.php?id=" . $movie_id . "';
          </script>";
} else {
    echo "<script>
            alert('댓글 등록 실패');
            history.back();
          </script>";
}
?>