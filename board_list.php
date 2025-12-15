<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 시간 표시 함수
function get_time_ago($datetime)
{
    $time_diff = time() - strtotime($datetime);

    if ($time_diff < 60) {
        return '방금 전';
    } else if ($time_diff < 3600) {
        return floor($time_diff / 60) . '분 전';
    } else if ($time_diff < 86400) {
        return floor($time_diff / 3600) . '시간 전';
    } else {
        return date('Y-m-d', strtotime($datetime));
    }
}

// 정렬 조건 처리
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// 정렬 쿼리 설정
switch ($sort) {
    case 'views':
        $order_by = "ORDER BY read_count DESC";
        break;
    case 'oldest':
        $order_by = "ORDER BY regdate ASC";
        break;
    default: // newest
        $order_by = "ORDER BY regdate DESC";
}

$posts = db_select("SELECT * FROM board_posts {$order_by}");
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시판 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .sort-links {
            margin-bottom: 20px;
            text-align: right;
        }

        .sort-links a {
            margin-left: 10px;
            color: #666;
            text-decoration: none;
            padding: 5px 10px;
            transition: all 0.2s;
        }

        .sort-links a:hover {
            color: #2c5282;
        }

        .sort-links a.active {
            color: #2c5282;
            font-weight: bold;
            border-bottom: 2px solid #2c5282;
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="content_wrap">
            <div class="content_header">
                <h2>게시판</h2>
                <?php if (isset($_SESSION['username'])): ?>
                    <a href="board_write.php" class="write_btn">글쓰기</a>
                <?php endif; ?>
            </div>

            <div class="sort-links">
                <a href="?sort=newest" <?php echo $sort == 'newest' ? 'class="active"' : ''; ?>>최신순</a>
                <a href="?sort=oldest" <?php echo $sort == 'oldest' ? 'class="active"' : ''; ?>>오래된순</a>
                <a href="?sort=views" <?php echo $sort == 'views' ? 'class="active"' : ''; ?>>조회순</a>
            </div>

            <table class="board_list">
                <thead>
                    <tr>
                        <th>번호</th>
                        <th>제목</th>
                        <th>작성자</th>
                        <th>작성일</th>
                        <th>조회수</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($posts): ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?php echo $post['b_idx']; ?></td>
                                <td>
                                    <a href="board_view.php?b_idx=<?php echo $post['b_idx']; ?>&page=<?php echo $page; ?>&sort=<?php echo $sort; ?>">
                                        <?php echo $post['b_title']; ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo $post['is_anonymous'] ? '익명' : $post['nick_name']; ?>
                                </td>
                                <td><?php echo get_time_ago($post['regdate']); ?></td>
                                <td><?php echo $post['read_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">게시글이 없습니다.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>
</body>

</html>