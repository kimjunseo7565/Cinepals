// 지도 및 서비스 전역 변수
let map;
let ps;
let geocoder;
let markers = [];
let currentLocation = null;
let allTheaters = []; // 전체 영화관 목록 저장
let favoriteTheaters = []; // DB에서 로드한 즐겨찾기 목록
let currentLocationMarker = null; // 현재 위치 마커 (중복 방지용)
let currentSortType = 'distance'; // 현재 정렬 타입 (distance 또는 name)

const FAVORITE_API_URL = './inc/fav_theater.php';

let isLocating = false;

// 작은따옴표 이스케이프 헬퍼 함수
function escapeQuotes(str) {
    return str ? str.replace(/'/g, "\\'") : '';
}

function getCurrentLocation() {
    getCurrentLocationWrapped();
}

// 메인 지도 열기 함수
function openKakaoMap() {
    const modal = document.getElementById('mapModal');
    modal.style.display = 'flex';

    // 모달 배경 클릭 시 닫기 (이벤트 중복 방지)
    modal.onclick = function (e) {
        if (e.target === modal) {
            closeKakaoMap();
        }
    };

    // ESC 키로 닫기
    document.onkeydown = function (e) {
        if (e.key === 'Escape') {
            closeKakaoMap();
        }
    };

    setTimeout(() => {
        if (!map) {
            initializeMap();
        }
        createIntegratedInterface();

        // 지도 크기 재조정 (중요!)
        if (map) {
            map.relayout();
        }
    }, 200);
}

// 통합 인터페이스 생성 (모든 새 기능 포함)
function createIntegratedInterface() {
    const mapContainer = document.getElementById('map').parentElement;

    // 기존 컨트롤 제거
    const existingControls = document.querySelectorAll('.map-control-panel, .theater-sidebar');
    existingControls.forEach(control => control.remove());

    // 메인 컨테이너 스타일 개선
    mapContainer.style.display = 'flex';
    mapContainer.style.width = '95vw';
    mapContainer.style.height = '85vh';
    mapContainer.style.maxWidth = '1400px';
    mapContainer.style.borderRadius = '12px';
    mapContainer.style.overflow = 'hidden';
    mapContainer.style.boxShadow = '0 10px 40px rgba(0,0,0,0.3)';
    mapContainer.style.backgroundColor = '#ffffff';

    // 지도 컨테이너 조정
    const mapElement = document.getElementById('map');
    mapElement.style.flex = '1';
    mapElement.style.height = '100%';
    mapElement.style.position = 'relative';

    // 향상된 상단 컨트롤 패널 생성
    const topControlPanel = document.createElement('div');
    topControlPanel.className = 'map-control-panel';
    topControlPanel.style.cssText = `
        position: absolute;
        top: 20px;
        left: 20px;
        right: 20px;
        z-index: 9999;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        border: 1px solid rgba(255,255,255,0.3);
        display: flex;
        gap: 20px;
        align-items: center;
        flex-wrap: wrap;
    `;

    topControlPanel.innerHTML = `
        <div style="flex: 1; min-width: 320px;">
            <!-- 검색 영역 -->
            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <div style="flex: 1; position: relative;">
                    <input type="text" id="locationSearch" 
                           placeholder="지역명을 입력하세요 (예: 강남역, 홍대, 명동)" 
                           style="width: 100%; 
                                  padding: 12px 48px 12px 16px; 
                                  border: 2px solid #e8eaed; 
                                  border-radius: 12px; 
                                  font-size: 15px; 
                                  outline: none;
                                  transition: all 0.2s ease;
                                  background: #fff;
                                  box-shadow: 0 2px 8px rgba(0,0,0,0.04);"
                           onfocus="this.style.borderColor='#1a73e8'; this.style.boxShadow='0 4px 16px rgba(26,115,232,0.15)'; showSearchHistory();"
                           onblur="setTimeout(hideSearchHistory, 200);">
                    
                    <!-- 최근 검색 드롭다운 -->
                    <div id="searchHistory" style="position: absolute; 
                                                   top: 100%; 
                                                   left: 0; 
                                                   right: 0; 
                                                   background: white; 
                                                   border: 1px solid #e8eaed; 
                                                   border-radius: 12px; 
                                                   margin-top: 4px;
                                                   display: none;
                                                   z-index: 1001;
                                                   box-shadow: 0 4px 16px rgba(0,0,0,0.1);
                                                   max-height: 200px;
                                                   overflow-y: auto;">
                    </div>
                    
                    <button onclick="searchByLocation()" 
                            style="position: absolute; 
                                   right: 6px; 
                                   top: 50%; 
                                   transform: translateY(-50%);
                                   width: 36px; 
                                   height: 36px;
                                   background: #1a73e8; 
                                   border: none; 
                                   border-radius: 10px; 
                                   color: white; 
                                   cursor: pointer;
                                   display: flex;
                                   align-items: center;
                                   justify-content: center;
                                   transition: all 0.2s ease;"
                            onmouseover="this.style.background='#1557b0'"
                            onmouseout="this.style.background='#1a73e8'">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                        </svg>
                    </button>
                </div>
                
                <button onclick="getCurrentLocationWrapped()"
                        style="padding: 12px 20px;
                               background: #34a853; 
                               color: white; 
                               border: none; 
                               border-radius: 12px; 
                               cursor: pointer; 
                               font-weight: 500;
                               font-size: 14px;
                               display: flex;
                               align-items: center;
                               gap: 8px;
                               transition: all 0.2s ease;
                               box-shadow: 0 2px 8px rgba(52,168,83,0.2);
                               white-space: nowrap;"
                        onmouseover="this.style.background='#2d8e47'"
                        onmouseout="this.style.background='#34a853'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3A8.994 8.994 0 0 0 13 3.06V1h-2v2.06A8.994 8.994 0 0 0 3.06 11H1v2h2.06A8.994 8.994 0 0 0 11 20.94V23h2v-2.06A8.994 8.994 0 0 0 20.94 13H23v-2h-2.06zM12 19c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/>
                    </svg>
                    내 위치
                </button>
            </div>
            
            <!-- 필터 및 정렬 영역 -->
            <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                <span style="font-weight: 600; color: #5f6368; font-size: 14px;">영화관:</span>
                <label style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: linear-gradient(135deg, #fff, #f8f9fa); border-radius: 20px; cursor: pointer; font-size: 13px; border: 1px solid #e8eaed; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <input type="checkbox" id="cgvFilter" checked onchange="filterTheaters()" style="margin: 0; width: 16px; height: 16px; accent-color: #fb4357;">
                    <span style="color: #fb4357; font-weight: 700;">CGV</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: linear-gradient(135deg, #fff, #f8f9fa); border-radius: 20px; cursor: pointer; font-size: 13px; border: 1px solid #e8eaed; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <input type="checkbox" id="lotteFilter" checked onchange="filterTheaters()" style="margin: 0; width: 16px; height: 16px; accent-color: #e50914;">
                    <span style="color: #e50914; font-weight: 700;">롯데시네마</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: linear-gradient(135deg, #fff, #f8f9fa); border-radius: 20px; cursor: pointer; font-size: 13px; border: 1px solid #e8eaed; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <input type="checkbox" id="megaboxFilter" checked onchange="filterTheaters()" style="margin: 0; width: 16px; height: 16px; accent-color: #5c3098;">
                    <span style="color: #5c3098; font-weight: 700;">메가박스</span>
                </label>
                
                <span style="color: #d1d5db;">|</span>
                
                <!-- 정렬 옵션 -->
                <span style="font-weight: 600; color: #5f6368; font-size: 14px;">정렬:</span>
                <button onclick="sortTheaters('distance')" id="sortDistance" 
                        style="padding: 6px 12px; background: #f3f4f6; color: #374151; border: none; border-radius: 16px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s;">
                    거리순
                </button>
                <button onclick="sortTheaters('name')" id="sortName"
                        style="padding: 6px 12px; background: #f3f4f6; color: #374151; border: none; border-radius: 16px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s;">
                    이름순
                </button>
            </div>
        </div>
        
        <!-- 우측 정보 및 닫기 버튼 -->
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="text-align: center; padding: 16px 20px; background: linear-gradient(135deg, #f8f9fa, #e8f0fe); border-radius: 12px; border: 1px solid #e8eaed; min-width: 120px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <div style="font-size: 24px; font-weight: 700; color: #1a73e8; margin-bottom: 4px;" id="theaterCount">0</div>
                <div style="font-size: 12px; color: #5f6368; font-weight: 500;">개 영화관</div>
            </div>
            <button onclick="closeKakaoMap()" style="width: 44px; height: 44px; background: linear-gradient(135deg, #ea4335, #d33b2c); color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 18px; font-weight: bold; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(234,67,53,0.3); position: relative; z-index: 10000; pointer-events: auto;">
                ✕
            </button>
        </div>
    `;

    mapElement.appendChild(topControlPanel);

    // 향상된 사이드바 생성
    const sidebar = document.createElement('div');
    sidebar.className = 'theater-sidebar';
    sidebar.style.cssText = `
        width: 350px;
        height: 100%;
        background: #ffffff;
        border-left: 1px solid #e5e5e5;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    `;

    sidebar.innerHTML = `
        <div style="padding: 20px; border-bottom: 1px solid #e5e5e5; background: #f8f9fa;">
            <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px; font-weight: 700;">🎬 영화관</h3>
            
            <!-- 탭 버튼 -->
            <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                <button id="tabNearby" onclick="switchTheaterTab('nearby')" 
                        style="flex: 1; padding: 10px; background: #1a73e8; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.2s;">
                    주변 영화관
                </button>
                <button id="tabFavorites" onclick="switchTheaterTab('favorites')" 
                        style="flex: 1; padding: 10px; background: #f3f4f6; color: #666; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.2s;">
                    즐겨찾기
                </button>
            </div>
            
            <p id="tabDescription" style="margin: 0; color: #666; font-size: 13px;">지도에서 영화관을 클릭하거나 아래 목록에서 선택하세요</p>
        </div>
        
        <!-- 주변 영화관 목록 -->
        <div id="theaterList" style="flex: 1; padding: 15px; display: block;">
            <div style="text-align: center; padding: 40px 20px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;">🎬</div>
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">영화관을 검색해주세요</div>
                <div style="font-size: 14px;">지역을 검색하면 주변 영화관이 표시됩니다</div>
            </div>
        </div>
        
        <!-- 즐겨찾기 목록 -->
        <div id="favoritesList" style="flex: 1; padding: 15px; display: none;">
            <div style="text-align: center; padding: 40px 20px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;">⭐</div>
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">즐겨찾기한 영화관이 없습니다</div>
                <div style="font-size: 14px;">영화관 옆 별 아이콘을 눌러 즐겨찾기하세요</div>
            </div>
        </div>
`;

    mapContainer.appendChild(sidebar);

    // 검색창 이벤트 설정
    setupSearchEvents();

    // 지도 레이아웃 강제 재조정 추가
    setTimeout(() => {
        if (map) {
            map.relayout();
        }
    }, 300);
}

// 검색 이벤트 설정
function setupSearchEvents() {
    const locationInput = document.getElementById('locationSearch');
    if (locationInput) {
        locationInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                searchByLocation();
            }
        });

        // 검색 기록 이벤트 추가
        locationInput.addEventListener('focus', function () {
            this.style.borderColor = '#1a73e8';
            this.style.boxShadow = '0 4px 16px rgba(26,115,232,0.15)';
            showSearchHistory();
        });

        locationInput.addEventListener('blur', function () {
            setTimeout(hideSearchHistory, 200);
        });
    }

    // 내 위치 버튼 찾기
    setTimeout(() => {
        const allButtons = document.querySelectorAll('button');

        allButtons.forEach((btn, index) => {
            if (btn.textContent && btn.textContent.trim() === '내 위치') {
                btn.onclick = function (e) {
                    e.preventDefault();
                    getCurrentLocationWrapped();
                };
            }
        });
    }, 1000);
}

