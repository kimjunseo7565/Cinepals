<?php
require_once "inc/session.php";
require_once "inc/db.php";
require_once "inc/movie_api_combined.php";

// DB가 비어 있는지 확인하고, 비어 있으면 API 호출
/*
$movies_count = db_select("SELECT COUNT(*) as count FROM moviesdb")[0]['count'];
if ($movies_count == 0) {
    require_once "inc/movie_api_combined.php";
    get_combined_now_playing_movies();
    get_combined_upcoming_movies();
}
*/
$now_playing_movies = get_movies_from_db('now_playing');
$upcoming_movies_raw = get_movies_from_db('upcoming');

// ⭐ 재개봉 영화 맨 뒤로 정렬 (인기순 기준)
$oneYearAgo = date('Y-m-d', strtotime('-1 year'));
usort($now_playing_movies, function ($a, $b) use ($oneYearAgo) {
    $isOldA = !empty($a['release_date']) && $a['release_date'] < $oneYearAgo;
    $isOldB = !empty($b['release_date']) && $b['release_date'] < $oneYearAgo;

    if ($isOldA && !$isOldB) return 1;
    if ($isOldB && !$isOldA) return -1;

    return ($b['audience_count'] ?? 0) - ($a['audience_count'] ?? 0);
});

// ⭐ movies.php와 동일하게 중복 제거
$movie_ids = [];
foreach ($now_playing_movies as $movie) {
    $movie_ids[] = $movie['movie_id'];
}

$upcoming_movies = [];
foreach ($upcoming_movies_raw as $movie) {
    if (!in_array($movie['movie_id'], $movie_ids)) {
        $upcoming_movies[] = $movie;
    }
}

// DB에서 이벤트 목록 가져오기 (메인페이지용 - 최대 3개)
$con = mysqli_connect("localhost", "root", "", "moviedb");

// events 테이블이 없으면 생성
$create_table = "CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    cinema_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    link_url TEXT,
    main_image VARCHAR(255),
    detail_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($con, $create_table);

$events_sql = "SELECT * FROM events 
              
               ORDER BY created_at DESC LIMIT 3";
$events_result = mysqli_query($con, $events_sql);
$events = [];
if ($events_result) {
    while ($row = mysqli_fetch_assoc($events_result)) {
        $events[] = $row;
    }
}
mysqli_close($con);

// 현재 날짜 가져오기
$current_date = date('Y-m-d');

// 슬라이더용 영화 (배경 이미지가 있는 영화)
$slider_movies = [];
foreach ($now_playing_movies as $movie) {
    if (!empty($movie['poster_image']) && $movie['poster_image'] != 'images/default_poster.jpg') {
        // 배경 이미지 설정 (통합 API에서는 poster_image만 제공하므로 이를 backdrop으로도 사용)
        $movie['backdrop_image'] = $movie['poster_image'];
        $slider_movies[] = $movie;
        // 슬라이더에 5개만 표시
        if (count($slider_movies) >= 5) {
            break;
        }
    }
}

