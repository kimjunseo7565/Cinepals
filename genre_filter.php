<?php
// 현재 선택된 장르 가져오기
$selected_genre = isset($_GET['genre']) ? $_GET['genre'] : '';

// 자주 사용되는 장르 목록 (기존과 동일)
$popular_genres = [
   '드라마', '액션', '코메디', '로맨스', '스릴러', 
   '공포', 'SF', '판타지', '어드벤처', '범죄'
];
?>

<!-- 기존 스타일 정의 그대로 -->
<style>
   /* 사이드바 장르 필터 - !important 추가로 우선순위 강화 */
   .genre_filter {
       position: fixed !important;
       right: 20px !important;
       top: 25% !important;
       width: 160px !important;
       padding: 15px !important;
       background-color: #1e2028 !important;
       border-radius: 5px !important;
       z-index: 999 !important;
       box-shadow: 0 2px 10px rgba(0,0,0,0.3) !important;
       border: 1px solid #444 !important;
   }
   
   .filter_title {
       margin-bottom: 10px !important;
       font-size: 16px !important;
       color: #fff !important;
       text-align: center !important;
       cursor: pointer !important;
       font-weight: bold !important;
   }
   
   /* 장르 버튼 목록 - 초기 상태 */
   .genre_buttons {
       display: none !important;
       flex-direction: column !important;
       gap: 5px !important;
       max-height: 300px !important;
       overflow-y: auto !important;
   }
   
   /* 클릭 시 또는 장르가 선택된 상태에서 표시 */
   .genre_buttons.show {
       display: flex !important;
   }
   
   .genre_btn {
       display: block !important;
       padding: 8px 12px !important;
       background-color: #333 !important;
       color: #fff !important;
       border-radius: 20px !important;
       text-decoration: none !important;
       font-size: 14px !important;
       text-align: center !important;
       transition: all 0.3s ease !important;
       border: none !important;
       cursor: pointer !important;
       margin: 2px 0 !important;
   }
   
   .genre_btn:hover {
       background-color: #555 !important;
       color: #fff !important;
       text-decoration: none !important;
   }
   
   .genre_btn.active {
       background-color: #e50914 !important;
       font-weight: bold !important;
       color: #fff !important;
   }
   
   /* 필터링된 영화 카드 숨김 처리 */
   .movie_card.genre-filtered {
       display: none !important;
   }
   
   /* 모바일에서는 장르 필터 숨김 */
   @media (max-width: 1200px) {
       .genre_filter {
           display: none !important;
       }
   }

   /* 테마별 스타일 - JavaScript로 클래스 추가되면 적용 */
   .genre_filter.theme-light {
       background-color: #ffffff !important;
       border: 1px solid #ddd !important;
       box-shadow: 0 2px 10px rgba(0,0,0,0.15) !important;
   }

   .genre_filter.theme-light .filter_title {
       color: #333 !important;
   }

   .genre_filter.theme-light .genre_btn {
       background-color: #f8f9fa !important;
       color: #333 !important;
       border: 1px solid #ddd !important;
   }

   .genre_filter.theme-light .genre_btn:hover {
       background-color: #e9ecef !important;
       color: #333 !important;
   }

   .genre_filter.theme-light .genre_btn.active {
       background-color: #e50914 !important;
       color: #fff !important;
       border-color: #e50914 !important;
   }

   .genre_filter.theme-dark {
       background-color: #1e2028 !important;
       border: 1px solid #444 !important;
       box-shadow: 0 2px 10px rgba(255,255,255,0.1) !important;
   }
</style>

<!-- 장르 필터 UI -->
<div class="genre_filter">
   <h3 class="filter_title" id="filterTitle">장르 필터</h3>
   <div class="genre_buttons <?php echo (!empty($selected_genre)) ? 'show' : ''; ?>" id="genreButtons">
       <button class="genre_btn <?php echo empty($selected_genre) ? 'active' : ''; ?>" onclick="filterByGenre('')">
           전체
       </button>
       <?php foreach($popular_genres as $genre): ?>
           <button class="genre_btn <?php echo ($selected_genre === $genre) ? 'active' : ''; ?>" onclick="filterByGenre('<?php echo $genre; ?>')">
               <?php echo htmlspecialchars($genre); ?>
           </button>
       <?php endforeach; ?>
   </div>
</div>

<!-- 자바스크립트 - 기존 기능 유지하되 필터링만 단순화 -->
<script>
// 테마 감지 함수 (기존과 동일)
function detectCurrentTheme() {
   const lightTheme = document.getElementById('light-theme-style');
   const darkTheme = document.getElementById('dark-theme-style');
   
   if (lightTheme && lightTheme.media === 'all') {
       return 'light';
   } else {
       return 'dark';
   }
}

// 장르 필터에 테마 클래스 적용 (기존과 동일)
function applyThemeToGenreFilter() {
   const genreFilter = document.querySelector('.genre_filter');
   if (genreFilter) {
       const currentTheme = detectCurrentTheme();
       
       // 기존 테마 클래스 제거
       genreFilter.classList.remove('theme-light', 'theme-dark');
       
       // 현재 테마 클래스 추가
       genreFilter.classList.add('theme-' + currentTheme);
   }
}

