<?php
require_once("inc/session.php");
require_once("inc/db.php");
require_once("genre_filter.php"); 

// 검색어 가져오기
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// 검색 실행 - DB에서만 검색
$movies = [];
$unique_movie_ids = []; // 중복 확인용 배열
$unique_titles = []; // 제목으로도 중복 확인

if ($search_query) {
    // DB에서 영화 검색 (제목, 감독, 출연진으로 검색) - 출연진 추가!
    $query = "SELECT * FROM moviesdb WHERE title LIKE ? OR director LIKE ? OR actors LIKE ? ORDER BY release_date DESC";
    $search_param = '%' . $search_query . '%';
    $db_movies = db_select($query, [$search_param, $search_param, $search_param]);

    // 중복 제거하며 영화 목록에 추가
    foreach ($db_movies as $movie) {
        if (!in_array($movie['movie_id'], $unique_movie_ids) && !in_array($movie['title'], $unique_titles)) {
            $unique_movie_ids[] = $movie['movie_id'];
            $unique_titles[] = $movie['title'];
            $movies[] = $movie;
        }
    }

    // 검색어와의 유사도에 따라 결과 정렬
    if (!empty($movies) && !empty($search_query)) {
        usort($movies, function ($a, $b) use ($search_query) {
            // 제목 유사도 계산
            $similarity_a = similar_text(strtolower($a['title']), strtolower($search_query), $percent_a);
            $similarity_b = similar_text(strtolower($b['title']), strtolower($search_query), $percent_b);

            // 유사도 높은 순(내림차순)으로 정렬
            return $percent_b <=> $percent_a;
        });
    }
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>검색 결과 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .content_header {
            margin: 30px 0 20px;
            padding: 0 20px;
        }

        .search_info {
            margin-bottom: 15px;
            color: #aaa;
            font-size: 14px;
        }

        .movie_grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            padding: 20px;
        }

        .movie_card {
            position: relative;
            transition: transform 0.3s ease;
        }

        .movie_card:hover {
            transform: translateY(-5px);
        }

        .movie_poster {
            position: relative;
            width: 100%;
            padding-bottom: 145%;
            overflow: hidden;
            border-radius: 10px;
        }

        .movie_poster img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .movie_info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9), transparent);
            color: white;
            border-radius: 0 0 10px 10px;
        }

        .movie_title {
            font-size: 1rem;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .movie_rating {
            font-size: 0.9rem;
        }

        .movie_rating i {
            color: #ffd700;
            margin-right: 5px;
        }

        .no_results {
            grid-column: span 5;
            text-align: center;
            padding: 50px 0;
            color: #aaa;
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="content_wrap">
            <div class="content_header">
                <h2>'<?php echo htmlspecialchars($search_query); ?>' 검색 결과 (<?php echo count($movies); ?>)</h2>
            </div>

            <div class="movie_grid">
                <?php if (!empty($movies)): ?>
                    <?php foreach ($movies as $movie): ?>
                        <?php if (!empty($movie['poster_image']) && $movie['poster_image'] != 'images/default_poster.jpg'): ?>
                            <div class="movie_card" data-genre="<?php echo htmlspecialchars($movie['genre'] ?? ''); ?>">
                                <div class="movie_poster">
                                    <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>&source=<?php echo $movie['source'] ?? 'kmdb'; ?>">
                                        <img src="<?php echo isset($movie['poster_image']) ? $movie['poster_image'] : 'images/default_poster.jpg'; ?>"
                                            alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                        <div class="movie_info">
                                            <h3 class="movie_title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                                            <div class="movie_rating">
                                                <i class="fas fa-star"></i>
                                                <?php echo number_format(isset($movie['rating']) ? $movie['rating'] : 0, 1); ?>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no_results">
                        <p>검색 결과가 없습니다.</p>
                        <p style="margin-top: 10px; font-size: 14px;">영화 제목, 감독 이름, 출연진 이름으로 검색해보세요.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>
</body>

</html>