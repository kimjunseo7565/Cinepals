<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>이메일로 가입하기 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="login_wrap">
            <div class="content_item">
                <div class="item_text">
                    <p class="item_type">Cinepals</p>
                    <p class="item_title">이메일로 가입하기</p>
                </div>

                <form name="member_form" class="register_form" action="member_insert.php" method="POST">
                    <div class="input_wrapper">
                        <input type="text" name="email" placeholder="이메일">
                    </div>

                    <div class="input_wrapper">
                        <input type="password" name="pass" placeholder="비밀번호 (3자 이상)">
                    </div>

                    <div class="input_wrapper">
                        <input type="password" name="pass_confirm" placeholder="비밀번호 확인">
                    </div>

                    <div class="input_wrapper">
                        <input type="text" name="nickname" placeholder="닉네임">
                    </div>

                    <div class="terms_wrapper">
                        <label class="terms_item">
                            <input type="checkbox" name="age_check" required>
                            <span>만 14세 이상이며 이용약관에 동의합니다</span>
                        </label>

                        <label class="terms_item">
                            <input type="checkbox" name="privacy_check" required>
                            <span>개인정보 수집 및 이용에 동의합니다</span>
                        </label>
                    </div>

                    <button type="button" class="signup_btn" onclick="check_input()">
                        가입하기
                    </button>

                    <div class="login_guide">
                        <p>이미 계정이 있으신가요? <a href="login.php">로그인하기</a></p>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>
    <script src="js/member.js"></script>
</body>

</html>