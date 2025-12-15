<?php
require_once("inc/session.php");
require_once("inc/db.php");
require_once("inc/kobis_api.php");
require_once("inc/movie_api_combined.php");
require_once("genre_filter.php");  // ⭐ UI만 사용

// DB에서 영화 데이터 가져오기
$now_playing_movies = get_movies_from_db('now_playing');
$upcoming_movies = get_movies_from_db('upcoming');

// 중복 제거하면서 병합
$all_movies = [];
$movie_ids = [];

foreach ($now_playing_movies as $movie) {
    $movie['status'] = 'now_playing';
    $all_movies[] = $movie;
    $movie_ids[] = $movie['movie_id'];
}

foreach ($upcoming_movies as $movie) {
    if (!in_array($movie['movie_id'], $movie_ids)) {
        $movie['status'] = 'upcoming';
        $all_movies[] = $movie;
        $movie_ids[] = $movie['movie_id'];
    }
}

$page_title = '영화 예매';
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
        (function() {
            var savedTheme = localStorage.getItem('theme') || 'dark';

            if (savedTheme === 'light') {
                document.documentElement.style.backgroundColor = '#fff';
                document.documentElement.style.color = '#333';
            } else {
                document.documentElement.style.backgroundColor = '#000';
                document.documentElement.style.color = '#fff';
            }
        })();
    </script>

    <title><?php echo $page_title; ?> - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* 헤더 종 아이콘 위치 고정 */
        .auth_btns {
            display: flex !important;
            align-items: center !important;
            gap: 15px !important;
        }

        .notification_btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-top: 2px !important;
            margin-right: 20px !important;
            /* ⭐ 추가 ⭐ */
        }

        /* ⭐ 이 부분 추가! */
        .notification_badge {
            top: -8px !important;
            /* movies.php에서는 원래 위치 유지 */
        }

        /* ⭐ 닉네임 왼쪽으로 이동 - 새로 추가 ⭐ */
        .signup_btn {
            margin-right: 5px !important;
        }

        .category_tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #333;
        }

        .category_tab {
            padding: 12px 24px;
            background: transparent;
            color: #aaa;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .category_tab.active {
            color: #fff;
            border-bottom: 3px solid #e50914;
        }

        .category_tab:hover {
            color: #fff;
        }

        .sort_filter {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding-left: 20px;
        }

        .sort_filter span {
            margin-right: 10px;
            color: #aaa;
        }

        .sort_btn {
            padding: 5px 8px;
            margin-right: 2px;
            background: transparent;
            color: #aaa;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .sort_btn:hover {
            background: transparent;
            color: #fff;
        }

        .sort_btn.active {
            background: transparent;
            color: #e50914;
            font-weight: bold;
        }

        .movie_grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            padding: 20px 0;
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

        .booking_button {
            text-align: center;
            margin-top: 10px;
        }

        .book_btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #e50914;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .book_btn:hover {
            background-color: #b2070f;
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="content_wrap">
            <div class="content_header">
                <h2><?php echo $page_title; ?></h2>
            </div>

            <div class="category_tabs">
                <a href="javascript:void(0)" class="category_tab active" data-category="all" onclick="filterMovies('all')">전체 영화</a>
                <a href="javascript:void(0)" class="category_tab" data-category="now_playing" onclick="filterMovies('now_playing')">상영 중</a>
                <a href="javascript:void(0)" class="category_tab" data-category="upcoming" onclick="filterMovies('upcoming')">개봉 예정</a>
            </div>

            <div class="sort_filter">
                <span>정렬:</span>
                <a href="javascript:void(0)" class="sort_btn active" data-sort="popularity" onclick="sortMovies('popularity')">인기순</a>
                <a href="javascript:void(0)" class="sort_btn" data-sort="latest" onclick="sortMovies('latest')">최신순</a>
                <a href="javascript:void(0)" class="sort_btn" data-sort="release" onclick="sortMovies('release')">개봉일순</a>
            </div>

            <div class="movie_grid">
                <?php if (!empty($all_movies)): ?>
                    <?php foreach ($all_movies as $movie): ?>
                        <?php if (!empty($movie['poster_image']) && $movie['poster_image'] != 'images/default_poster.jpg'): ?>
                            <div class="movie_card"
                                data-genre="<?php echo htmlspecialchars($movie['genre'] ?? ''); ?>"
                                data-status="<?php echo $movie['status']; ?>"
                                data-release="<?php echo $movie['release_date']; ?>"
                                data-audience="<?php echo $movie['audience_count'] ?? 0; ?>"
                                data-title="<?php echo htmlspecialchars($movie['title']); ?>">

                                <div class="movie_poster">
                                    <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>&source=<?php echo $movie['source']; ?>">
                                        <img src="<?php echo $movie['poster_image']; ?>"
                                            alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                            loading="lazy">
                                        <div class="movie_info">
                                            <h3 class="movie_title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                                            <div class="movie_rating">
                                                <i class="fas fa-star"></i>
                                                <?php echo number_format($movie['rating'], 1); ?>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <!-- 수정된 코드 -->
                                <div class="booking_button">
                                    <?php
                                    $oneYearAgo = date('Y-m-d', strtotime('-1 year'));
                                    $isRerelease = ($movie['release_date'] < $oneYearAgo && $movie['status'] == 'now_playing');
                                    ?>
                                    <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>&source=<?php echo $movie['source']; ?>"
                                        class="book_btn"><?php echo $isRerelease ? '재개봉' : '예매하기'; ?></a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no_results">
                        <p>영화 정보가 없습니다.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>

    <script>
        // ⭐ URL 파라미터 확인 (페이지 첫 로드 시에만)
        var urlParams = new URLSearchParams(window.location.search);
        var urlCategory = urlParams.get('category');

        // 현재 필터 상태 - URL에서 받은 값으로 초기화
        var currentCategory = (urlCategory === 'now_playing' || urlCategory === 'upcoming') ? urlCategory : 'all';
        var currentSort = 'popularity';
        var currentGenre = '';

        // 장르 필터 함수
        function filterByGenre(selectedGenre) {
            currentGenre = selectedGenre;
            document.querySelectorAll('.genre_btn').forEach(btn => {
                btn.classList.remove('active');
            });
            if (selectedGenre && selectedGenre !== '') {
                const targetBtn = document.querySelector(`.genre_btn[onclick*="'${selectedGenre}'"]`);
                if (targetBtn) targetBtn.classList.add('active');
            } else {
                const allBtn = document.querySelector('.genre_btn[onclick*="\'\'"]');
                if (allBtn) allBtn.classList.add('active');
            }
            applyFilters();
        }

        function updateSortButtons() {
            var popularityBtn = document.querySelector('[data-sort="popularity"]');
            var latestBtn = document.querySelector('[data-sort="latest"]');
            var releaseBtn = document.querySelector('[data-sort="release"]');

            document.querySelectorAll('.sort_btn').forEach(function(btn) {
                btn.classList.remove('active');
            });

            if (currentCategory === 'upcoming') {
                if (popularityBtn) popularityBtn.style.display = 'none';
                if (latestBtn) latestBtn.style.display = 'none';
                if (releaseBtn) {
                    releaseBtn.style.display = 'inline-block';
                    releaseBtn.classList.add('active');
                }
                currentSort = 'release';
            } else {
                if (popularityBtn) popularityBtn.style.display = 'inline-block';
                if (latestBtn) latestBtn.style.display = 'inline-block';
                if (releaseBtn) releaseBtn.style.display = 'none';

                if (currentSort === 'release') {
                    currentSort = 'popularity';
                }

                if (currentSort === 'popularity' && popularityBtn) {
                    popularityBtn.classList.add('active');
                } else if (currentSort === 'latest' && latestBtn) {
                    latestBtn.classList.add('active');
                }
            }
        }

        function filterMovies(category) {
            currentCategory = category;

            document.querySelectorAll('.category_tab').forEach(function(tab) {
                tab.classList.remove('active');
            });
            var activeTab = document.querySelector('[data-category="' + category + '"]');
            if (activeTab) activeTab.classList.add('active');

            // ⭐ 개봉 예정은 자동으로 개봉일순 정렬
            if (category === 'upcoming') {
                currentSort = 'release';
            }

            updateSortButtons();
            applyFilters();
        }

        function sortMovies(sort) {
            currentSort = sort;

            document.querySelectorAll('.sort_btn').forEach(function(btn) {
                btn.classList.remove('active');
            });
            var activeBtn = document.querySelector('[data-sort="' + sort + '"]');
            if (activeBtn) activeBtn.classList.add('active');

            applyFilters();
        }

        function applyFilters() {
            var movies = Array.from(document.querySelectorAll('.movie_card'));
            var movieGrid = document.querySelector('.movie_grid');

            var existingNoResults = movieGrid.querySelector('.no_results');
            if (existingNoResults) {
                existingNoResults.remove();
            }

            movies.sort(function(a, b) {
                if (currentSort === 'popularity') {
                    var releaseA = a.getAttribute('data-release');
                    var releaseB = b.getAttribute('data-release');
                    var statusA = a.getAttribute('data-status');
                    var statusB = b.getAttribute('data-status');

                    var oneYearAgo = new Date();
                    oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);

                    var dateA = releaseA ? new Date(releaseA) : null;
                    var dateB = releaseB ? new Date(releaseB) : null;

                    var isFutureA = statusA === 'upcoming';
                    var isFutureB = statusB === 'upcoming';
                    var isOldA = dateA && !isNaN(dateA.getTime()) && dateA < oneYearAgo;
                    var isOldB = dateB && !isNaN(dateB.getTime()) && dateB < oneYearAgo;

                    // 개봉 예정 영화는 맨 뒤로
                    if (isFutureA && !isFutureB) return 1;
                    if (isFutureB && !isFutureA) return -1;

                    // 재개봉 영화는 개봉 예정 바로 앞으로
                    if (isOldA && !isOldB) return 1;
                    if (isOldB && !isOldA) return -1;

                    // 같은 그룹끼리는 관객수로 정렬
                    return parseInt(b.getAttribute('data-audience')) - parseInt(a.getAttribute('data-audience'));
                } else if (currentSort === 'latest') {
                    var releaseA = a.getAttribute('data-release');
                    var releaseB = b.getAttribute('data-release');
                    var statusA = a.getAttribute('data-status');
                    var statusB = b.getAttribute('data-status');

                    var hasDateA = releaseA && releaseA.trim() !== '';
                    var hasDateB = releaseB && releaseB.trim() !== '';

                    if (!hasDateA && !hasDateB) return 0;
                    if (!hasDateA) return 1;
                    if (!hasDateB) return -1;

                    var dateA = new Date(releaseA);
                    var dateB = new Date(releaseB);

                    var isValidA = !isNaN(dateA.getTime());
                    var isValidB = !isNaN(dateB.getTime());

                    if (!isValidA && !isValidB) return 0;
                    if (!isValidA) return 1;
                    if (!isValidB) return -1;

                    var oneYearAgo = new Date();
                    oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);

                    var isFutureA = statusA === 'upcoming';
                    var isFutureB = statusB === 'upcoming';
                    var isOldA = dateA < oneYearAgo;
                    var isOldB = dateB < oneYearAgo;

                    if (isFutureA && !isFutureB) return 1;
                    if (isFutureB && !isFutureA) return -1;
                    if (isFutureA && isFutureB) return dateA - dateB;

                    if (isOldA && !isOldB) return 1;
                    if (isOldB && !isOldA) return -1;
                    if (isOldA && isOldB) return dateB - dateA;

                    return dateB - dateA;
                } else if (currentSort === 'release') {
                    var releaseA = a.getAttribute('data-release');
                    var releaseB = b.getAttribute('data-release');

                    var hasDateA = releaseA && releaseA.trim() !== '';
                    var hasDateB = releaseB && releaseB.trim() !== '';

                    if (!hasDateA && !hasDateB) return 0;
                    if (!hasDateA) return 1;
                    if (!hasDateB) return -1;

                    var dateA = new Date(releaseA);
                    var dateB = new Date(releaseB);

                    var isValidA = !isNaN(dateA.getTime());
                    var isValidB = !isNaN(dateB.getTime());

                    if (!isValidA && !isValidB) return 0;
                    if (!isValidA) return 1;
                    if (!isValidB) return -1;

                    return dateA - dateB;
                }
                return 0;
            });

            movies.forEach(function(movie) {
                movieGrid.appendChild(movie);
            });

            var visibleCount = 0;
            movies.forEach(function(movie) {
                var status = movie.getAttribute('data-status');
                var genre = movie.getAttribute('data-genre') || '';
                var title = movie.getAttribute('data-title') || '';
                var bookingBtn = movie.querySelector('.booking_button');

                var showMovie = false;

                if (currentCategory === 'all' || status === currentCategory) {
                    if (!currentGenre || currentGenre === '') {
                        showMovie = true;
                    } else {
                        if (genre.toLowerCase().includes(currentGenre.toLowerCase()) ||
                            title.toLowerCase().includes(currentGenre.toLowerCase())) {
                            showMovie = true;
                        }
                    }
                }

                if (showMovie) {
                    movie.style.display = 'block';
                    movie.classList.remove('genre-filtered');
                    visibleCount++;

                    if (bookingBtn) {
                        if (currentCategory === 'upcoming' || status === 'upcoming') {
                            bookingBtn.style.display = 'none';
                        } else {
                            bookingBtn.style.display = 'block';
                        }
                    }
                } else {
                    movie.style.display = 'none';
                    movie.classList.add('genre-filtered');
                }
            });

            if (visibleCount === 0) {
                var noResults = document.createElement('div');
                noResults.className = 'no_results';
                var message = '영화 정보가 없습니다.';
                if (currentGenre) {
                    message = currentGenre + ' 장르의 영화가 없습니다.';
                }
                noResults.innerHTML = '<p>' + message + '</p>';
                movieGrid.appendChild(noResults);
            }
        }

        function handleImageError() {
            var images = document.querySelectorAll('.movie_poster img');
            images.forEach(function(img) {
                img.onerror = function() {
                    this.src = 'images/default_poster.jpg';
                };
            });
        }

        window.addEventListener('load', function() {
            handleImageError();

            // ⭐ 개봉 예정으로 진입한 경우 개봉일순 정렬
            if (currentCategory === 'upcoming') {
                currentSort = 'release';
            }

            // ⭐ URL 파라미터가 있으면 해당 탭 활성화
            if (currentCategory !== 'all') {
                filterMovies(currentCategory);
            } else {
                updateSortButtons();
                applyFilters();
            }
        });
    </script>
</body>

</html>