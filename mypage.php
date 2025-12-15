<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 현재 로그인한 회원의 정보 가져오기
$member_id = $_SESSION['member_id'];
$member_info = db_select("SELECT * FROM members WHERE member_id = ?", [$member_id])[0];
// 회원 닉네임 가져오기 - 이 부분 추가
$member_nickname = $member_info['nickname'];

// 사용자의 리뷰 수를 실시간으로 가져오기 (존재하는 영화만)
$review_count = db_select("
    SELECT COUNT(*) as count 
    FROM movie_reviews_new r
    JOIN moviesdb m ON r.movie_id = m.movie_id
    WHERE r.member_id = ?
", [$member_id])[0]['count'];

// 사용자가 자주 본 장르 TOP 3 가져오기
$favorite_genres = db_select("
    SELECT m.genre, COUNT(*) as count
    FROM user_actions_new u
    JOIN moviesdb m ON u.movie_id = m.movie_id
    WHERE u.member_id = ? AND m.genre IS NOT NULL
    GROUP BY m.genre
    ORDER BY count DESC
    LIMIT 3
", [$member_id]);

// 생일 정보 분리
$birth_parts = [];
if (isset($member_info['birth_date'])) {
    $birth_parts = explode('-', $member_info['birth_date']);
}

?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>마이페이지 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* 프로필 박스 스타일만 추가 */
        .profile_container {
            display: flex;
            flex-direction: row;
            align-items: center;
            text-align: left;
            padding: 30px;
            /* 좌우 패딩 조정 */
            margin-bottom: 30px;
            background-color: #1e2028;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            width: 100%;
            /* 전체 너비 사용 */
            min-height: 200px;
            border: 1px solid #32353e;
        }

        .profile_image_container {
            width: 120px;
            height: 120px;
            margin-bottom: 20px;
            /* 아래 여백 추가 */
            overflow: hidden;
            border-radius: 50%;
            flex-shrink: 0;
            position: relative;
            margin-right: 30px;
        }

        .profile_image_container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile_buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
            flex-shrink: 0;
        }

        .profile_btn {
            padding: 7px 14px;
            background-color: #224472;
            color: #e9e9e9;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            transition: background 0.15s;
        }

        .profile_btn:hover {
            background-color: #295590;
        }

        .delete_btn {
            background-color: #9e3a36;
        }

        .delete_btn:hover {
            background-color: #b94540;
        }

        .stats_container {
            display: flex;
            flex-direction: row;
            /* 통계는 가로 배치 */
            justify-content: space-around;
            flex: 1;
            /* 남은 공간 모두 사용 */
            padding: 0 10px;
        }

        .stat_item {
            text-align: center;
            padding: 0 5px;
        }

        .stat_value {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #fff;
        }

        .stat_label {
            color: #9a9a9a;
            font-size: 20px;
        }

        /* 원본 폼 스타일 */
        .info_form {
            background-color: #1a1d24;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .form_group {
            margin-bottom: 20px;
        }

        .form_label {
            display: block;
            margin-bottom: 8px;
            color: #ddd;
        }

        .form_input {
            width: 100%;
            padding: 12px;
            background: #2a2d34;
            border: 1px solid #444;
            border-radius: 4px;
            color: #fff;
        }

        .date_group {
            display: flex;
            gap: 10px;
        }

        .date_input {
            flex: 1;
            padding: 12px;
            background: #2a2d34;
            border: 1px solid #444;
            border-radius: 4px;
            color: #fff;
            text-align: center;
        }

        .save_btn {
            background: #2c5282;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        /* 모달 스타일 */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #1a1d24;
            border-radius: 8px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }

        .modal h3 {
            color: #fff;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .modal p {
            color: #aaa;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .modal .info-text {
            background: #2a2d34;
            padding: 15px;
            border-radius: 4px;
            font-size: 14px;
            color: #ff9800;
        }

        .button_group {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .button_group button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .delete_btn {
            background: #d9534f;
            color: #fff;
        }

        .cancel_btn {
            background: #333;
            color: #fff;
        }

        /* 장르 섹션 스타일 */
        .genre_section {
            margin-top: 20px;
            padding: 15px;
            background-color: #2a2d34;
            border-radius: 6px;
            border: 1px solid #404450;
        }

        .genre_title {
            color: #ddd;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .genre_list {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .genre_tag {
            color: #000000;
            padding-right: 6px;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: bold;
        }

        .genre_tag.empty {
            color: #888;
            background-color: #404450;
        }

        /* 장르 통계 아이템 스타일 */
        .stat_item_genres {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .genres_value {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 6px;
            min-height: 24px;
            max-width: 150px;
        }

        .stat_label_genre {
            color: #777777;
            font-size: 20px;
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="mypage_container">
            <!-- 좌측 메뉴 -->
            <aside class="side_menu">
                <div class="menu_list">
                    <a href="#" class="menu_item">마이페이지(프로필,회원정보)</a>
                    <a href="my_comments.php" class="menu_item">리뷰</a>
                    <a href="mypage_movies.php" class="menu_item">영화목록</a>
                    <a href="#" class="menu_item" id="deleteAccountBtn">회원탈퇴</a>
                    <a href="logout.php" class="menu_item">로그아웃</a>
                </div>
            </aside>

            <!-- 우측 콘텐츠 -->
            <div class="content_area">
                <!-- 새로운 프로필 레이아웃 적용 -->
                <div class="profile_container">
                    <div class="profile_image_container">
                        <?php if (!empty($member_info['profile_image'])): ?>
                            <img src="<?php echo $member_info['profile_image']; ?>"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user" style="font-size: 60px; color: #555; display: flex; justify-content: center; align-items: center; height: 100%;"></i>
                        <?php endif; ?>
                    </div>

                    <div class="profile_buttons">
                        <button type="button" class="profile_btn" onclick="document.getElementById('profile_file').click()">
                            <i class="fas fa-file-upload"></i> 파일선택
                        </button>
                        <button type="button" class="profile_btn" onclick="document.getElementById('profileForm').submit()">
                            <i class="fas fa-upload"></i> 업로드
                        </button>
                        <?php if (!empty($member_info['profile_image'])): ?>
                            <a href="delete_profile.php" class="profile_btn delete_btn">
                                <i class="fas fa-trash-alt"></i> 삭제
                            </a>
                        <?php endif; ?>
                    </div>

                    <form id="profileForm" action="upload_profile.php" method="post" enctype="multipart/form-data" style="display: none;">
                        <input type="file" name="profile_image" id="profile_file" accept="image/*">
                    </form>

                    <!-- 새로운 통계 섹션 -->
                    <div class="stats_container">
                        <div class="stat_item">
                            <a href="my_comments.php" style="text-decoration: none; color: inherit;">
                                <div class="stat_label">내 리뷰</div>    
                                <div class="stat_value"><?php echo $review_count; ?></div>
                            </a>
                        </div>

                        <div class="stat_item">
                            <a href="mypage_movies.php" style="text-decoration: none; color: inherit;">
                                <?php
                                $saved_movies = db_select("
    SELECT COUNT(*) as count 
    FROM user_actions_new u
    JOIN moviesdb m ON u.movie_id = m.movie_id
    WHERE u.member_id = ?
", [$member_id]);
                                $saved_movies_count = $saved_movies[0]['count'];
                                ?>
                                <div class="stat_label">내 영화</div>
                                <div class="stat_value"><?php echo $saved_movies_count; ?></div>
                            </a>
                        </div>

                        <div class="stat_item stat_item_genres">
                            <div class="stat_label_genre">자주 본 장르</div>
                            <div class="genres_value">
                                <?php if (!empty($favorite_genres)): ?>
                                    <?php foreach ($favorite_genres as $genre): ?>
                                        <?php
                                        // 장르가 여러 개일 수 있으므로 첫 번째만 표시
                                        $main_genre = explode(',', $genre['genre'])[0];
                                        $main_genre = trim($main_genre);
                                        ?>
                                        <span class="genre_tag">#<?php echo htmlspecialchars($main_genre); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: #888; font-size: 12px;">아직 데이터 없음</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>



                <!-- 회원 정보 폼 -->
                <form class="info_form" action="update_profile.php" method="POST">
                    <div class="form_group">
                        <label class="form_label">닉네임</label>
                        <input type="text" name="name" class="form_input"
                            value="<?php echo $member_info['name']; ?>"
                            placeholder="이름을 입력해주세요.(필수)">
                    </div>

                    <div class="form_group">
                        <label class="form_label">생일 (선택)</label>
                        <div class="date_group">
                            <input type="text" name="birth_year" class="date_input"
                                placeholder="YYYY"
                                value="<?php echo isset($birth_parts[0]) ? $birth_parts[0] : ''; ?>">
                            <input type="text" name="birth_month" class="date_input"
                                placeholder="MM"
                                value="<?php echo isset($birth_parts[1]) ? $birth_parts[1] : ''; ?>">
                            <input type="text" name="birth_day" class="date_input"
                                placeholder="DD"
                                value="<?php echo isset($birth_parts[2]) ? $birth_parts[2] : ''; ?>">
                        </div>
                    </div>

                    <div class="form_group">
                        <label class="form_label">이메일</label>
                        <input type="text" name="email" class="form_input"
                            value="<?php echo $member_info['email']; ?>"
                            placeholder="이메일을 입력해주세요.(선택)">
                    </div>

                    <div class="form_group">
                        <label class="form_label">전화번호</label>
                        <input type="tel" name="phone" class="form_input"
                            value="<?php echo $member_info['phone'] ?? ''; ?>"
                            placeholder="전화번호를 입력해주세요.(선택)">
                    </div>

                    <div class="form_group">
                        <label class="form_label">한 줄 소개</label>
                        <input type="text" name="bio" class="form_input"
                            value="<?php echo $member_info['bio'] ?? ''; ?>"
                            placeholder="한 줄 소개를 입력해주세요.(선택)">
                    </div>

                    <button type="submit" class="save_btn">저장</button>
                </form>
            </div>

            <!-- 회원탈퇴 모달 -->
            <div id="deleteAccountModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3>회원탈퇴</h3>
                    <p>정말로 탈퇴하시겠습니까?</p>
                    <p class="info-text">※ 회원탈퇴 시 개인정보는 모두 삭제되나, 작성한 리뷰와 게시글은 '탈퇴한 회원'으로 표시되어 유지됩니다.</p>
                    <form id="deleteAccountForm" action="member_delete.php" method="POST">
                        <div class="form_group">
                            <label for="password">비밀번호 확인</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="button_group">
                            <button type="submit" class="delete_btn">탈퇴하기</button>
                            <button type="button" class="cancel_btn">취소</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>
    <script src="js/member_delete.js"></script>

    <script>
        // 년도 입력 검증
        document.querySelector('input[name="birth_year"]').addEventListener('blur', function() {
            var year = this.value.trim();

            if (year) {
                if (year.length !== 4) {
                    alert('년도는 4자리로 입력해주세요. (예: 2000)');
                    this.value = '';
                    return;
                }

                var yearNum = parseInt(year);
                var currentYear = new Date().getFullYear();

                if (yearNum < 1900 || yearNum > currentYear) {
                    alert('올바른 년도를 입력해주세요. (1900 ~ ' + currentYear + ')');
                    this.value = '';
                }
            }
        });

        document.querySelector('input[name="birth_month"]').addEventListener('blur', function() {
            if (this.value && this.value.length === 1) {
                this.value = '0' + this.value;
            }
        });

        document.querySelector('input[name="birth_day"]').addEventListener('blur', function() {
            if (this.value && this.value.length === 1) {
                this.value = '0' + this.value;
            }
        });

        document.querySelector('.info_form').addEventListener('submit', function(e) {
            var year = document.querySelector('input[name="birth_year"]').value.trim();
            var month = document.querySelector('input[name="birth_month"]').value.trim();
            var day = document.querySelector('input[name="birth_day"]').value.trim();

            if (year || month || day) {
                if (!year || !month || !day) {
                    alert('생일을 입력하시려면 년/월/일을 모두 입력해주세요.');
                    e.preventDefault();
                    return;
                }

                if (year.length !== 4) {
                    alert('년도는 4자리로 입력해주세요. (예: 2000)');
                    e.preventDefault();
                    return;
                }

                var yearNum = parseInt(year);
                var currentYear = new Date().getFullYear();

                if (yearNum < 1900 || yearNum > currentYear) {
                    alert('올바른 년도를 입력해주세요. (1900 ~ ' + currentYear + ')');
                    e.preventDefault();
                    return;
                }

                var monthNum = parseInt(month);
                if (monthNum < 1 || monthNum > 12) {
                    alert('월은 1-12 사이여야 합니다.');
                    e.preventDefault();
                    return;
                }

                var dayNum = parseInt(day);
                if (dayNum < 1 || dayNum > 31) {
                    alert('일은 1-31 사이여야 합니다.');
                    e.preventDefault();
                    return;
                }
            }
        });
    </script>

</body>

</html>