<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 검색어 가져오기 - 쉬운 방법
$search_query = '';
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

// 상태 필터 가져오기 - 쉬운 방법
$status_filter = '';
if (isset($_GET['status'])) {
    $status_filter = trim($_GET['status']);
}

// 전체 회원 데이터 가져오기 - status 컬럼 추가
$all_members = db_select("SELECT member_id, nickname, email, join_date, last_login, status FROM members ORDER BY join_date DESC");

// 검색과 필터링 - 쉬운 방법
$filtered_members = [];
$count = 0;

foreach ($all_members as $member) {
    // 30개까지만 가져오기 (더 많이 표시)
    if ($count >= 30) {
        break;
    }

    // 검색 조건 확인
    $search_match = true;
    if ($search_query != '') {
        $search_match = false;

        // 이름에서 검색
        if ($member['nickname'] != '' && strpos($member['nickname'], $search_query) !== false) {
            $search_match = true;
        }
        // 이메일에서 검색
        if ($member['email'] != '' && strpos($member['email'], $search_query) !== false) {
            $search_match = true;
        }
    }

    // 상태 필터 확인
    $status_match = true;
    if ($status_filter != '') {
        $member_status = $member['status'];
        if ($member_status == '' || $member_status == null) {
            $member_status = 'active';
        }
        if ($member_status != $status_filter) {
            $status_match = false;
        }
    }

    // 둘 다 맞으면 결과에 추가
    if ($search_match && $status_match) {
        $filtered_members[] = $member;
        $count = $count + 1;
    }
}

// 상태 텍스트 - 쉬운 방법
function get_status_text($status)
{
    if ($status == 'active' || $status == '' || $status == null) {
        return '활성';
    } else if ($status == 'banned') {
        return '정지';
    } else if ($status == 'deleted') {
        return '삭제됨';
    } else {
        return '활성';
    }
}

// 상태 색상 - 추가
function get_status_color($status)
{
    if ($status == 'active' || $status == '' || $status == null) {
        return '#28a745'; // 녹색
    } else if ($status == 'banned') {
        return '#dc3545'; // 빨간색
    } else if ($status == 'deleted') {
        return '#6c757d'; // 회색
    } else {
        return '#28a745';
    }
}