// 최근 검색 기록 관리
function saveSearchHistory(query) {
    let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
    // 중복 제거
    history = history.filter(item => item !== query);
    // 최신 검색어를 맨 앞에 추가
    history.unshift(query);
    // 최대 5개까지만 저장
    if (history.length > 5) {
        history = history.slice(0, 5);
    }
    localStorage.setItem('searchHistory', JSON.stringify(history));
}

function showSearchHistory() {
    const historyDiv = document.getElementById('searchHistory');
    const history = JSON.parse(localStorage.getItem('searchHistory')) || [];

    if (history.length === 0) {
        historyDiv.style.display = 'none';
        return;
    }

    let historyHTML = '';
    history.forEach((item, index) => {
        historyHTML += `
            <div onclick="selectSearchHistory('${item}')" 
                 style="padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; transition: background 0.1s;"
                 onmouseover="this.style.background='#f8f9fa'" 
                 onmouseout="this.style.background='white'">
                <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#9ca3af">
                        <path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18z"/>
                    </svg>
                    <span style="font-size: 14px; color: #374151;">${item}</span>
                </div>
                <button onclick="event.stopPropagation(); removeSearchHistory(${index})" 
                        style="background: none; border: none; cursor: pointer; padding: 4px; color: #9ca3af; font-size: 16px; transition: color 0.2s;"
                        onmouseover="this.style.color='#ef4444'" 
                        onmouseout="this.style.color='#9ca3af'"
                        title="삭제">×</button>
            </div>
        `;
    });

    historyDiv.innerHTML = historyHTML;
    historyDiv.style.display = 'block';
}

