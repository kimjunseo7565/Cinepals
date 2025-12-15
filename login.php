<?php
// session.php
session_start();

if(!isset($_SESSION['member_id']) && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php'])) {
   header('Location: login.php');
   exit;
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>로그인 - Cinepals</title>
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
                   <p class="item_title">로그인</p>
               </div>

               <form name="login_form" method="POST" action="login.post.php">
                   <div class="input_wrapper">
                   <input type="text" placeholder="이메일 또는 휴대폰 번호" name="email" value="<?= isset($_COOKIE['email']) ? $_COOKIE['email'] : ""; ?>" required>
                   </div>
                   <div class="input_wrapper">
                       <input type="password" placeholder="비밀번호" name="password" required>
                   </div>

                   <a href="#" class="forgot_link">비밀번호를 잊어버리셨나요?</a>

                   <label class="keep_login">
                       <input type="checkbox" name="keep_login">
                       <span>아이디 저장</span>
                   </label>

                   <button type="submit" class="login_button">
                       <span>로그인</span>
                   </button>
               </form>

               <div class="signup_guide">
                   <p>New to Cinepals? <a href="register.php">Cinepals 계정 만들기</a></p>
               </div>
           </div>
       </div>
   </main>

   <?php require_once("inc/footer.php"); ?>
   <script src="js/member.js"></script>
</body>

</html>