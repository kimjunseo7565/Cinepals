<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 현재 로그인한 회원의 member_id 가져오기
$member_id = $_SESSION['member_id'];

// 회원 닉네임 가져오기
$member_info = db_select("SELECT nickname FROM members WHERE member_id = ?", [$member_id])[0];
$member_nickname = $member_info['nickname'];

// 정렬 옵션 처리
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'recent';
$order_by = match($sort_order) {
    'rating_high' => 'r.rating DESC, r.created_at DESC',
    'rating_low' => 'r.rating ASC, r.created_at DESC',
    'oldest' => 'r.created_at ASC',
    default => 'r.created_at DESC'
};

// 쿼리 정의
$query = "
   SELECT r.*, m.title as movie_title, m.movie_id, m.poster_image, m.genre, m.director, m.release_date
   FROM movie_reviews_new r
   JOIN moviesdb m ON r.movie_id = m.movie_id
   WHERE r.member_id = ? 
   ORDER BY $order_by";

// 리뷰 데이터 가져오기
$comments = db_select($query, [$member_id]);

// 통계 계산
$total_reviews = count($comments);
$average_rating = 0;
if ($total_reviews > 0) {
    $sum = 0;
    foreach ($comments as $comment) {
        $sum += $comment['rating'];
    }
    $average_rating = $sum / $total_reviews;
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>나의 리뷰 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* 헤더 영역 */
        .page_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .header_left h2 {
            font-size: 24px;
            font-weight: 500;
            color: #030303;
            margin: 0;
        }

        .header_stats {
            display: flex;
            gap: 25px;
            align-items: center;
            margin-top: 8px;
        }

        .stat_item {
            font-size: 14px;
            color: #606060;
        }

        .stat_item strong {
            color: #030303;
            font-weight: 600;
        }

        /* 정렬 및 검색 */
        .controls_bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search_box {
            flex: 1;
            max-width: 400px;
            min-width: 200px;
            position: relative;
        }

        .search_input {
            width: 100%;
            padding: 8px 35px 8px 12px;
            border: 1px solid #ccc;
            border-radius: 2px;
            font-size: 14px;
            background: #f8f8f8;
            transition: all 0.2s;
        }

        .search_input:focus {
            outline: none;
            border-color: #1c62b9;
            background: #fff;
        }

        .search_icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #606060;
            pointer-events: none;
        }

        .sort_dropdown {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 2px;
            background: #f8f8f8;
            font-size: 14px;
            color: #030303;
            cursor: pointer;
            min-width: 150px;
        }

        .sort_dropdown:hover {
            background: #f0f0f0;
        }

        .sort_dropdown:focus {
            outline: none;
            border-color: #1c62b9;
        }

        /* 리뷰 리스트 */
        .reviews_container {
            background: #fff;
        }

        .review_item {
            display: flex;
            gap: 16px;
            padding: 14px 0;
            border-bottom: 1px solid #e5e5e5;
            cursor: pointer;
            transition: background 0.2s;
        }

        .review_item:hover {
            background: #f9f9f9;
        }

        .review_item:last-child {
            border-bottom: none;
        }

        /* 페이지 로드 시 애니메이션 */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .review_item {
            animation: slideIn 0.3s ease;
        }

        /* 썸네일 */
        .review_thumbnail {
            width: 100px;
            height: 140px;
            flex-shrink: 0;
            border-radius: 6px;
            overflow: hidden;
            background: #f0f0f0;
        }

        .review_thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 20%;
            transition: transform 0.2s;
        }

        .review_item:hover .review_thumbnail img {
            transform: scale(1.05);
        }

        /* 리뷰 정보 */
        .review_details {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 6px;
        }

        .review_title_row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .review_title {
            font-size: 16px;
            font-weight: 600;
            color: #030303;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
        }

        .rating_badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: #fff;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .rating_badge i {
            font-size: 11px;
        }

        .review_meta {
            font-size: 13px;
            color: #606060;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .meta_separator {
            color: #ccc;
        }

        .review_date {
            font-size: 12px;
            color: #999;
        }

        .review_content {
            font-size: 14px;
            color: #333;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            background: #f8f9fa;
            padding: 10px 12px;
            border-radius: 6px;
            border-left: 3px solid #065fd4;
            margin-top: 4px;
            max-width: 500px;
        }

        /* 햄버거 메뉴 */
        .review_actions {
            position: relative;
            display: flex;
            align-items: flex-start;
            padding-top: 4px;
        }

        .menu_btn {
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

        .menu_btn:hover {
            background: #f0f0f0;
        }

        .dropdown_menu {
            display: none;
            position: absolute;
            top: 36px;
            right: 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
            min-width: 100px;
            z-index: 100;
            overflow: hidden;
        }

        .dropdown_menu.active {
            display: block;
        }

        .dropdown_item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #333;
            cursor: pointer;
            transition: background 0.2s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            white-space: nowrap;
        }

        .dropdown_item:hover {
            background: #f5f5f5;
        }

        .dropdown_item i {
            font-size: 12px;
            width: 14px;
        }

        .dropdown_item.delete_item:hover {
            background: #fff5f5;
            color: #dc3545;
        }

        /* 모달 */
        .modal_overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal_overlay.active {
            display: flex;
        }

        .modal_box {
            background: #fff;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .modal_header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal_header h3 {
            font-size: 20px;
            font-weight: 500;
            color: #030303;
            margin: 0;
        }

        .close_btn {
            width: 40px;
            height: 40px;
            border: none;
            background: transparent;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #606060;
            transition: background 0.2s;
        }

        .close_btn:hover {
            background: #f2f2f2;
        }

        .modal_body {
            padding: 24px;
        }

        .form_group {
            margin-bottom: 20px;
        }

        .form_label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #030303;
            margin-bottom: 8px;
        }

        .rating_selector {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .rating_chip {
            padding: 8px 16px;
            border: 1px solid #ccc;
            border-radius: 16px;
            background: #f8f8f8;
            font-size: 14px;
            color: #606060;
            cursor: pointer;
            transition: all 0.2s;
        }

        .rating_chip:hover {
            background: #f0f0f0;
            border-color: #1c62b9;
        }

        .rating_chip.selected {
            background: #065fd4;
            color: #fff;
            border-color: #065fd4;
        }

        .content_textarea {
            width: 100%;
            min-height: 120px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 2px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            background: #f8f8f8;
            transition: all 0.2s;
        }

        .content_textarea:focus {
            outline: none;
            border-color: #1c62b9;
            background: #fff;
        }

        .modal_footer {
            padding: 16px 24px;
            border-top: 1px solid #e5e5e5;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal_btn {
            padding: 10px 16px;
            border: none;
            border-radius: 2px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .cancel_btn {
            background: transparent;
            color: #065fd4;
        }

        .cancel_btn:hover {
            background: #def1ff;
        }

        .save_btn {
            background: #065fd4;
            color: #fff;
        }

        .save_btn:hover {
            background: #0b5ed7;
        }

        /* 빈 상태 */
        .empty_state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty_icon {
            font-size: 80px;
            color: #e0e0e0;
            margin-bottom: 16px;
        }

        .empty_title {
            font-size: 20px;
            font-weight: 400;
            color: #030303;
            margin-bottom: 8px;
        }

        .empty_text {
            font-size: 14px;
            color: #606060;
            margin-bottom: 24px;
        }

        .explore_btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #065fd4;
            color: #fff;
            border: none;
            border-radius: 2px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }

        .explore_btn:hover {
            background: #0b5ed7;
        }

        /* 반응형 */
        @media (max-width: 768px) {
            .review_item {
                flex-direction: column;
                gap: 12px;
            }

            .review_thumbnail {
                width: 100%;
                height: 200px;
            }

            .review_actions {
                flex-direction: row;
                justify-content: flex-end;
            }

            .header_stats {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }

            .controls_bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search_box {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .review_thumbnail {
                width: 100%;
                height: 180px;
            }

            .page_header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .rating_selector {
                gap: 6px;
            }

            .rating_chip {
                padding: 6px 12px;
                font-size: 13px;
            }

            .review_title_row {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
        }

        /* 다크모드 */
        body.dark-mode .page_header { border-bottom-color: #333; }
        body.dark-mode .header_left h2 { color: #fff; }
        body.dark-mode .stat_item { color: #aaa; }
        body.dark-mode .stat_item strong { color: #fff; }
        body.dark-mode .search_input,
        body.dark-mode .sort_dropdown { background: #2a2a2a; border-color: #444; color: #fff; }
        body.dark-mode .search_input:focus,
        body.dark-mode .sort_dropdown:focus { border-color: #065fd4; background: #333; }
        body.dark-mode .reviews_container { background: transparent; }
        body.dark-mode .review_item { border-bottom-color: #333; }
        body.dark-mode .review_item:hover { background: #1a1a1a; }
        body.dark-mode .review_title { color: #fff; }
        body.dark-mode .review_meta,
        body.dark-mode .review_date { color: #888; }
        body.dark-mode .review_content { background: #1a1a1a; color: #ddd; border-left-color: #065fd4; }
        body.dark-mode .menu_btn { color: #aaa; }
        body.dark-mode .menu_btn:hover { background: #333; }
        body.dark-mode .dropdown_menu { background: #2a2a2a; box-shadow: 0 2px 12px rgba(0,0,0,0.4); }
        body.dark-mode .dropdown_item { color: #ddd; }
        body.dark-mode .dropdown_item:hover { background: #333; }
        body.dark-mode .dropdown_item.delete_item:hover { background: #3d1a1a; color: #ff7b7b; }
        body.dark-mode .modal_box { background: #1a1a1a; }
        body.dark-mode .modal_header { border-bottom-color: #333; }
        body.dark-mode .modal_header h3 { color: #fff; }
        body.dark-mode .form_label { color: #fff; }
        body.dark-mode .rating_chip { background: #2a2a2a; border-color: #444; color: #aaa; }
        body.dark-mode .rating_chip:hover { background: #333; }
        body.dark-mode .content_textarea { background: #2a2a2a; border-color: #444; color: #fff; }
        body.dark-mode .content_textarea:focus { background: #333; }
        body.dark-mode .modal_footer { border-top-color: #333; }
        body.dark-mode .empty_title { color: #fff; }
        body.dark-mode .empty_text { color: #888; }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="mypage_container">
            <?php require_once("inc/mypage_menu.php"); ?>

            <div class="content_area">
                <div class="page_header">
                    <div class="header_left">
                        <h2>나의 리뷰</h2>
                        <div class="header_stats">
                            <div class="stat_item">
                                <strong><?php echo $total_reviews; ?></strong>개의 리뷰
                            </div>
                            <?php if ($total_reviews > 0): ?>
                            <div class="stat_item">
                                평균 평점 <strong><?php echo number_format($average_rating, 1); ?></strong>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($comments)): ?>
                    <div class="controls_bar">
                        <div class="search_box">
                            <input type="text" class="search_input" id="searchInput" placeholder="영화 제목 검색">
                            <i class="fas fa-search search_icon"></i>
                        </div>
                        <select class="sort_dropdown" onchange="location.href='?sort='+this.value">
                            <option value="recent" <?php echo $sort_order === 'recent' ? 'selected' : ''; ?>>최신순</option>
                            <option value="oldest" <?php echo $sort_order === 'oldest' ? 'selected' : ''; ?>>오래된순</option>
                            <option value="rating_high" <?php echo $sort_order === 'rating_high' ? 'selected' : ''; ?>>평점 높은순</option>
                            <option value="rating_low" <?php echo $sort_order === 'rating_low' ? 'selected' : ''; ?>>평점 낮은순</option>
                        </select>
                    </div>

                    <div class="reviews_container" id="reviewsList">
                        <?php foreach ($comments as $comment): ?>
                            <div class="review_item" data-title="<?php echo strtolower($comment['movie_title']); ?>">
                                <div class="review_thumbnail" onclick="location.href='movie_detail.php?id=<?php echo $comment['movie_id']; ?>'">
                                    <img src="<?php echo !empty($comment['poster_image']) ? $comment['poster_image'] : 'images/default_poster.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($comment['movie_title']); ?>">
                                </div>

                                <div class="review_details" onclick="location.href='movie_detail.php?id=<?php echo $comment['movie_id']; ?>'">
                                    <div class="review_title_row">
                                        <div class="review_title"><?php echo htmlspecialchars($comment['movie_title']); ?></div>
                                        <div class="rating_badge">
                                            <i class="fas fa-star"></i>
                                            <?php echo $comment['rating']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="review_meta">
                                        <?php if (!empty($comment['director'])): ?>
                                            <span><?php echo htmlspecialchars($comment['director']); ?></span>
                                            <span class="meta_separator">•</span>
                                        <?php endif; ?>
                                        <?php if (!empty($comment['genre'])): ?>
                                            <span><?php echo htmlspecialchars($comment['genre']); ?></span>
                                            <span class="meta_separator">•</span>
                                        <?php endif; ?>
                                        <?php if (!empty($comment['release_date'])): ?>
                                            <span><?php echo date('Y', strtotime($comment['release_date'])); ?></span>
                                            <span class="meta_separator">•</span>
                                        <?php endif; ?>
                                        <span class="review_date"><?php echo date('Y년 m월 d일', strtotime($comment['created_at'])); ?></span>
                                    </div>
                                    
                                    <div class="review_content"><?php echo htmlspecialchars($comment['content']); ?></div>
                                </div>

                                <div class="review_actions" onclick="event.stopPropagation()">
                                    <button class="menu_btn" onclick="toggleMenu(this)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown_menu">
                                        <button class="dropdown_item" onclick="openEditModal(<?php echo $comment['review_id']; ?>, <?php echo $comment['rating']; ?>, `<?php echo addslashes($comment['content']); ?>`)">
                                            <i class="fas fa-pen"></i> 수정
                                        </button>
                                        <button class="dropdown_item delete_item" onclick="deleteReview(<?php echo $comment['review_id']; ?>)">
                                            <i class="fas fa-trash-alt"></i> 삭제
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty_state">
                        <div class="empty_icon"><i class="far fa-comment-dots"></i></div>
                        <h3 class="empty_title">작성한 리뷰가 없습니다</h3>
                        <p class="empty_text">영화를 보고 첫 리뷰를 남겨보세요</p>
                        <a href="movies.php" class="explore_btn"><i class="fas fa-film"></i> 영화 둘러보기</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>

    <div class="modal_overlay" id="editModal">
        <div class="modal_box">
            <div class="modal_header">
                <h3>리뷰 수정</h3>
                <button class="close_btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="editForm" method="POST" action="comment_update.php">
                <input type="hidden" name="review_id" id="editReviewId">
                <input type="hidden" name="rating" id="editRating">
                
                <div class="modal_body">
                    <div class="form_group">
                        <label class="form_label">평점</label>
                        <div class="rating_selector">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="rating_chip" data-rating="<?php echo $i; ?>" onclick="selectRating(<?php echo $i; ?>)"><?php echo $i; ?>점</div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form_group">
                        <label class="form_label">리뷰 내용</label>
                        <textarea name="content" id="editContent" class="content_textarea" placeholder="영화에 대한 솔직한 리뷰를 작성해주세요" required></textarea>
                    </div>
                </div>
                
                <div class="modal_footer">
                    <button type="button" class="modal_btn cancel_btn" onclick="closeModal()">취소</button>
                    <button type="submit" class="modal_btn save_btn">저장</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 햄버거 메뉴
        function toggleMenu(btn) {
            document.querySelectorAll('.dropdown_menu.active').forEach(menu => {
                if (menu !== btn.nextElementSibling) menu.classList.remove('active');
            });
            btn.nextElementSibling.classList.toggle('active');
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.review_actions')) {
                document.querySelectorAll('.dropdown_menu.active').forEach(menu => menu.classList.remove('active'));
            }
        });

        // 검색 기능 (애니메이션 없이 바로 숨김/표시)
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const reviews = document.querySelectorAll('.review_item');
            
            reviews.forEach(review => {
                const title = review.dataset.title;
                if (title.includes(searchTerm)) {
                    review.style.display = 'flex';
                } else {
                    review.style.display = 'none';
                }
            });
        });

        // 모달
        function openEditModal(reviewId, rating, content) {
            document.getElementById('editReviewId').value = reviewId;
            document.getElementById('editRating').value = rating;
            document.getElementById('editContent').value = content;
            selectRating(rating);
            document.getElementById('editModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function selectRating(rating) {
            document.getElementById('editRating').value = rating;
            document.querySelectorAll('.rating_chip').forEach(chip => {
                chip.classList.toggle('selected', parseInt(chip.dataset.rating) === rating);
            });
        }

        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
        document.getElementById('editModal')?.addEventListener('click', function(e) { if (e.target === this) closeModal(); });

        // 삭제
        function deleteReview(reviewId) {
            if (confirm('이 리뷰를 삭제하시겠습니까?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'comment_delete.php';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'review_id';
                input.value = reviewId;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>

</html>