function removeSearchHistory(index) {
    let history = JSON.parse(localStorage.getItem('searchHistory')) || [];
    history.splice(index, 1); // 해당 인덱스 항목 삭제
    localStorage.setItem('searchHistory', JSON.stringify(history));
    showSearchHistory(); // 업데이트된 목록 다시 표시
}

function hideSearchHistory() {
    document.getElementById('searchHistory').style.display = 'none';
}

function selectSearchHistory(query) {
    document.getElementById('locationSearch').value = query;
    hideSearchHistory();
    searchByLocationWithQuery(query);
}

// 즐겨찾기 관리 함수들
function getFavoriteTheaters() {
    return JSON.parse(localStorage.getItem('favoriteTheaters') || '[]');
}

function addToFavorites(theaterId, theaterName) {
    let favorites = getFavoriteTheaters();
    const favorite = { id: theaterId, name: theaterName, addedAt: Date.now() };

    // 중복 체크
    if (!favorites.find(fav => fav.id === theaterId)) {
        favorites.push(favorite);
        localStorage.setItem('favoriteTheaters', JSON.stringify(favorites));
        updateTheaterList(allTheaters, currentLocation); // 목록 다시 그리기
    }
}

function removeFromFavorites(theaterId) {
    let favorites = getFavoriteTheaters();
    favorites = favorites.filter(fav => fav.id !== theaterId);
    localStorage.setItem('favoriteTheaters', JSON.stringify(favorites));
    updateTheaterList(allTheaters, currentLocation); // 목록 다시 그리기
}

function isFavoriteTheater(theaterId) {
    const parts = theaterId.split('_');
    if (parts.length < 3) return false;

    const theaterName = parts[0];
    const x = parseFloat(parts[1]);
    const y = parseFloat(parts[2]);

    return favoriteTheaters.some(fav =>
        fav.theater_place_name === theaterName &&
        Math.abs(fav.theater_x - x) < 0.0001 &&
        Math.abs(fav.theater_y - y) < 0.0001
    );
}

// 현재 위치 가져오기
let watchId = null; // watchPosition ID 저장

function getCurrentLocationWrapped() {
    if (isLocating) return; // 중복 실행 차단
    isLocating = true;

    if (navigator.geolocation) {
        // 기존 watch 중지
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }

        let hasReceivedAccuratePosition = false;

        // watchPosition으로 정확한 위치를 기다림
        watchId = navigator.geolocation.watchPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy; // 정확도 (미터)

                console.log('위치 정확도:', accuracy, '미터');

                const coords = new kakao.maps.LatLng(lat, lng);

                // 항상 최신 위치로 업데이트
                map.setCenter(coords);
                map.setLevel(6);
                currentLocation = coords;
                addCurrentLocationMarker(coords);

                // 정확도가 100m 이하이거나 이미 정확한 위치를 받았으면 watch 중지
                if (accuracy <= 100 && !hasReceivedAccuratePosition) {
                    hasReceivedAccuratePosition = true;
                    searchNearbyTheaters(coords);

                    // watch 중지
                    if (watchId !== null) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                    isLocating = false;
                }
            },
            (error) => {
                alert('위치 정보를 가져올 수 없습니다. 지역명으로 검색해주세요.');
                console.error('위치 오류:', error);

                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
                isLocating = false;
            },
            {
                enableHighAccuracy: true,  // GPS 사용하여 정확도 높임
                timeout: 15000,            // 15초 타임아웃
                maximumAge: 0              // 캐시된 위치 사용 안함
            }
        );

        // 최대 15초 후에는 강제로 중지하고 현재 위치 사용
        setTimeout(() => {
            if (watchId !== null && !hasReceivedAccuratePosition) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;

                if (currentLocation) {
                    searchNearbyTheaters(currentLocation);
                }
                isLocating = false;
                console.log('타임아웃: 현재까지의 최선의 위치를 사용합니다.');
            }
        }, 15000);

    } else {
        alert('이 브라우저는 위치 서비스를 지원하지 않습니다.');
        isLocating = false;
    }
}

// 현재 위치 마커 추가
function addCurrentLocationMarker(position) {
    // 기존 현재 위치 마커가 있으면 제거
    if (currentLocationMarker) {
        currentLocationMarker.setMap(null);
        currentLocationMarker = null;
    }

    const markerImage = new kakao.maps.MarkerImage(
        'data:image/svg+xml,' + encodeURIComponent(`
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
                <circle cx="15" cy="15" r="12" fill="#007bff" stroke="white" stroke-width="3"/>
                <circle cx="15" cy="15" r="6" fill="white"/>
            </svg>
        `),
        new kakao.maps.Size(30, 30),
        { offset: new kakao.maps.Point(15, 15) }
    );

    currentLocationMarker = new kakao.maps.Marker({
        map: map,
        position: position,
        image: markerImage,
        zIndex: 999
    });

    const infowindow = new kakao.maps.InfoWindow({
        content: '<div style="padding: 10px; text-align: center; font-weight: bold; color: #007bff;">📍 현재 위치</div>'
    });

    kakao.maps.event.addListener(currentLocationMarker, 'click', function () {
        infowindow.open(map, currentLocationMarker);
    });
}

// 지도 초기화
function initializeMap() {
    try {
        const center = new kakao.maps.LatLng(37.5665, 126.9780); // 서울시청 좌표
        const mapContainer = document.getElementById('map');

        if (!mapContainer) {
            console.error('지도 컨테이너를 찾을 수 없습니다.');
            return;
        }

        map = new kakao.maps.Map(mapContainer, {
            center: center,
            level: 6 // 1km 축적
        });

        ps = new kakao.maps.services.Places();

        if (kakao.maps.services.Geocoder) {
            geocoder = new kakao.maps.services.Geocoder();
        }

        // 초기 영화관 검색 (서울시청 기준)
        searchNearbyTheaters(center);

        // 지도 초기화 후 명시적으로 중심과 줌 유지
        setTimeout(() => {
            map.setCenter(center);
            map.setLevel(6);
        }, 100);

    } catch (error) {
        console.error('지도 초기화 오류:', error);
    }

    loadFavorites();
}

