<?php
// 기존 PHP 코드는 동일하게 유지...
require_once("inc/session.php");
require_once("inc/db.php");
require_once("movie_links.php");
require_once("trailer_crawler.php");

$movie_id = $_GET['id'];

// DB에서 영화 정보 가져오기 - movie_id로 먼저 검색, 없으면 id로 검색
$movie_result = db_select("SELECT * FROM moviesdb WHERE movie_id = ?", [$movie_id]);

if (empty($movie_result)) {
    // movie_id로 찾지 못하면 id 컬럼으로 검색
    $movie_result = db_select("SELECT * FROM moviesdb WHERE id = ?", [$movie_id]);
}

if (empty($movie_result)) {
    die("영화 정보를 찾을 수 없습니다. (ID: $movie_id)");
}

$movie = $movie_result[0];

// 실제 DB의 id 값을 사용 (리뷰 저장시 필요)
$db_movie_id = $movie['id'];

// 영화 데이터 구조화
$movie_data = array();
$movie_data['movie_id'] = $movie['movie_id'];
$movie_data['db_id'] = $db_movie_id; // DB의 실제 id 추가
$movie_data['title'] = isset($movie['title']) ? $movie['title'] : '정보 없음';
$movie_data['release_date'] = isset($movie['release_date']) ? $movie['release_date'] : '';
$movie_data['poster_image'] = isset($movie['poster_image']) ? $movie['poster_image'] : 'images/default_poster.jpg';
$movie_data['director'] = isset($movie['director']) ? $movie['director'] : '';
$movie_data['genre'] = isset($movie['genre']) ? $movie['genre'] : '';
$movie_data['runtime'] = isset($movie['runtime']) ? $movie['runtime'] : '';
$movie_data['content'] = isset($movie['plot']) ? $movie['plot'] : '';
$movie_data['actors'] = isset($movie['actors']) ? $movie['actors'] : '';
$movie_data['audience_count'] = isset($movie['audience_count']) ? $movie['audience_count'] : 0;

// 현재 사용자의 좋아요/저장 상태 확인
$user_actions = array();
if (isset($_SESSION['member_id'])) {
    $actions_result = db_select(
        "SELECT action_type FROM user_actions_new WHERE movie_id = ? AND member_id = ?",
        [$movie['movie_id'], $_SESSION['member_id']]
    );

    if ($actions_result) {
        foreach ($actions_result as $action) {
            $user_actions[$action['action_type']] = true;
        }
    }
}

// 리뷰 가져오기 - DB의 id 컬럼 사용
$reviews = db_select(
    "SELECT r.*, m.nickname 
     FROM movie_reviews_new r 
     LEFT JOIN members m ON r.member_id = m.member_id 
     WHERE r.movie_id = ? 
     ORDER BY r.created_at DESC",
    [$movie['movie_id']]  // ← 올바름 (문자열 movie_id 사용)
);

// 영화 예고편 정보 가져오기
$trailer_info = get_naver_movie_trailer_medium($movie_data['title']);

