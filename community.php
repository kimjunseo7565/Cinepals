<?php
require_once("inc/session.php");

// 시간 표시 함수 - 단순화
function get_time_ago($datetime)
{
    $current_time = time();
    $post_time = strtotime($datetime);
    $time_diff = $current_time - $post_time;

    if ($time_diff < 60) {
        return '방금 전';
    }

    if ($time_diff < 3600) {
        $minutes = $time_diff / 60;
        $minutes = floor($minutes);
        return $minutes . '분 전';
    }

    if ($time_diff < 86400) {
        $hours = $time_diff / 3600;
        $hours = floor($hours);
        return $hours . '시간 전';
    }

    // 하루 이상이면 날짜 표시
    return date('Y-m-d', strtotime($datetime));
}

// DB 연결
$con = mysqli_connect("localhost", "root", "", "moviedb");

// 정렬 조건 처리 - 단순 if문으로
$sort = 'newest'; // 기본값
if (isset($_GET['sort'])) {
    $sort = $_GET['sort'];
}

// 검색 조건 처리 - 쉬운 방법 (수정된 부분)
$search_type = '';
$search_keyword = '';
// 기본적으로 관리자에 의해 삭제 처리된 게시글은 목록에서 제외
$where_condition = "WHERE b_title NOT LIKE '[관리자에 의해 삭제된 게시글]'";

// 빈 검색일 때는 위 where_condition만 유지, 검색 시 아래에서 AND로 연결

if (isset($_GET['search_type']) && isset($_GET['search_keyword'])) {
    $search_type = $_GET['search_type'];
    $search_keyword = $_GET['search_keyword'];

    // 검색어가 있고 "전체"가 아닌 경우에만 검색 조건 적용
    if (!empty($search_keyword) && $search_type != 'all') {
        $search_sql = '';
        if ($search_type == '글제목') {
            $search_sql = "b_title LIKE '%$search_keyword%'";
        }
        if ($search_type == '작성자') {
            $search_sql = "nick_name LIKE '%$search_keyword%'";
        }
        if ($search_type == '내용') {
            $search_sql = "b_contents LIKE '%$search_keyword%'";
        }

        if (!empty($search_sql)) {
            // 기존의 기본 조건에 AND로 연결
            $where_condition .= " AND (" . $search_sql . ")";
        }
    }
    // "전체"를 선택했거나 검색어가 없으면 기본 where_condition 유지
}

// 정렬 쿼리 설정 - 단순 if문
$order_by = "ORDER BY regdate DESC"; // 기본값

if ($sort == 'popular') {
    // 댓글 수로 정렬 (쉬운 방법)
    $order_by = "ORDER BY (SELECT COUNT(*) FROM board_comments WHERE board_comments.b_idx = board_posts.b_idx) DESC";
}
if ($sort == 'oldest') {
    $order_by = "ORDER BY regdate ASC";
}

// 게시글 가져오기
$sql = "SELECT * FROM board_posts {$where_condition} {$order_by}";
$result = mysqli_query($con, $sql);

$posts = array();
while ($row = mysqli_fetch_assoc($result)) {
    $posts[] = $row;
}

// 각 게시글의 댓글 수와 작성자 프로필 이미지 가져오기 - 기본 반복문으로
$comments_count = array();
$post_authors = array();

foreach ($posts as $post) {
    // 댓글 수 가져오기
    $count_sql = "SELECT COUNT(*) as count FROM board_comments WHERE b_idx = " . $post['b_idx'];
    $count_result = mysqli_query($con, $count_sql);
    $count_row = mysqli_fetch_assoc($count_result);
    $comments_count[$post['b_idx']] = $count_row['count'];

    // 각 게시글 작성자의 프로필 이미지 가져오기
    $author_sql = "SELECT profile_image FROM members WHERE member_id = " . $post['member_id'];
    $author_result = mysqli_query($con, $author_sql);
    if (mysqli_num_rows($author_result) > 0) {
        $author_row = mysqli_fetch_assoc($author_result);
        $post_authors[$post['b_idx']] = $author_row['profile_image'];
    } else {
        $post_authors[$post['b_idx']] = '';
    }
}

