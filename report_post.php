<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 로그인 체크
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('로그인이 필요합니다.'); history.back();</script>";
    exit;
}

if (isset($_POST['post_id']) && isset($_POST['reason'])) {
    $post_id = $_POST['post_id'];
    $reason = trim($_POST['reason']);
    $reporter_id = $_SESSION['member_id'];
    
    if (empty($reason)) {
        echo "<script>alert('신고 사유를 입력해주세요.'); history.back();</script>";
        exit;
    }
    
    $con = mysqli_connect("localhost", "root", "", "moviedb");
    
    // 중복 신고 체크
    $check_sql = "SELECT COUNT(*) as count FROM reports WHERE post_id = '$post_id' AND reporter_id = '$reporter_id'";
    $check_result = mysqli_query($con, $check_sql);
    $existing = mysqli_fetch_assoc($check_result);
    
    if ($existing['count'] > 0) {
        echo "<script>alert('이미 신고한 게시글입니다.'); history.back();</script>";
    } else {
        // 게시글 정보 가져오기
        $get_post = "SELECT b_title, b_contents FROM board_posts WHERE b_idx = '$post_id'";
        $post_result = mysqli_query($con, $get_post);
        
        $post_title = '';
        $post_content = '';
        
        if ($post_result && mysqli_num_rows($post_result) > 0) {
            $post_data = mysqli_fetch_assoc($post_result);
            $post_title = mysqli_real_escape_string($con, $post_data['b_title']);
            $post_content = mysqli_real_escape_string($con, $post_data['b_contents']);
        }
        
        // 신고 저장 (게시글 정보 포함)
        $escaped_reason = mysqli_real_escape_string($con, $reason);
        $insert_sql = "INSERT INTO reports (post_id, reporter_id, reason, post_title, post_content, created_at) 
                       VALUES ('$post_id', '$reporter_id', '$escaped_reason', '$post_title', '$post_content', NOW())";
        $result = mysqli_query($con, $insert_sql);
        
        if ($result) {
            echo "<script>alert('신고가 접수되었습니다.'); location.href='community.php';</script>";
        } else {
            echo "<script>alert('신고 접수에 실패했습니다.'); history.back();</script>";
        }
    }
    
    mysqli_close($con);
} else {
    echo "<script>alert('필수 정보가 누락되었습니다.'); history.back();</script>";
}
?>