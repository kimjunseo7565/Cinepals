<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 수정 모드인 경우 게시글 정보 가져오기
$post = null;
if (isset($_GET['edit'])) {
    $post = db_select("SELECT * FROM board_posts WHERE b_idx = ?", [$_GET['edit']])[0];
    // 본인 글이 아닌 경우 접근 차단
    if ($post['member_id'] != $_SESSION['member_id']) {
        echo "<script>alert('접근 권한이 없습니다.'); history.back();</script>";
        exit;
    }
}

// 현재 로그인한 사용자의 닉네임 가져오기
$user = db_select("SELECT nickname FROM members WHERE member_id = ?", [$_SESSION['member_id']])[0];
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['edit']) ? '글 수정' : '글쓰기'; ?> - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="content_wrap">
            <div class="content_header">
                <h2><?php echo isset($_GET['edit']) ? '글 수정' : '글쓰기'; ?></h2>
            </div>

            <form method="post" action="./board_write_submit.php">
                <?php if ($post): ?>
                    <input type="hidden" name="b_idx" value="<?php echo $post['b_idx']; ?>">
                <?php endif; ?>
                
                <label for="b_title">제목</label>
                <input type="text" id="b_title" name="b_title" 
                       value="<?php echo $post ? htmlspecialchars($post['b_title']) : ''; ?>" 
                       placeholder="제목을 입력하세요." required />

                <div style="margin: 10px 0;">
                    작성자: <span><?php echo htmlspecialchars($user['nickname']); ?></span>
                </div>

                <label for="b_contents">내용</label>
                <textarea id="b_contents" name="b_contents" placeholder="내용을 입력하세요." required><?php echo $post ? htmlspecialchars($post['b_contents']) : ''; ?></textarea>

                <div class="button_group">
                    <button type="submit"><?php echo $post ? '수정' : '등록'; ?></button>
                    <button type="button" onclick="history.back()">취소</button>
                </div>
            </form>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>
</body>
</html>