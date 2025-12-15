<?php
require_once("inc/session.php");
require_once("inc/db.php");
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>가입 완료 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <div class="success_container">
        <div class="success_box">
            <h1 class="success_title">Cinepals</h1>
            <p class="success_text">회원가입 완료</p>

            <div class="success_content">
                <i class="fa-solid fa-circle-check success_icon"></i>
                <h2 class="success_message">회원가입이 완료되었습니다!</h2>
                <p class="success_desc">
                    Cinepals의 회원이 되신 것을 환영합니다.<br>
                    이제 다양한 서비스를 이용하실 수 있습니다.
                </p>
                <a href="index.php" class="success_btn">메인페이지로 이동</a>
            </div>
        </div>
    </div>

    <?php require_once("inc/footer.php"); ?>
</body>

</html>