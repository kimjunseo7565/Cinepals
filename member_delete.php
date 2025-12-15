<?php
require_once("inc/session.php");

$member_id = $_SESSION['member_id'];
$con = mysqli_connect("localhost", "root", "", "moviedb");

if (!$member_id) {
    echo "<script>
            alert('로그인 정보가 유효하지 않습니다.');
            location.href = 'login.php';
          </script>";
    exit();
}

// 비밀번호 확인
if (isset($_POST['password'])) {
    $password = $_POST['password'];

    // 간단한 비밀번호 확인
    $sql = "SELECT password FROM members WHERE member_id = '$member_id'";
    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) == 0) {
        echo "<script>
                alert('사용자 정보를 찾을 수 없습니다.');
                history.back();
              </script>";
        exit();
    }

    $user_data = mysqli_fetch_assoc($result);

    if ($password != $user_data['password']) {
        echo "<script>
                alert('비밀번호가 일치하지 않습니다.');
                history.back();
              </script>";
        exit();
    }

    // 간단하게 순서대로 처리
    
    // 1. 사용자가 작성한 리뷰 익명화
    mysqli_query($con, "UPDATE movie_reviews_new SET anonymous = 1 WHERE member_id = '$member_id'");

    // 2. 좋아요/저장 기록 삭제
    mysqli_query($con, "DELETE FROM user_actions_new WHERE member_id = '$member_id'");

    // 3. 게시글 익명화
    mysqli_query($con, "UPDATE board_posts SET is_anonymous = 1, nick_name = '탈퇴한 회원' WHERE member_id = '$member_id'");

    // 4. 게시글 댓글 익명화
    mysqli_query($con, "UPDATE board_comments SET nickname = '탈퇴한 회원' WHERE member_id = '$member_id'");

    // 5. 회원 정보 삭제
    $delete_result = mysqli_query($con, "DELETE FROM members WHERE member_id = '$member_id'");

    if ($delete_result) {
        session_destroy();
        echo "<script>
                alert('회원탈퇴가 완료되었습니다. 그동안 이용해주셔서 감사합니다.');
                location.href = 'index.php';
              </script>";
    } else {
        echo "<script>
                alert('회원탈퇴 처리 중 오류가 발생했습니다. 다시 시도해주세요.');
                history.back();
              </script>";
    }
} else {
    echo "<script>
            alert('잘못된 접근입니다.');
            location.href = 'mypage.php';
          </script>";
}

mysqli_close($con);
?>