<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인 - Cinepals</title>
    <!-- 영화 리뷰 사이트 색상 테마 적용 -->
    <link rel="stylesheet" href="css/admin_login.css">
</head>
<body class="admin_page">
    <div class="admin_login_container">
        <div class="login_box">
            <h2>관리자 로그인</h2>
            <form action="admin_login_process.php" method="post">
                <input type="text" name="user_id" placeholder="아이디" required>
                <input type="password" name="password" placeholder="비밀번호" required>
                <button type="submit">로그인</button>
            </form>
        </div>
    </div>
</body>
</html>
