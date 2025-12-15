<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 이벤트 목록 가져오기
$events = db_select("SELECT * FROM events ORDER BY created_at DESC");

// 이벤트 삭제 처리
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $event_id = $_GET['id'];
    db_update_delete("DELETE FROM events WHERE id = ?", [$event_id]);
    echo "<script>alert('이벤트가 삭제되었습니다.'); location.href='manager_event.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>이벤트 관리 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .main_wrapper {
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
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

        .content_title {
            font-size: 28px;
            margin-bottom: 30px;
            /* color: #fff; 이 줄 삭제  */
        }

        .add_event_btn {
            background: #2c5282;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 30px;
            text-decoration: none;
            display: inline-block;
        }

        .events_table {
            width: 100%;
            background: #1a1d24;
            border: 1px solid #333;
            border-collapse: collapse;
        }

        .events_table th,
        .events_table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        .events_table th {
            background: #2a2d34;
            text-align: center !important;
            color: #fff;
            font-weight: bold;
        }

        .events_table td {
            color: #ccc;
        }

        .events_table tr {
            position: relative;
        }

        .events_table th::after {
            content: "";
            position: absolute;
            left: 20px;    /* 왼쪽 뚫림 길이 */
            right: 20px;   /* 오른쪽 뚫림 길이 */
            bottom: 0;
            height: 2px;   /* 선 두께 */
            background-color: #db34a9; /* 선 색상 */
        }

        .events_table td::after {
            content: "";
            position: absolute;
            left: 20px;    /* 왼쪽 뚫림 길이 */
            right: 20px;   /* 오른쪽 뚫림 길이 */
            bottom: 0;
            height: 1px;   /* 선 두께 */
            background-color: #e0e0e0; /* 선 색상 */
        }

        .event_image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .action_btns {
            display: flex;
            gap: 5px;
        }

        .btn_edit,
        .btn_delete {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
        }

        .btn_edit {
            background: #2c5282;
            color: white;
        }

        .btn_delete {
            background: #dc3545;
            color: white;
        }

        .no_events {
            text-align: center;
            padding: 50px;
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

        .side_menu .home:hover {
            color: #e74c3c !important;
        }

        .side_menu .member:hover {
            color: #3498db !important;
        }

        .side_menu .active {
            color: #db34a9 !important;
        }

        .side_menu .report:hover {
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
                    <a href="manager_event.php" class="menu_item active">이벤트 관리</a>
                    <a href="manager_reports.php" class="menu_item report">리뷰/댓글관리</a>
                </div>
            </aside>

            <!-- 우측 콘텐츠 -->
            <div class="content_area">
                <h2 class="content_title">이벤트 관리</h2>

                <a href="event_add.php" class="add_event_btn">
                    <i class="fas fa-plus"></i> 새 이벤트 등록
                </a>

                <table class="events_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>이미지</th>
                            <th style="width: 29%">제목</th>
                            <th style="width: 15%">기간</th>
                            <th style="width: 9%">영화관</th>
                            <th>등록일</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($events)): ?>
                            <?php
                            $counter = count($events);
                            foreach ($events as $event):
                            ?>
                                <tr>
                                    <td><?php echo $counter--; ?></td>
                                    <td>
                                        <?php if (!empty($event['main_image'])): ?>
                                            <img src="<?php echo $event['main_image']; ?>" alt="이벤트 이미지" class="event_image">
                                        <?php else: ?>
                                            <span>이미지 없음</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td>
                                        <?php echo $event['start_date']; ?> ~
                                        <?php echo $event['end_date'] ? $event['end_date'] : '무제한'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['cinema_type']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($event['created_at'])); ?></td>
                                    <td>
                                        <div class="action_btns">
                                            <a href="event_edit.php?id=<?php echo $event['id']; ?>" class="btn_edit">수정</a>
                                            <a href="?delete=1&id=<?php echo $event['id']; ?>"
                                                onclick="return confirm('정말 삭제하시겠습니까?')"
                                                class="btn_delete">삭제</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no_events">등록된 이벤트가 없습니다.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>
</body>

</html>