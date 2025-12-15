<?php
require_once("inc/session.php");
require_once("inc/db.php");
require_once("inc/profanity_filter.php");

if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

if (isset($_POST['review_id']) && isset($_POST['content']) && isset($_POST['rating'])) {
    $con = mysqli_connect("localhost", "root", "", "moviedb");

    // 🔥 보안 처리 추가
    $review_id = mysqli_real_escape_string($con, $_POST['review_id']);
    $content = mysqli_real_escape_string($con, filter_profanity($_POST['content']));
    $rating = mysqli_real_escape_string($con, $_POST['rating']);
    $member_id = mysqli_real_escape_string($con, $_SESSION['member_id']);

    // 리뷰 정보 가져오기
    $check_sql = "SELECT * FROM movie_reviews_new WHERE review_id = '$review_id' AND member_id = '$member_id'";
    $check_result = mysqli_query($con, $check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        echo "<script>alert('수정할 리뷰를 찾을 수 없거나 권한이 없습니다.'); history.back();</script>";
        exit;
    }

    $review = mysqli_fetch_assoc($check_result);
    $movie_id = $review['movie_id'];

    // 리뷰 업데이트
    $update_sql = "UPDATE movie_reviews_new 
                   SET content = '$content', rating = '$rating' 
                   WHERE review_id = '$review_id' AND member_id = '$member_id'";
    $result = mysqli_query($con, $update_sql);

    if ($result) {
        // 영화 평점 재계산
        $stats_sql = "SELECT COUNT(*) as count, SUM(rating) as sum FROM movie_reviews_new WHERE movie_id = '$movie_id'";
        $stats_result = mysqli_query($con, $stats_sql);
        $stats = mysqli_fetch_assoc($stats_result);

        // 평점 계산
        $new_rating = 0;
        if ($stats['count'] > 0) {
            $new_rating = round($stats['sum'] / $stats['count'], 1);
            if ($new_rating > 10) $new_rating = 10;
        }

        // 영화 평점 업데이트
        // ✅ id → movie_id로 변경
        $update_movie_sql = "UPDATE moviesdb SET rating = '$new_rating' WHERE movie_id = '$movie_id'";
        mysqli_query($con, $update_movie_sql);

        // 영화 정보 가져오기 (리다이렉션용)
        $movie_sql = "SELECT movie_id FROM moviesdb WHERE id = '$movie_id'";
        $movie_result = mysqli_query($con, $movie_sql);
        $movie_info = mysqli_fetch_assoc($movie_result);
        $movie_external_id = $movie_info ? $movie_info['movie_id'] : $movie_id;

        // 어디서 왔는지 확인해서 적절한 곳으로 리다이렉션
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if (strpos($referer, 'my_comments.php') !== false) {
            echo "<script>alert('리뷰가 수정되었습니다.'); location.href='my_comments.php';</script>";
        } else {
            echo "<script>alert('리뷰가 수정되었습니다.'); location.href='movie_detail.php?id=" . urlencode($movie_external_id) . "';</script>";
        }
    } else {
        echo "<script>alert('수정 처리 중 오류가 발생했습니다.'); history.back();</script>";
    }

    mysqli_close($con);
} else {
    echo "<script>alert('필수 정보가 누락되었습니다.'); history.back();</script>";
}
