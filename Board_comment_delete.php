<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 로그인 체크
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('로그인이 필요합니다.'); history.back();</script>";
    exit;
}

if (!isset($_GET['comment_id'])) {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

$con = mysqli_connect("localhost", "root", "", "moviedb");

$comment_id = mysqli_real_escape_string($con, $_GET['comment_id']);
$member_id = mysqli_real_escape_string($con, $_SESSION['member_id']);

// 본인의 댓글인지 확인하고 게시글 번호 가져오기
$check_sql = "SELECT b_idx FROM board_comments WHERE comment_id = '$comment_id' AND member_id = '$member_id'";
$check_result = mysqli_query($con, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    echo "<script>alert('삭제 권한이 없습니다.'); history.back();</script>";
    exit;
}

$row = mysqli_fetch_assoc($check_result);
$b_idx = $row['b_idx'];

// 댓글 삭제
$delete_sql = "DELETE FROM board_comments WHERE comment_id = '$comment_id' AND member_id = '$member_id'";
$result = mysqli_query($con, $delete_sql);

if ($result) {
    echo "<script>
        alert('댓글이 삭제되었습니다.');
        window.location.href = 'community.php?open_comments=$b_idx';
      </script>";
} else {
    echo "<script>
            alert('댓글 삭제에 실패했습니다.');
            history.back();
          </script>";
}

mysqli_close($con);
?>