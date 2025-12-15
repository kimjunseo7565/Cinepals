<?php
require_once("inc/session.php");
require_once("inc/db.php");
require_once("inc/kobis_api.php");
require_once("inc/movie_api_combined.php");

// 관리자 권한 체크
if (!isset($_SESSION['member_id'])) {
    die("<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>");
}

// 시간 제한 늘리기
set_time_limit(300);

$con = mysqli_connect("localhost", "root", "", "moviedb");

echo "<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>영화 데이터 업데이트 - Cinepals</title>
    <link rel='stylesheet' href='css/style.css'>
    <style>
        .update_container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            text-align: center;
        }
        .message_box {
            background: #f8f9fa;
            padding: 30px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #2c5282;
        }
        .success { 
            border-left-color: #28a745; 
            background: #d4edda;
            color: #155724;
        }
        .back_btn {
            background: #e50914;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 30px;
        }
        .back_btn:hover {
            background: #c40711;
        }
        h2 {
            color: #333;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>";

require_once("inc/header.php");

echo "<div class='update_container'>
    <h2>영화 데이터 업데이트</h2>";

try {
    // 기존 영화 데이터 모두 삭제
    mysqli_query($con, "DELETE FROM moviesdb");

    // 새로운 영화 데이터 가져오기
    $now_playing_movies = get_combined_now_playing_movies();
    $upcoming_movies = get_combined_upcoming_movies();

    // 업데이트 시간 기록
    $update_time_sql = "INSERT INTO site_settings (name, value) VALUES ('last_movie_update', NOW()) 
                        ON DUPLICATE KEY UPDATE value = NOW()";
    mysqli_query($con, $update_time_sql);

    // ✅ 사용자 리뷰 기반 평점 재계산
    $rating_update = "
        UPDATE moviesdb m
        SET rating = (
            SELECT AVG(r.rating) 
            FROM movie_reviews_new r 
            WHERE r.movie_id = m.movie_id
        ),
        total_votes = (
            SELECT COUNT(*) 
            FROM movie_reviews_new r 
            WHERE r.movie_id = m.movie_id
        )
        WHERE EXISTS (
            SELECT 1 FROM movie_reviews_new r2 
            WHERE r2.movie_id = m.movie_id
        )
    ";
    mysqli_query($con, $rating_update);

    // 포스터가 있는 영화만 카운트 (manager_home.php와 동일한 방식)
    $now_playing_count = mysqli_fetch_assoc(mysqli_query(
        $con,
        "SELECT COUNT(*) as cnt FROM moviesdb 
         WHERE status = 'now_playing'
         AND poster_image != 'images/default_poster.jpg'
         AND poster_image IS NOT NULL 
         AND poster_image != ''"
    ))['cnt'];

    $upcoming_count = mysqli_fetch_assoc(mysqli_query(
        $con,
        "SELECT COUNT(*) as cnt FROM moviesdb 
         WHERE status = 'upcoming'
         AND poster_image != 'images/default_poster.jpg'
         AND poster_image IS NOT NULL 
         AND poster_image != ''"
    ))['cnt'];

    echo "<div class='message_box success'>
        <h3>✓ 영화 데이터 업데이트 완료!</h3>
        <p>현재상영작 " . $now_playing_count . "개와 개봉예정작 " . $upcoming_count . "개가 업데이트되었습니다.</p>
        
    </div>";
} catch (Exception $e) {
    echo "<div class='message_box' style='border-left-color: #dc3545; background: #f8d7da; color: #721c24;'>
        <h3>✗ 업데이트 실패</h3>
        <p>오류가 발생했습니다.</p>
    </div>";
}

echo "<div style='text-align: center;'>
    <button onclick='location.href=\"manager_home.php\"' class='back_btn'>
        관리자 페이지로 돌아가기
    </button>
</div>";

echo "</div>";

require_once("inc/footer.php");
mysqli_close($con);

echo "</body></html>";
