<?php

/**
 * 영화진흥위원회 API 연동 함수
 */

// 영화진흥위원회 API 키 설정
define('KOBIS_API_KEY', ''); // 여기에 발급받은 영화진흥위원회 API 키를 입력
define('KOBIS_BASE_URL', 'http://www.kobis.or.kr/kobisopenapi/webservice/rest');

/**
 * 영화진흥위원회 API 요청 함수
 */
function kobis_api_request($endpoint, $params)
{
    // API 키 추가
    $params = array_merge(['key' => KOBIS_API_KEY], $params);

    // URL 생성
    $url = KOBIS_BASE_URL . $endpoint . '?' . http_build_query($params);

    // CURL 설정
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 타임아웃 설정

    // API 호출 및 응답 처리
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("영화진흥위원회 API 요청 오류: " . $error);
        return null;
    }

    // JSON 응답 파싱
    $result = json_decode($response, true);
    return $result;
}

/**
 * 영화 상세정보 가져오기 - 새로 추가된 함수
 */
function kobis_get_movie_detail($movieCd)
{
    $params = ['movieCd' => $movieCd];
    $response = kobis_api_request('/movie/searchMovieInfo.json', $params);
    
    if (isset($response['movieInfoResult']['movieInfo'])) {
        $movie = $response['movieInfoResult']['movieInfo'];
        
        // 장르 정보 추출
        $genres = [];
        if (isset($movie['genres']) && is_array($movie['genres'])) {
            foreach ($movie['genres'] as $genre) {
                if (isset($genre['genreNm'])) {
                    $genres[] = $genre['genreNm'];
                }
            }
        }
        
        // 출연진 정보 추출
        $actors = [];
        if (isset($movie['actors']) && is_array($movie['actors'])) {
            foreach ($movie['actors'] as $actor) {
                if (isset($actor['peopleNm'])) {
                    $actors[] = $actor['peopleNm'];
                }
            }
        }
        
        // 감독 정보 추출
        $directors = [];
        if (isset($movie['directors']) && is_array($movie['directors'])) {
            foreach ($movie['directors'] as $director) {
                if (isset($director['peopleNm'])) {
                    $directors[] = $director['peopleNm'];
                }
            }
        }
        
        return [
            'genre' => !empty($genres) ? implode(', ', $genres) : '',
            'actors' => !empty($actors) ? implode(', ', array_slice($actors, 0, 5)) : '',
            'director' => !empty($directors) ? implode(', ', $directors) : '',
            'runtime' => isset($movie['showTm']) ? (int)$movie['showTm'] : null
        ];
    }
    
    return false;
}

/**
 * 현재 상영중인 영화 목록 가져오기 (일별 박스오피스 기준) - 수정됨
 */
