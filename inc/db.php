<?php
// 전역 연결 변수를 null로 초기화
$con = null;

// DB 연결 함수 - 매번 새로운 연결 생성
function db_get_connection()
{
    return mysqli_connect("localhost", "root", "", "moviedb");
}

// SELECT 쿼리 실행 함수 - 완전히 독립적인 연결 사용
function db_select($query, $params = array())
{
    $con = mysqli_connect("localhost", "root", "", "moviedb");

    if (!$con) {
        return array();
    }

    $stmt = mysqli_prepare($con, $query);
    if ($stmt === false) {
        mysqli_close($con);
        return array();
    }

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $data;
}

// INSERT 쿼리 실행 함수 - 독립적인 연결
function db_insert($query, $params = array())
{
    $con = mysqli_connect("localhost", "root", "", "moviedb");

    if (!$con) {
        return false;
    }

    if (!empty($params)) {
        for ($i = 0; $i < count($params); $i++) {
            $query = str_replace('?', "'" . mysqli_real_escape_string($con, $params[$i]) . "'", $query);
        }
    }

    $result = mysqli_query($con, $query);

    if ($result) {
        $insert_id = mysqli_insert_id($con);
        mysqli_close($con);
        return $insert_id;
    } else {
        mysqli_close($con);
        return false;
    }
}

// UPDATE, DELETE 쿼리 실행 함수 - 독립적인 연결
function db_update_delete($query, $params = array())
{
    $con = mysqli_connect("localhost", "root", "", "moviedb");

    if (!$con) {
        return false;
    }

    if (!empty($params)) {
        for ($i = 0; $i < count($params); $i++) {
            $query = str_replace('?', "'" . mysqli_real_escape_string($con, $params[$i]) . "'", $query);
        }
    }

    $result = mysqli_query($con, $query);
    mysqli_close($con);
    return $result;
}

// 영화 상태 결정 함수
function get_simple_movie_status($movie_data)
{
    $today = date('Y-m-d');
    $release_date = isset($movie_data['release_date']) ? $movie_data['release_date'] : '';

    if ($release_date == '') {
        return 'unknown';
    }

    if ($release_date > $today) {
        return 'upcoming';
    } else {
        return 'now_playing';
    }
}