// 지역 검색
function searchByLocation() {
    const locationInput = document.getElementById('locationSearch');
    const query = locationInput.value.trim();

    if (!query) {
        alert('지역명을 입력해주세요.');
        return;
    }

    // 검색 기록 저장
    saveSearchHistory(query);
    hideSearchHistory();

    searchByLocationWithQuery(query);
}

function searchByLocationWithQuery(query) {
    // 위치 추적 중지
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
    isLocating = false;

    // 지역 검색 시 기존 현재 위치 마커 제거
    if (currentLocationMarker) {
        currentLocationMarker.setMap(null);
        currentLocationMarker = null;
    }

    ps.keywordSearch(query, function (data, status) {
        if (status === kakao.maps.services.Status.OK) {
            const coords = new kakao.maps.LatLng(data[0].y, data[0].x);
            map.setCenter(coords);
            map.setLevel(6);
            currentLocation = coords;
            searchNearbyTheaters(coords);
        } else {
            if (geocoder) {
                geocoder.addressSearch(query, function (result, status) {
                    if (status === kakao.maps.services.Status.OK) {
                        const coords = new kakao.maps.LatLng(result[0].y, result[0].x);
                        map.setCenter(coords);
                        map.setLevel(6);
                        currentLocation = coords;
                        searchNearbyTheaters(coords);
                    } else {
                        alert('해당 지역을 찾을 수 없습니다. 다른 지역명을 입력해주세요.');
                    }
                });
            } else {
                alert('해당 지역을 찾을 수 없습니다. 다른 지역명을 입력해주세요.');
            }
        }
    });
}

// 주변 영화관 검색
function searchNearbyTheaters(position) {
    clearMarkers();

    const theaterKeywords = ['CGV', '롯데시네마', '메가박스'];
    allTheaters = [];
    let searchCount = 0;

    theaterKeywords.forEach(keyword => {
        ps.keywordSearch(keyword, function (data, status) {
            searchCount++;

            if (status === kakao.maps.services.Status.OK) {
                data.forEach(place => {
                    // 🔥 실제 영화관만 필터링
                    const isRealTheater =
                        place.place_name.includes('CGV') ||
                        place.place_name.includes('롯데시네마') ||
                        place.place_name.includes('메가박스');

                    // 영화관이 아닌 곳 제외 (봉구비어, 치킨집 등)
                    const isNotTheater =
                        place.place_name.includes('봉구') ||
                        place.place_name.includes('ATM') ||
                        place.place_name.includes('챔피언') ||
                        place.place_name.includes('365') ||
                        place.place_name.includes('치킨') ||
                        place.place_name.includes('피자') ||
                        place.place_name.includes('카페') ||
                        place.category_name.includes('음식점') ||
                        place.category_name.includes('카페');

                    if (!isRealTheater || isNotTheater) {
                        return; // 영화관이 아니면 스킵
                    }

                    const isDuplicate = allTheaters.some(existing =>
                        Math.abs(existing.y - place.y) < 0.001 &&
                        Math.abs(existing.x - place.x) < 0.001
                    );

                    if (!isDuplicate) {
                        place.distance = getDistance(position.getLat(), position.getLng(), place.y, place.x);
                        allTheaters.push(place);
                    }
                });
            }

            if (searchCount === theaterKeywords.length) {
                allTheaters.sort((a, b) => a.distance - b.distance);
                displayAllTheaters(allTheaters, position);
            }
        }, {
            location: position,
            radius: 5000,
            size: 15
        });
    });
}

