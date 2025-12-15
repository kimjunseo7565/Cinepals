<?php
require_once("inc/session.php");
require_once("inc/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'];
    $post_id = $_POST['post_id'];

    $con = mysqli_connect("localhost", "root", "", "moviedb");

    // 1. 게시글 정보를 reports 테이블에 저장 (완료 전에)
    $get_post = "SELECT b_title, b_contents, member_id FROM board_posts WHERE b_idx = '$post_id'";
    $post_result = mysqli_query($con, $get_post);
    
    if ($post_result && mysqli_num_rows($post_result) > 0) {
        $post_data = mysqli_fetch_assoc($post_result);
        $title = mysqli_real_escape_string($con, $post_data['b_title']);
        $content = mysqli_real_escape_string($con, $post_data['b_contents']);
        $author_id = $post_data['member_id'];
        
        // reports 테이블에 제목과 내용 저장
        $save_post_info = "UPDATE reports SET 
                          post_title = '$title',
                          post_content = '$content'
                          WHERE report_id = '$report_id'";
        mysqli_query($con, $save_post_info);
    }

    // 2. 신고 상태를 "완료"로 변경
    $update_report = "UPDATE reports SET status = '완료' WHERE report_id = '$report_id'";
    mysqli_query($con, $update_report);

    // 3. 게시글 삭제하지 않고 제목과 내용을 변경
    $update_post = "UPDATE board_posts SET 
                    b_title = '[관리자에 의해 삭제된 게시글]',
                    b_contents = '이 게시글은 신고 처리되어 삭제되었습니다.'
                    WHERE b_idx = '$post_id'";
    mysqli_query($con, $update_post);

    // 4. 해당 게시글의 댓글 삭제
    $delete_comments = "DELETE FROM board_comments WHERE b_idx = '$post_id'";
    mysqli_query($con, $delete_comments);

    // 5. 게시글 작성자에게 알림 전송
    if (isset($author_id) && isset($title)) {
        // 짧은 메시지 (기본 표시)
        $short_message = "회원님의 게시글 '$title'이(가) 커뮤니티 운영 정책 위반으로 관리자에 의해 삭제되었습니다.";
        
        // 긴 메시지 (펼쳤을 때 표시)
        $long_message = "안녕하세요, Cinepals입니다.\n\n귀하의 게시글이 커뮤니티 운영 정책을 위반하여 삭제되었습니다.\n\n커뮤니티 가이드라인을 준수하여 주시기 바랍니다.\n자세한 내용은 커뮤니티 규정을 참고해주세요.\n\n감사합니다.";
        
        // 구분자로 연결
        $notification_message = $short_message . "|||" . $long_message;
        $escaped_notification = mysqli_real_escape_string($con, $notification_message);
        
        $insert_notification = "INSERT INTO notifications (member_id, message, type, post_id, created_at) 
                               VALUES ('$author_id', '$escaped_notification', 'admin', '$post_id', NOW())";
        mysqli_query($con, $insert_notification);
    }

    mysqli_close($con);

    echo "success";
} else {
    echo "error";
}
?>