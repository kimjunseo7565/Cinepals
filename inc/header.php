<?php
require_once("inc/db.php");
//check_movie_update_needed();

// ⭐ 관리자 페이지 확인
$current_page = basename($_SERVER['PHP_SELF']);
$is_admin_page = (strpos($current_page, 'manager_') === 0);
?>

<?php if ($is_admin_page): ?>
    <!-- ⭐ 관리자 페이지: localStorage 강제 설정 + 라이트모드만 로드 -->
    <script>
        localStorage.setItem('theme', 'light');
    </script>
    <link rel="stylesheet" id="light-theme-style" href="css/light-theme.css" media="all">
<?php else: ?>
    <!-- 일반 페이지: 다크/라이트 모드 둘 다 로드 -->
    <link rel="stylesheet" id="dark-theme-style" href="css/dark-theme.css">
    <link rel="stylesheet" id="light-theme-style" href="css/light-theme.css" media="none">
<?php endif; ?>

<?php if (!$is_admin_page): ?>
    <script>
        // 즉시 테마 적용 함수
        function applyTheme() {
            var savedTheme = localStorage.getItem('theme') || 'dark';
            var lightTheme = document.getElementById('light-theme-style');
            var darkTheme = document.getElementById('dark-theme-style');

            if (lightTheme && darkTheme) {
                if (savedTheme === 'light') {
                    lightTheme.media = 'all';
                    darkTheme.media = 'none';
                } else {
                    lightTheme.media = 'none';
                    darkTheme.media = 'all';
                }
            }
        }

        // 즉시 실행
        applyTheme();

        // 뒤로가기 대응 (중요!)
        window.addEventListener('pageshow', function(event) {
            applyTheme();
        });
    </script>
<?php endif; ?>

<style>
    .notification_btn {
        color: #aaa;
        text-decoration: none;
        font-size: 16px;
        position: relative;
    }

    .notification_btn:hover {
        color: #fff;
    }

    .notification_btn.has_unread {
        color: #fff !important;
    }

    .notification_badge {
        position: absolute;
        top: -3px;
        right: -8px;
        background: #e50914;
        color: white;
        font-size: 8px;
        padding: 1px 6px 3px 6px;
        border-radius: 10px;
        font-weight: bold;
    }
</style>

<style id="light-notification-style" media="none">
    .notification_btn.has_unread {
        color: #333 !important;
    }
</style>

<header id="header">
    <div class="header_container container">
        <h1 class="logo">
            <a href="index.php">Cinepals</a>
        </h1>

        <nav class="gnb">
            <ul class="main_menu_bar">
                <li class="menu_item"><a href="movies.php" class="nav_item">예매</a></li>
                <li class="menu_item"><a href="event.php" class="nav_item">이벤트</a></li>
                <li class="menu_item"><a href="community.php" class="nav_item">커뮤니티</a></li>
                <li class="menu_item"><a href="mypage.php" class="nav_item">마이페이지</a></li>
            </ul>
        </nav>

        <div class="util_area">
            <form action="search.php" method="get" class="search_form">
                <input type="text" name="q" placeholder="영화 제목, 감독을 검색하세요">
                <button type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <?php if ($is_admin_page): ?>
                <!-- 관리자 페이지: 버튼 보이지만 비활성화 -->
                <button id="themeBtn" style="margin: 0 10px; width: 80px; background: none; border: none; color: #666; cursor: not-allowed; opacity: 0.5;" disabled title="관리자 페이지는 라이트모드 고정입니다">
                    <i class="fas fa-sun"></i> 라이트모드
                </button>
            <?php else: ?>
                <!-- 일반 페이지: 정상 작동 -->
                <button id="themeBtn" style="margin: 0 10px; width: 80px; background: none; border: none; color: var(--primary-text); cursor: pointer;">
                    <i class="fas fa-sun"></i> 라이트모드
                </button>
            <?php endif; ?>

            <?php if (isset($_SESSION['member_id']) === false) { ?>
                <div class="auth_btns">
                    <a href="login.php" class="login_btn">로그인</a>
                    <a href="register.php" class="signup_btn">회원가입</a>
                </div>
            <?php } else {
                // 회원 정보 확인
                $member_data = db_select("SELECT nickname FROM members WHERE member_id = ?", [$_SESSION['member_id']]);

                // 회원이 존재하지 않으면 로그아웃 처리
                if (empty($member_data)) {
                    session_destroy();
                    echo "<script>
                alert('회원 정보를 찾을 수 없습니다. 다시 로그인해주세요.');
                location.href='login.php';
              </script>";
                    exit;
                }

                $member = $member_data[0];
                $unread_count = db_select("SELECT COUNT(*) as count FROM notifications WHERE member_id = ? AND is_read = 0", [$_SESSION['member_id']]);
                $unread = !empty($unread_count) ? $unread_count[0]['count'] : 0;
            ?>
                <div class="auth_btns">
                    <a href="notifications.php" class="notification_btn <?php echo $unread > 0 ? 'has_unread' : ''; ?>" style="position: relative; margin-right: 15px;">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread > 0): ?>
                            <span class="notification_badge"><?php echo $unread; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="mypage.php" class="signup_btn"><?php echo $member['nickname']; ?></a>
                    <a href="logout.php" class="login_btn">로그아웃</a>
                </div>
            <?php } ?>
        </div>
    </div>
</header>

<?php if (!$is_admin_page): ?>
    <script src="js/theme.js"></script>
<?php endif; ?>

<?php if (!$is_admin_page): ?>
    <script>
        function updateNotificationStyle() {
            const headerLightTheme = document.getElementById('light-theme-style');
            const lightNotif = document.getElementById('light-notification-style');

            if (headerLightTheme && lightNotif) {
                lightNotif.media = headerLightTheme.media;
            }
        }

        window.addEventListener('load', updateNotificationStyle);

        const notifObserver = new MutationObserver(updateNotificationStyle);
        const headerLightThemeForObserver = document.getElementById('light-theme-style');
        if (headerLightThemeForObserver) {
            notifObserver.observe(headerLightThemeForObserver, {
                attributes: true,
                attributeFilter: ['media']
            });
        }
    </script>
<?php endif; ?>