// 거리 계산 함수
function getDistance(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

// 모든 영화관 표시
function displayAllTheaters(theaters, centerPos) {
    theaters.forEach(theater => {
        displayMarker(new kakao.maps.LatLng(theater.y, theater.x), theater, centerPos);
    });

    updateTheaterCount(theaters.length);
    updateTheaterList(theaters, centerPos);
    filterTheaters();
}

// 영화관 개수 업데이트
function updateTheaterCount(count) {
    const theaterCountElement = document.getElementById('theaterCount');
    if (theaterCountElement) {
        theaterCountElement.textContent = count;
    }
}

// 정렬 함수
function sortTheaters(sortType) {
    if (!allTheaters.length || !currentLocation) return;

    // 현재 정렬 타입 저장
    currentSortType = sortType;

    // 정렬 버튼 스타일 업데이트
    document.getElementById('sortDistance').style.background = '#f3f4f6';
    document.getElementById('sortDistance').style.color = '#374151';
    document.getElementById('sortName').style.background = '#f3f4f6';
    document.getElementById('sortName').style.color = '#374151';

    const activeBtn = document.getElementById(`sort${sortType.charAt(0).toUpperCase() + sortType.slice(1)}`);
    activeBtn.style.background = '#1a73e8';
    activeBtn.style.color = 'white';

    updateTheaterList(allTheaters, currentLocation);
}

// 영화관 목록 업데이트 (즐겨찾기 포함)
function updateTheaterList(theaters, centerPos) {
    const theaterList = document.getElementById('theaterList');
    if (!theaterList) return;

    if (theaters.length === 0) {
        theaterList.innerHTML = `
            <div style="text-align: center; padding: 40px 20px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;">🎬</div>
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">영화관을 찾을 수 없습니다</div>
                <div style="font-size: 14px;">다른 지역을 검색해보세요</div>
            </div>
        `;
        return;
    }

    const sortedTheaters = theaters.sort((a, b) => {
        const aTheaterId = `${a.place_name}_${a.x}_${a.y}`;
        const bTheaterId = `${b.place_name}_${b.x}_${b.y}`;
        const aIsFav = isFavoriteTheater(aTheaterId);
        const bIsFav = isFavoriteTheater(bTheaterId);

        // 즐겨찾기 우선
        if (aIsFav && !bIsFav) return -1;
        if (!aIsFav && bIsFav) return 1;

        // currentSortType에 따라 정렬
        if (currentSortType === 'name') {
            return a.place_name.localeCompare(b.place_name);
        }
        return a.distance - b.distance;
    });

    let listHTML = '';
    sortedTheaters.forEach((theater, index) => {
        const distance = theater.distance ? theater.distance.toFixed(1) : '0.0';
        const theaterId = `${theater.place_name}_${theater.x}_${theater.y}`;
        const theaterIdEscaped = escapeQuotes(theaterId);
        const theaterNameEscaped = escapeQuotes(theater.place_name);
        const isFav = isFavoriteTheater(theaterId);

        let chainClass = 'other';
        let chainColor = '#666';
        if (theater.place_name.includes('CGV')) {
            chainClass = 'cgv';
            chainColor = '#fb4357';
        } else if (theater.place_name.includes('롯데시네마')) {
            chainClass = 'lotte';
            chainColor = '#e50914';
        } else if (theater.place_name.includes('메가박스')) {
            chainClass = 'megabox';
            chainColor = '#5c3098';
        }

        listHTML += `
            <div class="theater-item theater-${chainClass}" style="
                padding: 15px; 
                margin-bottom: 10px; 
                border: 1px solid #e5e5e5; 
                border-radius: 8px; 
                cursor: pointer; 
                transition: all 0.2s; 
                background: white;
                ${isFav ? 'border-left: 4px solid #fbbf24; background: linear-gradient(135deg, #fffbeb, #fef3c7); box-shadow: 0 2px 8px rgba(251, 191, 36, 0.2);' : ''}
            " onclick="focusTheater(${theater.y}, ${theater.x})" 
               onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.style.transform='translateY(-2px)'" 
               onmouseout="this.style.boxShadow='${isFav ? '0 2px 8px rgba(251, 191, 36, 0.2)' : 'none'}'; this.style.transform='translateY(0)'">
               
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: ${chainColor}; line-height: 1.3;">
                            ${theater.place_name}
                        </h4>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="background: ${chainColor}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; white-space: nowrap;">
                            ${distance}km
                        </span>
                        <button id="listFavBtn_${theaterId.replace(/\s/g, '_')}" onclick="event.stopPropagation(); toggleFavorite('${theaterIdEscaped}', '${theaterNameEscaped}')" 
                                style="background: none; border: none; cursor: pointer; padding: 0; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: transform 0.1s;"
                                onmouseover="this.style.transform='scale(1.2)'" 
                                onmouseout="this.style.transform='scale(1)'">
                            <svg width="${isFav ? '18' : '16'}" height="${isFav ? '18' : '16'}" viewBox="0 0 24 24" fill="${isFav ? '#fbbf24' : 'none'}" stroke="${isFav ? 'none' : '#999'}" stroke-width="2">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div style="color: #666; font-size: 13px; line-height: 1.4; margin-bottom: 10px;">
                    ${theater.road_address_name || theater.address_name}
                </div>
                
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    ${createBookingButtons(theater)}
                    <button onclick="event.stopPropagation(); openNavigation('${theaterNameEscaped}', ${theater.y}, ${theater.x})" 
                            style="padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600;">
                        길찾기
                    </button>
                </div>
            </div>
        `;
    });

    theaterList.innerHTML = listHTML;
}


// 예매 버튼 생성
function createBookingButtons(theater) {
    let buttons = '';

    if (theater.place_name.includes('CGV')) {
        buttons = `<button onclick="event.stopPropagation(); window.open('https://cgv.co.kr/', '_blank')" style="padding: 6px 12px; background: #fb4357; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600;">CGV 예매</button>`;
    } else if (theater.place_name.includes('롯데시네마')) {
        buttons = `<button onclick="event.stopPropagation(); window.open('https://www.lottecinema.co.kr/', '_blank')" style="padding: 6px 12px; background: #e50914; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600;">롯데 예매</button>`;
    } else if (theater.place_name.includes('메가박스')) {
        buttons = `<button onclick="event.stopPropagation(); window.open('https://www.megabox.co.kr/', '_blank')" style="padding: 6px 12px; background: #5c3098; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600;">메가박스 예매</button>`;
    }

    return buttons;
}

// 길찾기 열기
function openNavigation(name, lat, lng) {
    const url = `https://map.kakao.com/link/to/${encodeURIComponent(name)},${lat},${lng}`;
    window.open(url, '_blank');
}

// 영화관 포커스
function focusTheater(lat, lng) {
    const position = new kakao.maps.LatLng(lat, lng);
    map.setCenter(position);
    map.setLevel(3);

    // 해당 마커의 정보창 열기
    const targetMarker = markers.find(marker =>
        Math.abs(marker.getPosition().getLat() - lat) < 0.0001 &&
        Math.abs(marker.getPosition().getLng() - lng) < 0.0001
    );

    if (targetMarker && targetMarker.infowindow) {
        // 다른 정보창들 닫기
        markers.forEach(m => {
            if (m.infowindow) m.infowindow.close();
        });
        targetMarker.infowindow.open(map, targetMarker);
    }
}

// 마커 표시 (즐겨찾기 표시 포함)
// 마커 표시 (즐겨찾기 표시 포함)
function displayMarker(position, place, centerPos) {
    const theaterId = `${place.place_name}_${place.x}_${place.y}`;
    const theaterIdEscaped = escapeQuotes(theaterId);
    const placeNameEscaped = escapeQuotes(place.place_name);
    const isFav = isFavoriteTheater(theaterId);

    let markerColor = '#e50914';
    let cinemaChain = 'other';

    if (place.place_name.includes('CGV')) {
        markerColor = '#fb4357';
        cinemaChain = 'cgv';
    } else if (place.place_name.includes('롯데시네마')) {
        markerColor = '#e50914';
        cinemaChain = 'lotte';
    } else if (place.place_name.includes('메가박스')) {
        markerColor = '#5c3098';
        cinemaChain = 'megabox';
    }

    const markerImage = new kakao.maps.MarkerImage(
        'data:image/svg+xml,' + encodeURIComponent(`
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
                ${isFav ?
                `<circle cx="15" cy="15" r="12" fill="#fbbf24" stroke="white" stroke-width="2"/>
                     <text x="15" y="20" text-anchor="middle" fill="white" font-size="12" font-weight="bold">⭐</text>` :
                `<circle cx="15" cy="15" r="12" fill="${markerColor}" stroke="white" stroke-width="2"/>
                     <text x="15" y="20" text-anchor="middle" fill="white" font-size="12" font-weight="bold">🎬</text>`
            }
            </svg>
        `),
        new kakao.maps.Size(30, 30),
        { offset: new kakao.maps.Point(15, 15) }
    );

    const marker = new kakao.maps.Marker({
        map: map,
        position: position,
        image: markerImage
    });

    marker.cinemaChain = cinemaChain;
    markers.push(marker);

    const distance = place.distance ? place.distance.toFixed(1) : '0.0';

    const infowindow = new kakao.maps.InfoWindow({
        content: `
        <div style="padding: 15px; min-width: 280px; max-width: 400px; width: max-content; position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                <div style="font-weight: bold; color: ${markerColor}; font-size: 16px; flex: 1; line-height: 1.3; word-break: keep-all; overflow-wrap: break-word; padding-right: 10px; max-width: 300px;">
                    ${place.place_name}
                </div>
                <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
                    <button id="favBtn_${theaterId.replace(/\s/g, '_')}" onclick="toggleFavorite('${theaterIdEscaped}', '${placeNameEscaped}')" 
                            style="background: none; border: none; cursor: pointer; padding: 0; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: transform 0.2s;"
                            onmouseover="this.style.transform='scale(1.15)'"
                            onmouseout="this.style.transform='scale(1)'">
                        <svg width="${isFav ? '20' : '18'}" height="${isFav ? '20' : '18'}" viewBox="0 0 24 24" fill="${isFav ? '#fbbf24' : 'none'}" stroke="${isFav ? 'none' : '#999'}" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </button>
                    <button onclick="closeInfoWindow()" 
                            style="background: transparent; border: none; cursor: pointer; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 20px; color: #999; line-height: 1; padding: 0; transition: all 0.2s;"
                            onmouseover="this.style.color='#666'"
                            onmouseout="this.style.color='#999'">
                        ✕
                    </button>
                </div>
            </div>
            <div style="font-size: 12px; color: #666; margin-bottom: 10px; line-height: 1.4;">
                ${place.road_address_name || place.address_name}
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                <span style="background: ${markerColor}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; white-space: nowrap;">
                    ${distance}km
                </span>
                ${place.phone ? `<span style="font-size: 12px; color: #666; white-space: nowrap;">📞 ${place.phone}</span>` : ''}
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                ${createBookingButtons(place)}
                <button onclick="openNavigation('${placeNameEscaped}', ${place.y}, ${place.x})" style="padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600; white-space: nowrap;">
                    길찾기
                </button>
            </div>
        </div>
    `
    });

    kakao.maps.event.addListener(marker, 'click', function () {
        markers.forEach(m => {
            if (m.infowindow) m.infowindow.close();
        });
        infowindow.open(map, marker);
        marker.infowindow = infowindow;
    });

    marker.infowindow = infowindow;
}

// 영화관 필터링
function filterTheaters() {
    const cgvChecked = document.getElementById('cgvFilter')?.checked;
    const lotteChecked = document.getElementById('lotteFilter')?.checked;
    const megaboxChecked = document.getElementById('megaboxFilter')?.checked;

    let visibleCount = 0;

    markers.forEach(marker => {
        let shouldShow = false;

        switch (marker.cinemaChain) {
            case 'cgv':
                shouldShow = cgvChecked;
                break;
            case 'lotte':
                shouldShow = lotteChecked;
                break;
            case 'megabox':
                shouldShow = megaboxChecked;
                break;
            default:
                shouldShow = true;
        }

        if (shouldShow) {
            marker.setMap(map);
            visibleCount++;
        } else {
            marker.setMap(null);
            if (marker.infowindow) {
                marker.infowindow.close();
            }
        }
    });

    // 사이드바 아이템도 필터링
    const theaterItems = document.querySelectorAll('.theater-item');
    theaterItems.forEach(item => {
        const shouldShow =
            (cgvChecked && item.classList.contains('theater-cgv')) ||
            (lotteChecked && item.classList.contains('theater-lotte')) ||
            (megaboxChecked && item.classList.contains('theater-megabox')) ||
            item.classList.contains('theater-other');

        item.style.display = shouldShow ? 'block' : 'none';
    });

    updateTheaterCount(visibleCount);
}

// 마커 제거
function clearMarkers() {
    markers.forEach(marker => {
        marker.setMap(null);
    });
    markers = [];
}

// 지도 닫기
function closeTheaterMap() {
    const modal = document.getElementById('mapModal');
    modal.style.display = 'none';

    // 이벤트 리스너 정리
    modal.onclick = null;
    document.onkeydown = null;

    // 위치 추적 중지
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
    isLocating = false;

    // 현재 위치 마커 제거
    if (currentLocationMarker) {
        currentLocationMarker.setMap(null);
        currentLocationMarker = null;
    }

    // 열려있는 InfoWindow 닫기
    markers.forEach(marker => {
        if (marker.infowindow) {
            marker.infowindow.close();
        }
    });

    // 생성된 컨트롤들 정리
    const controls = document.querySelectorAll('.map-control-panel, .theater-sidebar');
    controls.forEach(control => control.remove());
}

// 호환성을 위한 별칭
function closeKakaoMap() {
    closeTheaterMap();
}

async function loadFavorites() {
    try {
        const response = await fetch(FAVORITE_API_URL, {
            method: 'GET',
            credentials: 'include'
        });
        if (!response.ok) throw new Error('Failed to load favorites');

        const favorites = await response.json();
        console.log('DB에서 로드된 즐겨찾기:', favorites);

        favoriteTheaters = favorites || [];

        // 현재 표시된 영화관 목록 다시 업데이트
        if (allTheaters && currentLocation) {
            updateTheaterList(allTheaters, currentLocation);
        }

        // 즐겨찾기 목록도 업데이트 (즐겨찾기 탭이 열려있을 경우를 대비)
        const favoritesList = document.getElementById('favoritesList');
        if (favoritesList && favoritesList.style.display !== 'none') {
            updateFavoritesList();
        }

    } catch (error) {
        console.error('즐겨찾기 로드 오류:', error);
        favoriteTheaters = [];
    }
}

async function addFavorite(theater) {
    try {
        const response = await fetch(FAVORITE_API_URL, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                theater_place_name: theater.theater_place_name,
                theater_x: theater.theater_x,
                theater_y: theater.theater_y
            }),
        });

        if (response.status === 401) {
            if (confirm('로그인이 필요한 서비스입니다.\n로그인 페이지로 이동하시겠습니까?')) {
                window.location.href = 'login.php';
            }
            return 'login_required'; // 특수 값 반환
        }

        const result = await response.json();

        if (!response.ok) {
            console.error('서버 오류:', result);
            return false;
        }
        return true;
    } catch (error) {
        console.error('즐겨찾기 추가 오류:', error);
        return false;
    }
}

