<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- ⭐ 여기부터 추가 ⭐ -->
    <style>
        .social_login_btn.Cinepals {
            background: #E50914 !important;
            color: #fff !important;
            border: none !important;
        }

        .social_login_btn.google {
            background: #fff !important;
            color: #333 !important;
            border: 2px solid #dadce0 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        }

        .social_login_btn.naver {
            background: #03C75A !important;
            color: #fff !important;
            border: none !important;
        }

        .social_login_btn.kakao {
            background: #FEE500 !important;
            color: #3C1E1E !important;
            border: none !important;
        }

        .social_login_btn.twitter {
            background: #1DA1F2 !important;
            color: #fff !important;
            border: none !important;
        }

        .social_login_btn.apple {
            background: #000 !important;
            color: #fff !important;
            border: none !important;
        }

        .social_login_btn.email {
            background: #495057 !important;
            color: #fff !important;
            border: none !important;
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="login_wrap">
            <div class="content_item">
                <div class="item_text">
                    <p class="item_type">Cinepals</p>
                    <p class="item_title">회원가입 페이지</p>
                </div>

                <div class="register_form">
                    <button class="social_login_btn Cinepals">
                        Cinepals으로 로그인
                    </button>

                    <button class="social_login_btn google">
                        Google 계정으로 계속하기
                    </button>

                    <button class="social_login_btn naver">
                        네이버 계정으로 계속하기
                    </button>

                    <button class="social_login_btn kakao">
                        카카오 계정으로 계속하기
                    </button>

                    <button class="social_login_btn twitter">
                        트위터 계정으로 계속하기
                    </button>

                    <button class="social_login_btn apple">
                        Apple로 계속하기
                    </button>

                    <div class="divider">또는</div>

                    <button class="social_login_btn email" onclick="location.href='email_signup.php'">
                        새로운 이메일로 가입하기
                    </button>

                    <p class="signup_text">
                        • 연결된 계정으로 간편 로그인이 가능합니다.<br>
                        • 연결된 계정정보는 로그인 용도로만 사용됩니다.<br>
                        • 계정 연결시 각 서비스의 이용약관에 동의하게 됩니다.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>
</body>

</html>