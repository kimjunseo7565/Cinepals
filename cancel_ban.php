<?php
require_once("inc/session.php");
require_once("inc/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'];
    $post_id = $_POST['post_id'];

    $con = mysqli_connect("localhost", "root", "", "moviedb");

    // 게시글 정보를 reports 테이블에서 가져오기
    $get_post_info = "SELECT post_title, post_content FROM reports WHERE report_id = '$report_id'";
    $result = mysqli_query($con, $get_post_info);
    $post_info = mysqli_fetch_assoc($result);

    // 신고 상태를 "대기"로 변경
    $update_report = "UPDATE reports SET status = '대기' WHERE report_id = '$report_id'";
    mysqli_query($con, $update_report);

    // 게시글 복구
    $title = mysqli_real_escape_string($con, $post_info['post_title']);
    $content = mysqli_real_escape_string($con, $post_info['post_content']);
    $update_post = "UPDATE board_posts SET 
                    b_title = '$title',
                    b_contents = '$content'
                    WHERE b_idx = '$post_id'";
    mysqli_query($con, $update_post);

    mysqli_close($con);

    echo "success";
} else {
    echo "error";
}
?>