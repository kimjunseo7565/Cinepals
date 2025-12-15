<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 전체 데이터 가져오기 - 쉬운 방법
$all_members = db_select("SELECT * FROM members");
$all_movies = db_select("SELECT * FROM moviesdb 
                         WHERE poster_image != 'images/default_poster.jpg'
                         AND poster_image IS NOT NULL 
                         AND poster_image != ''");
$all_reports = db_select("SELECT * FROM reports");
$all_movie_reviews = db_select("SELECT * FROM movie_reviews_new");
$all_board_comments = db_select("SELECT * FROM board_comments");

// 숫자 세기 - 쉬운 방법
$total_members = 0;
$active_members = 0;
$new_members = 0;
$deleted_members = 0;
$banned_members = 0;
$total_movies = 0;
$total_reports = 0;
$total_reviews = 0;

// 이번주와 오늘 데이터 세기
$today_reviews = 0;

// 오늘 날짜 구하기
$today_date = date('Y-m-d');
$week_ago_date = date('Y-m-d', strtotime('-7 days'));

// 회원 수 세기 - 상태별로 분류
foreach ($all_members as $member) {
    $total_members = $total_members + 1;

    // 회원 상태별 분류
    $status = isset($member['status']) ? $member['status'] : 'active';

    if ($status === 'banned') {
        $banned_members = $banned_members + 1;
    } else if ($status === 'deleted') {
        $deleted_members = $deleted_members + 1;
    } else {
        $active_members = $active_members + 1;
    }

    // 신규 회원 (7일 이내 가입)
    $join_date = date('Y-m-d', strtotime($member['join_date']));
    if ($join_date >= $week_ago_date && $status === 'active') {
        $new_members = $new_members + 1;
    }
}



// 영화 수 세기 및 장르별 통계
$genre_counts = array();

foreach ($all_movies as $movie) {
    $total_movies = $total_movies + 1;

    // 장르별 통계 계산 - 5개 주요 장르로만 분류 (한 영화당 하나만 카운트)
    if (!empty($movie['genre'])) {
        // 먼저 모든 공백 제거
        $clean_genre = str_replace(' ', '', $movie['genre']);

        // 콤마로 분리
        $genres = explode(',', $clean_genre);

        // 첫 번째로 매칭되는 장르만 카운트 (중복 방지)
        $counted = false;

        foreach ($genres as $genre) {
            if ($counted) break; // 이미 카운트했으면 중단

            if (!empty($genre) && $genre !== '추후에공개') {
                if (strpos($genre, '액션') !== false || strpos($genre, '모험') !== false) {
                    if (isset($genre_counts['액션/모험'])) {
                        $genre_counts['액션/모험'] = $genre_counts['액션/모험'] + 1;
                    } else {
                        $genre_counts['액션/모험'] = 1;
                    }
                    $counted = true;
                } else if (strpos($genre, '드라마') !== false || strpos($genre, '멜로') !== false || strpos($genre, '로맨스') !== false) {
                    if (isset($genre_counts['드라마/로맨스'])) {
                        $genre_counts['드라마/로맨스'] = $genre_counts['드라마/로맨스'] + 1;
                    } else {
                        $genre_counts['드라마/로맨스'] = 1;
                    }
                    $counted = true;
                } else if (strpos($genre, '코미디') !== false || strpos($genre, '가족') !== false) {
                    if (isset($genre_counts['코미디/가족'])) {
                        $genre_counts['코미디/가족'] = $genre_counts['코미디/가족'] + 1;
                    } else {
                        $genre_counts['코미디/가족'] = 1;
                    }
                    $counted = true;
                } else if (strpos($genre, '스릴러') !== false || strpos($genre, '공포') !== false || strpos($genre, '범죄') !== false) {
                    if (isset($genre_counts['스릴러/공포'])) {
                        $genre_counts['스릴러/공포'] = $genre_counts['스릴러/공포'] + 1;
                    } else {
                        $genre_counts['스릴러/공포'] = 1;
                    }
                    $counted = true;
                } else if (strpos($genre, 'SF') !== false || strpos($genre, '판타지') !== false || strpos($genre, '애니') !== false) {
                    if (isset($genre_counts['SF/판타지'])) {
                        $genre_counts['SF/판타지'] = $genre_counts['SF/판타지'] + 1;
                    } else {
                        $genre_counts['SF/판타지'] = 1;
                    }
                    $counted = true;
                }
            }
        }

        // 어떤 카테고리에도 속하지 않으면 기타로
        if (!$counted) {
            if (isset($genre_counts['기타'])) {
                $genre_counts['기타'] = $genre_counts['기타'] + 1;
            } else {
                $genre_counts['기타'] = 1;
            }
        }
    }
}

// 신고 수 세기 및 상태별 분류
$report_pending = 0;  // 처리대기
$report_completed = 0;  // 처리완료
$report_rejected = 0;  // 기각

foreach ($all_reports as $report) {
    $total_reports = $total_reports + 1;

    // 상태별 분류 (DB에는 '대기'만 있음)
    $status = isset($report['status']) ? $report['status'] : '대기';

    if ($status === '완료' || $status === '처리완료') {
        $report_completed = $report_completed + 1;
    } elseif ($status === '기각' || $status === '반려') {
        $report_rejected = $report_rejected + 1;
    } else {
        // '대기' 또는 기타
        $report_pending = $report_pending + 1;
    }
}

// 영화 리뷰 수 세기
foreach ($all_movie_reviews as $review) {
    $total_reviews = $total_reviews + 1;

    // 오늘 작성된 리뷰인지 확인
    $review_date = date('Y-m-d', strtotime($review['created_at']));
    if ($review_date == $today_date) {
        $today_reviews = $today_reviews + 1;
    }
}

// 게시판 댓글 수 세기
foreach ($all_board_comments as $comment) {
    $total_reviews = $total_reviews + 1;

    // 오늘 작성된 댓글인지 확인
    $comment_date = date('Y-m-d', strtotime($comment['regdate']));
    if ($comment_date == $today_date) {
        $today_reviews = $today_reviews + 1;
    }
}
// 이번달과 지난달 날짜 계산
$this_month_start = date('Y-m-01');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end = date('Y-m-t', strtotime('-1 month'));

// 이번달 작성 댓글 수
$this_month_reviews = 0;
foreach ($all_movie_reviews as $review) {
    $review_date = date('Y-m-d', strtotime($review['created_at']));
    if ($review_date >= $this_month_start) {
        $this_month_reviews = $this_month_reviews + 1;
    }
}
foreach ($all_board_comments as $comment) {
    $comment_date = date('Y-m-d', strtotime($comment['regdate']));
    if ($comment_date >= $this_month_start) {
        $this_month_reviews = $this_month_reviews + 1;
    }
}

// 지난달 작성 댓글 수
$last_month_reviews = 0;
foreach ($all_movie_reviews as $review) {
    $review_date = date('Y-m-d', strtotime($review['created_at']));
    if ($review_date >= $last_month_start && $review_date <= $last_month_end) {
        $last_month_reviews = $last_month_reviews + 1;
    }
}
foreach ($all_board_comments as $comment) {
    $comment_date = date('Y-m-d', strtotime($comment['regdate']));
    if ($comment_date >= $last_month_start && $comment_date <= $last_month_end) {
        $last_month_reviews = $last_month_reviews + 1;
    }
}
// 이번달 신고 수
$this_month_reports = 0;
foreach ($all_reports as $report) {
    $report_date = date('Y-m-d', strtotime($report['created_at']));
    if ($report_date >= $this_month_start) {
        $this_month_reports = $this_month_reports + 1;
    }
}
// 최근 가입한 회원 5명 가져오기
$recent_members = db_select("SELECT * FROM members ORDER BY join_date DESC LIMIT 5");

// 마지막 영화 업데이트 시간 가져오기
$last_update = db_select("SELECT value FROM site_settings WHERE name = 'last_movie_update'");
$last_update_time = !empty($last_update) ? $last_update[0]['value'] : '업데이트 기록 없음';

// 숫자에 콤마 추가하는 함수 (쉬운 방법)
function add_comma($number)
{
    return number_format($number);
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 대시보드 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .main_wrapper {
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard_container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard_header {
            text-align: center;
            margin-bottom: 40px;
            color: #333;
        }

        .dashboard_header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        /* 영화 업데이트 섹션 */
        .movie_update_section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            border-left: 4px solid #e50914;
            text-align: center;
        }

        .update_title {
            font-size: 20px;
            font-weight: bold;
            color: #e50914;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .last_update_info {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .update_btn {
            background: linear-gradient(135deg, #e50914, #ff1e2d);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .update_btn:hover {
            background: linear-gradient(135deg, #c40711, #e50914);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(229, 9, 20, 0.4);
        }

        .update_btn:active {
            transform: translateY(0);
        }

        .dashboard_grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        /* 왼쪽: 원형 차트 */
        .chart_section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            align-self: start;
        }

        .chart_title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .chart_container {
            width: 300px;
            height: 300px;
            margin: 0 auto;
        }

        .chart_legend {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px 30px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            min-height: 100px;
            align-content: start;
        }

        .legend_item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            height: 28px;
        }

        .legend_color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
        }

        /* 오른쪽: 통계 박스들 */
        .stats_section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            align-self: start;

        }

        .stat_box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid transparent;
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .stat_box:hover {
            transform: translateY(-5px);
        }

        .stat_box.active {
            border-left-color: #e50914;
            background: #f8f9fa;
        }

        .stat_box.members {
            border-left-color: #3498db;
        }

        .stat_box.movies {
            border-left-color: #e74c3c;
        }

        .stat_box.reviews {
            border-left-color: #2ecc71;
        }

        .stat_box.reports {
            border-left-color: #f39c12;
        }

        .stat_number {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .stat_label {
            font-size: 16px;
            color: #666;
            margin-bottom: 8px;
        }

        .stat_change {
            font-size: 12px;
            color: #888;
        }

        /* 하단: 최근 가입 회원 */
        .recent_section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        .section_title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }

        .recent_grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .recent_member {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #f9f9f9;
        }

        .member_name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .member_email {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .member_date {
            font-size: 11px;
            color: #999;
        }

        /* 사이드 메뉴 스타일 조정 */
        .admin_container {
            display: flex;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px;
        }

        .side_menu {
            width: 250px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            height: fit-content;
        }

        .side_menu .active {
            color: #e74c3c !important;
        }

        .side_menu .member:hover {
            color: #3498db !important;
        }

        .side_menu .event:hover {
            color: #db34a9 !important;
        }

        .side_menu .report:hover {
            color: #f39c12 !important;
        }

        .content_area {
            flex: 1;
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="admin_container">
            <!-- 좌측 메뉴 -->
            <aside class="side_menu">
                <div class="menu_list">
                    <a href="#" class="menu_item active">대시보드</a>
                    <a href="manager_members.php" class="menu_item member">회원 관리</a>
                    <a href="manager_event.php" class="menu_item event">이벤트 관리</a>
                    <a href="manager_reports.php" class="menu_item report">리뷰/댓글관리</a>
                </div>
            </aside>

            <!-- 메인 콘텐츠 -->
            <div class="content_area">
                <div class="dashboard_container">
                    <div class="dashboard_header">
                        <h1>관리자 대시보드</h1>
                        <p>Cinepals 전체 현황</p>
                    </div>

                    <!-- 영화 데이터 업데이트 섹션 -->
                    <div class="movie_update_section">
                        <h3 class="update_title">
                            <i class="fas fa-film"></i>
                            영화 데이터 업데이트
                        </h3>
                        <div class="last_update_info">
                            <i class="fas fa-clock"></i>
                            마지막 업데이트: <?php echo $last_update_time; ?>
                        </div>
                        <button class="update_btn" onclick="updateMovieData()">
                            <i class="fas fa-sync-alt"></i>
                            영화 데이터 업데이트 실행
                        </button>
                    </div>

                    <!-- 메인 대시보드 영역 -->
                    <div class="dashboard_grid">
                        <!-- 왼쪽: 원형 차트 -->
                        <div class="chart_section">
                            <h3 class="chart_title" id="chartTitle">회원 상태별 통계</h3>
                            <div class="chart_container">
                                <canvas id="statsChart"></canvas>
                            </div>
                            <div class="chart_legend" id="chartLegend">
                                <div class="legend_item">
                                    <div class="legend_color" style="background: #3498db;"></div>
                                    <span>기존회원</span>
                                </div>
                                <div class="legend_item">
                                    <div class="legend_color" style="background: #2ecc71;"></div>
                                    <span>신규회원</span>
                                </div>
                                <div class="legend_item">
                                    <div class="legend_color" style="background: #95a5a6;"></div>
                                    <span>탈퇴회원</span>
                                </div>
                                <div class="legend_item">
                                    <div class="legend_color" style="background: #e74c3c;"></div>
                                    <span>정지회원</span>
                                </div>
                            </div>
                        </div>

                        <!-- 오른쪽: 통계 박스들 -->
                        <div class="stats_section">
                            <div class="stat_box members active" onclick="showMemberChart()">
                                <div class="stat_number"><?php echo add_comma($total_members); ?></div>
                                <div class="stat_label">전체 회원</div>
                                <div class="stat_change">회원 상태별 통계</div>
                            </div>
                            <div class="stat_box movies" onclick="showMovieChart()">
                                <div class="stat_number"><?php echo add_comma($total_movies); ?></div>
                                <div class="stat_label">전체 영화</div>
                                <div class="stat_change">장르별 통계</div>
                            </div>
                            <div class="stat_box reviews" onclick="showReviewChart()">
                                <div class="stat_number"><?php echo add_comma($total_reviews); ?></div>
                                <div class="stat_label">전체 리뷰</div>
                                <div class="stat_change">리뷰/댓글 통계</div>
                            </div>
                            <div class="stat_box reports" onclick="showReportChart()">
                                <div class="stat_number"><?php echo add_comma($total_reports); ?></div>
                                <div class="stat_label">신고 컨텐츠</div>
                                <div class="stat_change">신고 접수</div>
                            </div>
                        </div>
                    </div>

                    <!-- 하단: 최근 가입 회원 -->
                    <div class="recent_section">
                        <h3 class="section_title">최근 가입 회원</h3>
                        <div class="recent_grid">
                            <?php if ($total_members > 0): ?>
                                <?php foreach ($recent_members as $member): ?>
                                    <div class="recent_member">
                                        <div class="member_name">
                                            <?php
                                            if ($member['nickname'] != '') {
                                                echo htmlspecialchars($member['nickname']);
                                            } else {
                                                echo htmlspecialchars($member['name']);
                                            }
                                            ?>
                                        </div>
                                        <div class="member_email"><?php echo htmlspecialchars($member['email']); ?></div>
                                        <div class="member_date"><?php echo date('Y-m-d', strtotime($member['join_date'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="recent_member">
                                    <div class="member_name">가입된 회원이 없습니다</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>

    <script>
        // 차트 변수
        let statsChart;

        // 차트 데이터 (영화 장르별 통계 추가)
        const chartData = {
            member: {
                labels: ['기존회원', '신규회원', '탈퇴회원', '정지회원'],
                data: [<?php echo $active_members - $new_members; ?>, <?php echo $new_members; ?>, <?php echo $deleted_members; ?>, <?php echo $banned_members; ?>],
                colors: ['#3498db', '#2ecc71', '#95a5a6', '#e74c3c'],
                title: '회원 상태별 통계'
            },
            movie: {
                labels: [
                    <?php
                    // 모든 장르 표시
                    arsort($genre_counts);
                    $genre_labels = array();
                    foreach ($genre_counts as $genre => $count) {
                        $genre_labels[] = "'" . addslashes($genre) . "'";
                    }
                    echo implode(', ', $genre_labels);
                    ?>
                ],
                data: [<?php echo implode(', ', array_values($genre_counts)); ?>],
                colors: ['#e74c3c', '#f39c12', '#3498db', '#2ecc71', '#9b59b6', '#e67e22', '#1abc9c', '#34495e', '#16a085', '#27ae60', '#2980b9', '#8e44ad', '#c0392b', '#d35400', '#bdc3c7', '#95a5a6', '#7f8c8d', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#00bcd4', '#009688', '#4caf50', '#8bc34a', '#cddc39', '#ffeb3b', '#ffc107', '#ff9800', '#ff5722', '#795548', '#607d8b'],
                title: '영화 장르별 통계'
            },
            review: {
                labels: ['영화리뷰', '게시판댓글', '이번달작성', '지난달작성'],
                data: [<?php echo count($all_movie_reviews); ?>, <?php echo count($all_board_comments); ?>, <?php echo $this_month_reviews; ?>, <?php echo $last_month_reviews; ?>],
                colors: ['#2ecc71', '#3498db', '#f39c12', '#e67e22'],
                title: '리뷰/댓글 통계'
            },
            report: {
                labels: ['처리대기', '처리완료', '기각', '이번달신고'],
                data: [<?php echo $report_pending; ?>, <?php echo $report_completed; ?>, <?php echo $report_rejected; ?>, <?php echo $this_month_reports; ?>],
                colors: ['#f39c12', '#2ecc71', '#95a5a6', '#3498db'],
                title: '신고 처리 통계'
            }
        };

        // 초기 차트 생성
        function initChart() {
            const ctx = document.getElementById('statsChart').getContext('2d');
            statsChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: chartData.member.labels,
                    datasets: [{
                        data: chartData.member.data,
                        backgroundColor: chartData.member.colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // 차트 업데이트 함수
        function updateChart(type) {
            const data = chartData[type];
            statsChart.data.labels = data.labels;
            statsChart.data.datasets[0].data = data.data;
            statsChart.data.datasets[0].backgroundColor = data.colors;
            statsChart.update();

            // 제목과 범례 업데이트
            document.getElementById('chartTitle').textContent = data.title;
            updateLegend(data);

            // 활성 박스 표시
            document.querySelectorAll('.stat_box').forEach(box => box.classList.remove('active'));
            document.querySelector('.stat_box.' + type + (type === 'member' ? 's' : '')).classList.add('active');
        }

        // 범례 업데이트
        function updateLegend(data) {
            const legend = document.getElementById('chartLegend');
            let legendHTML = '';
            data.labels.forEach((label, index) => {
                legendHTML += `
            <div class="legend_item">
                <div class="legend_color" style="background: ${data.colors[index]};"></div>
                <span>${label} (${data.data[index]}개)</span>
            </div>
        `;
            });
            legend.innerHTML = legendHTML;
        }

        // 각 통계 박스 클릭 이벤트
        function showMemberChart() {
            updateChart('member');
        }

        function showMovieChart() {
            updateChart('movie');
        }

        function showReviewChart() {
            updateChart('review');
        }

        function showReportChart() {
            updateChart('report');
        }

        // 영화 데이터 업데이트 함수
        function updateMovieData() {
            if (confirm('영화 데이터를 업데이트하시겠습니까?\n\n이 작업은 몇 분이 소요될 수 있습니다.')) {
                // 버튼 비활성화
                const updateBtn = document.querySelector('.update_btn');
                const originalText = updateBtn.innerHTML;
                updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 업데이트 중...';
                updateBtn.disabled = true;
                updateBtn.style.opacity = '0.6';
                updateBtn.style.cursor = 'not-allowed';

                // update_movies.php로 이동
                window.location.href = 'update_movies.php';
            }
        }

        // 페이지 로드시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            initChart();
            updateLegend(chartData.member);
        });
    </script>
</body>

</html>