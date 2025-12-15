// 장르 선택 상자가 변경될 때 실행될 기능
document.querySelector('.filter_select').addEventListener('change', function() {
    // 어떤 장르가 선택됐는지 가져오기 (소문자로 변환)
    let selectedGenre = this.value.toLowerCase();

    // 모든 영화 카드 가져오기
    let movies = document.querySelectorAll('.movie_card');

    // 각 영화 카드를 확인
    movies.forEach(function(movie) {
        // 영화의 장르 정보 가져오기 (소문자로 변환)
        let movieGenre = movie.querySelector('.movie_meta:nth-child(3)').textContent.toLowerCase();

        // 필터링 (includes로 부분 일치 검사)
        if(selectedGenre === '' || movieGenre.includes(selectedGenre)) {
            movie.style.display = 'block';  // 보여주기
        } else {
            movie.style.display = 'none';   // 숨기기
        }
    });
});