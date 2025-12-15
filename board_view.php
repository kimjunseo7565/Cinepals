<?php
require_once("inc/session.php");
require_once("inc/db.php");

if (!isset($_GET['b_idx'])) {
    die("잘못된 접근입니다.");
}

// 조회수 증가
$update_sql = "UPDATE board_posts SET read_count = read_count + 1 WHERE b_idx = :b_idx";
db_insert($update_sql, [':b_idx' => $_GET['b_idx']]);

// 글 조회
$sql = "SELECT bp.*, m.profile_image 
        FROM board_posts bp 
        LEFT JOIN members m ON bp.member_id = m.member_id 
        WHERE bp.b_idx = :b_idx";
$post = db_select($sql, [':b_idx' => $_GET['b_idx']])[0];
if (!$post) {
    die("존재하지 않는 글입니다.");
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>글 보기 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .post_container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #1a1d24;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .post_header {
            padding-bottom: 20px;
            border-bottom: 1px solid #2c2f35;
            margin-bottom: 20px;
        }

        .post_title {
            font-size: 24px;
            color: #fff;
            margin-bottom: 15px;
        }

        .post_meta {
            display: flex;
            justify-content: space-between;
            color: #aaa;
            font-size: 14px;
        }

        .post_meta_left {
            display: flex;
            gap: 20px;
        }

        .post_content {
            min-height: 200px;
            line-height: 1.6;
            color: #fff;
            padding: 20px 0;
            white-space: pre-wrap;
        }

        .post_buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding-top: 20px;
            border-top: 1px solid #2c2f35;
            margin-top: 20px;
        }

        .post_btn {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .btn_list {
            background: #2c5282;
            color: #fff;
        }

        .btn_edit {
            background: #2a2d34;
            color: #fff;
        }

        .btn_delete {
            background: #822c2c;
            color: #fff;
        }

        .post_btn:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="post_container">
            <div class="post_header">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h1 class="post_title" style="margin: 0;"><?php echo htmlspecialchars($post['b_title']); ?></h1>
                    <?php if(!empty($post['update_date'])): ?><span style="color: #ff7675; font-size: 14px; white-space: nowrap; margin-right: 15px;">*수정됨</span><?php endif; ?>
                </div>
                <div class="post_meta">
                    <div class="post_meta_left">
                        <span>작성자:
                            <?php if (!empty($post['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($post['profile_image']); ?>" alt="프로필" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover; margin-right: 5px; vertical-align: middle;">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($post['nick_name']); ?>
                        </span>
                        <span>작성일: <?php echo date('Y-m-d H:i', strtotime($post['regdate'])); ?></span>
                    </div>
                    <div class="post_meta_right">
                        <span>조회수: <?php echo number_format($post['read_count']); ?></span>
                    </div>
                </div>
            </div>

            <div class="post_content">
                <?php echo nl2br(htmlspecialchars($post['b_contents'])); ?>
            </div>

            <div class="post_buttons">
                <button onclick="location.href='community.php'" class="post_btn btn_list">목록</button>
                <?php if (isset($_SESSION['member_id']) && $_SESSION['member_id'] == $post['member_id']): ?>
                    <button onclick="location.href='board_write.php?edit=<?php echo $post['b_idx']; ?>'"
                        class="post_btn btn_edit">수정</button>
                    <button onclick="deletePost()" class="post_btn btn_delete">삭제</button>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>

    <script>
        function deletePost() {
            if (confirm('정말 삭제하시겠습니까?')) {
                location.href = 'board_delete.php?b_idx=<?php echo $post['b_idx']; ?>';
            }
        }
    </script>
</body>

</html>