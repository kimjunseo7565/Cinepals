<?php
require_once("inc/session.php");
require_once("inc/db.php");

$member_id = $_SESSION['member_id'];

$member_info = db_select("SELECT nickname FROM members WHERE member_id = ?", [$member_id])[0];
$member_nickname = $member_info['nickname'];

// 좋아요한 영화 목록
$liked_movies = db_select(
    "SELECT m.* FROM moviesdb m
     WHERE m.movie_id IN (
         SELECT movie_id FROM user_actions_new 
         WHERE member_id = ? AND action_type = 'like'
     )
     ORDER BY m.release_date DESC",
    [$member_id]
);

// 저장한 영화 목록
$saved_movies = db_select(
    "SELECT m.* FROM moviesdb m
     WHERE m.movie_id IN (
         SELECT movie_id FROM user_actions_new 
         WHERE member_id = ? AND action_type = 'save'
     )
     ORDER BY m.release_date DESC",
    [$member_id]
);

$total_user_movies = count($liked_movies) + count($saved_movies);
$has_user_movies = $total_user_movies > 0;
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>나의 영화 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* 라이트모드 (기본) */
        :root {
            --bg-primary: #fff;
            --bg-secondary: #f9fafb;
            --bg-tertiary: #f3f4f6;
            --text-primary: #333;
            --text-secondary: #666;
            --text-muted: #999;
            --border-color: #e5e7eb;
            --border-light: #e0e5f2;
            --shadow: rgba(0, 0, 0, 0.08);
        }

        /* 다크모드 */
        body.dark-mode {
            --bg-primary: #1a1d24;
            --bg-secondary: #2a2d34;
            --bg-tertiary: #3a3d44;
            --text-primary: #fff;
            --text-secondary: #aaa;
            --text-muted: #666;
            --border-color: #3a3d44;
            --border-light: #3a3d44;
            --shadow: rgba(0, 0, 0, 0.3);
        }

        /* 전역 overflow 제한 */
        .content_area {
            overflow: hidden !important;
            max-width: 100% !important;
        }

        .movie_section {
            margin-bottom: 30px;
            padding: 20px;
            background: var(--bg-primary);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 8px var(--shadow);
            position: relative;
        }

        .movie_section h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--text-primary);
        }

        .movie_grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            max-height: 400px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .movie_grid.expanded {
            max-height: none;
        }

        .toggle_more_btn {
            display: block;
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .toggle_more_btn:hover {
            background: var(--bg-tertiary);
        }

        .toggle_more_btn i {
            margin-left: 5px;
            transition: transform 0.3s;
        }

        .toggle_more_btn.active i {
            transform: rotate(180deg);
        }

        .movie_card {
            display: flex;
            background: var(--bg-secondary);
            border-radius: 4px;
            overflow: hidden;
            height: 120px;
            border: 1px solid var(--border-color);
        }

        .movie_poster {
            width: 80px;
            flex-shrink: 0;
        }

        .movie_poster img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .movie_info {
            flex: 1;
            padding: 10px;
            position: relative;
        }

        .movie_title {
            font-size: 14px;
            font-weight: normal;
            color: var(--text-primary);
            margin-bottom: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-clamp: 2;
            overflow: hidden;
        }

        .movie_title a {
            color: var(--text-primary);
            text-decoration: none;
        }

        .movie_director {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .remove_btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 5px;
        }

        .remove_btn:hover {
            color: var(--text-primary);
        }

        .empty_message {
            color: var(--text-secondary);
            text-align: center;
            padding: 20px 0;
        }

        /* AI 섹션 */
        .ai_section {
            margin-bottom: 30px;
            background: var(--bg-primary);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
            box-shadow: 0 2px 12px var(--shadow);
        }

        .ai_section::before {
            content: '';
            display: block;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f64f59);
        }

        .ai_header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .ai_icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
        }

        .ai_title h2 {
            font-size: 18px;
            color: var(--text-primary);
            margin: 0;
        }

        .ai_title p {
            font-size: 13px;
            color: var(--text-secondary);
            margin: 4px 0 0 0;
        }

        /* 탭 스타일 */
        .ai_tabs {
            display: flex;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
        }

        .ai_tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            border: none;
            background: none;
            transition: all 0.2s;
            border-bottom: 3px solid transparent;
        }

        .ai_tab:hover {
            color: #667eea;
            background: var(--bg-tertiary);
        }

        .ai_tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: var(--bg-tertiary);
        }

        .ai_tab i {
            margin-right: 8px;
        }

        .ai_tab_content {
            display: none;
            overflow: hidden;
            max-width: 100%;
        }

        .ai_tab_content.active {
            display: block;
        }

        /* AI 추천 콘텐츠 */
        .ai_recommend_content {
            padding: 20px;
            background: var(--bg-primary);
            overflow: hidden;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
        }

        .ai_empty_state {
            text-align: center;
            padding: 40px 20px;
        }

        .ai_empty_state i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
            display: block;
        }

        .ai_empty_state p {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .ai_get_btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .ai_get_btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        /* AI 분석 박스 */
        .ai_analysis_box {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
            color: var(--text-primary);
            font-size: 13px;
            line-height: 1.5;
            word-break: keep-all;
            overflow-wrap: break-word;
        }

        .ai_analysis_box i {
            color: #667eea;
            margin-right: 8px;
        }

        /* AI 안내 문구 */
        .ai_info_box {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 15px;
            color: var(--text-secondary);
            font-size: 12px;
            text-align: center;
            line-height: 1.5;
        }

        .ai_info_box i {
            color: #667eea;
            margin-right: 5px;
        }

        /* 로딩 */
        .ai_loading {
            text-align: center;
            padding: 60px 20px;
        }

        .ai_loading_spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border-color);
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        .ai_loading p {
            color: var(--text-secondary);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* 캐러셀 스타일 */
        .carousel-container {
            position: relative;
            overflow: hidden;
            padding: 0 45px;
            box-sizing: border-box;
            max-width: 100%;
            width: 100%;
        }

        .carousel-track {
            display: flex;
            gap: 15px;
            transition: transform 0.4s ease;
            width: max-content;
        }

        .carousel-item {
            flex: 0 0 150px;
            min-width: 150px;
            max-width: 150px;
        }

        .recommendation_card {
            background: var(--bg-secondary);
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid var(--border-color);
        }

        .recommendation_card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px var(--shadow);
        }

        .recommendation_card a {
            text-decoration: none;
            color: inherit;
        }

        .recommendation_poster {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .recommendation_poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .recommendation_info {
            padding: 10px;
            position: relative;
        }

        .recommendation_title {
            font-size: 13px;
            color: var(--text-primary);
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .recommendation_reason {
            font-size: 11px;
            color: var(--text-secondary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-clamp: 2;
            overflow: hidden;
            line-height: 1.4;
            max-height: 2.8em;
            transition: max-height 0.3s ease;
        }

        .recommendation_reason.expanded {
            -webkit-line-clamp: unset;
            line-clamp: unset;
            max-height: none;
        }

        .reason_toggle {
            font-size: 10px;
            color: #667eea;
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px 0;
            margin-top: 3px;
            display: block;
            width: 100%;
            text-align: center;
        }

        .reason_toggle:hover {
            text-decoration: underline;
        }

        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            background: rgba(102, 126, 234, 0.9);
            border: none;
            border-radius: 50%;
            color: #fff;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .carousel-arrow:hover {
            background: rgba(118, 75, 162, 0.9);
        }

        .carousel-prev { left: 0; }
        .carousel-next { right: 0; }

        /* AI 채팅 스타일 */
        .ai_chat_messages {
            height: 350px;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: var(--bg-primary);
        }

        .chat_message {
            display: flex;
            gap: 10px;
            max-width: 85%;
        }

        .chat_message.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .chat_message.ai {
            align-self: flex-start;
        }

        .chat_avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .chat_message.ai .chat_avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .chat_message.user .chat_avatar {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
        }

        .chat_bubble {
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.5;
        }

        .chat_message.ai .chat_bubble {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border-bottom-left-radius: 4px;
        }

        .chat_message.user .chat_bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .chat_welcome {
            text-align: center;
            color: var(--text-secondary);
            font-size: 14px;
            padding: 40px 20px;
        }

        .chat_welcome i {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 15px;
            display: block;
        }

        .chat_suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            margin-top: 15px;
        }

        .chat_suggestion {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            color: #667eea;
            cursor: pointer;
            transition: all 0.2s;
        }

        .chat_suggestion:hover {
            background: #667eea;
            color: #fff;
        }

        .ai_chat_input_area {
            display: flex;
            gap: 10px;
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .ai_chat_input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        .ai_chat_input:focus {
            border-color: #667eea;
        }

        .ai_chat_input::placeholder {
            color: var(--text-muted);
        }

        .ai_chat_send {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        .ai_chat_send:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .typing_indicator {
            display: flex;
            gap: 4px;
            padding: 12px 16px;
            background: var(--bg-secondary);
            border-radius: 18px;
            border-bottom-left-radius: 4px;
            width: fit-content;
        }

        .typing_indicator span {
            width: 8px;
            height: 8px;
            background: var(--text-muted);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing_indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing_indicator span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.6; }
            30% { transform: translateY(-5px); opacity: 1; }
        }
    </style>
</head>

<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="mypage_container">
            <?php require_once("inc/mypage_menu.php"); ?>

            <div class="content_area">

                <!-- 좋아요한 영화 섹션 -->
                <section class="movie_section">
                    <h2>좋아요한 영화</h2>
                    <div class="movie_grid">
                        <?php if (!empty($liked_movies)): ?>
                            <?php foreach ($liked_movies as $movie): ?>
                                <div class="movie_card">
                                    <div class="movie_poster">
                                        <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>">
                                            <img src="<?php echo $movie['poster_image']; ?>" alt="영화 포스터">
                                        </a>
                                    </div>
                                    <div class="movie_info">
                                        <h3 class="movie_title">
                                            <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>"><?php echo $movie['title']; ?></a>
                                        </h3>
                                        <p class="movie_director"><?php echo $movie['director']; ?></p>
                                        <button class="remove_btn" onclick="removeMovie('<?php echo $movie['movie_id']; ?>', 'like')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty_message">좋아요한 영화가 없습니다.</p>
                        <?php endif; ?>
                    </div>
                    <?php if (count($liked_movies) > 6): ?>
                        <button class="toggle_more_btn" onclick="toggleMovieSection(this, 'liked')">
                            더보기 <i class="fas fa-chevron-down"></i>
                        </button>
                    <?php endif; ?>
                </section>

                <!-- 저장한 영화 섹션 -->
                <section class="movie_section">
                    <h2>저장한 영화</h2>
                    <div class="movie_grid">
                        <?php if (!empty($saved_movies)): ?>
                            <?php foreach ($saved_movies as $movie): ?>
                                <div class="movie_card">
                                    <div class="movie_poster">
                                        <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>">
                                            <img src="<?php echo $movie['poster_image']; ?>" alt="영화 포스터">
                                        </a>
                                    </div>
                                    <div class="movie_info">
                                        <h3 class="movie_title">
                                            <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>"><?php echo $movie['title']; ?></a>
                                        </h3>
                                        <p class="movie_director"><?php echo $movie['director']; ?></p>
                                        <button class="remove_btn" onclick="removeMovie('<?php echo $movie['movie_id']; ?>', 'save')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty_message">저장한 영화가 없습니다.</p>
                        <?php endif; ?>
                    </div>
                    <?php if (count($saved_movies) > 6): ?>
                        <button class="toggle_more_btn" onclick="toggleMovieSection(this, 'saved')">
                            더보기 <i class="fas fa-chevron-down"></i>
                        </button>
                    <?php endif; ?>
                </section>

                <!-- AI 섹션 -->
                <section class="ai_section">
                    <div class="ai_header">
                        <div class="ai_icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="ai_title">
                            <h2>AI 영화 추천</h2>
                            <p>인공지능이 <?php echo htmlspecialchars($member_nickname); ?>님의 취향을 분석합니다</p>
                        </div>
                    </div>

                    <!-- 탭 -->
                    <div class="ai_tabs">
                        <button class="ai_tab active" onclick="switchTab('recommend')">
                            <i class="fas fa-magic"></i>AI 추천
                        </button>
                        <button class="ai_tab" onclick="switchTab('chat')">
                            <i class="fas fa-comments"></i>AI 채팅
                        </button>
                    </div>

                    <!-- AI 추천 탭 -->
                    <div class="ai_tab_content active" id="tab_recommend">
                        <div class="ai_recommend_content" id="ai_content">
                            <?php if ($has_user_movies): ?>
                                <div class="ai_loading">
                                    <div class="ai_loading_spinner"></div>
                                    <p>AI가 <?php echo $total_user_movies; ?>개의 영화를 분석하고 있어요...</p>
                                </div>
                            <?php else: ?>
                                <div class="ai_empty_state">
                                    <i class="fas fa-film"></i>
                                    <p>먼저 마음에 드는 영화를 좋아요하거나 저장해주세요!<br>AI가 취향을 분석해서 추천해드릴게요.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- AI 채팅 탭 -->
                    <div class="ai_tab_content" id="tab_chat">
                        <div class="ai_chat_messages" id="chatMessages">
                            <div class="chat_welcome">
                                <i class="fas fa-comments"></i>
                                <p><strong><?php echo htmlspecialchars($member_nickname); ?></strong>님, 안녕하세요! 👋</p>
                                <p>오늘은 어떤 영화 정보를 찾으시나요?</p>
                                <div class="chat_suggestions">
                                    <button class="chat_suggestion" onclick="sendSuggestion('오늘 우울한데 힐링 영화 추천해줘')">😢 힐링 영화</button>
                                    <button class="chat_suggestion" onclick="sendSuggestion('친구랑 볼 재밌는 영화 뭐 있어?')">😆 코미디</button>
                                    <button class="chat_suggestion" onclick="sendSuggestion('긴장감 넘치는 스릴러 추천해줘')">😱 스릴러</button>
                                    <button class="chat_suggestion" onclick="sendSuggestion('요즘 극장에서 인기있는 영화 뭐야?')">🔥 현재 상영작</button>
                                </div>
                            </div>
                        </div>

                        <div class="ai_chat_input_area">
                            <input type="text" class="ai_chat_input" id="chatInput" placeholder="메시지를 입력하세요..." onkeypress="handleKeyPress(event)">
                            <button class="ai_chat_send" id="chatSendBtn" onclick="sendMessage()">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>

    <script>
        var currentIndex = 0;
        var autoSlideInterval;
        var chatHistory = [];
        var isWelcomeVisible = true;

        // 페이지 로드 시 AI 추천 (캐시 있으면 캐시 사용)
        window.addEventListener('load', function() {
            <?php if ($has_user_movies): ?>
            var cachedData = sessionStorage.getItem('ai_recommendation');
            if (cachedData) {
                displayAIRecommendation(JSON.parse(cachedData));
            } else {
                getAIRecommendation();
            }
            <?php endif; ?>
        });

        // 탭 전환
        function switchTab(tab) {
            document.querySelectorAll('.ai_tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.ai_tab_content').forEach(c => c.classList.remove('active'));
            
            event.target.closest('.ai_tab').classList.add('active');
            document.getElementById('tab_' + tab).classList.add('active');
        }

        // AI 추천 받기
        function getAIRecommendation() {
            var aiContent = document.getElementById('ai_content');
            
            aiContent.innerHTML = `
                <div class="ai_loading">
                    <div class="ai_loading_spinner"></div>
                    <p>AI가 취향을 분석하고 있어요...</p>
                </div>
            `;

            fetch('ai_recommend.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 캐시에 저장
                        sessionStorage.setItem('ai_recommendation', JSON.stringify(data));
                        displayAIRecommendation(data);
                    } else {
                        aiContent.innerHTML = `
                            <div class="ai_empty_state">
                                <i class="fas fa-exclamation-circle"></i>
                                <p>${data.message}</p>
                                <button class="ai_get_btn" onclick="getAIRecommendation()">
                                    <i class="fas fa-redo"></i> 다시 시도
                                </button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    aiContent.innerHTML = `
                        <div class="ai_empty_state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>오류가 발생했습니다. 잠시 후 다시 시도해주세요.</p>
                            <button class="ai_get_btn" onclick="getAIRecommendation()">
                                <i class="fas fa-redo"></i> 다시 시도
                            </button>
                        </div>
                    `;
                });
        }

        // AI 추천 결과 표시
        function displayAIRecommendation(data) {
            var aiContent = document.getElementById('ai_content');
            var totalMovies = data.recommendations.length;
            
            var moviesHTML = '';
            data.recommendations.forEach(function(movie, index) {
                moviesHTML += `
                    <div class="carousel-item">
                        <div class="recommendation_card">
                            <a href="movie_detail.php?id=${movie.movie_id}">
                                <div class="recommendation_poster">
                                    <img src="${movie.poster_image}" alt="${movie.title}">
                                </div>
                            </a>
                            <div class="recommendation_info">
                                <h3 class="recommendation_title">${movie.title}</h3>
                                <p class="recommendation_reason" id="reason-${index}">${movie.reason}</p>
                                <button class="reason_toggle" onclick="toggleReason(event, ${index})">더보기 ▼</button>
                            </div>
                        </div>
                    </div>
                `;
            });

            // 화살표는 항상 표시하고, JS에서 필요없으면 숨김
            var arrowsHTML = `
                <button class="carousel-arrow carousel-prev" onclick="moveCarousel('prev')">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="carousel-arrow carousel-next" onclick="moveCarousel('next')">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;

            aiContent.innerHTML = `
                <div class="ai_analysis_box">
                    <i class="fas fa-lightbulb"></i>${data.taste_analysis}
                </div>
                <div class="carousel-container">
                    <div class="carousel-track" id="ai-carousel-track">
                        ${moviesHTML}
                    </div>
                    ${arrowsHTML}
                </div>
                <div class="ai_info_box">
                    <i class="fas fa-info-circle"></i>새로운 영화를 좋아요/저장하면 취향에 맞는 다른 추천을 받을 수 있어요!
                </div>
            `;

            // 화살표 필요 여부 확인 후 숨김 + 호버 이벤트 등록
            setTimeout(function() {
                var container = document.querySelector('.carousel-container');
                var track = document.getElementById('ai-carousel-track');
                if (container && track) {
                    var containerWidth = container.offsetWidth - 80;
                    var visibleItems = Math.floor(containerWidth / 165);
                    
                    if (totalMovies <= visibleItems) {
                        var arrows = container.querySelectorAll('.carousel-arrow');
                        arrows.forEach(function(arrow) { arrow.style.display = 'none'; });
                    } else {
                        startAutoSlide();
                    }

                    // 마우스 호버 이벤트 (항상 등록)
                    container.onmouseenter = function() {
                        stopAutoSlide();
                    };
                    container.onmouseleave = function() {
                        if (totalMovies > visibleItems) {
                            startAutoSlide();
                        }
                    };
                }
            }, 100);
        }

        // 캐러셀 이동
        function moveCarousel(direction) {
            var track = document.getElementById('ai-carousel-track');
            if (!track) return;

            var container = track.parentElement;
            var containerWidth = container.offsetWidth - 80; // 패딩 제외
            var items = track.querySelectorAll('.carousel-item');
            var totalItems = items.length;
            var itemWidth = 165; // 150px + 15px gap
            var visibleItems = Math.floor(containerWidth / itemWidth);
            
            if (totalItems <= visibleItems) return;

            var maxIndex = totalItems - visibleItems;
            
            if (direction === 'next') {
                currentIndex++;
                if (currentIndex > maxIndex) currentIndex = 0;
            } else {
                currentIndex--;
                if (currentIndex < 0) currentIndex = maxIndex;
            }

            track.style.transform = 'translateX(' + (-(currentIndex * itemWidth)) + 'px)';
        }

        function startAutoSlide() {
            stopAutoSlide(); // 기존 interval 먼저 제거
            autoSlideInterval = setInterval(function() {
                moveCarousel('next');
            }, 3000);
        }

        function stopAutoSlide() {
            if (autoSlideInterval) {
                clearInterval(autoSlideInterval);
                autoSlideInterval = null;
            }
        }

        // AI 채팅
        function sendMessage() {
            var input = document.getElementById('chatInput');
            var message = input.value.trim();
            
            if (!message) return;
            
            if (isWelcomeVisible) {
                document.getElementById('chatMessages').innerHTML = '';
                isWelcomeVisible = false;
            }
            
            addMessage(message, 'user');
            input.value = '';
            
            document.getElementById('chatSendBtn').disabled = true;
            showTypingIndicator();
            
            fetch('ai_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message, history: chatHistory })
            })
            .then(response => response.json())
            .then(data => {
                hideTypingIndicator();
                document.getElementById('chatSendBtn').disabled = false;
                
                if (data.success) {
                    addMessage(data.message, 'ai');
                    chatHistory.push({ role: 'user', content: message });
                    chatHistory.push({ role: 'assistant', content: data.message });
                } else {
                    addMessage('미안, 잠시 문제가 생겼어! 다시 말해줄래? 😅', 'ai');
                }
            })
            .catch(error => {
                hideTypingIndicator();
                document.getElementById('chatSendBtn').disabled = false;
                addMessage('연결에 문제가 생겼어. 잠시 후 다시 시도해줘! 🙏', 'ai');
            });
        }

        function sendSuggestion(text) {
            document.getElementById('chatInput').value = text;
            sendMessage();
        }

        function addMessage(text, type) {
            var container = document.getElementById('chatMessages');
            var div = document.createElement('div');
            div.className = 'chat_message ' + type;
            div.innerHTML = `
                <div class="chat_avatar"><i class="fas fa-${type === 'ai' ? 'robot' : 'user'}"></i></div>
                <div class="chat_bubble">${text}</div>
            `;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        function showTypingIndicator() {
            var container = document.getElementById('chatMessages');
            var div = document.createElement('div');
            div.className = 'chat_message ai';
            div.id = 'typingIndicator';
            div.innerHTML = `
                <div class="chat_avatar"><i class="fas fa-robot"></i></div>
                <div class="typing_indicator"><span></span><span></span><span></span></div>
            `;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        function hideTypingIndicator() {
            var indicator = document.getElementById('typingIndicator');
            if (indicator) indicator.remove();
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') sendMessage();
        }

        // 영화 제거 (캐시도 삭제)
        function removeMovie(movieId, actionType) {
            // AI 추천 캐시 삭제 (다음 로드 시 새로 추천받음)
            sessionStorage.removeItem('ai_recommendation');
            
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'user_action.php';
            form.innerHTML = `
                <input type="hidden" name="movie_id" value="${movieId}">
                <input type="hidden" name="action_type" value="${actionType}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // 영화 섹션 더보기/접기
        function toggleMovieSection(btn, section) {
            var grid = btn.previousElementSibling;
            
            if (grid.classList.contains('expanded')) {
                // 접기
                grid.classList.remove('expanded');
                btn.innerHTML = '더보기 <i class="fas fa-chevron-down"></i>';
                btn.classList.remove('active');
            } else {
                // 펼치기
                grid.classList.add('expanded');
                btn.innerHTML = '접기 <i class="fas fa-chevron-up"></i>';
                btn.classList.add('active');
            }
        }

        // AI 추천 이유 더보기/접기
        function toggleReason(event, index) {
            event.preventDefault();
            event.stopPropagation();
            
            var reasonEl = document.getElementById('reason-' + index);
            var btn = event.target;
            
            if (reasonEl.classList.contains('expanded')) {
                // 접기
                reasonEl.classList.remove('expanded');
                btn.innerHTML = '더보기 ▼';
            } else {
                // 펼치기
                reasonEl.classList.add('expanded');
                btn.innerHTML = '접기 ▲';
            }
        }
    </script>
</body>

</html>