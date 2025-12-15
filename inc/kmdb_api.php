<?php
/**
 * KMDb API 연동 함수
 */

// KMDb API 키 설정  
define('KMDB_API_KEY', ''); // 여기에 발급받은 KMDb API 키를 입력

/**
 * KMDb API 검색 함수
 */
function kmdb_api_request($title) {
    $base_url = 'http://api.koreafilm.or.kr/openapi-data2/wisenut/search_api/search_json2.jsp';
    
    // 기본 파라미터 설정
    $params = [
        'collection' => 'kmdb_new2',
        'ServiceKey' => KMDB_API_KEY,
        'title' => $title,
        'detail' => 'Y',
        'listCount' => 40  // 검색 결과 수를 40개로 설정 (정확한 매칭을 위해)
    ];

    // URL 생성
    $url = $base_url . '?' . http_build_query($params);

    // CURL 설정
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // API 호출 및 응답 처리
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("KMDb API 요청 오류: " . $error);
        return null;
    }
    
    $data = json_decode($response, true);
    return $data;
}

/**
 * 영화 상세 정보 가져오기
 */
function kmdb_get_movie_details($title) {
    // DB에서 영화 상세 정보 확인
    $query = "SELECT * FROM moviesdb WHERE title = ?";
    $movie = db_select($query, [$title]);
    
    // DB에 상세 정보가 있는지 확인 (plot이 있으면 상세 정보가 있다고 가정)
    if (!empty($movie) && !empty($movie[0]['plot'])) {
        return $movie[0];
    }
    
    // DB에 상세 정보가 없으면 API 호출
    $data = kmdb_api_request($title);
    if (!isset($data['Data'][0]['Result']) || empty($data['Data'][0]['Result'])) {
        return null;
    }
    
    // 제목이 정확히 일치하는 영화 찾기
    $exact_match = null;
    foreach ($data['Data'][0]['Result'] as $result) {
        // 제목에서 !HS와 !HE 태그 제거
        $clean_title = isset($result['title']) ? $result['title'] : '';
        $clean_title = str_replace(['!HS', '!HE'], '', $clean_title);
        $clean_title = preg_replace('/\s+/', ' ', $clean_title); // 공백 여러 개를 하나로 변경
        $clean_title = trim($clean_title); // 앞뒤 공백 제거
        
        // 제목 비교 (대소문자 구분 없이)
        if (strtolower($clean_title) === strtolower($title)) {
            $exact_match = $result;
            break;
        }
    }
    
    // 정확히 일치하는 영화가 없으면 null 반환
    if (!$exact_match) {
        return null;
    }
    
    $movie = $exact_match;
    
    // 포스터 URL 처리 - KMDb에서만 시도
    $poster_url = '';
    if (isset($movie['posters']) && !empty($movie['posters'])) {
        $posters = explode('|', $movie['posters']);
        if (!empty($posters[0])) {
            $poster_url = $posters[0];
        }
    }
    
    // 기본 포스터 이미지 설정
    if (empty($poster_url)) {
        $poster_url = 'images/default_poster.jpg';
    }
    
    // 줄거리 처리
    $plot = '';
    if (isset($movie['plots']['plot'][0]['plotText'])) {
        $plot = $movie['plots']['plot'][0]['plotText'];
    }
    
    // 감독 처리
    $director = '';
    if (isset($movie['directors']['director'][0]['directorNm'])) {
        $director = $movie['directors']['director'][0]['directorNm'];
    }
    
    // 배우 처리
    $actors = [];
    if (isset($movie['actors']['actor'])) {
        foreach ($movie['actors']['actor'] as $actor) {
            if (isset($actor['actorNm'])) {
                $actors[] = $actor['actorNm'];
            }
        }
    }
    
    // 장르 및 등급 처리
    $genre = isset($movie['genre']) ? $movie['genre'] : '';
    $rating = isset($movie['rating']) ? $movie['rating'] : '';
    
    $movie_data = [
        'movie_id' => isset($movie['DOCID']) ? $movie['DOCID'] : '',
        'title' => $title, // 원래 검색한 제목 사용 (태그가 없는 상태)
        'release_date' => isset($movie['repRlsDate']) ? $movie['repRlsDate'] : null,
        'director' => $director,
        'actors' => implode(', ', array_slice($actors, 0, 5)),
        'genre' => $genre,
        'rating' => 0, // 평점은 항상 0으로 설정
        'plot' => $plot,
        'runtime' => isset($movie['runtime']) ? $movie['runtime'] : null,
        'poster_image' => $poster_url,
        'source' => 'kmdb'
    ];
    
    // DB에 저장 또는 업데이트
    save_movie_to_db($movie_data);
    
    return $movie_data;
}
?>