// URL 파라미터에서 장르 정보 읽기 및 저장
function saveGenreToUrl(genre) {
   const url = new URL(window.location);
   if (genre && genre !== '') {
       url.searchParams.set('genre', genre);
   } else {
       url.searchParams.delete('genre');
   }
   history.replaceState(null, '', url);
}

// 페이지 로드시 URL에서 장르 복원
function restoreGenreFromUrl() {
   const urlParams = new URLSearchParams(window.location.search);
   const savedGenre = urlParams.get('genre');
   
   if (savedGenre) {
       // 장르 버튼 펼치기
       const genreButtons = document.getElementById('genreButtons');
       if (genreButtons) {
           genreButtons.classList.add('show');
       }
       
       // 해당 장르로 필터링 실행
       filterByGenre(savedGenre);
   }
}

// 장르별 필터링 함수 - 단순화된 버전
function filterByGenre(selectedGenre) {
   // URL에 장르 정보 저장
   saveGenreToUrl(selectedGenre);
   
   const movieCards = document.querySelectorAll('.movie_card');
   let visibleCount = 0;
   
   movieCards.forEach(card => {
       let showMovie = false;
       
       if(!selectedGenre || selectedGenre === '') {
           // 전체 보기
           showMovie = true;
       } else {
           // 영화 제목에서 장르 키워드 찾기
           const movieTitle = card.querySelector('.movie_title, .movie-title, h3, h2, .carousel-title');
           const movieGenreAttr = card.getAttribute('data-genre') || '';
           
           let movieText = '';
           if (movieTitle) {
               movieText = movieTitle.textContent.toLowerCase();
           }
           
           // 간단한 키워드 매칭
           const lowerGenre = selectedGenre.toLowerCase();
           
           if (movieText.includes(lowerGenre) || movieGenreAttr.toLowerCase().includes(lowerGenre)) {
               showMovie = true;
           }
       }
       
       if(showMovie) {
           card.classList.remove('genre-filtered');
           card.style.display = 'block';
           visibleCount++;
       } else {
           card.classList.add('genre-filtered');
           card.style.display = 'none';
       }
   });
   
   // 활성 버튼 업데이트
   document.querySelectorAll('.genre_btn').forEach(btn => {
       btn.classList.remove('active');
   });
   
   // 클릭된 버튼 활성화
   if (selectedGenre && selectedGenre !== '') {
       const targetBtn = document.querySelector(`.genre_btn[onclick="filterByGenre('${selectedGenre}')"]`);
       if (targetBtn) {
           targetBtn.classList.add('active');
       }
   } else {
       const allBtn = document.querySelector('.genre_btn[onclick="filterByGenre(\'\')"]');
       if (allBtn) allBtn.classList.add('active');
   }
   
   // 결과 없음 처리
   const movieGrid = document.querySelector('.movie_grid');
   const existingNoResults = movieGrid ? movieGrid.querySelector('.no_results') : null;
   
   if (existingNoResults) {
       existingNoResults.remove();
   }
   
   if (visibleCount === 0 && selectedGenre && selectedGenre !== '' && movieGrid) {
       const noResults = document.createElement('div');
       noResults.className = 'no_results';
       noResults.style.gridColumn = 'span 5';
       noResults.style.textAlign = 'center';
       noResults.style.padding = '50px 0';
       noResults.style.color = '#aaa';
       noResults.innerHTML = '<p>' + selectedGenre + ' 장르의 영화가 없습니다.</p>';
       movieGrid.appendChild(noResults);
   }
}

document.addEventListener('DOMContentLoaded', function() {
   // 초기 테마 적용 (기존과 동일)
   applyThemeToGenreFilter();
   
   // 테마 변경 감지 (기존과 동일)
   const lightTheme = document.getElementById('light-theme-style');
   const darkTheme = document.getElementById('dark-theme-style');
   
   if (lightTheme && darkTheme) {
       const observer = new MutationObserver(function(mutations) {
           mutations.forEach(function(mutation) {
               if (mutation.type === 'attributes' && mutation.attributeName === 'media') {
                   applyThemeToGenreFilter();
               }
           });
       });
       
       // 테마 변경 감지 시작
       observer.observe(lightTheme, { attributes: true });
       observer.observe(darkTheme, { attributes: true });
   }
   
   // 장르 필터 제목 클릭 시 목록 토글 (기존 기능 유지)
   const filterTitle = document.getElementById('filterTitle');
   const genreButtons = document.getElementById('genreButtons');
   
   if(filterTitle && genreButtons) {
       // 제목 클릭 시 토글
       filterTitle.addEventListener('click', function(event) {
           event.stopPropagation();
           genreButtons.classList.toggle('show');
       });
   }
   
   // 장르가 선택된 경우 박스를 펼친 상태로 유지 (기존 기능)
   const selectedGenre = '<?php echo $selected_genre; ?>';
   if(selectedGenre && genreButtons) {
       genreButtons.classList.add('show');
   }
   
   // 페이지 로드시 장르 상태 복원
   restoreGenreFromUrl();
});
</script>