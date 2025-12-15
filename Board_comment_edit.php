<?php
require_once("inc/session.php");
require_once("inc/db.php");
require_once("inc/profanity_filter.php");

// 로그인 체크
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('로그인이 필요합니다.'); history.back();</script>";
    exit;
}

$con = mysqli_connect("localhost", "root", "", "moviedb");

// 수정 모드
if (isset($_POST['comment_id']) && isset($_POST['comment'])) {
    $comment_id = mysqli_real_escape_string($con, $_POST['comment_id']);
    $comment = mysqli_real_escape_string($con, filter_profanity(trim($_POST['comment'])));
    $member_id = mysqli_real_escape_string($con, $_SESSION['member_id']);
    
    // 댓글 내용 체크
    if (empty($comment)) {
        echo "<script>alert('댓글 내용을 입력해주세요.'); history.back();</script>";
        exit;
    }
    
    // 본인의 댓글인지 확인
    $check_sql = "SELECT b_idx FROM board_comments WHERE comment_id = '$comment_id' AND member_id = '$member_id'";
    $check_result = mysqli_query($con, $check_sql);
    
    if (mysqli_num_rows($check_result) == 0) {
        echo "<script>alert('수정 권한이 없습니다.'); history.back();</script>";
        exit;
    }
    
    $row = mysqli_fetch_assoc($check_result);
    $b_idx = $row['b_idx'];
    
    // 댓글 수정
    $update_sql = "UPDATE board_comments SET comment = '$comment', update_date = NOW() WHERE comment_id = '$comment_id' AND member_id = '$member_id'";
    $result = mysqli_query($con, $update_sql);
    
    if ($result) {
        echo "<script>
            alert('댓글이 수정되었습니다.');
            window.location.href = 'community.php?open_comments=$b_idx';
          </script>";
    } else {
        echo "<script>
                alert('댓글 수정에 실패했습니다.');
                history.back();
              </script>";
    }
} else {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
}

mysqli_close($con);
?>