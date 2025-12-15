<?php
require_once("inc/session.php");

$member_id = $_SESSION['member_id'];
$con = mysqli_connect("localhost", "root", "", "moviedb");

// 폼에서 데이터 가져오기
$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$bio = $_POST['bio'];

// 생년월일 조합
$birth_year = trim($_POST['birth_year']);
$birth_month = trim($_POST['birth_month']);
$birth_day = trim($_POST['birth_day']);

// 생년월일 처리
if (!empty($birth_year) && !empty($birth_month) && !empty($birth_day)) {
    $birth_date = $birth_year . '-' . sprintf('%02d', $birth_month) . '-' . sprintf('%02d', $birth_day);
    $birth_sql = "birth_date='$birth_date',";
} else if (empty($birth_year) && empty($birth_month) && empty($birth_day)) {
    $birth_sql = "birth_date=NULL,";
} else {
    echo "<script>
            alert('생일을 입력하시려면 년/월/일을 모두 입력해주세요.');
            history.back();
          </script>";
    exit;
}

// 기존 닉네임 가져오기
$old_name_sql = "SELECT nickname FROM members WHERE member_id = '$member_id'";
$old_name_result = mysqli_query($con, $old_name_sql);
$old_name_data = mysqli_fetch_assoc($old_name_result);
$old_nickname = $old_name_data['nickname'];

// 회원 정보 업데이트
$sql = "UPDATE members SET 
        name='$name',
        nickname='$name',
        email='$email',
        phone='$phone',
        $birth_sql
        bio='$bio'
        WHERE member_id='$member_id'";

$result = mysqli_query($con, $sql);

if (mysqli_affected_rows($con) > 0) {
    // 닉네임이 변경된 경우 커뮤니티 관련 데이터도 업데이트
    if ($old_nickname !== $name) {
        // 게시글 작성자명 업데이트 (익명이 아닌 경우만)
        $update_posts_sql = "UPDATE board_posts SET 
                            nick_name = '$name' 
                            WHERE member_id = '$member_id' AND is_anonymous = 0";
        mysqli_query($con, $update_posts_sql);

        // 댓글 작성자명 업데이트
        $update_comments_sql = "UPDATE board_comments SET 
                               nickname = '$name' 
                               WHERE member_id = '$member_id'";
        mysqli_query($con, $update_comments_sql);

        // 영화 리뷰도 있다면 업데이트 (익명이 아닌 경우만)
        $check_review_table = "SHOW TABLES LIKE 'movie_reviews_new'";
        $table_exists = mysqli_query($con, $check_review_table);
        if (mysqli_num_rows($table_exists) > 0) {
            // movie_reviews_new 테이블에는 nickname 컬럼이 없으므로 members 테이블 조인으로 처리
            // 별도 업데이트는 필요 없음 (조인으로 실시간 반영)
        }
    }

    echo "<script>
            alert('수정되었습니다.');
            location.href='mypage.php';
          </script>";
} else {
    echo "<script>
            alert('변경된 내용이 없습니다.');
            location.href='mypage.php';
          </script>";
}

mysqli_close($con);
