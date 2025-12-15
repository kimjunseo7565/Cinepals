<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 현재 로그인한 회원의 좋아요/저장한 영화 가져오기
$member_id = $_SESSION['member_id'];

// 회원 닉네임 가져오기
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

$has_user_movies = !empty($liked_movies) || !empty($saved_movies);
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
        .movie_section {
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .movie_section h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }

        .movie_grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }

        .movie_card {
            display: flex;
            background: #f9fafb;
            border-radius: 4px;
            overflow: hidden;
            height: 120px;
            border: 1px solid #e5e7eb;
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
            color: #333;
            margin-bottom: 5px;
        }

        .movie_title a {
            color: #333;
            text-decoration: none;
        }

        .movie_title a:hover {
            color: #667eea;
        }

        .movie_director {
            font-size: 12px;
            color: #666;
        }

        .remove_btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: transparent;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px;
        }

        .remove_btn:hover {
            color: #333;
        }

        .empty_message {
            color: #666;
            text-align: center;
            padding: 20px 0;
        }

        /* AI 채팅 섹션 */
        .ai_chat_section {
            margin-bottom: 30px;
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            border-radius: 12px;
            border: 1px solid #e0e5f2;
            box-shadow: 0 2px 12px rgba(102, 126, 234, 0.08);
            overflow: hidden;
        }

        .ai_chat_header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px;
            border-bottom: 1px solid #e0e5f2;
        }

        .ai_chat_icon {
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

        .ai_chat_title h2 {
            font-size: 18px;
            color: #333;
            margin: 0;
        }

        .ai_chat_title p {
            font-size: 13px;
            color: #666;
            margin: 4px 0 0 0;
        }

        .ai_chat_messages {
            height: 350px;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: #fff;
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
            background: #e5e7eb;
            color: #666;
        }

        .chat_bubble {
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.5;
        }

        .chat_message.ai .chat_bubble {
            background: #f3f4f6;
            color: #333;
            border-bottom-left-radius: 4px;
        }

        .chat_message.user .chat_bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .chat_welcome {
            text-align: center;
            color: #666;
            font-size: 14px;
            padding: 40px 20px;
        }

        .chat_welcome i {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 15px;
            display: block;
        }

        .chat_welcome p {
            margin: 5px 0;
        }

        .chat_suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            margin-top: 15px;
        }

        .chat_suggestion {
            background: #fff;
            border: 1px solid #e0e5f2;
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
            border-color: #667eea;
        }

        .ai_chat_input_area {
            display: flex;
            gap: 10px;
            padding: 15px 20px;
            border-top: 1px solid #e0e5f2;
            background: #f8f9ff;
        }

        .ai_chat_input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e0e5f2;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .ai_chat_input:focus {
            border-color: #667eea;
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
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .ai_chat_send:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .ai_chat_send:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* 타이핑 인디케이터 */
        .typing_indicator {
            display: flex;
            gap: 4px;
            padding: 12px 16px;
            background: #f3f4f6;
            border-radius: 18px;
            border-bottom-left-radius: 4px;
            width: fit-content;
        }

        .typing_indicator span {
            width: 8px;
            height: 8px;
            background: #999;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing_indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing_indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

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

                <!-- AI 채팅 섹션 -->
                <section class="ai_chat_section">
                    <div class="ai_chat_header">
                        <div class="ai_chat_icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="ai_chat_title">
                            <h2>AI 영화 추천</h2>
                            <p>시네팔에게 오늘 볼 영화를 물어보세요!</p>
                        </div>
                    </div>

                    <div class="ai_chat_messages" id="chatMessages">
                        <div class="chat_welcome">
                            <i class="fas fa-comments"></i>
                            <p><strong><?php echo htmlspecialchars($member_nickname); ?></strong>님, 안녕하세요! 👋</p>
                            <p>오늘은 어떤 영화 정보를 찾으시나요?</p>
                            <div class="chat_suggestions">
                                <button class="chat_suggestion" onclick="sendSuggestion('요즘 인기있는 힐링 영화 추천해주세요')">😢 힐링 영화</button>
                                <button class="chat_suggestion" onclick="sendSuggestion('현재 인기있는 코미디 영화 뭐예요?')">😆 코미디</button>
                                <button class="chat_suggestion" onclick="sendSuggestion('요즘 핫한 스릴러 영화 추천해주세요')">😱 스릴러</button>
                                <button class="chat_suggestion" onclick="sendSuggestion('지금 극장에서 인기있는 영화 뭐예요?')">🔥 현재 상영작</button>
                            </div>
                        </div>
                    </div>

                    <div class="ai_chat_input_area">
                        <input type="text" class="ai_chat_input" id="chatInput" placeholder="메시지를 입력하세요..." onkeypress="handleKeyPress(event)">
                        <button class="ai_chat_send" id="chatSendBtn" onclick="sendMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </section>

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
                </section>

            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>

    <script>
        let chatHistory = [];
        let isWelcomeVisible = true;

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
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
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: message,
                    history: chatHistory
                })
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
            const messagesContainer = document.getElementById('chatMessages');
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat_message ${type}`;
            
            const avatar = document.createElement('div');
            avatar.className = 'chat_avatar';
            avatar.innerHTML = type === 'ai' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
            
            const bubble = document.createElement('div');
            bubble.className = 'chat_bubble';
            bubble.textContent = text;
            
            messageDiv.appendChild(avatar);
            messageDiv.appendChild(bubble);
            messagesContainer.appendChild(messageDiv);
            
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function showTypingIndicator() {
            const messagesContainer = document.getElementById('chatMessages');
            
            const typingDiv = document.createElement('div');
            typingDiv.className = 'chat_message ai';
            typingDiv.id = 'typingIndicator';
            
            const avatar = document.createElement('div');
            avatar.className = 'chat_avatar';
            avatar.innerHTML = '<i class="fas fa-robot"></i>';
            
            const indicator = document.createElement('div');
            indicator.className = 'typing_indicator';
            indicator.innerHTML = '<span></span><span></span><span></span>';
            
            typingDiv.appendChild(avatar);
            typingDiv.appendChild(indicator);
            messagesContainer.appendChild(typingDiv);
            
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function hideTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) {
                indicator.remove();
            }
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        function removeMovie(movieId, actionType) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'user_action.php';
            
            var movieInput = document.createElement('input');
            movieInput.type = 'hidden';
            movieInput.name = 'movie_id';
            movieInput.value = movieId;
            form.appendChild(movieInput);
            
            var actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action_type';
            actionInput.value = actionType;
            form.appendChild(actionInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>

</html>