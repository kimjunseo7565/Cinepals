<!-- 지도 모달 -->
<div id="mapModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
    <div style="position:relative; width:600px; height:400px; background:#fff; border-radius:8px; overflow:hidden;">
        <div id="map" style="width:100%; height:100%;"></div>
    </div>
</div>

<!-- 우측하단 지도 버튼 -->
<button class="floating-map-btn" onclick="openKakaoMap()">
    <i class="fas fa-map-marker-alt"></i>
</button>

<style>
    .floating-map-btn {
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 1000;
        background: #e50914;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.18);
        font-size: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
    }

    .floating-map-btn:hover {
        background: #c40711;
    }
</style>

<!-- 카카오맵 API -->
<script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=&libraries=services"></script>
<script src="js/kakaomap.js"></script>