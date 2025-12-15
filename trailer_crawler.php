<?php
// 캐시 디렉토리 설정
if (!defined('CACHE_DIR')) {
    define('CACHE_DIR', __DIR__ . '/cache/');
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0777, true);
    }
}

/**
 * 중급 수준의 예고편 검색 (캐시 포함)
 */
function get_naver_movie_trailer_medium($movie_title) {
    // 1. 간단한 제목 정제
    $cleaned_title = simple_clean_title($movie_title);
    
    // 2. 캐시 확인 (5일 유효) - 예고편 없어도 캐시 사용
    $cache_file = CACHE_DIR . md5($cleaned_title . "_trailer") . '.json';
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 432000) {
        $cached_data = json_decode(file_get_contents($cache_file), true);
        if ($cached_data) {
            return $cached_data;
        }
    }
    
    // 3. 기본 대기 시간
    usleep(500000); // 0.5초
    
    // 4. 다양한 키워드로 검색 (19세 영화 대응)
    $search_keywords = array(
        $cleaned_title . " 메인 예고편",
        $cleaned_title . " 티저 예고편", 
        $cleaned_title . " 예고편"
    );
    
    $trailer_data = null;
    
    // 각 키워드로 순차 검색
    foreach ($search_keywords as $keyword) {
        $trailer_data = search_naver_simple($keyword);
        
        // 예고편을 찾았으면 중단
        if ($trailer_data['type'] != 'none') {
            break;
        }
        
        usleep(300000); // 0.3초 대기
    }
    
    // 모든 검색 실패시 원본 제목으로 재검색
    if ($trailer_data['type'] == 'none' && $movie_title != $cleaned_title) {
        $trailer_data = search_naver_simple($movie_title . " 예고편");
    }
    
    // 네이버TV에서 못 찾으면 유튜브에서 검색
    if ($trailer_data['type'] == 'none') {
        foreach ($search_keywords as $keyword) {
            $trailer_data = search_youtube($keyword);
            
            if ($trailer_data['type'] != 'none') {
                break;
            }
            
            usleep(300000);
        }
        
        // 유튜브에서도 원본 제목으로 재시도
        if ($trailer_data['type'] == 'none' && $movie_title != $cleaned_title) {
            $trailer_data = search_youtube($movie_title . " 예고편");
        }
    }
    
    // 5. 결과 캐싱 (예고편 없어도 캐싱)
    $result = [
        'title' => $movie_title,
        'trailer' => $trailer_data
    ];
    file_put_contents($cache_file, json_encode($result));
    
    return $result;
}

/**
 * 간단한 제목 정제
 */
function simple_clean_title($title) {
    $bad_chars = array('(', ')', '[', ']', ':', '!', '?', '-', '_');
    foreach ($bad_chars as $char) {
        $title = str_replace($char, ' ', $title);
    }
    
    // 연속 공백 제거
    while (strpos($title, '  ') !== false) {
        $title = str_replace('  ', ' ', $title);
    }
    
    return trim($title);
}

/**
 * 단순화된 네이버 검색 - 네이버TV만
 */
function search_naver_simple($movie_title) {
    $search_url = "https://search.naver.com/search.naver?query=" . urlencode($movie_title);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $search_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$html || $http_code != 200) {
        return create_no_result();
    }
    
    // 네이버 TV만 검색
    $naver_patterns = [
        '/tv\.naver\.com\/embed\/(\d+)/',
        '/tv\.naver\.com\/v\/(\d+)/',
        '/tvcast\.naver\.com\/v\/(\d+)/'
    ];
    
    // 네이버 TV 검색
    foreach ($naver_patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            return [
                'trailer_id' => $matches[1],
                'trailer_url' => "https://tv.naver.com/embed/" . $matches[1],
                'type' => 'naver'
            ];
        }
    }
    
    return create_no_result();
}

/**
 * 유튜브 검색 (네이버TV에서 못 찾을 때만)
 */
function search_youtube($movie_title) {
    $search_url = "https://search.naver.com/search.naver?query=" . urlencode($movie_title);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $search_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$html || $http_code != 200) {
        return create_no_result();
    }
    
    // 유튜브 패턴
    $youtube_patterns = [
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
        '/youtu\.be\/([a-zA-Z0-9_-]+)/',
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/'
    ];
    
    // 유튜브 검색
    foreach ($youtube_patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            return [
                'trailer_id' => $matches[1],
                'trailer_url' => "https://www.youtube.com/embed/" . $matches[1],
                'type' => 'youtube'
            ];
        }
    }
    
    return create_no_result();
}

/**
 * 결과 없음 처리
 */
function create_no_result() {
    return [
        'trailer_id' => '',
        'trailer_url' => '',
        'type' => 'none',
        'message' => '예고편을 찾을 수 없습니다.'
    ];
}
?>