// 최근 접속일 처리 - 쉬운 방법
function get_last_login($last_login)
{
    if ($last_login == '' || $last_login == '접속 없음' || $last_login == null) {
        return '없음';
    } else {
        return date('Y-m-d', strtotime($last_login));
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원 관리 - Cinepals</title>
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
            gap: 20px !important;
            margin-left: -0px !important;
            /* ⭐ 이 줄 추가 - 전체 왼쪽으로 이동 ⭐ */

        }

        .main_menu_bar .menu_item {
            margin-right: 0 !important;
            padding: 0 10px !important;
        }

        /* ⭐ 이벤트, 커뮤니티, 마이페이지만 왼쪽으로 이동 ⭐ */
        .main_menu_bar .menu_item:nth-child(2),
        /* 이벤트 */
        .main_menu_bar .menu_item:nth-child(3),
        /* 커뮤니티 */
        .main_menu_bar .menu_item:nth-child(4) {
            /* 마이페이지 */
            margin-left: -10px !important;
            /* 왼쪽으로 10px 이동 */
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

        .admin_container {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px;
        }

        .content_area {
            flex: 1;
        }

        .content_header {
            margin-bottom: 30px;
        }

        .content_header h2 {
            color: #fff;
            font-size: 28px;
            margin: 0;
        }

        .search_filter_area {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .search_form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search_filter {
            padding: 10px;
            background: #2a2d34;
            border: 1px solid #444;
            border-radius: 4px;
            color: #fff;
            width: 300px;
        }

        .filter_select {
            padding: 10px;
            background: #2a2d34;
            border: 1px solid #444;
            border-radius: 4px;
            color: #fff;
            width: 120px;
        }

        .search_btn {
            padding: 10px 20px;
            background: #2c5282;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .members_table_wrap {
            background: white;
            color: #000;
            font-size: 16px;
            border-radius: 15px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 0;
            border-left: 4px solid #3498db;
        }

        .members_table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed !important;
            /* ⭐ 추가 */
        }

        .members_table th {
            border: 0 !important;
            text-align: center !important;
            background-color: #fff !important;
            color: #111 !important;
            font-weight: bold !important;
            padding: 15px;
        }

        /* ⭐ 각 컬럼 너비 고정 */
        .members_table th:nth-child(1),
        .members_table td:nth-child(1) {
            width: 30% !important;
        }

        .members_table th:nth-child(2),
        .members_table td:nth-child(2) {
            width: 14% !important;
        }

        .members_table th:nth-child(3),
        .members_table td:nth-child(3) {
            width: 14% !important;
        }

        .members_table th:nth-child(4),
        .members_table td:nth-child(4) {
            width: 12% !important;
            padding-left: 25px !important;
        }

        /* ⭐ 관리 헤더 - 삭제 버튼 위로 */
        .members_table th:nth-child(5) {
            width: 30% !important;
            text-align: left !important;
            padding-left: 90px !important;
            /* 헤더는 오른쪽으로 */
        }

        /* ⭐ 관리 셀 - 버튼은 왼쪽 정렬 */
        .members_table td:nth-child(5) {
            width: 30% !important;
            text-align: left !important;
            padding-left: 30px !important;
            /* 버튼은 왼쪽 */
        }

        .members_table td {
            border: 0 !important;
            background-color: #fff !important;
            color: #111 !important;
            text-align: center !important;
            padding: 15px;
            vertical-align: middle;
        }

        /* ⭐ 회원정보와 가입일 사이 간격 완전 제거 */
        .members_table th:nth-child(1),
        .members_table td:nth-child(1) {
            padding-right: 0px !important;
        }

        .members_table th:nth-child(2),
        .members_table td:nth-child(2) {
            padding-left: 0px !important;
        }

        .members_table tr {
            position: relative;
        }

        .members_table th::after {
            content: "";
            position: absolute;
            left: 20px;
            right: 20px;
            bottom: 0;
            height: 2px;
            background-color: #3498db;
        }

        .members_table td::after {
            content: "";
            position: absolute;
            left: 20px;
            right: 20px;
            bottom: 0;
            height: 1px;
            background-color: #e0e0e0;
        }

        .member_info {
            color: #fff;
        }

        .member_name {
            font-weight: bold;
            color: #111 !important;
            margin-bottom: 5px;
        }

        .member_email {
            font-size: 14px;
            color: #888;
        }

        .member_status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .status_active {
            background: #28a745;
        }

        .status_banned {
            background: #dc3545;
        }

        .status_deleted {
            background: #6c757d;
        }

        .action_buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: flex-start !important;
        }

        .action_btn {
            padding: 5px 10px;
            border: 1px solid #333;
            background: #2a2d34;
            color: #ccc;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.2s;
            text-decoration: none;
        }

        .action_btn:hover {
            background: #333;
        }

        .btn_ban {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .btn_unban {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }

        .btn_delete {
            background: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        .text-center {
            text-align: center;
            padding: 40px;
            color: #666;
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

        .side_menu .active {
            color: #3498db !important;
        }

        .side_menu .event:hover {
            color: #db34a9 !important;
        }

        .side_menu .report:hover {
            color: #f39c12 !important;
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
        }

        .popup_content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 0;
            width: 900px;
            height: 600px;
            max-width: 90%;
            max-height: 90%;
        }

        .popup_frame {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 8px;
        }

        .popup_header {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1001;
        }

        .close_btn {
            background: rgba(0, 0, 0, 0);
            border: none;
            color: 111;
            font-size: 18px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="admin_container">
            <aside class="side_menu">
                <div class="menu_list">
                    <a href="manager_home.php" class="menu_item home">대시보드</a>
                    <a href="manager_members.php" class="menu_item active">회원 관리</a>
                    <a href="manager_event.php" class="menu_item event">이벤트 관리</a>
                    <a href="manager_reports.php" class="menu_item report">리뷰/댓글관리</a>
                </div>
            </aside>

            <div class="content_area">
                <div class="content_header">
                    <h2>전체 회원 관리</h2>
                </div>

                <div class="search_filter_area">
                    <form method="GET" action="manager_members.php" class="search_form">
                        <input type="text" name="search" class="search_filter" placeholder="회원 검색 (이메일, 닉네임)" value="<?php echo htmlspecialchars($search_query); ?>">
                        <select name="status" class="filter_select">
                            <option value="">전체</option>
                            <option value="active" <?php if ($status_filter == 'active') echo 'selected'; ?>>활성</option>
                            <option value="banned" <?php if ($status_filter == 'banned') echo 'selected'; ?>>정지</option>
                            <option value="deleted" <?php if ($status_filter == 'deleted') echo 'selected'; ?>>삭제됨</option>
                        </select>
                        <button type="submit" class="search_btn">검색</button>
                    </form>
                </div>

                <div class="members_table_wrap">
                    <table class="members_table">
                        <thead>
                            <tr>
                                <th>회원정보</th>
                                <th>가입일</th>
                                <th>최근 접속일</th>
                                <th>상태</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($filtered_members) > 0): ?>
                                <?php foreach ($filtered_members as $member): ?>
                                    <tr>
                                        <td>
                                            <div class="member_info">
                                                <div class="member_name"><?php echo htmlspecialchars($member['nickname']); ?></div>
                                                <div class="member_email"><?php echo htmlspecialchars($member['email']); ?></div>
                                            </div>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($member['join_date'])); ?></td>
                                        <td><?php echo get_last_login($member['last_login']); ?></td>
                                        <td>
                                            <?php
                                            $member_status = $member['status'];
                                            if ($member_status == '' || $member_status == null) {
                                                $member_status = 'active';
                                            }
                                            ?>
                                            <span class="member_status status_<?php echo $member_status; ?>">
                                                <?php echo get_status_text($member_status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action_buttons">
                                                <button class="action_btn" onclick="viewMemberDetails(<?php echo $member['member_id']; ?>)">상세</button>

                                                <?php if ($member_status == 'deleted'): ?>
                                                    <!-- 삭제된 회원은 복구 버튼만 -->
                                                    <button class="action_btn btn_unban" onclick="restoreMember(<?php echo $member['member_id']; ?>)">복구</button>
                                                    <button class="action_btn" onclick="permanentDeleteMember(<?php echo $member['member_id']; ?>)">영구삭제</button>
                                                <?php else: ?>
                                                    <!-- 일반 회원은 삭제 + 정지/활성 버튼 -->
                                                    <button class="action_btn" onclick="deleteMember(<?php echo $member['member_id']; ?>)">삭제</button>
                                                    <?php if ($member_status == 'active'): ?>
                                                        <button class="action_btn" onclick="updateMemberStatus(<?php echo $member['member_id']; ?>, 'banned')">정지</button>
                                                    <?php else: ?>
                                                        <button class="action_btn" onclick="updateMemberStatus(<?php echo $member['member_id']; ?>, 'active')">활성</button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">검색 결과가 없습니다.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- 팝업 -->
    <div id="memberDetailPopup" class="popup_overlay">
        <div class="popup_content">
            <div class="popup_header">
                <button class="close_btn" onclick="closePopup()">✕</button>
            </div>
            <iframe id="memberDetailFrame" class="popup_frame" src=""></iframe>
        </div>
    </div>

    <script>
        function viewMemberDetails(memberId) {
            const frame = document.getElementById('memberDetailFrame');
            frame.src = 'member_detail.php?id=' + memberId;

            frame.onload = function() {
                const frameDoc = frame.contentDocument || frame.contentWindow.document;
                const style = document.createElement('style');
                style.innerHTML = `
            body { background: #f5f5f5; }
            .container {
                background: #ffffff;
                color: #000;
                font-size: 16px;
                border-radius: 15px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                border: 0;
                border-left: 4px solid #3498db;
            }
            .header {
                border-bottom: 2px solid #aaa;
            }
            .header h2 {
                color: #111;
            }
            .profile_image {
                border: 1px solid #aaa;
                background: #fff;
            }
            .info_row {
                border-bottom: 1px solid #aaa;
            }
            .info_label {
                color: #555;
            }
            .info_value {
                color: #555;
            }
            .bio_section {
                border: 1px solid #aaa;
                background: #f0f0f0;
            }
            .bio_title {
                color: #555;
            }
            .bio_content {
                color: #333;
            }
            .close_btn {
                background: #3498db;
            }
            .close_btn:hover {
                background: #2488cb;
            }
        `;
                frameDoc.head.appendChild(style);
            };

            document.getElementById('memberDetailPopup').style.display = 'flex';
        }


        function closePopup() {
            document.getElementById('memberDetailPopup').style.display = 'none';
            // iframe 초기화
            document.getElementById('memberDetailFrame').src = '';
        }

        // 팝업 외부 클릭시 닫기
        window.onclick = function(event) {
            var popup = document.getElementById('memberDetailPopup');
            if (event.target == popup) {
                closePopup();
            }
        }

        function deleteMember(memberId) {
            if (confirm('정말로 이 회원을 삭제하시겠습니까?')) {
                window.location.href = 'delete_member.php?member_id=' + memberId;
            }
        }

        function updateMemberStatus(memberId, status) {
            var message = '';
            if (status == 'banned') {
                message = '정말로 이 회원을 정지시키겠습니까?';
            } else {
                message = '정말로 이 회원을 활성화시키겠습니까?';
            }

            if (confirm(message)) {
                window.location.href = 'update_status.php?member_id=' + memberId + '&status=' + status;
            }
        }

        function restoreMember(memberId) {
            if (confirm('정말로 이 회원을 복구하시겠습니까?')) {
                window.location.href = 'restore_member.php?member_id=' + memberId;
            }
        }

        function permanentDeleteMember(memberId) {
            if (confirm('경고: 영구삭제된 회원은 복구할 수 없습니다.\n\n정말로 이 회원을 영구적으로 삭제하시겠습니까?')) {
                window.location.href = 'permanent_delete_member.php?member_id=' + memberId;
            }
        }
    </script>


    <?php require_once("inc/footer.php"); ?>
</body>

</html>