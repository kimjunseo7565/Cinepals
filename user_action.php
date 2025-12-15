<?php
require_once("inc/session.php");

// 수정된 코드
if (!isset($_SESSION['member_id'])) {
    // AJAX 요청인지 확인
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo 'login_required';
        exit;
    }
    // 일반 form 요청
    echo "<script>alert('로그인 후 이용바랍니다.'); location.href='login.php';</script>";
    exit;
}

if (isset($_POST['movie_id']) && isset($_POST['action_type'])) {
    $movie_id = $_POST['movie_id'];
    $action_type = $_POST['action_type'];
    $member_id = $_SESSION['member_id'];

    // 유효한 액션 타입인지 확인
    if ($action_type != 'like' && $action_type != 'save') {
        // AJAX vs Form 구분
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo 'error';
            exit;
        }
        echo "<script>alert('잘못된 요청입니다.'); history.back();</script>";
        exit;
    }

    $con = mysqli_connect("localhost", "root", "", "moviedb");

    // 기존 액션 확인 (간단하게)
    $check_sql = "SELECT * FROM user_actions_new WHERE movie_id = '$movie_id' AND member_id = '$member_id' AND action_type = '$action_type'";
    $existing_action = mysqli_query($con, $check_sql);

    if (mysqli_num_rows($existing_action) > 0) {
        // 액션 삭제 (토글)
        $delete_sql = "DELETE FROM user_actions_new WHERE movie_id = '$movie_id' AND member_id = '$member_id' AND action_type = '$action_type'";
        $result = mysqli_query($con, $delete_sql);
    } else {
        // 액션 추가
        $insert_sql = "INSERT INTO user_actions_new (movie_id, member_id, action_type) VALUES ('$movie_id', '$member_id', '$action_type')";
        $result = mysqli_query($con, $insert_sql);
    }

    if ($result) {
        // AJAX 요청인지 확인
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo 'success';  // AJAX 응답
            exit;
        }
        
        // 일반 form 요청 - mypage_movies.php에서 온 경우 해당 페이지로 리다이렉션
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if (strpos($referer, 'mypage_movies.php') !== false) {
            echo "<script>location.href='mypage_movies.php';</script>";
        } else {
            echo "<script>location.href='movie_detail.php?id=" . $movie_id . "';</script>";
        }
    } else {
        // AJAX vs Form 구분
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo 'error';
            exit;
        }
        echo "<script>alert('처리 중 오류가 발생했습니다.'); history.back();</script>";
    }
    
    mysqli_close($con);
} else {
    // AJAX vs Form 구분
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo 'error';
        exit;
    }
    echo "<script>alert('필수 정보가 누락되었습니다.'); history.back();</script>";
}
?>