<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $cinema_type = $_POST['cinema_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $link_url = $_POST['link_url'];
    $main_image = '';
    $detail_image = '';
    
    // 메인 이미지 업로드 처리
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
        $upload_dir = 'img/';
        $file_extension = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'event_' . time() . '_main.' . $file_extension;
        
        if (move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_dir . $new_filename)) {
            $main_image = $upload_dir . $new_filename;
        }
    }
    
    // 상세 이미지 업로드 처리
    if (isset($_FILES['detail_image']) && $_FILES['detail_image']['error'] == 0) {
        $upload_dir = 'img/';
        $file_extension = pathinfo($_FILES['detail_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'event_' . time() . '_detail.' . $file_extension;
        
        if (move_uploaded_file($_FILES['detail_image']['tmp_name'], $upload_dir . $new_filename)) {
            $detail_image = $upload_dir . $new_filename;
        }
    }
    
    // DB에 저장
    $con = mysqli_connect("localhost", "root", "", "moviedb");
    
    // events 테이블이 없으면 생성
    $create_table = "CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        cinema_type VARCHAR(50) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE,
        link_url TEXT,
        main_image VARCHAR(255),
        detail_image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($con, $create_table);
    
    $insert_sql = "INSERT INTO events (title, cinema_type, start_date, end_date, link_url, main_image, detail_image) 
                   VALUES ('$title', '$cinema_type', '$start_date', '$end_date', '$link_url', '$main_image', '$detail_image')";
    
    if (mysqli_query($con, $insert_sql)) {
        echo "<script>alert('이벤트가 등록되었습니다.'); location.href='manager_event.php';</script>";
    } else {
        echo "<script>alert('등록 중 오류가 발생했습니다.');</script>";
    }
    
    mysqli_close($con);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>이벤트 등록 - Cinepals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .main_wrapper {
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }

        .admin_container {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px;
        }
        
        .content_area {
            flex: 1;
        }
        
        .content_title {
            color: #fff;
            font-size: 28px;
            margin-bottom: 30px;
        }
        
        .event_form {
            background: #1a1d24;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #333;
        }
        
        .form_group {
            margin-bottom: 20px;
        }
        
        .form_label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
            font-weight: bold;
        }
        
        .form_input,
        .form_select,
        .form_textarea {
            width: 100%;
            padding: 12px;
            background: #2a2d34;
            border: 1px solid #444;
            border-radius: 4px;
            color: #fff;
            box-sizing: border-box;
        }
        
        .form_input:focus,
        .form_select:focus,
        .form_textarea:focus {
            outline: none;
            border-color: #2c5282;
        }
        
        .form_textarea {
            height: 100px;
            resize: vertical;
        }
        
        .file_input {
            background: #2a2d34;
            border: 1px solid #444;
            border-radius: 4px;
            padding: 10px;
            color: #fff;
        }
        
        .date_group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .date_input {
            flex: 1;
        }
        
        .form_buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn_submit {
            background: #2c5282;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn_cancel {
            background: #666;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        
        .help_text {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
        
        .required {
            color: #ff6b6b;
        }

        .side_menu {
            width: 250px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            height: fit-content;
        }

        .side_menu .home:hover {
            color: #e74c3c !important;
        }

        .side_menu .member:hover {
            color: #3498db !important;
        }

        .side_menu .active {
            color: #db34a9 !important;
        }

        .side_menu .report:hover {
            color: #f39c12 !important;
        }
    </style>
</head>
<body>
    <?php require_once("inc/header.php"); ?>

    <main class="main_wrapper">
        <div class="admin_container">
            <!-- 좌측 메뉴 -->
            <aside class="side_menu">
                <div class="menu_list">
                    <a href="manager_home.php" class="menu_item home">대시보드</a>
                    <a href="manager_members.php" class="menu_item member">회원 관리</a>
                    <a href="manager_event.php" class="menu_item active">이벤트 관리</a>
                    <a href="manager_reports.php" class="menu_item report">리뷰/댓글관리</a>
                </div>
            </aside>

            <!-- 우측 콘텐츠 -->
            <div class="content_area">
                <h2 class="content_title">새 이벤트 등록</h2>
                
                <form class="event_form" method="POST" enctype="multipart/form-data">
                    <div class="form_group">
                        <label class="form_label">이벤트 제목 <span class="required">*</span></label>
                        <input type="text" name="title" class="form_input" required 
                               placeholder="예: 무료선착순쿠폰 #3차">
                    </div>
                    
                    <div class="form_group">
                        <label class="form_label">영화관 <span class="required">*</span></label>
                        <select name="cinema_type" class="form_select" required>
                            <option value="">영화관을 선택하세요</option>
                            <option value="CGV">CGV</option>
                            <option value="롯데시네마">롯데시네마</option>
                            <option value="메가박스">메가박스</option>
                            <option value="기타">기타</option>
                        </select>
                    </div>
                    
                    <div class="form_group">
                        <label class="form_label">이벤트 기간 <span class="required">*</span></label>
                        <div class="date_group">
                            <input type="date" name="start_date" class="form_input date_input" required>
                            <span style="color: #fff;">~</span>
                            <input type="date" name="end_date" class="form_input date_input" 
                                   placeholder="종료일 (비워두면 무제한)">
                        </div>
                        <div class="help_text">종료일을 비워두면 무제한으로 설정됩니다.</div>
                    </div>
                    
                    <div class="form_group">
                        <label class="form_label">링크 URL</label>
                        <input type="url" name="link_url" class="form_input" 
                               placeholder="https://www.cgv.co.kr/culture-event/...">
                        <div class="help_text">이벤트 상세 페이지나 참여 링크를 입력하세요.</div>
                    </div>
                    
                    <div class="form_group">
                        <label class="form_label">메인 이미지 <span class="required">*</span></label>
                        <input type="file" name="main_image" class="file_input" accept="image/*" required>
                        <div class="help_text">메인페이지와 이벤트 목록에 표시될 이미지입니다. (권장: 400x200px)</div>
                    </div>
                    
                    <div class="form_group">
                        <label class="form_label">상세 이미지</label>
                        <input type="file" name="detail_image" class="file_input" accept="image/*">
                        <div class="help_text">이벤트 상세페이지에 표시될 이미지입니다. (선택사항)</div>
                    </div>
                    
                    <div class="form_buttons">
                        <button type="submit" class="btn_submit">
                            <i class="fas fa-save"></i> 이벤트 등록
                        </button>
                        <a href="manager_event.php" class="btn_cancel">
                            <i class="fas fa-times"></i> 취소
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php require_once("inc/footer.php"); ?>
    
    <script>
        // 파일 선택시 미리보기 (선택사항)
        document.querySelector('input[name="main_image"]').addEventListener('change', function(e) {
            if (e.target.files[0]) {
                console.log('메인 이미지 선택됨:', e.target.files[0].name);
            }
        });
        
        document.querySelector('input[name="detail_image"]').addEventListener('change', function(e) {
            if (e.target.files[0]) {
                console.log('상세 이미지 선택됨:', e.target.files[0].name);
            }
        });
    </script>
</body>
</html>