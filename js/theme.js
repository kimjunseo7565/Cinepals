// theme.js - 전체 코드

function toggleTheme() {
    var currentTheme = localStorage.getItem('theme') || 'dark';
    var lightTheme = document.getElementById('light-theme-style');
    var darkTheme = document.getElementById('dark-theme-style');
    var themeBtn = document.getElementById('themeBtn');
    
    if (currentTheme === 'light') {
        // 다크모드로
        lightTheme.media = 'none';
        darkTheme.media = 'all';
        themeBtn.innerHTML = '<i class="fas fa-sun"></i> 라이트모드';
        localStorage.setItem('theme', 'dark');
    } else {
        // 라이트모드로
        lightTheme.media = 'all';
        darkTheme.media = 'none';
        themeBtn.innerHTML = '<i class="fas fa-moon"></i> 다크모드';
        localStorage.setItem('theme', 'light');
    }
}

// 버튼 업데이트 함수
function updateThemeButton() {
    var currentTheme = localStorage.getItem('theme') || 'dark';
    var themeBtn = document.getElementById('themeBtn');
    
    if (themeBtn) {
        if (currentTheme === 'light') {
            themeBtn.innerHTML = '<i class="fas fa-moon"></i> 다크모드';
        } else {
            themeBtn.innerHTML = '<i class="fas fa-sun"></i> 라이트모드';
        }
        themeBtn.onclick = toggleTheme;
    }
}

// DOM 준비 후 실행
document.addEventListener('DOMContentLoaded', updateThemeButton);

// 뒤로가기 시 버튼도 업데이트
window.addEventListener('pageshow', updateThemeButton);