function getKoreanDate($date_string)
{
    if (empty($date_string)) return '';

    $korean_days = array('일', '월', '화', '수', '목', '금', '토');
    $day_index = date('w', strtotime($date_string));
    return date('Y.m.d', strtotime($date_string)) . '(' . $korean_days[$day_index] . ')';
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>한국 영화 커뮤니티 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* 기존 슬라이더 스타일 유지 */
        .main-slider {
            position: relative;
            width: 100%;
            height: 500px;
            overflow: hidden;
            margin-bottom: 40px;
            background-color: #000;
        }

        .slider-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            background-color: #111;
            display: flex;
            align-items: flex-end;
        }

        .slide.active {
            opacity: 1;
        }

        .slide-content {
            width: 100%;
            padding: 30px 30px 30px 100px;
            color: white;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9), transparent);
        }

        .slide-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .slide-desc {
            font-size: 1rem;
            margin-bottom: 15px;
            max-width: 60%;
        }

        .release-date {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        .slide-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #e50914;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
        }

        .slide-rating {
            display: inline-block;
            margin-left: 15px;
            font-size: 1.1rem;
        }

        .slide-rating i {
            color: #ffd700;
            margin-right: 5px;
        }

        .slider-nav {
            position: absolute;
            bottom: 20px;
            right: 30px;
            display: flex;
            gap: 10px;
        }

        .slider-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
        }

        .slider-dot.active {
            background-color: white;
        }

        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2rem;
            color: white;
            background: rgba(0, 0, 0, 0.5);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
        }

        .slider-prev {
            left: 20px;
        }

        .slider-next {
            right: 20px;
        }

        /* 영화 카테고리 선택 탭 스타일 */
        .movie_category_tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #333;
        }

        .category_tab {
            padding: 12px 24px;
            background: transparent;
            color: #aaa;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .category_tab.active {
            color: #fff;
            border-bottom: 3px solid #e50914;
        }

        .category_tab:hover {
            color: #fff;
        }

        .movies_container {
            display: none;
        }

        .movies_container.active {
            display: block;
        }

        /* 슬라이더가 없을 경우 표시할 메시지 */
        .no-slider-message {
            text-align: center;
            padding: 100px 0;
            background-color: #1a1d24;
            color: white;
            margin-bottom: 40px;
        }

        /* 캐러셀 스타일 */
        .movie-carousel {
            position: relative;
            margin-bottom: 40px;
        }

        .carousel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .carousel-title {
            font-size: 24px;
            color: #fff;
        }

        .carousel-container {
            position: relative;
            overflow: hidden;
        }

        .carousel-track {
            display: flex;
            transition: transform 0.5s ease;
        }

        .carousel-item {
            flex: 0 0 20%;
            min-width: 20%;
            padding: 0 10px;
            box-sizing: border-box;
        }

        .carousel-item a {
            display: block;
            text-decoration: none;
            color: #fff;
        }

        .carousel-poster {
            position: relative;
            width: 100%;
            padding-bottom: 150%;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .carousel-poster img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .carousel-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9), transparent);
        }

        .carousel-title {
            font-size: 16px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .carousel-rating {
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .carousel-rating i {
            color: #ffd700;
            margin-right: 5px;
        }

        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            font-size: 18px;
        }

        .carousel-prev {
            left: 0;
        }

        .carousel-next {
            right: 0;
        }

        /* 이벤트 섹션 스타일 - 수정된 부분 */
        .section_title {
            font-size: 24px;
            font-weight: bold;
            margin: 40px 0 20px 0;
            color: #fff;
        }

        .event_container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            gap: 20px;
            margin-bottom: 40px;
            max-width: 1200px;
        }

        .event_item {
            background: #1a1d24;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease;
            flex: 0 0 calc(33.333% - 14px);
            max-width: 380px;
            min-width: 280px;
        }

        .event_item:hover {
            transform: translateY(-5px);
        }

        .event_image {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .event_image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .event_info {
            padding: 15px;
        }

        .event_title {
            font-size: 18px;
            margin-bottom: 5px;
            color: #fff;
        }

        .event_date {
            font-size: 14px;
            color: #aaa;
        }

        /* 이벤트가 없을 때 메시지 스타일 */
        .no_events_message {
            width: 100%;
        }

        /* 반응형 */
        @media (max-width: 768px) {
            .event_item {
                flex: 0 0 100%;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .event_item {
                flex: 0 0 calc(50% - 10px);
            }
        }

        /* 광고 슬라이더 스타일 */
        .ad_slider {
            position: relative;
            width: 100%;
            height: 150px;
            overflow: hidden;
            margin: 40px 0 20px 0;
            border: 1px solid #000 !important;
        }

        .ad_slides {
            display: flex;
            transition: transform 0.5s ease;
            height: 100%;
        }

        .ad_slide {
            min-width: 100%;
            height: 100%;
        }

        .ad_slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ad_arrow {
            display: none;
        }

        .ad_prev {
            left: 10px;
        }

        .ad_next {
            right: 10px;
        }

        .ad_indicators {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
        }

        .ad_indicator {
            width: 10px;
            height: 10px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            cursor: pointer;
        }

        .ad_indicator.active {
            background: #fff;
        }

        .no_events {
            width: 100%;
            text-align: center;
            padding: 50px;
            color: #888;
            background: #1a1d24;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <?php require_once "inc/header.php"; ?>

    <div class="main_wrapper">
        <!-- 메인 슬라이더 (롯데시네마 스타일) -->
        <?php if (count($slider_movies) > 0): ?>
            <div class="main-slider">
                <div class="slider-container">
                    <?php foreach ($slider_movies as $index => $movie): ?>
                        <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>"
                            data-movie-id="<?php echo htmlspecialchars($movie['movie_id']); ?>"
                            style="background-image: url('<?php echo $movie['backdrop_image']; ?>');">
                            <div class="slide-content">
                                <h2 class="slide-title"><?php echo htmlspecialchars($movie['title']); ?></h2>
                                <p class="slide-desc">
                                    <?php
                                    $plot = $movie['plot'];
                                    $trimmed_plot = mb_substr($plot, 0, 150, 'UTF-8');
                                    echo htmlspecialchars($trimmed_plot . (mb_strlen($plot, 'UTF-8') > 150 ? '...' : ''));
                                    ?>
                                </p>
                                <p class="release-date">개봉일: <?php echo date('Y.m.d', strtotime($movie['release_date'])); ?></p>
                                <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>&source=<?php echo $movie['source']; ?>" class="slide-btn">상세보기</a>
                                <span class="slide-rating">
                                    <i class="fas fa-star"></i>
                                    <?php echo number_format($movie['rating'], 1); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($slider_movies) > 1): ?>
                        <div class="slider-arrow slider-prev">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        <div class="slider-arrow slider-next">
                            <i class="fas fa-chevron-right"></i>
                        </div>

                        <div class="slider-nav">
                            <?php foreach ($slider_movies as $index => $movie): ?>
                                <div class="slider-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="no-slider-message">
                <h2>영화 정보를 불러오는 중입니다...</h2>
                <p>잠시 후 다시 시도해주세요.</p>
            </div>
        <?php endif; ?>

        <!-- 영화 카테고리 선택 탭 -->
        <div class="movie_category_tabs">
            <button class="category_tab active" data-target="now-playing">상영 중</button>
            <button class="category_tab" data-target="upcoming">개봉 예정</button>
        </div>

        <!-- 상영 중인 영화 캐러셀 -->
        <div class="movie-carousel movies_container active" id="now-playing">
            <div class="carousel-header">
                <h2 class="carousel-title">상영 중인 영화</h2>
                <a href="movies.php?category=now_playing" class="more_btn">더보기</a>
            </div>

            <div class="carousel-container">
                <div class="carousel-track" id="now-playing-track">
                    <?php foreach ($now_playing_movies as $movie): ?>
                        <?php if (!empty($movie['poster_image']) && $movie['poster_image'] != 'images/default_poster.jpg'): ?>
                            <div class="carousel-item">
                                <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>&source=<?php echo $movie['source']; ?>">
                                    <div class="carousel-poster">
                                        <img src="<?php echo $movie['poster_image']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                        <div class="carousel-info">
                                            <h3 class="carousel-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                                            <div class="carousel-rating">
                                                <i class="fas fa-star"></i>
                                                <?php echo number_format($movie['rating'], 1); ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (empty($now_playing_movies)): ?>
                        <div class="no_results" style="width: 100%; text-align: center; padding: 50px 0;">
                            <p>현재 상영 중인 영화 정보가 없습니다.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (count($now_playing_movies) > 5): ?>
                    <div class="carousel-arrow carousel-prev" data-target="now-playing-track">
                        <i class="fas fa-chevron-left"></i>
                    </div>
                    <div class="carousel-arrow carousel-next" data-target="now-playing-track">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 개봉 예정 영화 캐러셀 -->
        <div class="movie-carousel movies_container" id="upcoming">
            <div class="carousel-header">
                <h2 class="carousel-title">개봉 예정영화</h2>
                <a href="movies.php?category=upcoming" class="more_btn">더보기</a>
            </div>

            <div class="carousel-container">
                <div class="carousel-track" id="upcoming-track">
                    <?php foreach ($upcoming_movies as $movie): ?>
                        <?php if (!empty($movie['poster_image']) && $movie['poster_image'] != 'images/default_poster.jpg'): ?>
                            <div class="carousel-item">
                                <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>&source=<?php echo $movie['source']; ?>">
                                    <div class="carousel-poster">
                                        <img src="<?php echo $movie['poster_image']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                        <div class="carousel-info">
                                            <h3 class="carousel-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                                            <div class="carousel-rating">
                                                <i class="fas fa-star"></i>
                                                <?php echo number_format($movie['rating'], 1); ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (empty($upcoming_movies)): ?>
                        <div class="no_results" style="width: 100%; text-align: center; padding: 50px 0;">
                            <p>개봉 예정 영화 정보가 없습니다.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (count($upcoming_movies) > 5): ?>
                    <div class="carousel-arrow carousel-prev" data-target="upcoming-track">
                        <i class="fas fa-chevron-left"></i>
                    </div>
                    <div class="carousel-arrow carousel-next" data-target="upcoming-track">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 광고 슬라이더 -->
        <div class="ad_slider">
            <div class="ad_slides">

                <div class="ad_slide">
                    <a href="https://www.megabox.co.kr/event/detail?eventNo=17647" target="_blank">
                        <img src="images/ad2.jpg" alt="광고 2">
                    </a>
                </div>
                <div class="ad_slide">
                    <a href="https://www.megabox.co.kr/event/detail?eventNo=17502" target="_blank">
                        <img src="images/ad3.jpg" alt="광고 3" style="cursor: pointer;">
                    </a>
                </div>
                <div class="ad_slide">
                    <a href="https://www.megabox.co.kr/event/detail?eventNo=18948" target="_blank">
                        <img src="images/ad4.png" alt="광고 4" style="cursor: pointer;">
                    </a>
                </div>
            </div>

            <div class="ad_arrow ad_prev">
                <i class="fas fa-chevron-left"></i>
            </div>
            <div class="ad_arrow ad_next">
                <i class="fas fa-chevron-right"></i>
            </div>

            <div class="ad_indicators">
                <div class="ad_indicator active" data-index="0"></div>
                <div class="ad_indicator" data-index="1"></div>
                <div class="ad_indicator" data-index="2"></div>

            </div>
        </div>

        <!-- 이벤트 섹션 - 완전 DB 연동 -->
        <h2 class="section_title">이벤트</h2>
        <div class="event_container">
            <?php if (!empty($events)): ?>
                <?php foreach ($events as $event): ?>
                    <div class="event_item">
                        <a href="event_detail.php?id=<?php echo $event['id']; ?>">
                            <div class="event_image">
                                <img src="<?php echo $event['main_image']; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                            </div>
                            <div class="event_info">
                                <h3 class="event_title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p class="event_date">
                                    <?php echo getKoreanDate($event['start_date']); ?> ~
                                    <?php echo $event['end_date'] ? getKoreanDate($event['end_date']) : '무제한'; ?>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no_events">
                    <h3>등록된 이벤트가 없습니다</h3>
                    <p>현재 등록된 이벤트가 없습니다.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once "inc/footer.php"; ?>

    <script>
        // 메인 슬라이더 - 매우 간단하게
        var currentSlide = 0;
        var totalSlides = document.querySelectorAll('.slide').length;

        // 슬라이드 이동 함수
        function goToSlide(slideNumber) {
            // 모든 슬라이드 숨기기
            var slides = document.querySelectorAll('.slide');
            for (var i = 0; i < slides.length; i++) {
                slides[i].classList.remove('active');
            }

            // 모든 도트 비활성화
            var dots = document.querySelectorAll('.slider-dot');
            for (var i = 0; i < dots.length; i++) {
                dots[i].classList.remove('active');
            }

            // 현재 슬라이드와 도트 활성화
            currentSlide = slideNumber;
            slides[currentSlide].classList.add('active');
            if (dots[currentSlide]) {
                dots[currentSlide].classList.add('active');
            }

            // 상세보기 링크 업데이트
            updateDetailLink();
        }

        // 다음 슬라이드
        function nextSlide() {
            var next = currentSlide + 1;
            if (next >= totalSlides) {
                next = 0;
            }
            goToSlide(next);
        }

        // 이전 슬라이드
        function prevSlide() {
            var prev = currentSlide - 1;
            if (prev < 0) {
                prev = totalSlides - 1;
            }
            goToSlide(prev);
        }

        // 상세보기 링크 업데이트
        function updateDetailLink() {
            var activeSlide = document.querySelector('.slide.active');
            if (activeSlide) {
                var movieId = activeSlide.getAttribute('data-movie-id');
                var detailBtns = document.querySelectorAll('.slide-btn');
                for (var i = 0; i < detailBtns.length; i++) {
                    detailBtns[i].href = 'movie_detail.php?id=' + movieId;
                }
            }
        }

        // 영화 카테고리 탭
        function showMovieCategory(categoryName) {
            // 모든 탭 비활성화
            var tabs = document.querySelectorAll('.category_tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }

            // 모든 컨테이너 숨기기
            var containers = document.querySelectorAll('.movies_container');
            for (var i = 0; i < containers.length; i++) {
                containers[i].classList.remove('active');
            }

            // 클릭한 탭과 컨테이너 활성화
            var clickedTab = document.querySelector('[data-target="' + categoryName + '"]');
            if (clickedTab) {
                clickedTab.classList.add('active');
            }

            var targetContainer = document.getElementById(categoryName);
            if (targetContainer) {
                targetContainer.classList.add('active');
            }
        }

        // 캐러셀 이동
        function moveCarousel(trackId, direction) {
            var track = document.getElementById(trackId);
            if (!track) return;

            var items = track.querySelectorAll('.carousel-item');
            if (items.length <= 5) return; // 5개 이하면 이동 불필요

            var currentPosition = track.style.transform;
            var currentX = 0;

            // 현재 위치 계산
            if (currentPosition && currentPosition.includes('translateX')) {
                var match = currentPosition.match(/translateX\((-?\d+)%\)/);
                if (match) {
                    currentX = parseInt(match[1]);
                }
            }

            // 새 위치 계산
            var newX = currentX;
            if (direction === 'next') {
                newX = currentX - 20; // 한 칸씩 이동 (20% = 한 아이템)
                var maxMove = -(items.length - 5) * 20; // 최대 이동 거리
                if (newX < maxMove) {
                    newX = maxMove;
                }
            } else {
                newX = currentX + 20;
                if (newX > 0) {
                    newX = 0;
                }
            }

            // 이동 적용
            track.style.transform = 'translateX(' + newX + '%)';
        }

        // 광고 슬라이더
        var adCurrentIndex = 0;
        var adTotalSlides = 3;

        function moveAdSlide(direction) {
            if (direction === 'next') {
                adCurrentIndex++;
                if (adCurrentIndex >= adTotalSlides) {
                    adCurrentIndex = 0;
                }
            } else if (direction === 'prev') {
                adCurrentIndex--;
                if (adCurrentIndex < 0) {
                    adCurrentIndex = adTotalSlides - 1;
                }
            }

            // 슬라이드 이동
            var adSlides = document.querySelector('.ad_slides');
            if (adSlides) {
                adSlides.style.transform = 'translateX(-' + (adCurrentIndex * 100) + '%)';
            }

            // 인디케이터 업데이트
            var indicators = document.querySelectorAll('.ad_indicator');
            for (var i = 0; i < indicators.length; i++) {
                indicators[i].classList.remove('active');
            }
            if (indicators[adCurrentIndex]) {
                indicators[adCurrentIndex].classList.add('active');
            }
        }

        // 페이지 로드시 이벤트 연결
        window.onload = function() {
            // 슬라이더가 있는지 확인
            if (totalSlides > 1) {
                // 화살표 버튼 연결
                var prevBtn = document.querySelector('.slider-prev');
                var nextBtn = document.querySelector('.slider-next');
                if (prevBtn) prevBtn.onclick = prevSlide;
                if (nextBtn) nextBtn.onclick = nextSlide;

                // 도트 버튼 연결
                var dots = document.querySelectorAll('.slider-dot');
                for (var i = 0; i < dots.length; i++) {
                    dots[i].onclick = function() {
                        var index = parseInt(this.getAttribute('data-index'));
                        goToSlide(index);
                    };
                }

                // 자동 슬라이드 (5초마다)
                setInterval(nextSlide, 5000);
            }

            // 카테고리 탭 연결
            var categoryTabs = document.querySelectorAll('.category_tab');
            for (var i = 0; i < categoryTabs.length; i++) {
                categoryTabs[i].onclick = function() {
                    var target = this.getAttribute('data-target');
                    showMovieCategory(target);
                };
            }

            // 캐러셀 화살표 연결
            var carouselPrevs = document.querySelectorAll('.carousel-prev');
            var carouselNexts = document.querySelectorAll('.carousel-next');

            for (var i = 0; i < carouselPrevs.length; i++) {
                carouselPrevs[i].onclick = function() {
                    var target = this.getAttribute('data-target');
                    moveCarousel(target, 'prev');
                };
            }

            for (var i = 0; i < carouselNexts.length; i++) {
                carouselNexts[i].onclick = function() {
                    var target = this.getAttribute('data-target');
                    moveCarousel(target, 'next');
                };
            }

            // 광고 슬라이더 연결
            var adPrev = document.querySelector('.ad_prev');
            var adNext = document.querySelector('.ad_next');
            if (adPrev) adPrev.onclick = function() {
                moveAdSlide('prev');
            };
            if (adNext) adNext.onclick = function() {
                moveAdSlide('next');
            };

            // 광고 인디케이터 연결
            var adIndicators = document.querySelectorAll('.ad_indicator');
            for (var i = 0; i < adIndicators.length; i++) {
                adIndicators[i].onclick = function() {
                    adCurrentIndex = parseInt(this.getAttribute('data-index'));
                    moveAdSlide('stay');
                };
            }

            // 광고 자동 슬라이드 (5초마다)
            setInterval(function() {
                moveAdSlide('next');
            }, 5000);

            // 초기 상세보기 링크 설정
            updateDetailLink();
        };
    </script>
    <?php include 'inc/kakaomap_api.php'; ?>
</body>

</html>