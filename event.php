<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 현재 선택된 영화관 설정 (기본값 CGV)
$cinema = isset($_GET['cinema']) ? $_GET['cinema'] : 'cgv';

// DB에서 이벤트 목록 가져오기
$con = mysqli_connect("localhost", "root", "", "moviedb");

// 영화관별 이벤트 필터링
$cinema_filter = '';
if ($cinema == 'cgv') {
    $cinema_filter = "WHERE cinema_type = 'CGV'";
} elseif ($cinema == 'lotte') {
    $cinema_filter = "WHERE cinema_type = '롯데시네마'";
} elseif ($cinema == 'megabox') {
    $cinema_filter = "WHERE cinema_type = '메가박스'";
}

$events_sql = "SELECT * FROM events 
               $cinema_filter
               
               ORDER BY created_at DESC";
$events_result = mysqli_query($con, $events_sql);

$events = [];
if ($events_result) {
    while ($row = mysqli_fetch_assoc($events_result)) {
        $events[] = $row;
    }
}

function getKoreanDate($date_string)
{
    if (empty($date_string)) return '';

    $korean_days = array('일', '월', '화', '수', '목', '금', '토');
    $day_index = date('w', strtotime($date_string));
    return date('Y.m.d', strtotime($date_string)) . '(' . $korean_days[$day_index] . ')';
}

mysqli_close($con);
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>이벤트 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* 기존 스타일 유지 */
        .event_heading {
            font-size: 28px;
            margin: 20px 0 40px 0;
            color: #fff;
            text-transform: uppercase;
            padding-left: 20px;
        }

        /* 탭 스타일 */
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
            text-decoration: none;
        }

        .category_tab.active {
            color: #fff;
            border-bottom: 3px solid #e50914;
        }

        .category_tab:hover {
            color: #fff;
        }

        /* 이벤트 컨테이너 - index.php와 동일하게 수정 */
        .event_container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            gap: 20px;
            margin-bottom: 40px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
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
            margin-bottom: 10px;
        }

        .event_cinema {
            font-size: 12px;
            color: #e50914;
            font-weight: bold;
        }

        .no_events {
            width: 100%;
            text-align: center;
            padding: 50px;
            color: #888;
            background: #1a1d24;
            border-radius: 10px;
        }

        /* 반응형 디자인 */
        @media (max-width: 768px) {
            .event_container {
                padding: 0 20px;
            }

            .event_item {
                flex: 0 0 100%;
                max-width: none;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .event_item {
                flex: 0 0 calc(50% - 10px);
            }
        }

        @media (min-width: 1025px) {
            .event_item {
                flex: 0 0 calc(33.333% - 14px);
            }
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <h2 class="event_heading">EVENT</h2>

        <div class="movie_category_tabs">
            <a href="event.php?cinema=cgv" class="category_tab <?php echo $cinema == 'cgv' ? 'active' : ''; ?>">CGV</a>
            <a href="event.php?cinema=lotte" class="category_tab <?php echo $cinema == 'lotte' ? 'active' : ''; ?>">롯데시네마</a>
            <a href="event.php?cinema=megabox" class="category_tab <?php echo $cinema == 'megabox' ? 'active' : ''; ?>">메가박스</a>
        </div>

        <!-- DB에서 가져온 이벤트 목록 -->
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
                    <p><?php echo $cinema == 'cgv' ? 'CGV' : ($cinema == 'lotte' ? '롯데시네마' : '메가박스'); ?>의 이벤트 정보가 없습니다.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>
</body>

</html>