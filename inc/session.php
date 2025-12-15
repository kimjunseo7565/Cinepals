<?php
// 간단한 세션 시작
if (!isset($_SESSION)) {
   session_start();
}

// 로그인이 필요없는 페이지들
$public_pages = [
    'index.php', 
    'login.php', 
    'sign_up.php', 
    'register.php',
    'email_signup.php',
    'member_insert.php', 
    'success.php',
    'movies.php',
    'movie_detail.php',
    'search.php',
    'event.php',
    'event_detail.php',
    'community.php',
    'admin_login.php',
    'admin_login_process.php'
];

// 현재 페이지
$current_page = basename($_SERVER['PHP_SELF']);
// 수정된 코드
if (!in_array($current_page, $public_pages)) {
   if (!isset($_SESSION['member_id'])) {
      echo "<script>
               alert('로그인 후 이용바랍니다.');
               location.href='login.php';
            </script>";
      exit;
   }
}