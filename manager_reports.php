<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 욕설 필터 단어 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_filter'])) {
        $filter_word = trim($_POST['filter_word']);
        if (!empty($filter_word)) {
            $con = mysqli_connect("localhost", "root", "", "moviedb");

            // 중복 체크
            $escaped_word = mysqli_real_escape_string($con, $filter_word);
            $check_sql = "SELECT COUNT(*) as count FROM filter_words WHERE word = '$escaped_word'";
            $check_result = mysqli_query($con, $check_sql);
            $count = mysqli_fetch_assoc($check_result)['count'];

            if ($count == 0) {
                $insert_sql = "INSERT INTO filter_words (word, created_at) VALUES ('$escaped_word', NOW())";
                mysqli_query($con, $insert_sql);
                echo "<script>alert('필터 단어가 추가되었습니다.'); window.location.href='manager_reports.php';</script>";
            } else {
                echo "<script>alert('이미 존재하는 필터 단어입니다.');</script>";
            }

            mysqli_close($con);
        }
    }

    if (isset($_POST['delete_filter'])) {
        $word_id = $_POST['word_id'];
        db_update_delete("DELETE FROM filter_words WHERE id = ?", [$word_id]);
        echo "<script>alert('필터 단어가 삭제되었습니다.'); window.location.href='manager_reports.php';</script>";
    }
}

// filter_words 테이블이 없으면 생성
$con = mysqli_connect("localhost", "root", "", "moviedb");
$create_table = "CREATE TABLE IF NOT EXISTS filter_words (
    id INT AUTO_INCREMENT PRIMARY KEY,
    word VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($con, $create_table);
mysqli_close($con);

// 필터 단어 목록 가져오기
$filter_words = db_select("SELECT * FROM filter_words ORDER BY created_at DESC");

// 필터 파라미터 받기
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// 신고 목록 가져오기 - 상태별 필터링 추가
$where_clause = "";
if ($status_filter === 'waiting') {
    $where_clause = "WHERE r.status = '대기'";
} elseif ($status_filter === 'completed') {
    $where_clause = "WHERE r.status = '완료'";
} elseif ($status_filter === 'rejected') {
    $where_clause = "WHERE r.status = '기각'";
}

