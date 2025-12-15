<?php
require_once("inc/session.php");
require_once("inc/db.php");

$member_id = $_SESSION['member_id'];

// 페이지 진입 시 모든 알림을 자동으로 읽음 처리
$con = mysqli_connect("localhost", "root", "", "moviedb");
mysqli_query($con, "UPDATE notifications SET is_read = 1 WHERE member_id = '$member_id' AND is_read = 0");
mysqli_close($con);

// 알림 삭제
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $notification_id = $_POST['id'];
    $con = mysqli_connect("localhost", "root", "", "moviedb");
    mysqli_query($con, "DELETE FROM notifications WHERE id = '$notification_id' AND member_id = '$member_id'");
    mysqli_close($con);
    header("Location: notifications.php");
    exit;
}

// 모든 알림 가져오기
$notifications = db_select("SELECT * FROM notifications WHERE member_id = ? ORDER BY created_at DESC", [$member_id]);

// 시간 표시 함수
function get_time_ago($datetime)
{
    $time_diff = time() - strtotime($datetime);

    if ($time_diff < 60) {
        return '방금 전';
    } else if ($time_diff < 3600) {
        return floor($time_diff / 60) . '분 전';
    } else if ($time_diff < 86400) {
        return floor($time_diff / 3600) . '시간 전';
    } else if ($time_diff < 2592000) {
        return floor($time_diff / 86400) . '일 전';
    } else {
        return date('Y-m-d', strtotime($datetime));
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>알림 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .notifications_container {
            max-width: 800px;
            margin: 20px auto !important;
            padding: 10px !important;
        }

        .notifications_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px !important;
            padding-bottom: 10px !important;
            border-bottom: 2px solid #ddd;
        }

        .page_title {
            font-size: 24px;
            color: var(--primary-text);
            margin: 0;
        }

        .notification_item {
            background: var(--card-bg);
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 12px !important;
            margin-bottom: 12px !important;
            transition: all 0.3s ease;
            position: relative;
        }

        .notification_item:hover {
            border-color: #5a9fd4;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .notification_header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .notification_content {
            flex: 1;
            align-self: flex-start !important;
        }

        .notification_message {
            color: var(--primary-text);
            font-size: 14px !important;
            line-height: 1.4 !important;
            padding: 10px 12px !important;
            min-height: 0 !important;
            height: auto !important;
            display: block !important;
            background: rgba(0, 0, 0, 0.02);
            border-radius: 4px;
            border-left: 2px solid #a0c4e8;
            white-space: normal !important;
        }

        .notification_message a {
            color: var(--primary-text);
            text-decoration: none;
            cursor: pointer;
        }

        .notification_message a:hover {
            color: #2c5282;
            text-decoration: underline;
        }

        .notification_message a[href="javascript:void(0);"] {
            color: var(--secondary-text);
        }

        .notification_message a[href="javascript:void(0);"]:hover {
            color: #dc3545;
            text-decoration: line-through;
        }

        /* 펼치기/접기 스타일 */
        .expandable_notification {
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            display: block !important;
            min-height: auto !important;
            height: auto !important;
            line-height: 1.3 !important;
        }

        .short_message {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 !important;
            margin: 0 !important;
            min-height: auto !important;
            height: auto !important;
            line-height: 1.3 !important;
        }

        .short_message i.fa-chevron-down,
        .short_message i.fa-chevron-up {
            font-size: 10px !important;
            line-height: 1 !important;
        }

        .short_message:hover {
            color: #2c5282;
        }

        .long_message {
            color: var(--secondary-text);
            font-size: 14px;
            line-height: 1.6;
            animation: slideDown 0.3s ease;
        }

        .long_message[style*="display: none"] {
            height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: hidden !important;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification_date {
            color: var(--secondary-text);
            font-size: 12px !important;
            margin-top: 6px !important;
        }

        .notification_actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-left: 10px;
            flex-shrink: 0;
            align-self: flex-start;
            position: relative;
        }

        .hamburger_btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 18px;
            background: transparent;
            color: #666;
        }

        .hamburger_btn:hover {
            background: #f0f0f0;
        }

        .action_menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 100;
            min-width: 120px;
            margin-top: 4px;
            overflow: hidden;
        }

        .action_menu.show {
            display: block;
        }

        .action_btn {
            padding: 10px 16px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.15s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            text-align: left;
            color: #333;
        }

        .action_btn:hover {
            background: #f5f5f5;
        }

        .delete_btn {
            color: #e53935;
        }

        .delete_btn:hover {
            background: #ffebee;
        }

        .no_notifications {
            text-align: center;
            padding: 60px 20px;
            background: var(--card-bg);
            border-radius: 8px;
            color: var(--secondary-text);
        }

        .no_notifications i {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--secondary-text);
        }

        .no_notifications h3 {
            margin-bottom: 10px;
            color: var(--secondary-text);
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="notifications_container">
            <div class="notifications_header">
                <h2 class="page_title">알림</h2>
            </div>

            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $notif_id = $notification['id'];
                    $full_message = $notification['message'];

                    // 메시지 분리 (짧은 메시지 ||| 긴 메시지)
                    $message_parts = explode('|||', $full_message);
                    $short_message = $message_parts[0];
                    $long_message = isset($message_parts[1]) ? $message_parts[1] : null;
                    $has_detail = !empty($long_message);

                    // Attempt to find a related post link for certain notification types (e.g., comment)
                    $goto_link = null;
                    $goto_deleted = false; // flag: referenced post/comment was deleted

                    if (!empty($notification['type']) && $notification['type'] === 'comment') {
                        // post_id와 comment_id가 있는 경우 (새로운 알림)
                        if (!empty($notification['post_id']) && !empty($notification['comment_id'])) {
                            $post_id = $notification['post_id'];
                            $comment_id = $notification['comment_id'];

                            // 게시글이 존재하는지 확인
                            $post_rows = db_select("SELECT b_idx, b_title FROM board_posts WHERE b_idx = ?", [$post_id]);

                            if (!empty($post_rows)) {
                                // 게시글이 삭제 표시인지 확인
                                if (mb_strpos($post_rows[0]['b_title'], '관리자에 의해 삭제된 게시글') !== false) {
                                    $goto_deleted = true;
                                } else {
                                    // 댓글이 존재하는지 확인
                                    $comment_rows = db_select("SELECT comment_id FROM board_comments WHERE comment_id = ?", [$comment_id]);

                                    if (!empty($comment_rows)) {
                                        // 정상 게시글 + 정상 댓글 - 링크 생성
                                        $goto_link = 'community.php?open_comments=' . $post_id;
                                    } else {
                                        // 댓글이 삭제됨
                                        $goto_deleted = true;
                                    }
                                }
                            } else {
                                // 게시글이 완전히 삭제됨
                                $goto_deleted = true;
                            }
                        }
                        // post_id와 comment_id가 없는 경우 (기존 알림) - 제목으로 찾기
                        else if (preg_match("/회원님의 게시글 '([^']+)'에 새 댓글/", $full_message, $matches)) {
                            $possible_title = $matches[1];

                            // Try to find the post by title and owner (member who receives the notification)
                            $post_rows = db_select("SELECT b_idx, b_title FROM board_posts WHERE b_title = ? AND member_id = ? LIMIT 1", [$possible_title, $member_id]);

                            if (!empty($post_rows) && isset($post_rows[0]['b_idx'])) {
                                // 게시글이 삭제 표시인지 확인
                                if (mb_strpos($post_rows[0]['b_title'], '관리자에 의해 삭제된 게시글') !== false) {
                                    $goto_deleted = true;
                                } else {
                                    // 정상 게시글 - 링크 생성
                                    $goto_link = 'community.php?open_comments=' . $post_rows[0]['b_idx'];
                                }
                            } else {
                                // 게시글을 찾을 수 없음 = 삭제됨
                                $goto_deleted = true;
                            }
                        }
                    }
                    // 관리자 삭제 알림 처리 (type='admin')
                    else if (!empty($notification['type']) && $notification['type'] === 'admin') {
                        // post_id가 있는 경우
                        if (!empty($notification['post_id'])) {
                            $post_id = $notification['post_id'];

                            // 게시글이 존재하는지 확인
                            $post_rows = db_select("SELECT b_idx, b_title FROM board_posts WHERE b_idx = ?", [$post_id]);

                            if (!empty($post_rows)) {
                                // 게시글이 삭제 표시인지 확인
                                if (mb_strpos($post_rows[0]['b_title'], '관리자에 의해 삭제된 게시글') !== false) {
                                    // 삭제된 게시글 - 클릭하면 삭제 메시지
                                    $goto_deleted = true;
                                } else {
                                    // 정상 게시글 - 링크 생성 (복구된 경우)
                                    $goto_link = 'community.php?open_comments=' . $post_id;
                                }
                            } else {
                                // 게시글이 완전히 삭제됨
                                $goto_deleted = true;
                            }
                        }
                    }
                    ?>
                    <div class="notification_item" id="notif_<?php echo $notif_id; ?>">
                        <div class="notification_header">
                            <div class="notification_content">
                                <div class="notification_message"><?php if ($has_detail): ?><div class="expandable_notification">
                                            <div class="short_message" onclick="toggleDetail(<?php echo $notif_id; ?>)" style="cursor: pointer;"><?php echo nl2br(htmlspecialchars($short_message)); ?><i class="fas fa-chevron-down" id="arrow_<?php echo $notif_id; ?>" style="margin-left: 8px; font-size: 12px; transition: transform 0.3s;"></i></div>
                                            <div class="long_message" id="detail_<?php echo $notif_id; ?>" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;"><?php echo nl2br(htmlspecialchars($long_message)); ?></div>
                                        </div><?php else: ?><?php if (!empty($goto_link)): ?><a href="<?php echo htmlspecialchars($goto_link); ?>"><?php echo nl2br(htmlspecialchars($short_message)); ?></a><?php elseif (!empty($goto_deleted)): ?><a href="javascript:void(0);" onclick="deleteDeletedNotification(<?php echo $notif_id; ?>);"><?php echo nl2br(htmlspecialchars($short_message)); ?></a><?php else: ?><?php echo nl2br(htmlspecialchars($short_message)); ?><?php endif; ?><?php endif; ?></div>
                                <div class="notification_date">
                                    <?php echo get_time_ago($notification['created_at']); ?>
                                </div>
                            </div>
                            <div class="notification_actions">
                                <button class="hamburger_btn" onclick="toggleMenu(<?php echo $notif_id; ?>)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="action_menu" id="menu_<?php echo $notif_id; ?>">
                                    <form method="POST" style="display: contents;">
                                        <input type="hidden" name="id" value="<?php echo $notif_id; ?>">
                                        <button type="submit" name="delete" class="action_btn delete_btn" onclick="return confirm('이 알림을 삭제하시겠습니까?')">
                                            <i class="fas fa-trash"></i>
                                            <span>삭제</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no_notifications">
                    <i class="fas fa-bell-slash"></i>
                    <h3>알림이 없습니다</h3>
                    <p>새로운 알림이 도착하면 여기에 표시됩니다.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>

    <script>
        function toggleMenu(notifId) {
            event.stopPropagation();
            var menu = document.getElementById('menu_' + notifId);

            // 다른 모든 메뉴 닫기
            var allMenus = document.querySelectorAll('.action_menu');
            allMenus.forEach(function(m) {
                if (m.id !== 'menu_' + notifId) {
                    m.classList.remove('show');
                }
            });

            // 현재 메뉴 토글
            menu.classList.toggle('show');
        }

        // 외부 클릭 시 메뉴 닫기
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.notification_actions')) {
                var allMenus = document.querySelectorAll('.action_menu');
                allMenus.forEach(function(m) {
                    m.classList.remove('show');
                });
            }
        });

        // 알림 상세 내용 펼치기/접기 함수
        function toggleDetail(notifId) {
            var detailElement = document.getElementById('detail_' + notifId);
            var arrowElement = document.getElementById('arrow_' + notifId);

            if (detailElement.style.display === 'none') {
                // 펼치기
                detailElement.style.display = 'block';
                arrowElement.className = 'fas fa-chevron-up';
                arrowElement.style.transform = 'rotate(180deg)';
            } else {
                // 접기
                detailElement.style.display = 'none';
                arrowElement.className = 'fas fa-chevron-down';
                arrowElement.style.transform = 'rotate(0deg)';
            }
        }

        // 삭제된 게시글/댓글 알림 자동 삭제 함수
        function deleteDeletedNotification(notifId) {
            alert('이 게시글 또는 댓글은 삭제되었습니다.');

            // 알림 자동 삭제
            var formData = new FormData();
            formData.append('delete', '1');
            formData.append('id', notifId);

            fetch('notifications.php', {
                method: 'POST',
                body: formData
            }).then(function() {
                // DOM에서 알림 제거 (부드러운 애니메이션)
                var notifElement = document.getElementById('notif_' + notifId);
                notifElement.style.opacity = '0';
                notifElement.style.transform = 'translateX(-20px)';
                setTimeout(function() {
                    notifElement.remove();

                    // 알림이 모두 없어졌는지 확인
                    if (document.querySelectorAll('.notification_item').length === 0) {
                        location.reload();
                    }
                }, 300);
            });
        }
    </script>
</body>

</html>