<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
   <style>
       body {
           background-color: var(--primary-dark);
       }
   </style>
</head>
<body>
   <script>
       alert("로그아웃 되었습니다.");
       location.replace('index.php');
   </script>
</body>
</html>