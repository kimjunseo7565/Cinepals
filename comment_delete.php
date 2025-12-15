<?php
require_once("inc/session.php");
require_once("inc/db.php");

if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

if (isset($_POST['review_id'])) {
    $review_id = $_POST['review_id'];
    $con = mysqli_connect("localhost", "root", "", "moviedb");

    // 먼저 리뷰 정보만 가져오기
    $check_sql = "SELECT * FROM movie_reviews_new WHERE review_id = '$review_id' AND member_id = '" . $_SESSION['member_id'] . "'";
    $check_result = mysqli_query($con, $check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        echo "<script>alert('삭제할 리뷰를 찾을 수 없거나 권한이 없습니다.'); history.back();</script>";
        exit;
    }

    $review = mysqli_fetch_assoc($check_result);
    $movie_id = $review['movie_id'];

    // 영화 정보 가져오기 (리다이렉션용)
    $movie_sql = "SELECT movie_id FROM moviesdb WHERE id = '$movie_id'";
    $movie_result = mysqli_query($con, $movie_sql);
    $movie_info = mysqli_fetch_assoc($movie_result);
    $movie_external_id = $movie_info ? $movie_info['movie_id'] : $movie_id;

    // 리뷰 삭제 (간단하게)
    $delete_sql = "DELETE FROM movie_reviews_new WHERE review_id = '$review_id' AND member_id = '" . $_SESSION['member_id'] . "'";
    $result = mysqli_query($con, $delete_sql);

    if ($result) {
        // 영화 평점 업데이트 (간단하게)
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
        $update_sql = "UPDATE moviesdb SET rating = '$new_rating' WHERE movie_id = '$movie_id'";
        mysqli_query($con, $update_sql);

        // 어디서 왔는지 확인해서 적절한 곳으로 리다이렉션
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if (strpos($referer, 'my_comments.php') !== false) {
            echo "<script>alert('리뷰가 삭제되었습니다.'); location.href='my_comments.php';</script>";
        } else {
            echo "<script>alert('리뷰가 삭제되었습니다.'); location.href='movie_detail.php?id=" . urlencode($movie_external_id) . "';</script>";
        }
    } else {
        echo "<script>alert('삭제 처리 중 오류가 발생했습니다.'); history.back();</script>";
    }

    mysqli_close($con);
} else {
    echo "<script>alert('리뷰 ID가 제공되지 않았습니다.'); history.back();</script>";
}
