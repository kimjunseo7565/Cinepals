<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 이벤트 ID 가져오기
$event_id = isset($_GET['id']) ? $_GET['id'] : 1;

// DB에서 이벤트 정보 가져오기
$con = mysqli_connect("localhost", "root", "", "moviedb");
$event_sql = "SELECT * FROM events WHERE id = '$event_id'";
$event_result = mysqli_query($con, $event_sql);

$event = null;
$event_img = "img/event_3days_coupon.jpg"; // 기본 이미지
$link_url = "#"; // 기본 링크

if ($event_result && mysqli_num_rows($event_result) > 0) {
    // DB에서 이벤트를 찾은 경우
    $event = mysqli_fetch_assoc($event_result);
    $event_img = !empty($event['detail_image']) ? $event['detail_image'] : $event['main_image'];
    $link_url = !empty($event['link_url']) ? $event['link_url'] : "#";
} else {
    // DB에 이벤트가 없으면 기존 하드코딩된 이벤트 표시
    if ($event_id == 2) {
        $event_img = "img/event_10.jpg";
        $link_url = "http://www.cgv.co.kr/culture-event/event/detailViewUnited.aspx?seq=44073&page=9";
    } else if ($event_id == 3) {
        $event_img = "img/event_7.jpg";
        $link_url = "http://www.cgv.co.kr/culture-event/event/detailViewUnited.aspx?seq=44024&page=6";
    } else if ($event_id == 4) {
        $event_img = "img/event_8.jpg";
        $link_url = "https://www.lottecinema.co.kr/NLCHS/Event/EventTemplateInfo?eventId=201010016925156";
    } else if ($event_id == 5) {
        $event_img = "img/event_9.jpg";
        $link_url = "https://www.lottecinema.co.kr/NLCHS/Event/EventTemplateInfo?eventId=201010016925197";
    } else if ($event_id == 6) {
        $event_img = "img/event_lobe.jpg";
        $link_url = "https://www.lottecinema.co.kr/NLCHS/Event/EventTemplateStageGreeting?eventId=401070016925100";
    } else if ($event_id == 7) {
        $event_img = "img/event_11.jpg";
        $link_url = "https://www.megabox.co.kr/event/detail?eventNo=17471";
    } else if ($event_id == 8) {
        $event_img = "img/event_12.jpg";
        $link_url = "https://www.megabox.co.kr/event/detail?eventNo=17505";
    } else if ($event_id == 9) {
        $event_img = "img/event_13.jpg";
        $link_url = "https://www.megabox.co.kr/event/detail?eventNo=17485";
    }
}

mysqli_close($con);
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $event ? htmlspecialchars($event['title']) : '이벤트 상세'; ?> - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .event_detail {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .event_detail img {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .back_btn {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 20px;
            background: #e50914;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .back_btn:hover {
            background: #b7070f;
        }

        /* 이벤트 정보 박스 */
        .event_info_box {
            background: #1a1d24;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #333;
        }

        .event_info_title {
            font-size: 24px;
            color: #fff;
            margin-bottom: 15px;
            border-bottom: 2px solid #e50914;
            padding-bottom: 10px;
        }

        .event_info_row {
            display: flex;
            gap: 20px;
        }

        .event_info_item {
            display: inline-flex;
            width: 32%; /* 33.33% 대신 32%로 여유 공간 확보 */
            margin-bottom: 10px;
            margin-right: 1%; /* 항목 간 간격 */
            color: #ccc;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
        }

        .event_info_item:last-child {
            margin-right: 0; /* 마지막 항목은 오른쪽 마진 제거 */
        }

        .event_info_label {
            width: auto; /* 100px를 auto로 변경 */
            font-weight: bold;
            color: #e50914;
            margin-right: 10px; /* 라벨과 값 사이 간격 */
        }

        .event_info_value {
            flex: none; /* flex: 1을 none으로 변경 */
        }

        /* 바로가기 버튼 스타일 */
        .shortcut_btn {
            display: inline-block;
            padding: 15px 30px;
            background: #e50914;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .shortcut_btn:hover {
            background: #b50000;
            transform: translateY(-3px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        .shortcut_btn i {
            margin-right: 8px;
        }

        .button_container {
            text-align: center;
            margin: 30px 0;
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="event_detail">
            <a href="event.php" class="back_btn">
                <i class="fas fa-arrow-left"></i> 이벤트 목록으로
            </a>
            
            <?php if ($event): ?>
                <!-- DB에서 가져온 이벤트 정보 표시 -->
                <div class="event_info_box">
                    <h1 class="event_info_title"><?php echo htmlspecialchars($event['title']); ?></h1>
                    <div class="event_info_item">
                        <span class="event_info_label">영화관:</span>
                        <span class="event_info_value"><?php echo htmlspecialchars($event['cinema_type']); ?></span>
                    </div>
                    <div class="event_info_item">
                        <span class="event_info_label">시작일:</span>
                        <span class="event_info_value"><?php echo date('Y년 m월 d일', strtotime($event['start_date'])); ?></span>
                    </div>
                    <div class="event_info_item">
                        <span class="event_info_label">종료일:</span>
                        <span class="event_info_value">
                            <?php echo $event['end_date'] ? date('Y년 m월 d일', strtotime($event['end_date'])) : '무제한'; ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- 이벤트 상세 이미지 -->
            <img src="<?php echo $event_img; ?>" alt="이벤트 상세 이미지">
            
            <!-- 바로가기 버튼 -->
            <?php if ($link_url !== "#"): ?>
                <div class="button_container">
                    <a href="<?php echo $link_url; ?>" class="shortcut_btn" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        이벤트 참여하기
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>
</body>

</html>