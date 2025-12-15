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

// 🔥 보안 처리 추가
$b_idx = mysqli_real_escape_string($con, $_POST['b_idx']);
$comment = mysqli_real_escape_string($con, filter_profanity(trim($_POST['comment'])));
$member_id = mysqli_real_escape_string($con, $_SESSION['member_id']);

// 댓글 내용 체크
if (empty($comment)) {
    echo "<script>alert('댓글 내용을 입력해주세요.'); history.back();</script>";
    exit;
}

// 사용자 닉네임 가져오기
$user_sql = "SELECT nickname FROM members WHERE member_id = '$member_id'";
$user_result = mysqli_query($con, $user_sql);
$user = mysqli_fetch_assoc($user_result);
$nickname = mysqli_real_escape_string($con, $user['nickname']);

// 댓글 저장
$insert_sql = "INSERT INTO board_comments (b_idx, member_id, nickname, comment, regdate) 
               VALUES ('$b_idx', '$member_id', '$nickname', '$comment', NOW())";
$result = mysqli_query($con, $insert_sql);

if ($result) {
    // 방금 생성된 댓글 ID 가져오기
    $new_comment_id = mysqli_insert_id($con);
    
    // ⭐ 게시글 작성자에게 알림 전송
    $post_sql = "SELECT member_id, b_title FROM board_posts WHERE b_idx = '$b_idx'";
    $post_result = mysqli_query($con, $post_sql);
    $post_data = mysqli_fetch_assoc($post_result);

    // 게시글 작성자와 댓글 작성자가 다른 경우에만 알림 전송
    if ($post_data && $post_data['member_id'] != $member_id) {
        $post_author_id = $post_data['member_id'];
        $post_title = mysqli_real_escape_string($con, $post_data['b_title']);

        $notification_message = "회원님의 게시글 '$post_title'에 새 댓글이 달렸습니다.";
        $escaped_notification = mysqli_real_escape_string($con, $notification_message);

        // post_id와 comment_id도 함께 저장
        $notification_sql = "INSERT INTO notifications (member_id, message, type, post_id, comment_id, created_at) 
                            VALUES ('$post_author_id', '$escaped_notification', 'comment', '$b_idx', '$new_comment_id', NOW())";
        mysqli_query($con, $notification_sql);
    }

    echo "<script>
        alert('댓글이 등록되었습니다.');
        window.location.href = 'community.php?open_comments=$b_idx';
      </script>";
} else {
    echo "<script>
            alert('댓글 등록에 실패했습니다.');
            history.back();
          </script>";
}

mysqli_close($con);