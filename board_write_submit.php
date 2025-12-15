<?php
require_once("inc/session.php");
require_once("inc/db.php");
require_once("inc/profanity_filter.php");

if (trim($_POST['b_title']) == "" || trim($_POST['b_contents']) == "") {
    echo "<script>alert('제목과 내용을 입력해주세요.'); history.back();</script>";
    exit;
}

$con = mysqli_connect("localhost", "root", "", "moviedb");

// 현재 사용자 정보 가져오기
$member_sql = "SELECT nickname FROM members WHERE member_id = '" . $_SESSION['member_id'] . "'";
$member_result = mysqli_query($con, $member_sql);
$member = mysqli_fetch_assoc($member_result);

// 익명 체크 여부에 따라 작성자 설정
$writer_name = isset($_POST['is_anonymous']) ? '익명' : $member['nickname'];
$is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

// 🔥 보안 처리 추가
$b_title = mysqli_real_escape_string($con, filter_profanity($_POST['b_title']));
$b_contents = mysqli_real_escape_string($con, filter_profanity($_POST['b_contents']));
$member_id = mysqli_real_escape_string($con, $_SESSION['member_id']);
$writer_name = mysqli_real_escape_string($con, $writer_name);

if (isset($_POST['b_idx'])) {
    // 수정 모드
    $b_idx = mysqli_real_escape_string($con, $_POST['b_idx']);

    $update_sql = "UPDATE board_posts 
                   SET b_title = '$b_title',
                       b_contents = '$b_contents',
                       nick_name = '$writer_name',
                       is_anonymous = '$is_anonymous',
                       update_date = NOW()
                   WHERE b_idx = '$b_idx' 
                   AND member_id = '$member_id'";

    $result = mysqli_query($con, $update_sql);
} else {
    // 새글 작성 모드
    $insert_sql = "INSERT INTO board_posts 
                   (b_title, b_contents, nick_name, member_id, is_anonymous, regdate) 
                   VALUES 
                   ('$b_title', '$b_contents', '$writer_name', '$member_id', '$is_anonymous', NOW())";

    $result = mysqli_query($con, $insert_sql);
}

if ($result) {
    echo "<script>
        alert('" . (isset($_POST['b_idx']) ? '수정' : '등록') . " 되었습니다.');
        location.href = 'community.php';
    </script>";
} else {
    echo "<script>alert('저장하지 못했습니다.'); history.back();</script>";
}

mysqli_close($con);
?>