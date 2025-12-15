<?php
require_once("inc/session.php");
require_once("inc/db.php");

// member_id 받기
if (isset($_GET['member_id'])) {
    $member_id = $_GET['member_id'];

    // DB에서 완전히 삭제
    $result = db_update_delete("DELETE FROM members WHERE member_id = ?", [$member_id]);

    if ($result) {
        echo "<script>alert('회원이 영구적으로 삭제되었습니다.'); window.location.href='manager_members.php';</script>";
    } else {
        echo "<script>alert('삭제 중 오류가 발생했습니다.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('잘못된 접근입니다.'); window.location.href='manager_members.php';</script>";
}
