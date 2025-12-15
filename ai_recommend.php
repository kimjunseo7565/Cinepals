<?php
require_once("inc/session.php");
require_once("inc/db.php");
require_once("inc/gemini_api.php");

header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$member_id = $_SESSION['member_id'];

$member_info = db_select("SELECT nickname FROM members WHERE member_id = ?", [$member_id])[0];
$member_nickname = $member_info['nickname'];

// 사용자가 좋아요/저장한 영화 가져오기
$user_movies = db_select(
    "SELECT m.title, m.genre, m.director, m.rating 
     FROM moviesdb m
     WHERE m.movie_id IN (
         SELECT movie_id FROM user_actions_new 
         WHERE member_id = ? AND action_type IN ('like', 'save')
     )
     ORDER BY m.rating DESC
     LIMIT 20",
    [$member_id]
);

if (empty($user_movies)) {
    echo json_encode([
        'success' => false, 
        'message' => '먼저 마음에 드는 영화를 찜해주세요! 🎬'
    ]);
    exit;
}

// 찜한 영화 수에 따라 추천 수 결정 (최소 3개, 최대 8개)
$user_movie_count = count($user_movies);
$recommend_count = min(max($user_movie_count + 2, 3), 8);

// DB에 있는 영화 목록 가져오기 (추천 후보)
$all_movies = db_select(
    "SELECT movie_id, title, genre, director, rating, poster_image 
     FROM moviesdb 
     WHERE poster_image IS NOT NULL 
     AND poster_image != '' 
     AND poster_image != 'images/default_poster.jpg'
     AND movie_id NOT IN (
         SELECT movie_id FROM user_actions_new 
         WHERE member_id = ? AND action_type IN ('like', 'save')
     )
     ORDER BY RAND()
     LIMIT 200",
    [$member_id]
);

// 사용자 영화 목록 텍스트로 변환
$user_movies_text = "";
foreach ($user_movies as $movie) {
    $user_movies_text .= "- {$movie['title']} (장르: {$movie['genre']}, 감독: {$movie['director']})\n";
}

// 추천 후보 영화 목록 텍스트로 변환
$candidate_movies_text = "";
foreach ($all_movies as $index => $movie) {
    $candidate_movies_text .= ($index + 1) . ". {$movie['title']} (장르: {$movie['genre']}, 감독: {$movie['director']}, 평점: {$movie['rating']})\n";
}

// 현재 날짜
$current_date = date('Y년 m월 d일');

// 프롬프트
$prompt = "당신은 영화 추천 AI 전문가입니다.

**오늘 날짜: {$current_date}**
**추천할 영화 수: {$recommend_count}개**

[사용자가 찜한 영화 목록]
{$user_movies_text}

[추천 가능한 영화 목록]
{$candidate_movies_text}

위 정보를 바탕으로:
1. 사용자의 영화 취향을 2문장으로 분석해주세요 (정중한 높임말로)
2. 추천 가능한 영화 목록에서 정확히 {$recommend_count}개를 골라 추천해주세요
3. 각 영화마다 왜 이 사용자에게 어울리는지 1문장으로 설명해주세요 (높임말로)

반드시 다음 JSON 형식으로만 답변하세요:
{
    \"taste_analysis\": \"취향 분석 (높임말, 2문장)\",
    \"recommendations\": [
        {
            \"title\": \"영화 제목 (정확히)\",
            \"reason\": \"추천 이유 (높임말, 1문장)\"
        }
    ]
}";

// Gemini API 호출
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 1000
    ]
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($http_code !== 200) {
    $error_data = json_decode($response, true);
    $error_msg = isset($error_data['error']['message']) ? $error_data['error']['message'] : '서버 오류';
    echo json_encode([
        'success' => false, 
        'message' => '잠시 후 다시 시도해주세요 😅'
    ]);
    exit;
}

$result = json_decode($response, true);

if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'AI 응답을 처리할 수 없습니다.'
    ]);
    exit;
}

$ai_response = $result['candidates'][0]['content']['parts'][0]['text'];

// JSON 파싱
$ai_response = preg_replace('/```json\s*/', '', $ai_response);
$ai_response = preg_replace('/```\s*/', '', $ai_response);
$ai_response = trim($ai_response);

$ai_data = json_decode($ai_response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    preg_match('/\{[\s\S]*\}/', $ai_response, $matches);
    if (!empty($matches)) {
        $ai_data = json_decode($matches[0], true);
    }
}

if (!$ai_data) {
    echo json_encode([
        'success' => false, 
        'message' => '다시 시도해주세요 🙏'
    ]);
    exit;
}

// 추천된 영화들의 상세 정보 가져오기
$recommended_movies = [];
if (isset($ai_data['recommendations'])) {
    foreach ($ai_data['recommendations'] as $rec) {
        foreach ($all_movies as $movie) {
            if (strpos($movie['title'], $rec['title']) !== false || 
                strpos($rec['title'], $movie['title']) !== false) {
                $recommended_movies[] = [
                    'movie_id' => $movie['movie_id'],
                    'title' => $movie['title'],
                    'genre' => $movie['genre'],
                    'director' => $movie['director'],
                    'rating' => $movie['rating'],
                    'poster_image' => $movie['poster_image'],
                    'reason' => $rec['reason']
                ];
                break;
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'nickname' => $member_nickname,
    'taste_analysis' => $ai_data['taste_analysis'] ?? '취향 분석 완료!',
    'recommendations' => $recommended_movies
]);
?>