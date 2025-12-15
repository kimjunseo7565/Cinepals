<?php
require_once("inc/session.php");
require_once("inc/db.php");
require_once("inc/gemini_api.php");

header('Content-Type: application/json');

// 로그인 체크
if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$member_id = $_SESSION['member_id'];

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$user_message = trim($input['message'] ?? '');
$chat_history = $input['history'] ?? [];

if (empty($user_message)) {
    echo json_encode(['success' => false, 'message' => '메시지를 입력해주세요.']);
    exit;
}

// 회원 정보 가져오기
$member_info = db_select("SELECT nickname FROM members WHERE member_id = ?", [$member_id])[0];
$member_nickname = $member_info['nickname'];

// 현재 날짜
$current_date = date('Y년 m월 d일');

// 시스템 프롬프트
$system_prompt = "당신은 정확한 영화 정보만 제공하는 전문가입니다. 이름은 '시네팔'입니다.

**오늘 날짜: {$current_date}**
**위치: 대한민국**

사용자 이름: {$member_nickname}

핵심 규칙 (절대 지켜야 함):
1. **반드시 Google 검색으로 최신 정보를 확인한 후에만 답변하세요**
2. **CGV, 메가박스, 롯데시네마, 영화진흥위원회 공식 박스오피스 데이터만 신뢰하세요**
3. **불확실하거나 검증되지 않은 정보는 절대 답변하지 마세요**
4. **정보의 출처와 날짜를 반드시 확인하세요**
5. **오래된 기사나 예고 정보는 무시하세요**

정보 제공 규칙:
1. **반드시 실시간 박스오피스 순위 검색 후 답변**
2. **개봉일, 관객수 등 수치 정보는 공식 출처에서만 가져오기**
3. **확인되지 않은 정보는 '정확한 정보 확인이 필요합니다'라고 답변**
4. **과거 정보(1년 이상 된 영화)는 언급하지 마세요**

대화 스타일:
1. 높임말로 정중하게 (예: ~입니다, ~해요)
2. 짧고 명확하게 (2-3문장)
3. **정확성이 최우선**, 추측 금지
4. 이모지는 최소한으로

예시:
- 사용자: 요즘 볼만한 영화 뭐예요?
- 시네팔: [Google 검색 후] 현재 박스오피스 1위는 OOO입니다. (출처: 영화진흥위원회, {$current_date} 기준)";

// Gemini API용 대화 내용 구성
$contents = [];

// 시스템 프롬프트를 첫 번째 사용자 메시지로
$contents[] = [
    'role' => 'user',
    'parts' => [['text' => $system_prompt]]
];
$contents[] = [
    'role' => 'model',
    'parts' => [['text' => '안녕하세요! 저는 시네팔입니다~ 😊 어떤 영화를 찾으시나요?']]
];

// 이전 대화 기록 추가
foreach ($chat_history as $chat) {
    $role = ($chat['role'] === 'user') ? 'user' : 'model';
    $contents[] = [
        'role' => $role,
        'parts' => [['text' => $chat['content']]]
    ];
}

// 새 사용자 메시지 추가
$contents[] = [
    'role' => 'user',
    'parts' => [['text' => $user_message]]
];

// Gemini API 호출
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

$data = [
    'contents' => $contents,
    'tools' => [
        ['google_search' => new stdClass()]
    ],
    'generationConfig' => [
        'temperature' => 0.2,  // 정확성 최우선 (0에 가까울수록 정확)
        'maxOutputTokens' => 500
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
$curl_error = curl_error($ch);

if ($http_code !== 200) {
    // 디버깅용 로그
    error_log("Gemini API Error - HTTP Code: $http_code");
    error_log("Response: $response");
    error_log("CURL Error: $curl_error");
    
    echo json_encode([
        'success' => false, 
        'message' => '잠시 후 다시 시도해주세요.',
        'debug' => [
            'http_code' => $http_code,
            'error' => $curl_error,
            'response' => substr($response, 0, 200)
        ]
    ]);
    exit;
}

$result = json_decode($response, true);

if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode([
        'success' => false, 
        'message' => '응답을 처리할 수 없습니다.'
    ]);
    exit;
}

$ai_response = $result['candidates'][0]['content']['parts'][0]['text'];

echo json_encode([
    'success' => true,
    'message' => $ai_response
]);
?>