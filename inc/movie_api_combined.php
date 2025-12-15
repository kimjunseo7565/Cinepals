<?php

/**
 * 영화 API 통합 - KOBIS 개선 버전 (중복 제거 강화)
 * 중복 발견 시 KMDB에서만 가져오도록 수정
 */

require_once("kobis_api.php");
require_once("kmdb_api.php");

/**
 * 대체 검색어 생성 - 단순화
 */
function get_search_alternatives_simple($title)
{
    $alternatives = array();

    // 콜론으로 분리
    if (strpos($title, ':') !== false) {
        $parts = explode(':', $title);
        $alternatives[] = trim($parts[0]);
        if (isset($parts[1])) {
            $alternatives[] = trim($parts[1]);
        }
    }

    // 괄호 제거
    $no_brackets = $title;
    $bracket_pairs = array('(' => ')', '[' => ']', '{' => '}');
    foreach ($bracket_pairs as $open => $close) {
        while (strpos($no_brackets, $open) !== false && strpos($no_brackets, $close) !== false) {
            $start = strpos($no_brackets, $open);
            $end = strpos($no_brackets, $close, $start);
            if ($end !== false) {
                $no_brackets = substr($no_brackets, 0, $start) . substr($no_brackets, $end + 1);
            } else {
                break;
            }
        }
    }
    $alternatives[] = trim($no_brackets);

    // 공백으로 분리
    $words = explode(' ', $title);
    foreach ($words as $word) {
        $word = trim($word);
        if (strlen($word) > 3) {
            $alternatives[] = $word;
        }
    }

    // 중복 제거하고 빈 값 제거
    $final_alternatives = array();
    foreach ($alternatives as $alt) {
        $alt = trim($alt);
        if ($alt && strlen($alt) > 2 && $alt !== $title) {
            $is_duplicate = false;
            foreach ($final_alternatives as $existing) {
                if ($existing === $alt) {
                    $is_duplicate = true;
                    break;
                }
            }
            if (!$is_duplicate) {
                $final_alternatives[] = $alt;
            }
        }
    }

    return $final_alternatives;
}

/**
 * KMDB 매칭 로직 - 개선된 버전
 */
function find_best_kmdb_match_simple($kmdb_results, $kobis_movie)
{
    $kobis_title = $kobis_movie['title'];

    $kobis_release_date = '';
    if (isset($kobis_movie['release_date'])) {
        $kobis_release_date = str_replace('-', '', $kobis_movie['release_date']);
    }

    $best_movie = null;
    $highest_score = 0;

    // 각 KMDB 영화별로 점수 계산
    foreach ($kmdb_results as $kmdb_movie) {
        // 제목 정리
        $clean_title = '';
        if (isset($kmdb_movie['title'])) {
            $clean_title = $kmdb_movie['title'];
        }
        $clean_title = str_replace(array('!HS', '!HE'), '', $clean_title);
        $clean_title = trim($clean_title);

        // KMDB 개봉일 정리
        $kmdb_release_date = '';
        if (isset($kmdb_movie['repRlsDate'])) {
            $kmdb_release_date = $kmdb_movie['repRlsDate'];
        }

        // 점수 계산 시작
        $score = 0;

        // 1. 완전 일치 검사
        if (strtolower($clean_title) === strtolower($kobis_title)) {
            $score = $score + 100;
        }

        // 2. 부분 일치 검사
        if (strpos(strtolower($clean_title), strtolower($kobis_title)) !== false) {
            $score = $score + 60;
        }
        if (strpos(strtolower($kobis_title), strtolower($clean_title)) !== false) {
            $score = $score + 60;
        }

        // 3. 영어 제목 매칭 (특수문자 제거 후 비교)
        $simple_kobis = strtolower($kobis_title);
        $simple_kmdb = strtolower($clean_title);

        // 특수문자 제거
        $bad_chars = array(':', '(', ')', '-', ' ');
        foreach ($bad_chars as $char) {
            $simple_kobis = str_replace($char, '', $simple_kobis);
            $simple_kmdb = str_replace($char, '', $simple_kmdb);
        }

        if ($simple_kobis === $simple_kmdb) {
            $score = $score + 50;
        }

        // 4. 단어별 매칭
        $kobis_words = explode(' ', strtolower($kobis_title));
        $kmdb_words = explode(' ', strtolower($clean_title));
        
        $word_matches = 0;
        foreach ($kobis_words as $kobis_word) {
            if (strlen($kobis_word) > 2) {
                foreach ($kmdb_words as $kmdb_word) {
                    if (strpos($kmdb_word, $kobis_word) !== false || strpos($kobis_word, $kmdb_word) !== false) {
                        $word_matches++;
                        break;
                    }
                }
            }
        }
        
        if ($word_matches > 0) {
            $score = $score + ($word_matches * 15);
        }

        // 5. 개봉일 완전 일치
        if (!empty($kobis_release_date) && $kobis_release_date === $kmdb_release_date) {
            $score = $score + 30;
        }

        // 6. 개봉년도 일치
        if (!empty($kobis_release_date) && !empty($kmdb_release_date)) {
            $kobis_year = substr($kobis_release_date, 0, 4);
            $kmdb_year = substr($kmdb_release_date, 0, 4);
            if ($kobis_year === $kmdb_year) {
                $score = $score + 20;
            }
        }

        // 최고 점수 영화 저장
        if ($score > $highest_score) {
            $highest_score = $score;
            $best_movie = $kmdb_movie;
        }
    }

    // 점수 임계값 15점 이상이면 매칭 성공
    if ($highest_score > 15) {
        return $best_movie;
    }

    return null;
}