mysqli_close($con);
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>커뮤니티 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <!-- 폰트어썸 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* 전체 레이아웃 - 다크모드 */
        body {
            background-color: #0A0A0A;
            color: #fff;
        }

        .main_wrapper {
            background-color: #0A0A0A;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            border: 1px solid #0A0A0A !important;
        }

        .content_wrap {
            width: 100%;
            padding: 20px;
        }

        /* 검색 영역 */
        .search_area {
            display: flex;
            justify-content: flex-end;
            margin: 20px 0;
            align-items: center;
        }

        .search_select {
            border: 1px solid #333;
            padding: 8px 12px;
            border-radius: 4px 0 0 4px;
            background: #1A1A1A;
            color: #fff;
            min-width: 100px;
            outline: none;
        }

        .search_input_wrap {
            display: flex;
            border: 1px solid #333;
            border-left: none;
            border-radius: 0 4px 4px 0;
            overflow: hidden;
            width: 250px;
            background: #1A1A1A;
        }

        .search_input {
            flex: 1;
            border: none;
            padding: 8px 12px;
            outline: none;
            background: #1A1A1A;
            color: #fff;
        }

        .search_input::placeholder {
            color: #666;
        }

        .search_btn {
            background: #2c5282;
            color: white;
            border: none;
            padding: 0 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .search_btn:hover {
            background: #3a6fa8;
        }

        .search_btn i {
            font-size: 14px;
        }

        .write_post_btn {
            background: #2c5282;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            margin-left: 10px;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }

        .write_post_btn:hover {
            background: #3a6fa8;
        }

        /* 정렬 링크 */
        .sort-links {
            display: flex;
            justify-content: flex-end;
            margin: 20px 0;
        }

        .sort-links a {
            margin-left: 15px;
            color: #aaa;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            transition: color 0.2s, background 0.2s;
        }

        .sort-links a:hover {
            color: #fff;
        }

        .sort-links a.active {
            color: #fff;
            font-weight: bold;
            background: #2c5282;
        }

        /* 게시글 카드 */
        .post_list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
        }

        .post_card {
            background: #1A1A1A;
            border: 1px solid #1A1A1A !important;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin-bottom: 25px;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .post_card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
        }

        /* 게시글 상단 정보 */
        .post_info {
            padding: 15px 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .post_author {
            font-weight: bold;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .user_icon {
            color: #2c5282;
            font-size: 14px;
        }

        .post_date,
        .post_comment {
            color: #888;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .post_comment i {
            color: #666;
            font-size: 12px;
        }

        /* 게시글 제목 */
        .post_title {
            font-size: 20px;
            font-weight: bold;
            padding: 15px 15px 10px;
            color: #fff;
        }

        /* 게시글 내용 */
        .post_content {
            padding: 0 15px 15px;
            color: #ccc;
            line-height: 1.0;
        }

        /* 게시글 하단 버튼 */
        .post_footer {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            border-top: 1px solid #222;
            background: #1a1d24;
        }

        .more_btn {
            color: #888;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s;
        }

        .more_btn:hover {
            color: #fff;
        }

        .more_btn i {
            font-size: 12px;
        }

        /* 댓글 영역 */
        .comment_area {
            background: #222;
            padding: 15px;
            padding-top: 20px !important;
            padding-bottom: 20px !important;
            border-top: 1px solid #333;
            display: none;
        }

        .comment_area.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .comment_item {
            background: #2A2A2A;
            border: 1px solid #1A1A1A !important;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: left;
        }

        .comment_header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            text-align: left;
        }

        .comment_author {
            font-weight: bold;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 5px;
            text-align: left;
        }

        .comment_date {
            color: #888;
            font-size: 13px;
            text-align: right;
        }

        .comment_text {
            color: #ccc;
            line-height: 1.0;
            word-break: break-word;
        }

        /* 댓글 입력 - 완전히 새로 작성 */
        .comment_form {
            display: flex !important;
            margin-top: 20px !important;
            align-items: center !important;
            border-top: 1px solid #333 !important;
            padding-top: 15px !important;
        }

        .comment_input {
            flex: 1 !important;
            border: 1px solid #333 !important;
            border-radius: 4px !important;
            padding: 6px 10px !important;
            font-size: 14px !important;
            background: #2A2A2A !important;
            color: #fff !important;
            resize: none !important;
            font-family: inherit !important;
            height: 32px !important;
            min-height: 32px !important;
            max-height: 32px !important;
            line-height: 1.2 !important;
            overflow-y: auto !important;
        }

        .comment_submit {
            background: #2c5282 !important;
            color: white !important;
            border: none !important;
            border-radius: 4px !important;
            padding: 5px 10px !important;
            margin-left: 8px !important;
            cursor: pointer !important;
            height: 32px !important;
            font-size: 12px !important;
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
            transition: background 0.2s !important;
            flex-shrink: 0 !important;
        }

        .comment_submit:hover {
            background: #3a6fa8 !important;
        }

        /* 헤더 스타일 */
        .content_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #222;
        }

        .content_header h2 {
            font-size: 28px;
            color: #fff;
            font-weight: bold;
            position: relative;
        }

        .content_header h2:after {
            content: '';
            position: absolute;
            bottom: -16px;
            left: 0;
            width: 50px;
            height: 3px;
            background: #2c5282;
        }

        /* 게시물 상단 버튼 */
        .post_btns {
            padding: 15px 15px 0;
            display: flex;
            gap: 5px;
            justify-content: flex-end;
        }

        .btn_edit,
        .btn_delete {
            border: 1px solid #333;
            background: #2A2A2A;
            color: #ccc;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn_edit:hover,
        .btn_delete:hover {
            background: #333;
        }

        .btn_edit i,
        .btn_delete i {
            font-size: 11px;
        }

        /* 광고 슬라이더 스타일 */
        .ad_slider {
            position: relative;
            width: 100%;
            height: 150px;
            overflow: hidden;
            margin-bottom: 30px;
            border: 1px solid #0A0A0A !important;
        }

        .ad_slides {
            display: flex;
            transition: transform 0.5s ease;
            height: 100%;
        }

        .ad_slide {
            min-width: 100%;
            height: 100%;
        }

        .ad_slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ad_arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
        }

        .ad_prev {
            left: 10px;
        }

        .ad_next {
            right: 10px;
        }

        .ad_indicators {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
        }

        .ad_indicator {
            width: 10px;
            height: 10px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            cursor: pointer;
        }

        .ad_indicator.active {
            background: #fff;
        }

        /* 반응형 디자인 */
        @media (max-width: 768px) {
            .main_wrapper {
                max-width: 100%;
            }

            .post_info {
                flex-wrap: wrap;
            }

            .search_area {
                flex-wrap: wrap;
            }

            .search_input_wrap {
                width: 100%;
                margin-top: 10px;
            }

            .write_post_btn {
                margin-top: 10px;
                width: 100%;
                justify-content: center;
                margin-left: 0;
            }
        }

        /* 댓글 수정/삭제 버튼 스타일 */
        .comment_meta {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .comment_action_btn {
            border: 1px solid #ddd;
            background: #fff;
            color: #666;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .comment_action_btn:hover {
            background: #f5f5f5;
        }

        .comment_delete_btn:hover {
            background: #f5f5f5;
        }

        /* 댓글 수정 폼 스타일 */
        .comment_edit_form {
            margin-top: 10px;
        }

        .comment_edit_input {
            width: 100%;
            min-height: 60px;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 4px;
            background: #1A1A1A;
            color: #fff;
            font-size: 14px;
            resize: vertical;
            margin-bottom: 8px;
        }

        .comment_edit_input:focus {
            outline: none;
            border-color: #2c5282;
        }

        .comment_edit_buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .comment_edit_submit,
        .comment_edit_cancel {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .comment_edit_submit {
            background: #2c5282;
            color: white;
        }

        .comment_edit_submit:hover {
            background: #3a6fa8;
        }

        .comment_edit_cancel {
            background: #333;
            color: #aaa;
        }

        .comment_edit_cancel:hover {
            background: #444;
            color: #fff;
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="content_wrap">
            <div class="content_header">
                <h2>커뮤니티</h2>
            </div>

            <!-- 광고 슬라이더 -->
            <div class="ad_slider">
                <div class="ad_slides">
                    <div class="ad_slide">
                        <img src="images/community-banner.png" alt="커뮤니티 이용규칙">
                    </div>
                    <div class="ad_slide">
                        <img src="images/ad2.jpg" alt="광고 2">
                    </div>
                    <div class="ad_slide">
                        <img src="images/ad3.jpg" alt="광고 3">
                    </div>
                    <div class="ad_slide">
                        <img src="images/ad4.png" alt="광고 4">
                    </div>
                </div>

                <div class="ad_arrow ad_prev">
                    <i class="fas fa-chevron-left"></i>
                </div>
                <div class="ad_arrow ad_next">
                    <i class="fas fa-chevron-right"></i>
                </div>

                <div class="ad_indicators">
                    <div class="ad_indicator active" data-index="0"></div>
                    <div class="ad_indicator" data-index="1"></div>
                    <div class="ad_indicator" data-index="2"></div>
                    <div class="ad_indicator" data-index="3"></div>
                </div>
            </div>

            <!-- 검색 영역 - 수정된 부분 -->
            <div class="search_area">
                <select class="search_select" id="search_type">
                    <option value="all" <?php echo ($search_type == '' || $search_type == 'all') ? 'selected' : ''; ?>>전체</option>
                    <option value="글제목" <?php echo $search_type == '글제목' ? 'selected' : ''; ?>>글제목</option>
                    <option value="작성자" <?php echo $search_type == '작성자' ? 'selected' : ''; ?>>작성자</option>
                    <option value="내용" <?php echo $search_type == '내용' ? 'selected' : ''; ?>>내용</option>
                </select>
                <div class="search_input_wrap">
                    <input type="text" class="search_input" id="search_keyword" placeholder="검색어를 입력하세요" value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <button type="button" class="search_btn" onclick="doSearch()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <a href="board_write.php" class="write_post_btn">
                    <i class="fas fa-pencil-alt"></i>
                    기대평 작성
                </a>
            </div>

            <!-- 정렬 링크 -->
            <div class="sort-links">
                <?php
                // 활성 클래스 체크 - 단순 if문으로
                $newest_class = '';
                $oldest_class = '';
                $popular_class = '';

                if ($sort == 'newest') {
                    $newest_class = 'active';
                }
                if ($sort == 'oldest') {
                    $oldest_class = 'active';
                }
                if ($sort == 'popular') {
                    $popular_class = 'active';
                }

                // 검색 파라미터 유지
                $search_params = '';
                if (!empty($search_keyword)) {
                    $search_params = "&search_type=" . urlencode($search_type) . "&search_keyword=" . urlencode($search_keyword);
                }
                ?>
                <a href="?sort=newest<?php echo $search_params; ?>" class="<?php echo $newest_class; ?>">최신순</a>
                <a href="?sort=oldest<?php echo $search_params; ?>" class="<?php echo $oldest_class; ?>">오래된순</a>
                <a href="?sort=popular<?php echo $search_params; ?>" class="<?php echo $popular_class; ?>">인기순</a>
            </div>

            <!-- 게시글 목록 -->
            <div class="post_list">
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post_card" id="post-<?php echo $post['b_idx']; ?>">
                            <!-- 게시글 상단 버튼 -->
                            <div class="post_btns">
                                <?php
                                $can_edit = false;
                                if (isset($_SESSION['member_id'])) {
                                    if ($_SESSION['member_id'] == $post['member_id']) {
                                        $can_edit = true;
                                    }
                                }

                                if ($can_edit):
                                ?>
                                    <a href="board_write.php?edit=<?php echo $post['b_idx']; ?>" class="btn_edit">
                                        <i class="fas fa-edit"></i> 수정
                                    </a>
                                    <a href="javascript:void(0)" onclick="deletePost(<?php echo $post['b_idx']; ?>)" class="btn_delete">
                                        <i class="fas fa-trash-alt"></i> 삭제
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- 게시글 작성자 정보 -->
                            <div class="post_info">
                                <span class="post_author">
                                    <?php
                                    $has_profile_image = false;
                                    if (!empty($post_authors[$post['b_idx']])) {
                                        $has_profile_image = true;
                                    }

                                    if ($has_profile_image):
                                    ?>
                                        <img src="<?php echo htmlspecialchars($post_authors[$post['b_idx']]); ?>" alt="프로필" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover; margin-right: 5px;">
                                    <?php else: ?>
                                        <i class="fas fa-user user_icon"></i>
                                    <?php endif; ?>
                                    <?php
                                    if ($post['is_anonymous']) {
                                        echo '탈퇴한 회원';
                                    } else {
                                        echo $post['nick_name'];
                                    }
                                    ?>
                                </span>
                                <span class="post_date"><?php echo get_time_ago($post['regdate']); ?></span>
                                <span class="post_comment">
                                    <i class="fas fa-comment"></i> <?php echo $comments_count[$post['b_idx']]; ?>
                                </span>
                            </div>

                            <!-- 게시글 제목 -->
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <h3 class="post_title" style="margin: 0;"><?php echo $post['b_title']; ?></h3>
                            </div>

                            <!-- 게시글 내용 -->
                            <div class="post_content" style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <span><?php echo nl2br($post['b_contents']); ?></span>
                                <?php if(!empty($post['update_date'])): ?><span style="color: #ff7675; font-size: 12px; white-space: nowrap; margin-left: 8px; margin-right: 15px;">*수정됨</span><?php endif; ?>
                            </div>

                            <!-- 게시글 하단 더보기 버튼 -->
                            <div class="post_footer">
                                <a href="javascript:void(0)" class="more_btn toggle-comments" data-post-id="<?php echo $post['b_idx']; ?>">
                                    댓글 보기 <i class="fas fa-chevron-down"></i>
                                </a>
                                <a href="javascript:void(0)" class="more_btn" onclick="reportPost(<?php echo $post['b_idx']; ?>)" style="color: #dc3545;">
                                    신고하기 <i class="fas fa-exclamation-triangle"></i>
                                </a>
                            </div>

                            <!-- 댓글 영역 -->
                            <div id="comments-<?php echo $post['b_idx']; ?>" class="comment_area">
                                <?php
                                // 해당 게시글의 댓글들 가져오기
                                $comment_sql = "SELECT bc.*, m.profile_image 
                                                FROM board_comments bc 
                                                LEFT JOIN members m ON bc.member_id = m.member_id 
                                                WHERE bc.b_idx = " . $post['b_idx'] . " 
                                                ORDER BY bc.regdate ASC";
                                $comment_result = mysqli_query($con, $comment_sql);

                                if (mysqli_num_rows($comment_result) > 0):
                                    while ($comment = mysqli_fetch_assoc($comment_result)):
                                ?>
                                        <div class="comment_item" id="comment-<?php echo $comment['comment_id']; ?>">
                                            <div class="comment_header">
                                                <span class="comment_author">
                                                    <?php
                                                    if (!empty($comment['profile_image'])):
                                                    ?>
                                                        <img src="<?php echo htmlspecialchars($comment['profile_image']); ?>" alt="프로필" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; margin-right: 6px;">
                                                    <?php else: ?>
                                                        <i class="fas fa-user user_icon"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($comment['nickname']); ?>
                                                </span>
                                                <div class="comment_meta">
                                                    <span class="comment_date"><?php echo date('Y.m.d', strtotime($comment['regdate'])); ?></span>
                                                    <?php if (isset($_SESSION['member_id']) && $_SESSION['member_id'] == $comment['member_id']): ?>
                                                        <button class="comment_action_btn" onclick="editComment(<?php echo $comment['comment_id']; ?>)">
                                                            <i class="fas fa-edit"></i> 수정
                                                        </button>
                                                        <button class="comment_action_btn comment_delete_btn" onclick="deleteComment(<?php echo $comment['comment_id']; ?>)">
                                                            <i class="fas fa-trash"></i> 삭제
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="comment_text" id="text-<?php echo $comment['comment_id']; ?>" style="display: flex; justify-content: space-between; align-items: flex-start;">
                                                <span><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></span>
                                                <?php if(!empty($comment['update_date'])): ?><span style="color: #ff7675; font-size: 11px; white-space: nowrap; margin-left: 8px; margin-right: 12px;">*수정됨</span><?php endif; ?>
                                            </div>
                                            <!-- 수정 폼 (숨김) -->
                                            <form class="comment_edit_form" id="edit-form-<?php echo $comment['comment_id']; ?>" style="display:none;" action="board_comment_edit.php" method="post">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                                <textarea name="comment" class="comment_edit_input" required><?php echo htmlspecialchars($comment['comment']); ?></textarea>
                                                <div class="comment_edit_buttons">
                                                    <button type="submit" class="comment_edit_submit">
                                                        <i class="fas fa-check"></i> 완료
                                                    </button>
                                                    <button type="button" class="comment_edit_cancel" onclick="cancelEdit(<?php echo $comment['comment_id']; ?>)">
                                                        <i class="fas fa-times"></i> 취소
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php
                                    endwhile;
                                else:
                                    ?>
                                    <div style="text-align: center; padding: 10px; color: #888;">
                                        첫 번째 댓글을 작성해보세요!
                                    </div>
                                <?php endif; ?>

                                <!-- 댓글 입력 폼 -->
                                <?php if (isset($_SESSION['member_id'])): ?>
                                    <form class="comment_form" action="board_comment.php" method="post">
                                        <input type="hidden" name="b_idx" value="<?php echo $post['b_idx']; ?>">
                                        <textarea name="comment" class="comment_input" placeholder="댓글을 입력하세요..." required rows="1"></textarea>
                                        <button type="submit" class="comment_submit">
                                            <i class="fas fa-pencil-alt"></i>
                                            등록
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 10px; color: #888;">
                                        <a href="login.php" style="color: #2c5282;">로그인</a>하여 댓글을 작성하세요.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: #1A1A1A; border-radius: 8px; color: #888;">
                        <i class="fas fa-exclamation-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
                        <p>게시글이 없습니다.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>

    <script>
        // 게시글 삭제 함수 - 쉬운 방법
        function deletePost(b_idx) {
            if (confirm('정말 삭제하시겠습니까?')) {
                location.href = 'board_delete.php?b_idx=' + b_idx;
            }
        }

        // 댓글 토글 함수 - 쉬운 방법
        function toggleComments(postId) {
            var commentArea = document.getElementById('comments-' + postId);
            var button = document.querySelector('[data-post-id="' + postId + '"]');

            if (commentArea.style.display === 'none' || commentArea.style.display === '') {
                commentArea.style.display = 'block';
                button.innerHTML = '댓글 접기 <i class="fas fa-chevron-up"></i>';
            } else {
                commentArea.style.display = 'none';
                button.innerHTML = '댓글 보기 <i class="fas fa-chevron-down"></i>';
            }
        }

        // 신고 기능 - 단순한 form submit
        function reportPost(postId) {
            if (!confirm('이 게시글을 신고하시겠습니까?')) {
                return;
            }

            var reason = prompt('신고 사유를 입력해주세요:');
            if (!reason || reason.trim() === '') {
                alert('신고 사유를 입력해주세요.');
                return;
            }

            // 간단한 form submit 방식
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'report_post.php';

            var postInput = document.createElement('input');
            postInput.type = 'hidden';
            postInput.name = 'post_id';
            postInput.value = postId;
            form.appendChild(postInput);

            var reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'reason';
            reasonInput.value = reason.trim();
            form.appendChild(reasonInput);

            document.body.appendChild(form);
            form.submit();
        }

        // 검색 기능 - 수정된 함수 (쉬운 방법)
        function doSearch() {
            var searchType = document.getElementById('search_type').value;
            var searchKeyword = document.getElementById('search_keyword').value;
            
            var currentSort = '<?php echo isset($sort) ? $sort : "newest"; ?>';
            
            // "전체"를 선택했거나 검색어가 비어있으면 기본 페이지로
            if (searchType === 'all' || searchKeyword.trim() === '') {
                location.href = '?sort=' + currentSort;
                return;
            }
            
            // 일반 검색
            location.href = '?sort=' + currentSort + '&search_type=' + encodeURIComponent(searchType) + '&search_keyword=' + encodeURIComponent(searchKeyword);
        }

        // 광고 슬라이더 기능 - 쉬운 방법
        var adCurrentIndex = 0;
        var adTotalSlides = 4; // 광고 슬라이드 개수

        // 댓글 수정 함수
        function editComment(commentId) {
            // 텍스트 영역 숨기기
            document.getElementById('text-' + commentId).style.display = 'none';
            // 수정 폼 표시
            document.getElementById('edit-form-' + commentId).style.display = 'block';
        }

        // 댓글 수정 취소 함수
        function cancelEdit(commentId) {
            // 수정 폼 숨기기
            document.getElementById('edit-form-' + commentId).style.display = 'none';
            // 텍스트 영역 표시
            document.getElementById('text-' + commentId).style.display = 'block';
        }

        // 댓글 삭제 함수
        function deleteComment(commentId) {
            if (confirm('정말 이 댓글을 삭제하시겠습니까?')) {
                location.href = 'board_comment_delete.php?comment_id=' + commentId;
            }
        }

        function moveToSlide(index) {
            if (index < 0) index = adTotalSlides - 1;
            if (index >= adTotalSlides) index = 0;

            adCurrentIndex = index;

            var adSlides = document.querySelector('.ad_slides');
            if (adSlides) {
                adSlides.style.transform = 'translateX(-' + (adCurrentIndex * 100) + '%)';
            }

            // 인디케이터 업데이트
            var indicators = document.querySelectorAll('.ad_indicator');
            for (var i = 0; i < indicators.length; i++) {
                if (i === adCurrentIndex) {
                    indicators[i].className = 'ad_indicator active';
                } else {
                    indicators[i].className = 'ad_indicator';
                }
            }
        }

        // 페이지 로드시 초기화 - 쉬운 방법
        window.onload = function() {
            // 댓글 토글 버튼들에 이벤트 연결
            var toggleButtons = document.querySelectorAll('.toggle-comments');
            for (var i = 0; i < toggleButtons.length; i++) {
                var button = toggleButtons[i];
                button.onclick = function() {
                    toggleComments(this.getAttribute('data-post-id'));
                };
            }

            // 광고 슬라이더 버튼 이벤트
            var prevBtn = document.querySelector('.ad_prev');
            var nextBtn = document.querySelector('.ad_next');

            if (prevBtn) {
                prevBtn.onclick = function() {
                    moveToSlide(adCurrentIndex - 1);
                };
            }

            if (nextBtn) {
                nextBtn.onclick = function() {
                    moveToSlide(adCurrentIndex + 1);
                };
            }

            // 인디케이터 클릭 이벤트
            var indicators = document.querySelectorAll('.ad_indicator');
            for (var i = 0; i < indicators.length; i++) {
                indicators[i].onclick = function() {
                    var slideIndex = parseInt(this.getAttribute('data-index'));
                    moveToSlide(slideIndex);
                };
            }

            // 자동 슬라이드 (5초마다)
            setInterval(function() {
                moveToSlide(adCurrentIndex + 1);
            }, 5000);

            // URL 파라미터로 댓글 영역 열기
            const urlParams = new URLSearchParams(window.location.search);
            const openCommentsId = urlParams.get('open_comments');
            if (openCommentsId) {
                const commentArea = document.getElementById('comments-' + openCommentsId);
                const button = document.querySelector('[data-post-id="' + openCommentsId + '"]');
                if (commentArea && button) {
                    commentArea.style.display = 'block';
                    button.innerHTML = '댓글 접기 <i class="fas fa-chevron-up"></i>';
                    // 스크롤하여 해당 게시글 위치로 이동 (부드럽게, 중앙에 배치)
                    const postEl = document.getElementById('post-' + openCommentsId);
                    if (postEl) {
                        // 약간의 지연을 줘서 레이아웃이 안정된 후 스크롤
                        setTimeout(function() {
                            try {
                                postEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                // 강조 효과를 잠시 추가
                                const prevTransition = postEl.style.transition || '';
                                const prevBox = postEl.style.boxShadow || '';
                                postEl.style.transition = 'box-shadow 0.4s ease';
                                postEl.style.boxShadow = '0 8px 30px rgba(44,82,130,0.25)';
                                setTimeout(function() {
                                    postEl.style.boxShadow = prevBox;
                                    postEl.style.transition = prevTransition;
                                }, 1400);
                            } catch (e) {
                                // 아무 동작 안함
                            }
                        }, 120);
                    }
                }
            }
        };
    </script>
</body>

</html>