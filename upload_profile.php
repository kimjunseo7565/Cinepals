<?php
require_once("inc/session.php");

if(isset($_FILES['profile_image'])) {
   $file = $_FILES['profile_image'];
   $member_id = $_SESSION['member_id'];
   
   $allowed = array('jpg', 'jpeg', 'png', 'gif');
   $filename = $file['name'];
   $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
   
   if(!in_array($filetype, $allowed)) {
       echo "<script>alert('jpg, jpeg, png, gif 파일만 업로드 가능합니다.'); history.back();</script>";
       exit();
   }
   
   $newfilename = $member_id . "_" . time() . "." . $filetype;
   $upload_dir = "uploads/profiles/";
   
   if(!file_exists($upload_dir)) {
       mkdir($upload_dir, 0777, true);
   }
   
   if(move_uploaded_file($file['tmp_name'], $upload_dir . $newfilename)) {
       $filepath = $upload_dir . $newfilename;
       
       $con = mysqli_connect("localhost", "root", "", "moviedb");
       $sql = "UPDATE members SET profile_image = '$filepath' WHERE member_id = '$member_id'";
       $result = mysqli_query($con, $sql);
       mysqli_close($con);
       
       if($result) {
           echo "<script>alert('프로필 이미지가 업로드되었습니다.'); location.href='mypage.php';</script>";
       } else {
           echo "<script>alert('DB 업데이트 중 오류가 발생했습니다.'); history.back();</script>";
       }
   } else {
       echo "<script>alert('파일 업로드 중 오류가 발생했습니다.'); history.back();</script>";
   }
}
?>