/**
 * KMDB 검색 (대체 검색어 포함)
 */
function search_kmdb_data_simple($title)
{
    // 원본 제목으로 먼저 검색
    $data = kmdb_api_request($title);
    if (isset($data['Data'][0]['Result']) && !empty($data['Data'][0]['Result'])) {
        return $data;
    }

    // 원본 제목으로 실패하면 대체 검색어로 재시도
    $alternatives = get_search_alternatives_simple($title);
    foreach ($alternatives as $alt_title) {
        $alt_title = trim($alt_title);
        if (strlen($alt_title) > 2) {
            $data = kmdb_api_request($alt_title);
            if (isset($data['Data'][0]['Result']) && !empty($data['Data'][0]['Result'])) {
                return $data;
            }
        }
    }

    return null;
}

/**
 * 중복 확인 함수 - 초강력 버전 (인피니트 케이스 포함)
 */
function is_duplicate_movie($movie, $existing_movies)
{
    $title = strtolower(trim($movie['title']));
    $movie_id = isset($movie['movie_id']) ? $movie['movie_id'] : '';
    $release_date = isset($movie['release_date']) ? $movie['release_date'] : '';
    
    foreach ($existing_movies as $existing) {
        $existing_title = strtolower(trim($existing['title']));
        $existing_movie_id = isset($existing['movie_id']) ? $existing['movie_id'] : '';
        $existing_release_date = isset($existing['release_date']) ? $existing['release_date'] : '';
        
        // 1. movie_id가 같으면 중복
        if (!empty($movie_id) && !empty($existing_movie_id) && $movie_id === $existing_movie_id) {
            return true;
        }
        
        // 2. 제목이 완전히 같으면 중복
        if ($title === $existing_title) {
            return true;
        }
        
        // 3. 제목에서 특수문자와 공백 제거 후 비교 (완전 강화)
        $clean_title = preg_replace('/[^a-z0-9가-힣]/', '', $title);
        $clean_existing = preg_replace('/[^a-z0-9가-힣]/', '', $existing_title);
        
        if (!empty($clean_title) && !empty($clean_existing) && $clean_title === $clean_existing) {
            return true;
        }
        
        // 4. 핵심 키워드 기반 중복 검사 (인피니트 케이스)
        $title_keywords = extract_key_words($title);
        $existing_keywords = extract_key_words($existing_title);
        
        if (count(array_intersect($title_keywords, $existing_keywords)) >= 3) {
            return true;
        }
        
        // 5. 긴 제목의 경우 유사도 검사 (75% 이상 유사하면 중복) - 임계값 낮춤
        if (strlen($title) > 8 && strlen($existing_title) > 8) {
            similar_text($title, $existing_title, $percent);
            if ($percent > 75) {
                return true;
            }
        }
        
        // 6. 개봉일이 같고 제목이 60% 이상 유사하면 중복 - 임계값 낮춤
        if (!empty($release_date) && !empty($existing_release_date) && $release_date === $existing_release_date) {
            similar_text($title, $existing_title, $percent);
            if ($percent > 60) {
                return true;
            }
        }
        
        // 7. 특정 패턴 매칭 (콘서트, 더 무비 등)
        if (is_same_content_different_source($title, $existing_title)) {
            return true;
        }
    }
    
    return false;
}