$reports = db_select("
    SELECT r.*, 
           COALESCE(r.post_title, bp.b_title) as b_title,
           COALESCE(r.post_content, bp.b_contents) as b_contents,
           bp.member_id as post_author_id,
           m1.nickname as reporter_name,
           m2.nickname as author_name
    FROM reports r
    LEFT JOIN board_posts bp ON r.post_id = bp.b_idx
    LEFT JOIN members m1 ON r.reporter_id = m1.member_id
    LEFT JOIN members m2 ON bp.member_id = m2.member_id
    $where_clause
    ORDER BY r.created_at DESC
");

// 통계 계산
$total_reports = 0;
$waiting_reports = 0;
$completed_reports = 0;
$rejected_reports = 0;

// 전체 신고 데이터로 통계 계산 (필터 적용 안함)
$all_reports = db_select("SELECT status FROM reports");
foreach ($all_reports as $report) {
    $total_reports = $total_reports + 1;

    // 대기 상태인 신고 개수
    if ($report['status'] == '대기') {
        $waiting_reports = $waiting_reports + 1;
    }

    // 완료 상태인 신고 개수
    if ($report['status'] == '완료') {
        $completed_reports = $completed_reports + 1;
    }

    // 기각 상태인 신고 개수
    if ($report['status'] == '기각') {
        $rejected_reports = $rejected_reports + 1;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>리뷰/댓글관리 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .main_wrapper {
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }

        /* 헤더 요소 표시 강제 */
        #header .search_form,
        #header .notification_btn,
        #header .auth_btns {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* 메뉴 간격 좁히기 */
        .main_menu_bar {
            gap: 10px !important;
            margin-left: -0px !important;
            /* ⭐ 이 줄 추가 - 전체 왼쪽으로 이동 ⭐ */
        }

        .main_menu_bar .menu_item {
            margin-right: 0 !important;
            padding: 0 10px !important;
        }

        /* 알림 버튼 위치 */
        .auth_btns {
            display: flex !important;
            align-items: center !important;
            gap: 15px !important;
        }

        .notification_btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-right: 20px !important;
            /* ⭐ 이 줄 추가 - 종 모양 왼쪽으로 이동 ⭐ */
            margin-top: 3px !important;
            /* ⭐ 이 줄 추가 - 종 모양 아래로 이동 ⭐ */
        }

        .notification_badge {
            top: -8px !important;
        }

        /* ⭐ 닉네임만 왼쪽으로 이동 ⭐ */
        .signup_btn {
            margin-right: 5px !important;
        }

        /* 메인 컨테이너 */
        .admin_container {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px;
        }

        /* 콘텐츠 영역 */
        .content_area {
            flex: 1;
        }

        .title {
            font-size: 28px;
            margin: 0 0 30px 0;
        }

        /* 욕설 필터 섹션 */
        .filter_section {
            background: #1a1d24;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .filter_title {
            color: #fff;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e50914;
            padding-bottom: 8px;
        }

        .filter_add_form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }

        .filter_input {
            padding: 8px 12px;
            background: #2a2d34;
            border: 1px solid #444;
            border-radius: 4px;
            color: #fff;
            width: 200px;
        }

        .filter_btn {
            padding: 8px 16px;
            background: #e50914;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .filter_btn:hover {
            background: #c40711;
        }

        .filter_list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .filter_word {
            display: flex;
            align-items: center;
            background: #2a2d34;
            border: 1px solid #444;
            border-radius: 20px;
            padding: 5px 12px;
            color: #fff;
            font-size: 13px;
        }

        .filter_word_text {
            margin-right: 8px;
        }

        .filter_delete {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* 통계 박스들 */
        .stats {
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            justify-content: space-between;
        }

        .stat_box {
            background: #1a1d24;
            padding: 30px 20px;
            border: 1px solid #333;
            text-align: center;
            flex: 1;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .stat_box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .stat_box.active {
            background: #252830;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        .stat_number {
            font-size: 42px;
            font-weight: bold;
            color: #fff;
            margin-bottom: 10px;
        }

        .stat_label {
            color: #aaa;
            font-size: 16px;
        }

        /* 테이블 */
        .table_wrap {
            background: #1a1d24;
            border: 1px solid #333;
        }

        .reports_table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed !important;
            /* ⭐ 핵심! */
        }

        .reports_table th {
            background: #2a2d34;
            color: #fff;
            padding: 20px 15px;
            text-align: center !important;
            font-weight: bold;
            border-bottom: 1px solid #444;
            vertical-align: middle;
        }

        .reports_table th:nth-child(1) {
            width: 180px !important;
            max-width: 180px !important;
        }


        .reports_table td {
            padding: 20px 15px;
            border-bottom: 1px solid #333;
            color: #ccc;
            background: #1a1d24;
            text-align: center !important;
            vertical-align: middle !important;
            line-height: 1.5;
        }


        .reports_table td:nth-child(1) {
            width: 180px !important;
            max-width: 180px !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            cursor: default !important;
            /* ⭐ pointer → default */
            color: #111 !important;
            /* ⭐ #4da6ff → #111 (검은색) */
            padding: 20px 40px !important;
            /* 변경 */
            text-align: center !important;
            /* 변경 */
        }



        /* ⭐ 신고 일시(4번째)는 그대로, 상태(5번째)는 덜 이동 */
        .reports_table td:nth-child(4) {
            padding-left: 25px !important;
        }

        .reports_table td:nth-child(5) {
            padding-left: 18px !important;
        }

        .reports_table tr {
            position: relative;
        }

        .reports_table th::after {
            content: "";
            position: absolute;
            left: 20px;
            right: 20px;
            bottom: 0;
            height: 2px;
            background-color: #ffc107;
        }

        .reports_table td::after {
            content: "";
            position: absolute;
            left: 20px;
            right: 20px;
            bottom: 0;
            height: 1px;
            background-color: #e0e0e0;
        }

        /* 상태 버튼 */
        .status_btn {
            padding: 5px 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
        }

        .status_waiting {
            background: #ffc107;
            color: #000;
        }

        .status_completed {
            background: #28a745;
            color: #fff;
        }

        .status_rejected {
            background: #6c757d;
            color: #fff;
        }

        /* 팝업 스타일 */
        .popup_overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .popup_content {
            background: #fff;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 30px;
            width: 600px;
            max-width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            color: #000 !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1) !important;
            border-left: 4px solid #f39c12 !important;
        }

        .popup_title {
            color: #111;
            font-size: 22px;
            margin-bottom: 20px;
            border-bottom: 2px solid #f39c12;
            padding-bottom: 10px;
        }

        .popup_info {
            margin-bottom: 15px;
        }

        .popup_label {
            color: #555;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .popup_value {
            color: #111;
            font-size: 16px;
            padding: 10px;
            background: #ccc;
            border: 1px solid #aaa;
            border-radius: 4px;
            word-break: break-word;
        }

        .popup_buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .popup_btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
        }

        .ban_btn {
            background: #f39c12;
            color: white;
        }

        .ban_btn:hover {
            background: #e38c02;
        }

        .reject_btn {
            background: #6c757d;
            color: white;
        }

        .reject_btn:hover {
            background: #5a6268;
        }

        .close_btn {
            background: #6c757d;
            color: white;
        }

        .close_btn:hover {
            background: #5a6268;
        }

        /* 데이터 없음 */
        .no_data {
            text-align: center;
            padding: 60px;
            color: #888;
        }

        .side_menu {
            width: 250px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            height: fit-content;
        }

        .menu_list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .menu_item {
            padding: 12px 16px;
            color: #ccc;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .side_menu .home:hover {
            color: #e74c3c !important;
        }

        .side_menu .member:hover {
            color: #3498db !important;
        }

        .side_menu .event:hover {
            color: #db34a9 !important;
        }

        .side_menu .active {
            color: #f39c12 !important;
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
                    <a href="manager_home.php" class="menu_item home">대시보드</a>
                    <a href="manager_members.php" class="menu_item member">회원 관리</a>
                    <a href="manager_event.php" class="menu_item event">이벤트 관리</a>
                    <a href="#" class="menu_item active">리뷰/댓글관리</a>
                </div>
            </aside>

            <!-- 우측 콘텐츠 -->
            <div class="content_area">
                <h2 class="title">욕설 필터 및 신고 관리</h2>

                <!-- 욕설 필터 섹션 -->
                <div class="filter_section">
                    <h3 class="filter_title">필터 관리</h3>

                    <!-- 필터 단어 추가 폼 -->
                    <form method="POST" class="filter_add_form">
                        <input type="text" name="filter_word" class="filter_input" placeholder="필터링할 단어 입력" required>
                        <button type="submit" name="add_filter" class="filter_btn">추가</button>
                    </form>

                    <!-- 등록된 필터 단어 목록 -->
                    <div class="filter_list">
                        <?php if (!empty($filter_words)): ?>
                            <?php foreach ($filter_words as $word): ?>
                                <div class="filter_word">
                                    <span class="filter_word_text"><?php echo htmlspecialchars($word['word']); ?></span>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="word_id" value="<?php echo $word['id']; ?>">
                                        <button type="submit" name="delete_filter" class="filter_delete" onclick="return confirm('정말 삭제하시겠습니까?')">×</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color: #888;">등록된 필터 단어가 없습니다.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 통계 요약 -->
                <div class="stats">
                    <div class="stat_box <?php echo $status_filter === 'all' ? 'active' : ''; ?>" onclick="location.href='manager_reports.php?status=all'">
                        <div class="stat_number"><?php echo $total_reports; ?></div>
                        <div class="stat_label">총 신고 건수</div>
                    </div>
                    <div class="stat_box <?php echo $status_filter === 'waiting' ? 'active' : ''; ?>" onclick="location.href='manager_reports.php?status=waiting'">
                        <div class="stat_number"><?php echo $waiting_reports; ?></div>
                        <div class="stat_label">처리 대기</div>
                    </div>
                    <div class="stat_box <?php echo $status_filter === 'completed' ? 'active' : ''; ?>" onclick="location.href='manager_reports.php?status=completed'">
                        <div class="stat_number"><?php echo $completed_reports; ?></div>
                        <div class="stat_label">처리 완료</div>
                    </div>
                    <div class="stat_box <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" onclick="location.href='manager_reports.php?status=rejected'">
                        <div class="stat_number"><?php echo $rejected_reports; ?></div>
                        <div class="stat_label">기각</div>
                    </div>
                </div>

                <!-- 신고 목록 -->
                <div class="table_wrap">
                    <?php if (count($reports) > 0): ?>
                        <table class="reports_table">
                            <tr>
                                <th>신고 사유</th>
                                <th>신고자</th>
                                <th>작성자</th>
                                <th>신고 일시</th>
                                <th>상태</th>
                            </tr>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td onclick="openPopup(<?php echo $report['report_id']; ?>, '<?php echo htmlspecialchars(addslashes($report['reason'])); ?>', '<?php echo $report['reporter_name'] ? htmlspecialchars(addslashes($report['reporter_name'])) : '탈퇴한 회원'; ?>', '<?php echo $report['author_name'] ? htmlspecialchars(addslashes($report['author_name'])) : '탈퇴한 회원'; ?>', '<?php echo htmlspecialchars(addslashes($report['b_title'])); ?>', '<?php echo htmlspecialchars(addslashes($report['b_contents'])); ?>', '<?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?>', '<?php echo $report['status']; ?>', <?php echo $report['post_id']; ?>)">
                                        <?php echo htmlspecialchars($report['reason']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($report['reporter_name'] != '') {
                                            echo htmlspecialchars($report['reporter_name']);
                                        } else {
                                            echo '탈퇴한 회원';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($report['author_name'] != '') {
                                            echo htmlspecialchars($report['author_name']);
                                        } else {
                                            echo '탈퇴한 회원';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></td>
                                    <td>
                                        <button class="status_btn <?php
                                                                    if ($report['status'] == '완료') {
                                                                        echo 'status_completed';
                                                                    } else if ($report['status'] == '기각') {
                                                                        echo 'status_rejected';
                                                                    } else {
                                                                        echo 'status_waiting';
                                                                    }
                                                                    ?>"
                                            onclick="openPopup(<?php echo $report['report_id']; ?>, '<?php echo htmlspecialchars(addslashes($report['reason'])); ?>', '<?php echo $report['reporter_name'] ? htmlspecialchars(addslashes($report['reporter_name'])) : '탈퇴한 회원'; ?>', '<?php echo $report['author_name'] ? htmlspecialchars(addslashes($report['author_name'])) : '탈퇴한 회원'; ?>', '<?php echo htmlspecialchars(addslashes($report['b_title'])); ?>', '<?php echo htmlspecialchars(addslashes($report['b_contents'])); ?>', '<?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?>', '<?php echo $report['status']; ?>', <?php echo $report['post_id']; ?>)">
                                            <?php echo $report['status']; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <div class="no_data">
                            <h3>
                                <?php
                                if ($status_filter === 'waiting') {
                                    echo '처리 대기 중인 신고가 없습니다';
                                } elseif ($status_filter === 'completed') {
                                    echo '처리 완료된 신고가 없습니다';
                                } elseif ($status_filter === 'rejected') {
                                    echo '기각된 신고가 없습니다';
                                } else {
                                    echo '신고된 컨텐츠가 없습니다';
                                }
                                ?>
                            </h3>
                            <p>깨끗하고 건전한 커뮤니티가 유지되고 있습니다.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- 신고 상세 팝업 -->
    <div id="reportPopup" class="popup_overlay">
        <div class="popup_content">
            <h3 class="popup_title">신고 상세 정보</h3>

            <div class="popup_info">
                <div class="popup_label">신고 사유</div>
                <div class="popup_value" id="popup_reason"></div>
            </div>

            <div class="popup_info">
                <div class="popup_label">신고자</div>
                <div class="popup_value" id="popup_reporter"></div>
            </div>

            <div class="popup_info">
                <div class="popup_label">게시글 작성자</div>
                <div class="popup_value" id="popup_author"></div>
            </div>

            <div class="popup_info">
                <div class="popup_label">게시글 제목</div>
                <div class="popup_value" id="popup_title"></div>
            </div>

            <div class="popup_info">
                <div class="popup_label">게시글 내용</div>
                <div class="popup_value" id="popup_content" style="max-height: 150px; overflow-y: auto; white-space: pre-wrap;"></div>
            </div>

            <div class="popup_info">
                <div class="popup_label">신고 일시</div>
                <div class="popup_value" id="popup_date"></div>
            </div>

            <div class="popup_info">
                <div class="popup_label">현재 상태</div>
                <div class="popup_value" id="popup_status"></div>
            </div>

            <div class="popup_buttons">
                <button class="popup_btn ban_btn" id="ban_button" onclick="banPost()">게시글 삭제</button>
                <button class="popup_btn reject_btn" id="reject_button" onclick="rejectReport()">기각</button>
                <button class="popup_btn close_btn" onclick="closePopup()">닫기</button>
            </div>
        </div>
    </div>

    <?php require_once("inc/footer.php"); ?>

    <script>
        let currentReportId = 0;
        let currentPostId = 0;
        let currentStatus = '';

        function openPopup(reportId, reason, reporter, author, title, content, date, status, postId) {
            currentReportId = reportId;
            currentPostId = postId;
            currentStatus = status;

            document.getElementById('popup_reason').textContent = reason;
            document.getElementById('popup_reporter').textContent = reporter;
            document.getElementById('popup_author').textContent = author;
            document.getElementById('popup_title').textContent = title || '삭제된 게시글';
            document.getElementById('popup_content').textContent = content || '내용이 없습니다.';
            document.getElementById('popup_date').textContent = date;
            document.getElementById('popup_status').textContent = status;

            // 상태에 따라 버튼 텍스트와 기능 변경
            const banButton = document.getElementById('ban_button');
            const rejectButton = document.getElementById('reject_button');

            // 항상 버튼 표시
            banButton.style.display = 'block';
            rejectButton.style.display = 'block';

            if (status === '완료') {
                // 완료 상태: 삭제 취소, 기각 그대로
                banButton.textContent = '삭제 취소';
                banButton.onclick = function() {
                    cancelBan();
                };
                rejectButton.textContent = '기각';
                rejectButton.onclick = function() {
                    rejectReport();
                };
            } else if (status === '기각') {
                // 기각 상태: 삭제 그대로, 기각 취소
                banButton.textContent = '게시글 삭제';
                banButton.onclick = function() {
                    banPost();
                };
                rejectButton.textContent = '기각 취소';
                rejectButton.onclick = function() {
                    cancelReject();
                };
            } else {
                // 대기 상태: 둘 다 원래대로
                banButton.textContent = '게시글 삭제';
                banButton.onclick = function() {
                    banPost();
                };
                rejectButton.textContent = '기각';
                rejectButton.onclick = function() {
                    rejectReport();
                };
            }

            document.getElementById('reportPopup').style.display = 'flex';
        }

        function closePopup() {
            document.getElementById('reportPopup').style.display = 'none';
        }

        function banPost() {
            if (!confirm('이 게시글을 삭제 처리하시겠습니까?')) {
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'process_report.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('처리가 완료되었습니다.');
                    location.reload();
                } else {
                    alert('처리 중 오류가 발생했습니다.');
                }
            };

            xhr.send('report_id=' + currentReportId + '&post_id=' + currentPostId);
        }

        function rejectReport() {
            if (!confirm('이 신고를 기각하시겠습니까?\n신고자에게 알림이 전송됩니다.')) {
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'reject_report.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('신고가 기각되었습니다.');
                    location.reload();
                } else {
                    alert('처리 중 오류가 발생했습니다.');
                }
            };

            xhr.send('report_id=' + currentReportId);
        }

        function cancelBan() {
            if (!confirm('게시글 삭제를 취소하시겠습니까?\n게시글이 다시 복구됩니다.')) {
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'cancel_ban.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('삭제가 취소되었습니다.');
                    location.reload();
                } else {
                    alert('처리 중 오류가 발생했습니다.');
                }
            };

            xhr.send('report_id=' + currentReportId + '&post_id=' + currentPostId);
        }

        function cancelReject() {
            if (!confirm('신고 기각을 취소하시겠습니까?\n상태가 대기로 변경됩니다.')) {
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'cancel_reject.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('기각이 취소되었습니다.');
                    location.reload();
                } else {
                    alert('처리 중 오류가 발생했습니다.');
                }
            };

            xhr.send('report_id=' + currentReportId);
        }

        // 팝업 외부 클릭 시 닫기
        document.getElementById('reportPopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closePopup();
            }
        });
    </script>
</body>

</html>