// 영화 정보 저장 함수 - 독립적인 연결
function save_movie_to_db($movie_data)
{
    $con = mysqli_connect("localhost", "root", "", "moviedb");

    if (!$con) {
        return false;
    }

    if (
        !isset($movie_data['movie_id']) || !isset($movie_data['title']) ||
        empty($movie_data['movie_id']) || empty($movie_data['title'])
    ) {
        mysqli_close($con);
        return false;
    }

    $movie_id = mysqli_real_escape_string($con, $movie_data['movie_id']);

    $check_sql = "SELECT * FROM moviesdb WHERE movie_id = '$movie_id'";
    $existing = mysqli_query($con, $check_sql);

    if (mysqli_num_rows($existing) == 0) {
        $title = mysqli_real_escape_string($con, $movie_data['title']);
        $release_date = isset($movie_data['release_date']) && $movie_data['release_date'] ?
            "'" . mysqli_real_escape_string($con, $movie_data['release_date']) . "'" : 'NULL';
        $audience_count = isset($movie_data['audience_count']) ? (int)$movie_data['audience_count'] : 0;
        $director = isset($movie_data['director']) ?
            mysqli_real_escape_string($con, $movie_data['director']) : '';
        $actors = isset($movie_data['actors']) ?
            mysqli_real_escape_string($con, $movie_data['actors']) : '';
        $genre = isset($movie_data['genre']) ?
            mysqli_real_escape_string($con, $movie_data['genre']) : '';
        $plot = isset($movie_data['plot']) ?
            mysqli_real_escape_string($con, $movie_data['plot']) : '';
        $runtime = isset($movie_data['runtime']) && $movie_data['runtime'] ?
            (int)$movie_data['runtime'] : 'NULL';
        $poster_image = isset($movie_data['poster_image']) ?
            mysqli_real_escape_string($con, $movie_data['poster_image']) : 'images/default_poster.jpg';
        $source = isset($movie_data['source']) ?
            mysqli_real_escape_string($con, $movie_data['source']) : 'kobis';
        $status = get_simple_movie_status($movie_data);

        $insert_sql = "INSERT INTO moviesdb (
            movie_id, title, release_date, audience_count, rating, 
            director, actors, genre, plot, runtime, poster_image, source, status, created_at
        ) VALUES (
            '$movie_id', '$title', $release_date, $audience_count, 0,
            '$director', '$actors', '$genre', '$plot', $runtime, '$poster_image', '$source', '$status', NOW()
        )";

        $result = mysqli_query($con, $insert_sql);
        mysqli_close($con);
        return $result;
    } else {
        $old_movie = mysqli_fetch_assoc($existing);
        $updates = array();

        if (
            $old_movie['poster_image'] == 'images/default_poster.jpg' &&
            isset($movie_data['poster_image']) &&
            $movie_data['poster_image'] &&
            $movie_data['poster_image'] != 'images/default_poster.jpg'
        ) {
            $poster_escaped = mysqli_real_escape_string($con, $movie_data['poster_image']);
            $updates[] = "poster_image = '$poster_escaped'";
        }

        if ((empty($old_movie['director']) || $old_movie['director'] == '추후에 공개') &&
            isset($movie_data['director']) && !empty($movie_data['director'])
        ) {
            $director_escaped = mysqli_real_escape_string($con, $movie_data['director']);
            $updates[] = "director = '$director_escaped'";
        }

        if ((empty($old_movie['genre']) || $old_movie['genre'] == '추후에 공개') &&
            isset($movie_data['genre']) && !empty($movie_data['genre'])
        ) {
            $genre_escaped = mysqli_real_escape_string($con, $movie_data['genre']);
            $updates[] = "genre = '$genre_escaped'";
        }

        if ((empty($old_movie['actors']) || $old_movie['actors'] == '추후에 공개') &&
            isset($movie_data['actors']) && !empty($movie_data['actors'])
        ) {
            $actors_escaped = mysqli_real_escape_string($con, $movie_data['actors']);
            $updates[] = "actors = '$actors_escaped'";
        }

        if ($old_movie['plot'] == '' && isset($movie_data['plot']) && $movie_data['plot']) {
            $plot_escaped = mysqli_real_escape_string($con, $movie_data['plot']);
            $updates[] = "plot = '$plot_escaped'";
        }

        if (
            empty($old_movie['runtime']) &&
            isset($movie_data['runtime']) && !empty($movie_data['runtime'])
        ) {
            $runtime = (int)$movie_data['runtime'];
            $updates[] = "runtime = $runtime";
        }

        if (
            isset($movie_data['audience_count']) &&
            $movie_data['audience_count'] > $old_movie['audience_count']
        ) {
            $audience_count = (int)$movie_data['audience_count'];
            $updates[] = "audience_count = $audience_count";
        }

        if (!empty($updates)) {
            $update_sql = "UPDATE moviesdb SET " . implode(", ", $updates) . " WHERE movie_id = '$movie_id'";
            $result = mysqli_query($con, $update_sql);
            mysqli_close($con);
            return $result;
        }

        mysqli_close($con);
        return true;
    }
}

// DB에서 영화 데이터 가져오기 - 독립적인 연결
function get_movies_from_db($status)
{
    $con = mysqli_connect("localhost", "root", "", "moviedb");

    if (!$con) {
        return array();
    }

    if ($status == 'now_playing') {
        $sql = "SELECT * FROM moviesdb WHERE status = 'now_playing' ORDER BY audience_count DESC, release_date DESC";
    } else if ($status == 'upcoming') {
        // ⭐ 빈 날짜/NULL은 맨 뒤로!
        $sql = "SELECT * FROM moviesdb 
            WHERE status = 'upcoming' 
            ORDER BY 
                CASE 
                    WHEN release_date IS NULL OR release_date = '' THEN 1
                    ELSE 0
                END,
                release_date ASC";
    } else {
        $sql = "SELECT * FROM moviesdb WHERE status IN ('now_playing', 'upcoming') ORDER BY release_date DESC";
    }

    $result = mysqli_query($con, $sql);
    $movies = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $movies[] = $row;
        }
    }

    mysqli_close($con);
    return $movies;
}

// 영화 데이터 갱신 체크 - 완전히 비활성화
function check_movie_update_needed()
{
    return false;
}

// 하위 호환성을 위해 전역 연결도 생성 (기존 파일들을 위해)
$con = mysqli_connect("localhost", "root", "", "moviedb");