// 영화관 예매 링크 생성
$lotte_link = get_lotte_cinema_link($movie_data['title']);
$cgv_link = get_cgv_link($movie_data['title']);
$megabox_link = get_megabox_link($movie_data['title']);
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $movie_data['title']; ?> - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* 기존 CSS 스타일은 동일 */
        body {
            background: #000;
            color: #fff;
        }

        .movie_detail {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .movie_header {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
        }

        .movie_poster {
            width: 300px;
        }

        .movie_poster img {
            width: 100%;
            display: block;
        }

        .movie_info {
            flex: 1;
        }

        .movie_title {
            font-size: 32px;
            margin-bottom: 10px;
            color: #fff;
        }

        .movie_rating {
            margin-bottom: 20px;
        }

        .movie_rating .fa-star {
            color: #ffd700;
            margin-right: 5px;
        }

        .rating_score {
            color: #ffd700;
            font-size: 24px;
            margin-left: 5px;
        }

        .movie_meta {
            margin: 8px 0;
            color: #fff;
            font-size: 16px;
        }

        .meta_label {
            display: inline-block;
            color: #888;
            margin-right: 10px;
        }

        .action_buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }

        .action_btn {
            padding: 8px 16px;
            border: 1px solid #333;
            background: transparent;
            color: #fff;
            cursor: pointer;
            transition: 0.2s;
            font-size: 14px;
        }

        .action_btn.active {
            border-color: #ffd700;
            background: #333;
        }

        .movie_synopsis {
            margin-top: 20px;
            padding: 20px;
            background: #111;
            border: 1px solid #333;
            line-height: 1.6;
        }

        .review_section {
            margin-top: 40px;
        }

        .review_form {
            background: #111;
            border: 1px solid #333;
            padding: 20px;
        }

        .review_title {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .star_rating {
            margin-bottom: 15px;
        }

        .star_rating .fa-star {
            color: #666;
            font-size: 20px;
            cursor: pointer;
            margin-right: 5px;
        }

        .star_rating .fa-star.active {
            color: #ffd700;
        }

        .review_content textarea {
            width: 100%;
            height: 150px;
            padding: 15px;
            border: 1px solid #333;
            background: #fff;
            margin-bottom: 15px;
            resize: none;
            color: #333;
            font-family: inherit;
        }

        .review_buttons {
            display: flex;
            gap: 10px;
        }

        .review_btn {
            flex: 1;
            padding: 12px;
            border: none;
            cursor: pointer;
            font-size: 15px;
            text-align: center;
        }

        .review_cancel {
            background: #333;
            color: white;
        }

        .review_submit {
            background: #ffd700;
            color: black;
        }

        .my_reviews {
            margin-top: 40px;
        }

        .review_card {
            background: #111;
            border: 1px solid #333;
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
        }

        .review_card .star_rating {
            margin-bottom: 10px;
        }

        .review_card .review_content {
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .review_footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #888;
            font-size: 14px;
            flex-wrap: wrap;
        }

        /* 날짜를 오른쪽 상단에 고정 */
        .review_date {
            position: absolute;
            top: 5px;
            right: 5px;
            color: #888;
            font-size: 14px;
        }

        .review_actions {
            display: flex;
            gap: 10px;
        }

        .review_edit,
        .review_delete {
            padding: 5px 10px;
            border: 1px solid #333;
            background: transparent;
            color: #fff;
            cursor: pointer;
        }

        .trailer_section {
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .section_title {
            font-size: 20px;
            margin-bottom: 15px;
            color: #fff;
        }

        .trailer_player {
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .trailer_player iframe {
            display: block;
        }

        .no_trailer {
            padding: 20px;
            background: #111;
            color: #666;
            text-align: center;
            border-radius: 8px;
        }

        .theater_buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            margin-bottom: 20px;
            max-width: 400px;
        }

        .theater_btn {
            padding: 8px 16px;
            border: 1px solid #333;
            background: transparent;
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
            flex: 1;
            transition: 0.2s;
            font-size: 14px;
            min-width: 80px;
            max-width: 120px;
        }

        .theater_btn:hover {
            border-color: #ffd700;
            background: #333;
        }

        /* 리뷰 수정 폼 스타일 - 기존 방식 복구 */
        .edit-form {
            margin-top: 15px;
            display: none;
            width: 100%;
        }

        .edit-textarea {
            width: 100%;
            height: 150px;
            padding: 15px;
            background: #1a1a1a;
            border: 1px solid #333;
            color: #fff;
            margin-bottom: 10px;
            resize: none;
            font-size: 16px;
        }

        .edit-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            width: 100%;
        }

        .edit-save,
        .edit-cancel {
            width: 120px;
            padding: 10px 0;
            border: none;
            cursor: pointer;
            font-size: 14px;
            text-align: center;
            border-radius: 4px;
        }

        .edit-save {
            background: #2c5282;
            color: white;
        }

        .edit-cancel {
            background: #333;
            color: white;
        }

        /* 별점 수정 스타일 */
        .edit-rating {
            margin-bottom: 15px;
        }

        .edit-rating .fa-star {
            color: #666;
            font-size: 20px;
            cursor: pointer;
            margin-right: 5px;
        }

        .edit-rating .fa-star.active {
            color: #ffd700;
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <div class="movie_detail">
        <div class="movie_header">
            <?php if (!empty($movie_data['poster_image']) && $movie_data['poster_image'] != 'images/default_poster.jpg'): ?>
                <div class="movie_poster">
                    <img src="<?php echo $movie_data['poster_image']; ?>"
                        alt="<?php echo $movie_data['title']; ?>">
                </div>
            <?php endif; ?>

            <div class="movie_info">
                <h1 class="movie_title"><?php echo $movie_data['title']; ?></h1>

                <div class="movie_meta">
                    <span class="meta_label">감독:</span>
                    <?php
                    if (empty($movie_data['director']) || $movie_data['director'] == '정보 없음' || $movie_data['director'] == '정보 수집 중') {
                        echo '추후에 공개';
                    } else {
                        echo $movie_data['director'];
                    }
                    ?>
                </div>

                <div class="movie_meta">
                    <span class="meta_label">장르:</span>
                    <?php echo $movie_data['genre']; ?>
                </div>

                <div class="movie_meta">
                    <span class="meta_label">출연:</span>
                    <?php echo $movie_data['actors']; ?>
                </div>
                <div class="movie_meta">
                    <span class="meta_label">개봉일:</span>
                    <?php
                    if (empty($movie_data['release_date']) || $movie_data['release_date'] == '0000-00-00') {
                        echo '추후에 공개';
                    } else {
                        echo $movie_data['release_date'];
                    }
                    ?>
                </div>

                <div class="movie_meta">
                    <span class="meta_label">상영시간:</span>
                    <?php
                    if (!empty($movie_data['runtime']) && $movie_data['runtime'] != null) {
                        echo $movie_data['runtime'] . '분';
                    } else {
                        echo '추후에 공개';
                    }
                    ?>
                </div>

                <div class="movie_meta">
                    <span class="meta_label">관객수:</span>
                    <?php
                    if ($movie_data['audience_count'] > 0) {
                        echo number_format($movie_data['audience_count']) . '명';
                    } else {
                        echo '추후에 공개';
                    }
                    ?>
                </div>

                <div class="action_buttons">
                    <button class="action_btn <?php echo isset($user_actions['like']) ? 'active' : ''; ?>" onclick="toggleAction('like')">
                        <i class="far fa-thumbs-up"></i> 좋아요
                    </button>
                    <button class="action_btn <?php echo isset($user_actions['save']) ? 'active' : ''; ?>" onclick="toggleAction('save')">
                        <i class="far fa-bookmark"></i> 저장
                    </button>
                </div>

                <div class="theater_buttons">
                    <a href="<?php echo $lotte_link; ?>" target="_blank" class="theater_btn lotte">
                        롯데시네마
                    </a>
                    <a href="<?php echo $cgv_link; ?>" target="_blank" class="theater_btn cgv">
                        CGV
                    </a>
                    <a href="<?php echo $megabox_link; ?>" target="_blank" class="theater_btn megabox">
                        메가박스
                    </a>
                </div>

                <div class="movie_synopsis">
                    <?php echo nl2br($movie_data['content']); ?>
                </div>
            </div>
        </div>

        <!-- 예고편 섹션 -->
        <div class="trailer_section">
            <h3 class="section_title">영화 예고편</h3>
            <?php
            if ($trailer_info['trailer']['type'] == 'naver' || $trailer_info['trailer']['type'] == 'youtube') {
                echo '<div class="trailer_player">
                  <iframe width="100%" height="400" src="' . $trailer_info['trailer']['trailer_url'] . '" frameborder="0" allowfullscreen loading="lazy" style="background: #111; display: block;"></iframe>
                  </div>';
            } else {
                echo '<div class="no_trailer">예고편을 찾을 수 없습니다.</div>';
            }
            ?>
        </div>

        <div class="review_section">
            <form class="review_form" id="reviewForm">
                <h3 class="review_title">평점</h3>
                <div class="star_rating" id="starRating">
                    <i class="far fa-star" onclick="clickStar(1)"></i>
                    <i class="far fa-star" onclick="clickStar(2)"></i>
                    <i class="far fa-star" onclick="clickStar(3)"></i>
                    <i class="far fa-star" onclick="clickStar(4)"></i>
                    <i class="far fa-star" onclick="clickStar(5)"></i>
                    <i class="far fa-star" onclick="clickStar(6)"></i>
                    <i class="far fa-star" onclick="clickStar(7)"></i>
                    <i class="far fa-star" onclick="clickStar(8)"></i>
                    <i class="far fa-star" onclick="clickStar(9)"></i>
                    <i class="far fa-star" onclick="clickStar(10)"></i>
                </div>

                <div class="review_content">
                    <textarea id="reviewContent" placeholder="내용을 입력하세요" required></textarea>
                </div>

                <div class="review_buttons">
                    <button type="button" class="review_btn review_cancel" onclick="clearReview()">취소</button>
                    <button type="button" class="review_btn review_submit" onclick="submitReview()">리뷰 등록</button>
                </div>
                <input type="hidden" id="ratingValue" value="0">
            </form>
        </div>

        <div class="my_reviews">
            <?php if ($reviews): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review_card" id="review-<?php echo $review['review_id']; ?>">
                        <div class="star_rating">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <?php if ($i <= $review['rating']): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span class="rating_score"><?php echo $review['rating']; ?></span>
                        </div>
                        <div class="review_content"><?php echo $review['content']; ?></div>

                        <!-- 기존 방식의 수정 폼 (그 자리에서 수정) -->
                        <div class="edit-form" id="edit-form-<?php echo $review['review_id']; ?>">
                            <!-- 별점 수정 UI - 단순화 (클릭만) -->
                            <div class="edit-rating" id="edit-rating-<?php echo $review['review_id']; ?>">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <?php if ($i <= $review['rating']): ?>
                                        <i class="fas fa-star" onclick="clickEditStar(<?php echo $review['review_id']; ?>, <?php echo $i; ?>)"></i>
                                    <?php else: ?>
                                        <i class="far fa-star" onclick="clickEditStar(<?php echo $review['review_id']; ?>, <?php echo $i; ?>)"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <input type="hidden" name="edit-rating-value" id="edit-rating-value-<?php echo $review['review_id']; ?>" value="<?php echo $review['rating']; ?>">
                            </div>

                            <textarea class="edit-textarea"><?php echo $review['content']; ?></textarea>
                            <div class="edit-buttons">
                                <button type="button" class="edit-save" onclick="saveReview(<?php echo $review['review_id']; ?>)">저장</button>
                                <button type="button" class="edit-cancel" onclick="cancelEdit(<?php echo $review['review_id']; ?>)">취소</button>
                            </div>
                        </div>

                        <div class="review_footer">
                            <span>
                                <?php
                                if ($review['anonymous'] == 1 || empty($review['nickname'])) {
                                    echo '탈퇴한 회원';
                                } else {
                                    echo $review['nickname'];
                                }
                                ?>
                            </span>
                            <span class="review_date">
                                <?php echo date('Y-m-d H:i:s', strtotime($review['created_at'])); ?>
                            </span>
                            <?php if (isset($_SESSION['member_id']) && $review['member_id'] == $_SESSION['member_id']): ?>
                                <div class="review_actions">
                                    <button class="review_edit" onclick="editReview(<?php echo $review['review_id']; ?>)">
                                        수정
                                    </button>
                                    <button class="review_delete" onclick="deleteReview(<?php echo $review['review_id']; ?>)">
                                        삭제
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no_reviews" style="text-align: center; padding: 30px; background: #111; border: 1px solid #333; margin-top: 20px;">
                    <p>아직 등록된 리뷰가 없습니다. 첫 번째 리뷰를 작성해보세요!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once("inc/footer.php"); ?>

    <!-- 수정된 JavaScript -->
    <script>
        var currentRating = 0;

        // 별점 클릭 함수
        function clickStar(rating) {
            currentRating = rating;
            document.getElementById('ratingValue').value = rating;

            // 모든 별을 기본 상태로 설정
            var stars = document.querySelectorAll('#starRating .fa-star');
            for (var i = 0; i < stars.length; i++) {
                stars[i].className = 'far fa-star';
            }

            // 선택된 별까지 활성화
            for (var i = 0; i < rating; i++) {
                stars[i].className = 'fas fa-star';
            }
        }

        // 리뷰 제출 - form submit 방식 (수정된 부분)
        function submitReview() {
            var content = document.getElementById('reviewContent').value;
            var rating = document.getElementById('ratingValue').value;

            if (rating == '0') {
                alert('별점을 선택해주세요.');
                return;
            }

            if (content.trim() == '') {
                alert('리뷰 내용을 입력해주세요.');
                return;
            }

            // 간단한 form submit 방식 - DB의 id 값 사용
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'review_process.php';

            var movieInput = document.createElement('input');
            movieInput.type = 'hidden';
            movieInput.name = 'movie_id';
            movieInput.value = '<?php echo $db_movie_id; ?>'; // 수정된 부분: DB의 실제 id 사용
            form.appendChild(movieInput);

            var ratingInput = document.createElement('input');
            ratingInput.type = 'hidden';
            ratingInput.name = 'rating';
            ratingInput.value = rating;
            form.appendChild(ratingInput);

            var contentInput = document.createElement('input');
            contentInput.type = 'hidden';
            contentInput.name = 'content';
            contentInput.value = content;
            form.appendChild(contentInput);

            document.body.appendChild(form);
            form.submit();
        }

        // 리뷰 폼 초기화
        function clearReview() {
            document.getElementById('reviewContent').value = '';
            currentRating = 0;
            document.getElementById('ratingValue').value = '0';

            var stars = document.querySelectorAll('#starRating .fa-star');
            for (var i = 0; i < stars.length; i++) {
                stars[i].className = 'far fa-star';
            }
        }

        // 좋아요/저장 기능 - AJAX 방식 (수정된 부분)
        function toggleAction(actionType) {
            // AI 추천 캐시 삭제 (좋아요/저장 변경 시 AI 추천도 업데이트됨)
            sessionStorage.removeItem('ai_recommendation');
            
            var formData = new FormData();
            formData.append('movie_id', '<?php echo $movie['movie_id']; ?>');
            formData.append('action_type', actionType);

            // XMLHttpRequest 헤더 추가로 AJAX 요청임을 알림
            fetch('user_action.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(result => {
                    if (result === 'success') {
                        // 버튼 상태만 토글
                        toggleButtonState(actionType);
                    } else if (result === 'login_required') {
                        alert('로그인 후 이용바랍니다.');
                        location.href = 'login.php';
                    }
                })
                .catch(error => {
                    // 오류 메시지 제거
                });
        }

        // 버튼 상태 토글
        function toggleButtonState(actionType) {
            var buttons = document.querySelectorAll('.action_btn');
            for (var i = 0; i < buttons.length; i++) {
                if (buttons[i].onclick.toString().includes(actionType)) {
                    if (buttons[i].classList.contains('active')) {
                        buttons[i].classList.remove('active');
                    } else {
                        buttons[i].classList.add('active');
                    }
                    break;
                }
            }
        }

        // 리뷰 수정
        function editReview(reviewId) {
            var reviewCard = document.getElementById('review-' + reviewId);
            var contentDiv = reviewCard.querySelector('.review_content');
            var editForm = document.getElementById('edit-form-' + reviewId);

            contentDiv.style.display = 'none';
            editForm.style.display = 'block';
        }

        function cancelEdit(reviewId) {
            var reviewCard = document.getElementById('review-' + reviewId);
            var contentDiv = reviewCard.querySelector('.review_content');
            var editForm = document.getElementById('edit-form-' + reviewId);

            contentDiv.style.display = 'block';
            editForm.style.display = 'none';
        }

        // 수정용 별점 클릭
        function clickEditStar(reviewId, rating) {
            var ratingInput = document.getElementById('edit-rating-value-' + reviewId);
            ratingInput.value = rating;

            var stars = document.querySelectorAll('#edit-rating-' + reviewId + ' .fa-star');
            for (var i = 0; i < stars.length; i++) {
                stars[i].className = 'far fa-star';
            }

            for (var i = 0; i < rating; i++) {
                stars[i].className = 'fas fa-star';
            }
        }

        // 리뷰 수정 저장 - form submit 방식
        function saveReview(reviewId) {
            var editForm = document.getElementById('edit-form-' + reviewId);
            var content = editForm.querySelector('textarea').value;
            var rating = document.getElementById('edit-rating-value-' + reviewId).value;

            if (!content.trim()) {
                alert('리뷰 내용을 입력해주세요.');
                return;
            }

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'comment_update.php';

            var reviewIdInput = document.createElement('input');
            reviewIdInput.type = 'hidden';
            reviewIdInput.name = 'review_id';
            reviewIdInput.value = reviewId;
            form.appendChild(reviewIdInput);

            var contentInput = document.createElement('input');
            contentInput.type = 'hidden';
            contentInput.name = 'content';
            contentInput.value = content;
            form.appendChild(contentInput);

            var ratingInput = document.createElement('input');
            ratingInput.type = 'hidden';
            ratingInput.name = 'rating';
            ratingInput.value = rating;
            form.appendChild(ratingInput);

            document.body.appendChild(form);
            form.submit();
        }

        // 리뷰 삭제 - form submit 방식
        function deleteReview(reviewId) {
            if (!confirm('리뷰를 삭제하시겠습니까?')) {
                return;
            }

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'comment_delete.php';

            var reviewIdInput = document.createElement('input');
            reviewIdInput.type = 'hidden';
            reviewIdInput.name = 'review_id';
            reviewIdInput.value = reviewId;
            form.appendChild(reviewIdInput);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>

</html>