async function removeFavorite(theater) {
    try {
        const response = await fetch(FAVORITE_API_URL, {
            method: 'DELETE',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                theater_place_name: theater.theater_place_name,
                theater_x: theater.theater_x,
                theater_y: theater.theater_y
            }),
        });

        if (response.status === 401) {
            if (confirm('로그인이 필요한 서비스입니다.\n로그인 페이지로 이동하시겠습니까?')) {
                window.location.href = 'login.php';
            }
            return 'login_required'; // 특수 값 반환
        }

        const result = await response.json();

        if (!response.ok) {
            console.error('서버 오류:', result);
            return false;
        }
        return true;
    } catch (error) {
        console.error('즐겨찾기 삭제 오류:', error);
        return false;
    }
}

async function toggleFavorite(theaterId, theaterName) {
    try {
        const parts = theaterId.split('_');
        if (parts.length < 3) {
            alert('잘못된 영화관 정보입니다.');
            return;
        }

        const theater = {
            theater_place_name: theaterName,
            theater_x: parseFloat(parts[1]),
            theater_y: parseFloat(parts[2])
        };

        if (isFavoriteTheater(theaterId)) {
            const success = await removeFavorite(theater);
            if (success === 'login_required') {
                return; // 로그인 필요 - 아무것도 하지 않음
            } else if (success) {
                await loadFavorites();
                updateMarkerIcon(theaterId);
                updateFavButtonOnly(theaterId, false);
                updateCardStyle(theaterId, false); // 카드 스타일 업데이트 추가
            } else {
                alert('즐겨찾기 삭제에 실패했습니다.');
            }
        } else {
            const success = await addFavorite(theater);
            if (success === 'login_required') {
                return; // 로그인 필요 - 아무것도 하지 않음
            } else if (success) {
                await loadFavorites();
                updateMarkerIcon(theaterId);
                updateFavButtonOnly(theaterId, true);
                updateCardStyle(theaterId, true); // 카드 스타일 업데이트 추가
            } else {
                alert('즐겨찾기 추가에 실패했습니다.');
            }
        }

    } catch (error) {
        console.error('즐겨찾기 처리 중 오류:', error);
        alert('즐겨찾기 처리 중 문제가 발생했습니다.');
    }
}

