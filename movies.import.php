<?php
$con = mysqli_connect("localhost", "root", "", "moviedb");

// 모든 영화 가져오기
$query = mysqli_query($con, "SELECT * FROM movies");
$result = [];
while ($row = mysqli_fetch_assoc($query)) {
    $result[] = $row;
}

// 평점 높은 순으로 영화 가져오기
$rating_query = mysqli_query($con, "SELECT * FROM movies ORDER BY rating DESC LIMIT 5");
$result_rating_H = [];
while ($row = mysqli_fetch_assoc($rating_query)) {
    $result_rating_H[] = $row;
}

// 최신 영화 가져오기
$latest_query = mysqli_query($con, "SELECT * FROM movies ORDER BY release_date DESC");
$result_latest = [];
while ($row = mysqli_fetch_assoc($latest_query)) {
    $result_latest[] = $row;
}

// 인기 리뷰 가져오기
$top_reviews_query = mysqli_query($con, "
   SELECT r.*, m.title, m.poster_image 
   FROM reviews r 
   JOIN movies m ON r.movie_id = m.movie_id 
   ORDER BY r.views DESC 
   LIMIT 1
");
$top_reviews = [];
while ($row = mysqli_fetch_assoc($top_reviews_query)) {
    $top_reviews[] = $row;
}

// 최신 리뷰 가져오기
$latest_reviews_query = mysqli_query($con, "
   SELECT r.*, m.title, m.poster_image 
   FROM reviews r 
   JOIN movies m ON r.movie_id = m.movie_id 
   ORDER BY r.created_at DESC 
   LIMIT 5
");
$latest_reviews = [];
while ($row = mysqli_fetch_assoc($latest_reviews_query)) {
    $latest_reviews[] = $row;
}


// movies.import.php 에서
$top_collections_query = mysqli_query($con, "
   SELECT c.*, m.title, m.poster_image, m.movie_id
   FROM collections c 
   JOIN collection_movies cm ON c.collection_id = cm.collection_id 
   JOIN movies m ON cm.movie_id = m.movie_id 
   ORDER BY c.likes DESC 
   LIMIT 1
");
$top_collections = [];
while ($row = mysqli_fetch_assoc($top_collections_query)) {
    $top_collections[] = $row;
}
