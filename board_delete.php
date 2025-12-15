<?php
require_once("inc/session.php");
require_once("inc/db.php");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
if (!isset($_GET['b_idx'])) {
    die("<script>alert('잘못된 접근입니다.'); history.back();</script>");
}

$b_idx = $_GET['b_idx'];
$con = mysqli_connect("localhost", "root", "", "moviedb");

// 게시글 작성자 확인
$sql = "SELECT member_id FROM board_posts WHERE b_idx = '$b_idx'";
$result = mysqli_query($con, $sql);

if (mysqli_num_rows($result) == 0) {
    die("<script>alert('존재하지 않는 게시글입니다.'); history.back();</script>");
}

$post = mysqli_fetch_assoc($result);

// 작성자와 현재 로그인한 사용자가 다르면 삭제 불가
if ($post['member_id'] != $_SESSION['member_id']) {
    die("<script>alert('삭제 권한이 없습니다.'); history.back();</script>");
}

// 게시글 삭제
$delete_sql = "DELETE FROM board_posts WHERE b_idx = '$b_idx' AND member_id = '" . $_SESSION['member_id'] . "'";
$delete_result = mysqli_query($con, $delete_sql);

if ($delete_result) {
    echo "<script>
            alert('삭제되었습니다.');
            location.href='community.php';
          </script>";
} else {
    echo "<script>
            alert('삭제에 실패했습니다.');
            history.back();
          </script>";
}

mysqli_close($con);
?>
</body>
</html>