// 별 버튼만 업데이트
function updateFavButtonOnly(theaterId, isFav) {
    // InfoWindow 별 버튼 업데이트
    const infoBtnId = 'favBtn_' + theaterId.replace(/\s/g, '_');
    const infoBtn = document.getElementById(infoBtnId);
    if (infoBtn) {
        const svg = infoBtn.querySelector('svg');
        if (svg) {
            svg.setAttribute('fill', isFav ? '#fbbf24' : 'none');
            svg.setAttribute('stroke', isFav ? 'none' : '#999');
        }
    }

    // 사이드바 목록 별 버튼 업데이트
    const listBtnId = 'listFavBtn_' + theaterId.replace(/\s/g, '_');
    const listBtn = document.getElementById(listBtnId);
    if (listBtn) {
        const svg = listBtn.querySelector('svg');
        if (svg) {
            svg.setAttribute('fill', isFav ? '#fbbf24' : 'none');
            svg.setAttribute('stroke', isFav ? 'none' : '#999');
        }
    }
}

// 카드 스타일 업데이트 (즐겨찾기 상태에 따라)
function updateCardStyle(theaterId, isFav) {
    // 사이드바 목록에서 해당 카드 찾기
    const listBtnId = 'listFavBtn_' + theaterId.replace(/\s/g, '_');
    const listBtn = document.getElementById(listBtnId);

    if (listBtn) {
        // 버튼의 부모 요소(카드)를 찾기
        const card = listBtn.closest('.theater-item') || listBtn.closest('[onclick*="focusTheater"]');

        if (card) {
            if (isFav) {
                // 즐겨찾기 추가 - 노란색 스타일 적용
                card.style.borderLeft = '4px solid #fbbf24';
                card.style.background = 'linear-gradient(135deg, #fffbeb, #fef3c7)';
                card.style.boxShadow = '0 2px 8px rgba(251, 191, 36, 0.2)';
            } else {
                // 즐겨찾기 해제 - 기본 스타일로 복원
                card.style.borderLeft = '1px solid #e5e5e5';
                card.style.background = 'white';
                card.style.boxShadow = 'none';
            }
        }
    }
}

// 특정 영화관의 InfoWindow 업데이트 함수 추가
function updateInfoWindowForTheater(theaterId) {
    const parts = theaterId.split('_');
    if (parts.length < 3) return;

    const theaterName = parts[0];
    const x = parseFloat(parts[1]);
    const y = parseFloat(parts[2]);

    const targetMarker = markers.find(marker => {
        const pos = marker.getPosition();
        return Math.abs(pos.getLng() - x) < 0.0001 &&
            Math.abs(pos.getLat() - y) < 0.0001;
    });

    if (targetMarker) {
        if (targetMarker.infowindow) {
            targetMarker.infowindow.close();
        }

        const theaterInfo = allTheaters.find(t =>
            t.place_name === theaterName &&
            Math.abs(t.x - x) < 0.0001 &&
            Math.abs(t.y - y) < 0.0001
        );

        if (theaterInfo) {
            const theaterIdEscaped = escapeQuotes(theaterId);
            const theaterNameEscaped = escapeQuotes(theaterInfo.place_name);
            const isFav = isFavoriteTheater(theaterId);
            const distance = theaterInfo.distance ? theaterInfo.distance.toFixed(1) : '0.0';

            let markerColor = '#e50914';
            if (theaterInfo.place_name.includes('CGV')) markerColor = '#fb4357';
            else if (theaterInfo.place_name.includes('롯데시네마')) markerColor = '#e50914';
            else if (theaterInfo.place_name.includes('메가박스')) markerColor = '#5c3098';

            const newInfowindow = new kakao.maps.InfoWindow({
                content: `
                    <div style="padding: 15px; min-width: 280px; position: relative;">
                        <button onclick="this.parentElement.parentElement.parentElement.style.display='none'" 
                                style="position: absolute; top: 8px; right: 8px; background: #f3f4f6; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 16px; color: #666; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"
                                onmouseover="this.style.background='#e5e7eb'; this.style.color='#000'"
                                onmouseout="this.style.background='#f3f4f6'; this.style.color='#666'">
                            ✕
                        </button>
                        
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; padding-right: 20px;">
                            <div style="font-weight: bold; color: ${markerColor}; font-size: 16px; flex: 1;">
                                ${theaterInfo.place_name}
                            </div>
                            <button onclick="toggleFavorite('${theaterIdEscaped}', '${theaterNameEscaped}')" 
                                    style="background: none; border: none; cursor: pointer; padding: 4px; transition: transform 0.2s;"
                                    onmouseover="this.style.transform='scale(1.1)'"
                                    onmouseout="this.style.transform='scale(1)'">
                                ${isFav ? '⭐' : '☆'}
                            </button>
                        </div>
                        <div style="font-size: 12px; color: #666; margin-bottom: 10px; line-height: 1.4;">
                            ${theaterInfo.road_address_name || theaterInfo.address_name}
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="background: ${markerColor}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                ${distance}km
                            </span>
                            ${theaterInfo.phone ? `<span style="font-size: 12px; color: #666;">📞 ${theaterInfo.phone}</span>` : ''}
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            ${createBookingButtons(theaterInfo)}
                            <button onclick="openNavigation('${theaterNameEscaped}', ${theaterInfo.y}, ${theaterInfo.x})" style="padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600;">
                                길찾기
                            </button>
                        </div>
                    </div>
                `
            });

            targetMarker.infowindow = newInfowindow;
            newInfowindow.open(map, targetMarker);
        }
    }
}

