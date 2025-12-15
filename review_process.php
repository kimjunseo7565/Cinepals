<?php
require_once("inc/session.php");
require_once("inc/profanity_filter.php");

if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

if (isset($_POST['movie_id']) && isset($_POST['rating']) && isset($_POST['content'])) {
    $con = mysqli_connect("localhost", "root", "", "moviedb");
    
    // 🔥 이 한 줄만 추가!
    mysqli_set_charset($con, "utf8mb4");
    
    $movie_id = $_POST['movie_id'];
    $rating = $_POST['rating'];
    
    // 🔥 이것만 바꾸기 (따옴표 처리)
    $content = str_replace("'", "''", filter_profanity($_POST['content']));
    
    $member_id = $_SESSION['member_id'];

    if (empty($content)) {
        echo "<script>alert('리뷰 내용을 입력해주세요.'); history.back();</script>";
        exit;
    }

    if ($rating < 1 || $rating > 10) {
        echo "<script>alert('별점을 선택해주세요.'); history.back();</script>";
        exit;
    }

    // moviesdb.movie_id (문자열 ID)를 가져오기
    $get_movie_id_sql = "SELECT movie_id FROM moviesdb WHERE id = '$movie_id'";
    $movie_id_result = mysqli_query($con, $get_movie_id_sql);
    $movie_data = mysqli_fetch_assoc($movie_id_result);
    $movie_external_id = $movie_data['movie_id'];

    // 이미 리뷰를 작성했는지 확인
    $check_sql = "SELECT * FROM movie_reviews_new WHERE movie_id = '$movie_external_id' AND member_id = '$member_id'";
    $existing_review = mysqli_query($con, $check_sql);

    if (mysqli_num_rows($existing_review) > 0) {
        echo "<script>alert('이미 리뷰를 작성하셨습니다.'); history.back();</script>";
        exit;
    }

    // 리뷰 저장
    $insert_sql = "INSERT INTO movie_reviews_new (movie_id, member_id, rating, content, created_at) 
                   VALUES ('$movie_external_id', '$member_id', '$rating', '$content', NOW())";
    $result = mysqli_query($con, $insert_sql);
    
    if ($result) {
        // 평균 평점 다시 계산
        $stats_sql = "SELECT COUNT(*) as count, SUM(rating) as sum FROM movie_reviews_new WHERE movie_id = '$movie_external_id'";
        $stats_result = mysqli_query($con, $stats_sql);
        $stats = mysqli_fetch_assoc($stats_result);
        
        // 평균 계산
        $new_rating = 0;
        if ($stats['count'] > 0) {
            $new_rating = round($stats['sum'] / $stats['count'], 1);
            if ($new_rating > 10) $new_rating = 10;
        }
        
        // 영화 평점 업데이트
        $update_sql = "UPDATE moviesdb SET rating = '$new_rating' WHERE movie_id = '$movie_external_id'";
        mysqli_query($con, $update_sql);
        
        echo "<script>alert('리뷰가 등록되었습니다.'); location.href='movie_detail.php?id=" . $movie_external_id . "';</script>";
    } else {
        echo "<script>alert('리뷰 등록에 실패했습니다.'); history.back();</script>";
    }
    
    mysqli_close($con);
} else {
    echo "<script>alert('필수 정보가 누락되었습니다.'); history.back();</script>";
}
?>