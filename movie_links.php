<?php

/**
 * 영화관 영화 예매 링크 생성 (최종 개선 버전)
 * 롯데시네마, CGV, 메가박스 예매 링크 생성
 */

/**
 * 롯데시네마 영화 목록 링크 생성
 * 상영 중인 영화 목록 페이지로 이동
 * @return string 영화 목록 페이지 링크
 */
function get_lotte_cinema_link()
{
    // 롯데시네마 영화 목록 페이지 URL 반환
    return "https://www.lottecinema.co.kr/NLCHS/Movie/List?flag=1";
}

/**
 * CGV 영화 검색 URL 생성 (통합 검색)
 * @param string $movie_title 영화 제목
 * @return string 영화 검색 URL
 */
function get_cgv_link($movie_title)
{
    // 전체 제목 그대로 사용
    return "https://cgv.co.kr/tme/itgrSrch?swrd=" . urlencode($movie_title);
}

/**
 * 메가박스 영화 검색 URL 생성
 * @param string $movie_title 영화 제목
 * @return string 영화 검색 URL
 */
function get_megabox_link($movie_title)
{
    $search_title = $movie_title;

    // 극장판 단어 제거
    $search_title = str_replace('극장판', '', $search_title);

    // 콜론(:)과 그 뒤의 부제목 제거
    if (mb_strpos($search_title, ':', 0, 'UTF-8') !== false) {
        $search_title = trim(mb_substr($search_title, 0, mb_strpos($search_title, ':', 0, 'UTF-8'), 'UTF-8'));
    }

    // 콤마(,)와 그 뒤의 부제목 제거
    if (mb_strpos($search_title, ',', 0, 'UTF-8') !== false) {
        $search_title = trim(mb_substr($search_title, 0, mb_strpos($search_title, ',', 0, 'UTF-8'), 'UTF-8'));
    }

    // 불필요한 특수문자 및 괄호 제거
    $search_title = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $search_title);

    // 앞뒤 공백 제거 및 중복 공백 제거
    $search_title = trim(preg_replace('/\s+/', ' ', $search_title));

    return "https://www.megabox.co.kr/movie?searchText=" . urlencode($search_title);
}