// 탭 전환 함수
function switchTheaterTab(tab) {
    const nearbyBtn = document.getElementById('tabNearby');
    const favoritesBtn = document.getElementById('tabFavorites');
    const theaterList = document.getElementById('theaterList');
    const favoritesList = document.getElementById('favoritesList');
    const tabDescription = document.getElementById('tabDescription');

    if (tab === 'nearby') {
        // 주변 영화관 탭 활성화
        nearbyBtn.style.background = '#1a73e8';
        nearbyBtn.style.color = 'white';
        favoritesBtn.style.background = '#f3f4f6';
        favoritesBtn.style.color = '#666';

        theaterList.style.display = 'block';
        favoritesList.style.display = 'none';

        tabDescription.textContent = '지도에서 영화관을 클릭하거나 아래 목록에서 선택하세요';
    } else {
        // 즐겨찾기 탭 활성화
        nearbyBtn.style.background = '#f3f4f6';
        nearbyBtn.style.color = '#666';
        favoritesBtn.style.background = '#fbbf24';
        favoritesBtn.style.color = 'white';

        theaterList.style.display = 'none';
        favoritesList.style.display = 'block';

        tabDescription.textContent = '즐겨찾기한 영화관만 표시됩니다';

        // 즐겨찾기 목록 업데이트
        updateFavoritesList();
    }
}

// 즐겨찾기 목록 업데이트
function updateFavoritesList() {
    const favoritesList = document.getElementById('favoritesList');
    if (!favoritesList) return;

    if (favoriteTheaters.length === 0) {
        favoritesList.innerHTML = `
            <div style="text-align: center; padding: 40px 20px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;">⭐</div>
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">즐겨찾기한 영화관이 없습니다</div>
                <div style="font-size: 14px;">영화관 옆 별 아이콘을 눌러 즐겨찾기하세요</div>
            </div>
        `;
        return;
    }

    let listHTML = '';
    favoriteTheaters.forEach((fav) => {
        const theaterId = `${fav.theater_place_name}_${fav.theater_x}_${fav.theater_y}`;
        const theaterIdEscaped = escapeQuotes(theaterId);
        const theaterNameEscaped = escapeQuotes(fav.theater_place_name);

        // 체인별 색상 설정
        let chainColor = '#666';
        if (fav.theater_place_name.includes('CGV')) {
            chainColor = '#fb4357';
        } else if (fav.theater_place_name.includes('롯데시네마')) {
            chainColor = '#e50914';
        } else if (fav.theater_place_name.includes('메가박스')) {
            chainColor = '#5c3098';
        }

        listHTML += `
            <div style="
                padding: 15px; 
                margin-bottom: 10px; 
                border: 1px solid #fbbf24; 
                border-left: 4px solid #fbbf24;
                border-radius: 8px; 
                background: linear-gradient(135deg, #fffbeb, #fef3c7);
                box-shadow: 0 2px 8px rgba(251, 191, 36, 0.2);
                cursor: pointer;
                transition: all 0.2s;
            " onclick="focusTheaterByName('${theaterNameEscaped}', ${fav.theater_y}, ${fav.theater_x})"
               onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(251, 191, 36, 0.3)'" 
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(251, 191, 36, 0.2)'">
               
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: ${chainColor}; line-height: 1.3; display: flex; align-items: center; gap: 8px;">
                        ${fav.theater_place_name}
                    </h4>
                    <button onclick="event.stopPropagation(); toggleFavorite('${theaterIdEscaped}', '${theaterNameEscaped}')" 
                            style="background: none; border: none; cursor: pointer; font-size: 18px; padding: 4px; transition: transform 0.1s;"
                            onmouseover="this.style.transform='scale(1.2)'" 
                            onmouseout="this.style.transform='scale(1)'">
                        ⭐
                    </button>
                </div>
                
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;">
                    <button onclick="event.stopPropagation(); openNavigation('${theaterNameEscaped}', ${fav.theater_y}, ${fav.theater_x})" 
                            style="padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600;">
                        길찾기
                    </button>
                </div>
            </div>
        `;
    });

    favoritesList.innerHTML = listHTML;
}

// 극장 이름으로 포커스 (즐겨찾기 목록용)
function focusTheaterByName(name, lat, lng) {
    focusTheater(lat, lng);
    // 주변 영화관 탭으로 자동 전환
    switchTheaterTab('nearby');
}

function updateMarkerIcon(theaterId) {
    const parts = theaterId.split('_');
    if (parts.length < 3) return;

    const x = parseFloat(parts[1]);
    const y = parseFloat(parts[2]);

    // 해당 마커 찾기
    const targetMarker = markers.find(marker => {
        const pos = marker.getPosition();
        return Math.abs(pos.getLng() - x) < 0.0001 &&
            Math.abs(pos.getLat() - y) < 0.0001;
    });

    if (targetMarker) {
        // 즐겨찾기 상태 확인
        const isFav = isFavoriteTheater(theaterId);

        // 마커 색상 결정
        let markerColor = '#e50914';
        const theaterInfo = allTheaters.find(t =>
            Math.abs(t.x - x) < 0.0001 &&
            Math.abs(t.y - y) < 0.0001
        );

        if (theaterInfo) {
            if (theaterInfo.place_name.includes('CGV')) markerColor = '#fb4357';
            else if (theaterInfo.place_name.includes('롯데시네마')) markerColor = '#e50914';
            else if (theaterInfo.place_name.includes('메가박스')) markerColor = '#5c3098';
        }

        // 새 마커 이미지 생성
        const newMarkerImage = new kakao.maps.MarkerImage(
            'data:image/svg+xml,' + encodeURIComponent(`
                <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
                    ${isFav ?
                    `<circle cx="15" cy="15" r="12" fill="#fbbf24" stroke="white" stroke-width="2"/>
                         <text x="15" y="20" text-anchor="middle" fill="white" font-size="12" font-weight="bold">⭐</text>` :
                    `<circle cx="15" cy="15" r="12" fill="${markerColor}" stroke="white" stroke-width="2"/>
                         <text x="15" y="20" text-anchor="middle" fill="white" font-size="12" font-weight="bold">🎬</text>`
                }
                </svg>
            `),
            new kakao.maps.Size(30, 30),
            { offset: new kakao.maps.Point(15, 15) }
        );

        // 마커 이미지 업데이트
        targetMarker.setImage(newMarkerImage);
    }
}

// InfoWindow 닫기 함수
function closeInfoWindow() {
    markers.forEach(marker => {
        if (marker.infowindow) {
            marker.infowindow.close();
        }
    });
}