/**
 * 핵심 키워드 추출 함수
 */
function extract_key_words($title) {
    // 불용어 제거
    $stopwords = array('의', '를', '을', '이', '가', '와', '과', '에', '에서', '으로', '로', '더', '그', '그리고');
    
    // 특수문자 제거하고 단어 분리
    $clean_title = preg_replace('/[^\w\s가-힣]/u', ' ', $title);
    $words = preg_split('/\s+/', $clean_title);
    
    $keywords = array();
    foreach ($words as $word) {
        $word = trim($word);
        if (strlen($word) > 1 && !in_array($word, $stopwords)) {
            $keywords[] = $word;
        }
    }
    
    return $keywords;
}

/**
 * 동일 컨텐츠 다른 소스 판별 함수
 */
function is_same_content_different_source($title1, $title2) {
    // 콘서트 관련 패턴
    $patterns = array(
        '/(\w+)\s*(?:15주년|주년)?\s*콘서트.*무비/u',
        '/(\w+)\s*(?:콘서트|concert).*(?:무비|movie)/u',
        '/(\w+)\s*(?:리미티드|limited).*에디션/u'
    );
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $title1, $matches1) && preg_match($pattern, $title2, $matches2)) {
            if (isset($matches1[1]) && isset($matches2[1])) {
                $clean1 = preg_replace('/[^a-z0-9가-힣]/u', '', strtolower($matches1[1]));
                $clean2 = preg_replace('/[^a-z0-9가-힣]/u', '', strtolower($matches2[1]));
                if ($clean1 === $clean2) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

/**
 * KMDB에서 단독으로 영화 정보 가져오기 - 새로 추가
 */
function get_movie_from_kmdb_only($title)
{
    $kmdb_data = search_kmdb_data_simple($title);
    
    if (!$kmdb_data || !isset($kmdb_data['Data'][0]['Result']) || empty($kmdb_data['Data'][0]['Result'])) {
        return null;
    }
    
    // 첫 번째 결과를 사용 (가장 관련성이 높다고 가정)
    $movie = $kmdb_data['Data'][0]['Result'][0];
    
    // 제목 정리
    $clean_title = '';
    if (isset($movie['title'])) {
        $clean_title = $movie['title'];
    }
    $clean_title = str_replace(array('!HS', '!HE'), '', $clean_title);
    $clean_title = trim($clean_title);
    
    // 개봉일 처리
    $release_date = null;
    if (isset($movie['repRlsDate']) && !empty($movie['repRlsDate'])) {
        $release_date = substr($movie['repRlsDate'], 0, 4) . '-' .
            substr($movie['repRlsDate'], 4, 2) . '-' .
            substr($movie['repRlsDate'], 6, 2);
    }
    
    // 포스터 처리
    $poster_url = 'images/default_poster.jpg';
    if (isset($movie['posters']) && !empty($movie['posters'])) {
        $posters = explode('|', $movie['posters']);
        if (!empty($posters[0])) {
            $poster_url = $posters[0];
        }
    }
    
    // 감독 처리
    $director = '';
    if (isset($movie['directors']['director'][0]['directorNm'])) {
        $director = $movie['directors']['director'][0]['directorNm'];
    }
    
    // 줄거리 처리
    $plot = '';
    if (isset($movie['plots']['plot'][0]['plotText'])) {
        $plot = $movie['plots']['plot'][0]['plotText'];
    }
    
    // 배우 처리
    $actors = array();
    if (isset($movie['actors']['actor'])) {
        $count = 0;
        foreach ($movie['actors']['actor'] as $actor) {
            if ($count >= 5) {
                break;
            }
            if (isset($actor['actorNm'])) {
                $actors[] = $actor['actorNm'];
                $count = $count + 1;
            }
        }
    }
    
    // 장르 처리
    $genre = '';
    if (isset($movie['genre'])) {
        $genre = $movie['genre'];
    }
    
    // 영화 데이터 구성
    $movie_data = array(
        'movie_id' => isset($movie['DOCID']) ? $movie['DOCID'] : '',
        'title' => $clean_title,
        'release_date' => $release_date,
        'director' => $director,
        'actors' => implode(', ', $actors),
        'genre' => $genre,
        'rating' => 0,
        'plot' => $plot,
        'runtime' => isset($movie['runtime']) ? $movie['runtime'] : null,
        'poster_image' => $poster_url,
        'source' => 'kmdb',
        'audience_count' => 0,
        'booking_link' => "https://search.naver.com/search.naver?query=" . urlencode($clean_title . " 영화상영일정")
    );
    
    // DB에 저장
    save_movie_to_db($movie_data);
    
    return $movie_data;
}

/**
 * KOBIS+KMDB 매칭 함수 - KOBIS에서 이미 장르/출연진을 가져왔으면 보완만
 */
function add_kmdb_info_to_kobis_movie_simple($kobisMovie)
{
    $movie = $kobisMovie;

    // KOBIS에서 이미 장르와 출연진 정보가 있는지 확인
    $has_genre = !empty($movie['genre']);
    $has_actors = !empty($movie['actors']);
    $has_plot = !empty($movie['plot']);

    // 모든 정보가 있으면 KMDB 검색 생략
    if ($has_genre && $has_actors && $has_plot) {
        $movie['booking_link'] = "https://search.naver.com/search.naver?query=" . urlencode($movie['title'] . " 영화상영일정");
        save_movie_data_simple($movie);
        return $movie;
    }

    // KMDB에서 부족한 정보만 보완
    $kmdb_data = search_kmdb_data_simple($kobisMovie['title']);

    if (!$kmdb_data || !isset($kmdb_data['Data'][0]['Result']) || empty($kmdb_data['Data'][0]['Result'])) {
        // KMDB 데이터 없으면 기본값으로 채우기
        if (!$has_genre) $movie['genre'] = '추후에 공개';
        if (!$has_actors) $movie['actors'] = '출연진 정보 수집 중';
        if (!$has_plot) $movie['plot'] = '상세 정보를 준비 중입니다.';
        
        $movie['poster_image'] = 'images/default_poster.jpg';
        $movie['booking_link'] = "https://search.naver.com/search.naver?query=" . urlencode($movie['title'] . " 영화상영일정");
        save_movie_data_simple($movie);
        return $movie;
    }

    // 최적 매칭 찾기
    $exact_match = find_best_kmdb_match_simple($kmdb_data['Data'][0]['Result'], $kobisMovie);

    if ($exact_match) {
        // 포스터 이미지 설정
        if (empty($movie['poster_image']) || $movie['poster_image'] == 'images/default_poster.jpg') {
            $movie['poster_image'] = 'images/default_poster.jpg';
            if (isset($exact_match['posters']) && !empty($exact_match['posters'])) {
                $posters = explode('|', $exact_match['posters']);
                if (!empty($posters[0])) {
                    $movie['poster_image'] = $posters[0];
                }
            }
        }

        // 장르 설정 (KOBIS에서 없으면)
        if (!$has_genre) {
            if (isset($exact_match['genre']) && !empty($exact_match['genre'])) {
                $movie['genre'] = $exact_match['genre'];
            } else {
                $movie['genre'] = '추후에 공개';
            }
        }

        // 출연진 설정 (KOBIS에서 없으면)
        if (!$has_actors) {
            if (isset($exact_match['actors']['actor']) && !empty($exact_match['actors']['actor'])) {
                $actors = array();
                $actor_count = 0;
                foreach ($exact_match['actors']['actor'] as $actor) {
                    if ($actor_count >= 5) break;
                    if (isset($actor['actorNm']) && !empty($actor['actorNm'])) {
                        $actors[] = $actor['actorNm'];
                        $actor_count++;
                    }
                }
                if (!empty($actors)) {
                    $movie['actors'] = implode(', ', $actors);
                } else {
                    $movie['actors'] = '출연진 정보 수집 중';
                }
            } else {
                $movie['actors'] = '출연진 정보 수집 중';
            }
        }

        // 감독 정보 보완 (KOBIS에서 없으면)
        if (empty($movie['director'])) {
            if (isset($exact_match['directors']['director'][0]['directorNm'])) {
                $movie['director'] = $exact_match['directors']['director'][0]['directorNm'];
            } else {
                $movie['director'] = '정보 수집 중';
            }
        }

        // 줄거리 설정 (KOBIS에서 없으면)
        if (!$has_plot) {
            if (isset($exact_match['plots']['plot'][0]['plotText']) && !empty($exact_match['plots']['plot'][0]['plotText'])) {
                $movie['plot'] = $exact_match['plots']['plot'][0]['plotText'];
            } else {
                $movie['plot'] = '상세 정보를 준비 중입니다.';
            }
        }

        // 상영시간 설정
        if (empty($movie['runtime'])) {
            if (isset($exact_match['runtime']) && !empty($exact_match['runtime'])) {
                $movie['runtime'] = $exact_match['runtime'];
            }
        }
    } else {
        // 매칭 실패시 기본값
        if (!$has_genre) $movie['genre'] = '추후에 공개';
        if (!$has_actors) $movie['actors'] = '출연진 정보 수집 중';
        if (!$has_plot) $movie['plot'] = '상세 정보를 준비 중입니다.';
        if (empty($movie['director'])) $movie['director'] = '정보 수집 중';
        if (empty($movie['poster_image'])) $movie['poster_image'] = 'images/default_poster.jpg';
    }

    $movie['rating'] = 0;
    $movie['source'] = 'kobis';
    $movie['booking_link'] = "https://search.naver.com/search.naver?query=" . urlencode($movie['title'] . " 영화상영일정");

    // DB 저장
    save_movie_data_simple($movie);

    return $movie;
}

/**
 * 영화 데이터 저장 - 단순화
 */
function save_movie_data_simple($movie)
{
    // 필수 정보 체크
    $has_movie_id = false;
    if (isset($movie['movie_id']) && !empty($movie['movie_id'])) {
        $has_movie_id = true;
    }

    $has_title = false;
    if (isset($movie['title']) && !empty($movie['title'])) {
        $has_title = true;
    }

    if ($has_movie_id && $has_title) {
        try {
            // 저장할 데이터 준비
            $save_movie = array();
            $save_movie['movie_id'] = $movie['movie_id'];
            $save_movie['title'] = $movie['title'];

            if (isset($movie['release_date'])) {
                $save_movie['release_date'] = $movie['release_date'];
            } else {
                $save_movie['release_date'] = null;
            }

            if (isset($movie['audience_count'])) {
                $save_movie['audience_count'] = $movie['audience_count'];
            } else {
                $save_movie['audience_count'] = 0;
            }

            $save_movie['rating'] = 0;

            if (isset($movie['director'])) {
                $save_movie['director'] = $movie['director'];
            } else {
                $save_movie['director'] = '추후에 공개';
            }

            if (isset($movie['actors'])) {
                $save_movie['actors'] = $movie['actors'];
            } else {
                $save_movie['actors'] = '추후에 공개';
            }

            if (isset($movie['genre'])) {
                $save_movie['genre'] = $movie['genre'];
            } else {
                $save_movie['genre'] = '추후에 공개';
            }

            if (isset($movie['plot'])) {
                $save_movie['plot'] = $movie['plot'];
            } else {
                $save_movie['plot'] = '';
            }

            if (isset($movie['runtime'])) {
                $save_movie['runtime'] = $movie['runtime'];
            } else {
                $save_movie['runtime'] = null;
            }

            if (isset($movie['poster_image'])) {
                $save_movie['poster_image'] = $movie['poster_image'];
            } else {
                $save_movie['poster_image'] = 'images/default_poster.jpg';
            }

            $save_movie['source'] = 'kobis';

            $result = save_movie_to_db($save_movie);
            if ($result) {
                error_log("✓ DB 저장 성공: " . $movie['title']);
            }
        } catch (Exception $e) {
            error_log("DB 저장 오류: " . $e->getMessage());
        }
    }
}

/**
 * 현재 상영 중인 영화 가져오기 - 중복 시 KMDB만 사용하도록 수정
 */
function get_combined_now_playing_movies()
{
    // KOBIS에서 영화 데이터 가져오기 (이제 장르/출연진 포함)
    $kobisMovies = kobis_get_now_playing_movies();
    error_log("KOBIS에서 상영 중인 영화 가져옴: " . count($kobisMovies) . "개");

    $movies = array();

    foreach ($kobisMovies as $kobisMovie) {
        // 중복 체크 - 강화된 로직
        if (!is_duplicate_movie($kobisMovie, $movies)) {
            // KOBIS에서 이미 장르/출연진 정보가 있으면 KMDB는 보완용으로만 사용
            $enriched_movie = add_kmdb_info_to_kobis_movie_simple($kobisMovie);
            $movies[] = $enriched_movie;
        } else {
            // 중복이면 KMDB에서만 가져오기
            error_log("중복 감지된 영화: " . $kobisMovie['title'] . " - KMDB에서만 검색");
            $kmdb_movie = get_movie_from_kmdb_only($kobisMovie['title']);
            if ($kmdb_movie && !is_duplicate_movie($kmdb_movie, $movies)) {
                $movies[] = $kmdb_movie;
                error_log("KMDB에서 추가됨: " . $kmdb_movie['title']);
            }
        }
    }

    error_log("중복 제거된 상영 중인 영화: " . count($movies) . "개");
    return $movies;
}

/**
 * 개봉 예정 영화 가져오기 - KMDB만 사용하되 제대로 가져오기
 */
function get_combined_upcoming_movies()
{
    error_log("개봉 예정 영화는 KMDB API만 사용");
    
    $today = date('Y-m-d');
    $all_movies = array();

    // 1. DB에서 기존 개봉예정 영화 확인 (소량만)
    $query = "SELECT * FROM moviesdb 
              WHERE status = 'upcoming' AND release_date > ? 
              ORDER BY release_date ASC LIMIT 20";
    $db_movies = db_select($query, array($today));

    if (!empty($db_movies)) {
        error_log("DB에서 개봉 예정 영화 " . count($db_movies) . "개 발견");
        foreach ($db_movies as $movie) {
            if (!is_duplicate_movie($movie, $all_movies)) {
                $all_movies[] = $movie;
            }
        }
    }

    // 2. KMDB API에서 추가로 가져오기 (DB 데이터가 적으면)
    if (count($all_movies) < 30) {
        error_log("KMDB API에서 개봉 예정 영화 추가 검색");
        
        $today_api = date('Ymd');

        // 앞으로 6개월간의 개봉예정 영화 검색
        for ($month = 0; $month < 6; $month++) {
            $startDate = date('Ymd', strtotime('+' . $month . ' month'));
            $endDate = date('Ymd', strtotime('+' . ($month + 1) . ' month'));

            $base_url = 'http://api.koreafilm.or.kr/openapi-data2/wisenut/search_api/search_json2.jsp';
            $params = array(
                'collection' => 'kmdb_new2',
                'ServiceKey' => KMDB_API_KEY,
                'detail' => 'Y',
                'listCount' => 100,
                'releaseDts' => $startDate,
                'releaseDte' => $endDate,
                'sort' => 'prodYear,1'
            );

            $url = $base_url . '?' . http_build_query($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                error_log("KMDb API 요청 오류: " . $error);
                continue;
            }

            $data = json_decode($response, true);

            if (!isset($data['Data'][0]['Result']) || empty($data['Data'][0]['Result'])) {
                continue;
            }

            foreach ($data['Data'][0]['Result'] as $movie) {
                // 제목 정리
                $clean_title = '';
                if (isset($movie['title'])) {
                    $clean_title = $movie['title'];
                }
                $clean_title = str_replace(array('!HS', '!HE'), '', $clean_title);
                $clean_title = trim($clean_title);

                // 개봉일 처리
                $release_date = null;
                if (isset($movie['repRlsDate']) && !empty($movie['repRlsDate'])) {
                    $release_date = substr($movie['repRlsDate'], 0, 4) . '-' .
                        substr($movie['repRlsDate'], 4, 2) . '-' .
                        substr($movie['repRlsDate'], 6, 2);

                    if (strtotime($release_date) <= strtotime($today)) {
                        continue;
                    }
                } else {
                    continue;
                }

                // 포스터 처리
                $poster_url = 'images/default_poster.jpg';
                if (isset($movie['posters']) && !empty($movie['posters'])) {
                    $posters = explode('|', $movie['posters']);
                    if (!empty($posters[0])) {
                        $poster_url = $posters[0];
                    }
                }

                // 감독 처리
                $director = '';
                if (isset($movie['directors']['director'][0]['directorNm'])) {
                    $director = $movie['directors']['director'][0]['directorNm'];
                }

                // 줄거리 처리
                $plot = '';
                if (isset($movie['plots']['plot'][0]['plotText'])) {
                    $plot = $movie['plots']['plot'][0]['plotText'];
                }

                // 배우 처리
                $actors = array();
                if (isset($movie['actors']['actor'])) {
                    $count = 0;
                    foreach ($movie['actors']['actor'] as $actor) {
                        if ($count >= 5) {
                            break;
                        }
                        if (isset($actor['actorNm'])) {
                            $actors[] = $actor['actorNm'];
                            $count++;
                        }
                    }
                }

                // 장르 처리
                $genre = '';
                if (isset($movie['genre'])) {
                    $genre = $movie['genre'];
                }

                // 영화 데이터 구성
                $movie_data = array(
                    'movie_id' => isset($movie['DOCID']) ? $movie['DOCID'] : '',
                    'title' => $clean_title,
                    'release_date' => $release_date,
                    'director' => $director,
                    'actors' => implode(', ', $actors),
                    'genre' => $genre,
                    'rating' => 0,
                    'plot' => $plot,
                    'runtime' => isset($movie['runtime']) ? $movie['runtime'] : null,
                    'poster_image' => $poster_url,
                    'source' => 'kmdb',
                    'audience_count' => 0,
                    'booking_link' => "https://search.naver.com/search.naver?query=" . urlencode($clean_title . " 영화상영일정")
                );

                // 강화된 중복 체크
                if (!is_duplicate_movie($movie_data, $all_movies)) {
                    // DB에 저장
                    save_movie_to_db($movie_data);
                    $all_movies[] = $movie_data;
                }

                if (count($all_movies) >= 50) {
                    break 2;
                }
            }
            
            // API 호출 간격
            usleep(200000); // 0.2초 대기
        }
    }

    // 개봉일 기준 정렬
    usort($all_movies, function($a, $b) {
        return strtotime($a['release_date']) - strtotime($b['release_date']);
    });

    error_log("최종 개봉 예정 영화: " . count($all_movies) . "개");
    return $all_movies;
}

/**
 * KMDB 개봉예정 함수들 - 기존 유지
 */
function kmdb_get_upcoming_movies_simple()
{
    $today = date('Y-m-d');

    // DB에서 기존 개봉예정 영화 확인
    $query = "SELECT * FROM moviesdb 
              WHERE status = 'upcoming' AND release_date > ? 
              ORDER BY release_date ASC LIMIT 50";
    $movies = db_select($query, array($today));

    if (!empty($movies) && count($movies) > 10) {
        error_log("DB에서 개봉 예정 영화 " . count($movies) . "개 발견");
        return $movies;
    }

    error_log("KMDb API에서 개봉 예정 영화 검색 시작");
    $all_movies = array();

    $today_api = date('Ymd');

    // 앞으로 6개월간의 개봉예정 영화 검색
    for ($month = 0; $month < 6; $month = $month + 1) {
        $startDate = date('Ymd', strtotime('+' . $month . ' month'));
        $endDate = date('Ymd', strtotime('+' . ($month + 1) . ' month'));

        $base_url = 'http://api.koreafilm.or.kr/openapi-data2/wisenut/search_api/search_json2.jsp';
        $params = array(
            'collection' => 'kmdb_new2',
            'ServiceKey' => KMDB_API_KEY,
            'detail' => 'Y',
            'listCount' => 100,
            'releaseDts' => $startDate,
            'releaseDte' => $endDate,
            'sort' => 'prodYear,1'
        );

        $url = $base_url . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("KMDb API 요청 오류: " . $error);
            continue;
        }

        $data = json_decode($response, true);

        if (!isset($data['Data'][0]['Result']) || empty($data['Data'][0]['Result'])) {
            continue;
        }

        foreach ($data['Data'][0]['Result'] as $movie) {
            // 제목 정리
            $clean_title = '';
            if (isset($movie['title'])) {
                $clean_title = $movie['title'];
            }
            $clean_title = str_replace(array('!HS', '!HE'), '', $clean_title);
            $clean_title = trim($clean_title);

            // 개봉일 처리
            $release_date = null;
            if (isset($movie['repRlsDate']) && !empty($movie['repRlsDate'])) {
                $release_date = substr($movie['repRlsDate'], 0, 4) . '-' .
                    substr($movie['repRlsDate'], 4, 2) . '-' .
                    substr($movie['repRlsDate'], 6, 2);

                if (strtotime($release_date) <= strtotime($today)) {
                    continue;
                }
            } else {
                continue;
            }

            // 포스터 처리
            $poster_url = 'images/default_poster.jpg';
            if (isset($movie['posters']) && !empty($movie['posters'])) {
                $posters = explode('|', $movie['posters']);
                if (!empty($posters[0])) {
                    $poster_url = $posters[0];
                }
            }

            // 감독 처리
            $director = '';
            if (isset($movie['directors']['director'][0]['directorNm'])) {
                $director = $movie['directors']['director'][0]['directorNm'];
            }

            // 줄거리 처리
            $plot = '';
            if (isset($movie['plots']['plot'][0]['plotText'])) {
                $plot = $movie['plots']['plot'][0]['plotText'];
            }

            // 배우 처리
            $actors = array();
            if (isset($movie['actors']['actor'])) {
                $count = 0;
                foreach ($movie['actors']['actor'] as $actor) {
                    if ($count >= 5) {
                        break;
                    }
                    if (isset($actor['actorNm'])) {
                        $actors[] = $actor['actorNm'];
                        $count = $count + 1;
                    }
                }
            }

            // 장르 처리
            $genre = '';
            if (isset($movie['genre'])) {
                $genre = $movie['genre'];
            }

            // 영화 데이터 구성
            $movie_data = array(
                'movie_id' => isset($movie['DOCID']) ? $movie['DOCID'] : '',
                'title' => $clean_title,
                'release_date' => $release_date,
                'director' => $director,
                'actors' => implode(', ', $actors),
                'genre' => $genre,
                'rating' => 0,
                'plot' => $plot,
                'runtime' => isset($movie['runtime']) ? $movie['runtime'] : null,
                'poster_image' => $poster_url,
                'source' => 'kmdb'
            );

            // 강화된 중복 체크
            if (!is_duplicate_movie($movie_data, $all_movies)) {
                // DB에 저장
                save_movie_to_db($movie_data);
                $all_movies[] = $movie_data;
            }

            if (count($all_movies) >= 50) {
                break 2;
            }
        }
        
        // API 호출 간격
        usleep(200000); // 0.2초 대기
    }

    // 개봉일 기준 정렬
    usort($all_movies, function($a, $b) {
        return strtotime($a['release_date']) - strtotime($b['release_date']);
    });

    return $all_movies;
}

function get_kmdb_upcoming_movies_simple()
{
    return kmdb_get_upcoming_movies_simple();
}

// 기존 함수명 호환성 유지
function add_kmdb_info_to_kobis_movie($kobis_movie)
{
    return add_kmdb_info_to_kobis_movie_simple($kobis_movie);
}

function get_kmdb_upcoming_movies()
{
    return get_kmdb_upcoming_movies_simple();
}