function kobis_get_now_playing_movies()
{
    // DB에서 현재 상영 중인 영화 데이터 확인
    $query = "SELECT * FROM moviesdb 
              WHERE release_date <= CURDATE() 
              ORDER BY audience_count DESC, release_date DESC 
              LIMIT 50";
    $movies = db_select($query);

    // DB에 데이터가 있으면 반환
    if (!empty($movies)) {
        return $movies;
    }

    // DB에 데이터가 없으면 API 호출 (여러 날짜)
    $all_movies = [];
    $unique_movie_ids = [];

    // 최근 25일간의 박스오피스 데이터 가져오기
    for ($i = 1; $i <= 25; $i++) {
        $target_date = date('Ymd', strtotime('-' . $i . ' day'));

        $params = [
            'targetDt' => $target_date,
            'itemPerPage' => 20
        ];

        $response = kobis_api_request('/boxoffice/searchDailyBoxOfficeList.json', $params);

        if (isset($response['boxOfficeResult']['dailyBoxOfficeList'])) {
            foreach ($response['boxOfficeResult']['dailyBoxOfficeList'] as $movie) {
                // 중복 제거
                if (in_array($movie['movieCd'], $unique_movie_ids)) {
                    continue;
                }

                $unique_movie_ids[] = $movie['movieCd'];

                $release_date = isset($movie['openDt']) && !empty($movie['openDt']) ?
                    date('Y-m-d', strtotime($movie['openDt'])) : null;

                // 기본 영화 정보
                $movie_data = [
                    'movie_id' => $movie['movieCd'],
                    'title' => $movie['movieNm'],
                    'release_date' => $release_date,
                    'audience_count' => isset($movie['audiAcc']) ? $movie['audiAcc'] : 0,
                    'rating' => 0,
                    'source' => 'kobis',
                    'genre' => '',
                    'actors' => '',
                    'director' => '',
                    'runtime' => null
                ];

                // 상세정보 가져오기 (새로 추가)
                $detail = kobis_get_movie_detail($movie['movieCd']);
                if ($detail) {
                    $movie_data['genre'] = $detail['genre'];
                    $movie_data['actors'] = $detail['actors'];
                    $movie_data['director'] = $detail['director'];
                    $movie_data['runtime'] = $detail['runtime'];
                }

                // DB에 저장
                save_movie_to_db($movie_data);

                $all_movies[] = $movie_data;
            }
        }
        
        // API 호출 간격 (너무 빠른 호출 방지)
        usleep(200000); // 0.2초 대기
    }

    return $all_movies;
}

/**
 * 개봉 예정 영화 목록 가져오기 - 수정됨
 */
function kobis_get_upcoming_movies()
{
    // DB에서 개봉 예정 영화 데이터 확인
    $query = "SELECT * FROM moviesdb WHERE release_date > CURDATE() ORDER BY release_date ASC LIMIT 50";
    $movies = db_select($query);

    // DB에 데이터가 있으면 반환
    if (!empty($movies)) {
        return $movies;
    }

    // DB에 데이터가 없으면 API 호출
    $today = date('Ymd');
    $oneYearLater = date('Ymd', strtotime('+12 month'));
    $params = [
        'openStartDt' => $today,
        'openEndDt' => $oneYearLater,
        'itemPerPage' => 100
    ];

    $response = kobis_api_request('/movie/searchMovieList.json', $params);
    $movies = [];

    if (isset($response['movieListResult']['movieList'])) {
        foreach ($response['movieListResult']['movieList'] as $movie) {
            if (isset($movie['openDt']) && !empty($movie['openDt']) && $movie['openDt'] >= $today) {
                $release_date = date('Y-m-d', strtotime(substr($movie['openDt'], 0, 4) . '-' .
                    substr($movie['openDt'], 4, 2) . '-' .
                    substr($movie['openDt'], 6, 2)));

                // 기본 영화 정보
                $movie_data = [
                    'movie_id' => $movie['movieCd'],
                    'title' => $movie['movieNm'],
                    'release_date' => $release_date,
                    'rating' => 0,
                    'source' => 'kobis',
                    'genre' => '',
                    'actors' => '',
                    'director' => '',
                    'runtime' => null
                ];

                // 상세정보 가져오기 (새로 추가)
                $detail = kobis_get_movie_detail($movie['movieCd']);
                if ($detail) {
                    $movie_data['genre'] = $detail['genre'];
                    $movie_data['actors'] = $detail['actors'];
                    $movie_data['director'] = $detail['director'];
                    $movie_data['runtime'] = $detail['runtime'];
                }

                // 감독 정보가 없으면 기본 API에서 가져오기
                if (empty($movie_data['director']) && isset($movie['directors'][0]['peopleNm'])) {
                    $movie_data['director'] = $movie['directors'][0]['peopleNm'];
                }

                // DB에 저장
                save_movie_to_db($movie_data);

                $movies[] = $movie_data;
            }
            
            // API 호출 간격
            usleep(100000); // 0.1초 대기
        }
    }

    return $movies;
}