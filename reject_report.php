<?php
require_once("inc/session.php");
require_once("inc/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'];

    $con = mysqli_connect("localhost", "root", "", "moviedb");

    // 신고 정보 가져오기 (신고자 ID, 사유, 게시글 제목)
    $get_report = "SELECT r.reporter_id, r.reason, r.post_id, r.post_title, bp.b_title 
                   FROM reports r
                   LEFT JOIN board_posts bp ON r.post_id = bp.b_idx
                   WHERE r.report_id = '$report_id'";
    $result = mysqli_query($con, $get_report);
    $report_data = mysqli_fetch_assoc($result);
    $reporter_id = $report_data['reporter_id'];
    $reason = $report_data['reason'];
    
    // 게시글 제목 (reports에 저장된 것 우선, 없으면 실제 게시글에서)
    $post_title = !empty($report_data['post_title']) ? $report_data['post_title'] : $report_data['b_title'];
    if (empty($post_title)) {
        $post_title = "게시글";
    }

    // 신고 상태를 "기각"으로 변경
    $update_report = "UPDATE reports SET status = '기각' WHERE report_id = '$report_id'";
    mysqli_query($con, $update_report);

    // 신고자에게 알림 전송
    // 짧은 메시지 (기본 표시)
    $short_message = "회원님이 신고하신 게시글 '$post_title'에 대한 신고가 기각되었습니다.";
    
    // 긴 메시지 (펼쳤을 때 표시)
    $long_message = "안녕하세요, Cinepals입니다.\n\n회원님께서 신고하신 내용(사유: $reason)에 대해 검토를 완료하였습니다.\n\n검토 결과, 해당 게시글은 커뮤니티 운영 정책에 위배되지 않는 것으로 확인되어 신고가 기각되었음을 알려드립니다.\n\n추가 문의사항이 있으시면 고객센터로 연락 주시기 바랍니다.\n\n감사합니다.";
    
    // 구분자로 연결
    $message = $short_message . "|||" . $long_message;
    $escaped_message = mysqli_real_escape_string($con, $message);
    
    $insert_notification = "INSERT INTO notifications (member_id, message, type, created_at) 
                           VALUES ('$reporter_id', '$escaped_message', 'report', NOW())";
    mysqli_query($con, $insert_notification);

    mysqli_close($con);

    echo "success";
} else {
    echo "error";
}
?>