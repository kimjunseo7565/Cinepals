<?php
require_once("inc/session.php");
require_once("inc/db.php");

// 이벤트 ID 확인
if (!isset($_GET['id'])) {
    echo "<script>alert('잘못된 접근입니다.'); location.href='manager_event.php';</script>";
    exit;
}

$event_id = $_GET['id'];

// 기존 이벤트 정보 가져오기
$con = mysqli_connect("localhost", "root", "", "moviedb");
$event_sql = "SELECT * FROM events WHERE id = '$event_id'";
$event_result = mysqli_query($con, $event_sql);

if (!$event_result || mysqli_num_rows($event_result) == 0) {
    echo "<script>alert('존재하지 않는 이벤트입니다.'); location.href='manager_event.php';</script>";
    exit;
}

$event = mysqli_fetch_assoc($event_result);

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $cinema_type = $_POST['cinema_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $link_url = $_POST['link_url'];
    $main_image = $event['main_image']; // 기존 이미지 유지
    $detail_image = $event['detail_image']; // 기존 이미지 유지
    
    // 새로운 메인 이미지 업로드 처리
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
        $upload_dir = 'img/';
        $file_extension = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'event_' . time() . '_main.' . $file_extension;
        
        if (move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_dir . $new_filename)) {
            // 기존 이미지 삭제
            if (!empty($event['main_image']) && file_exists($event['main_image'])) {
                unlink($event['main_image']);
            }
            $main_image = $upload_dir . $new_filename;
        }
    }
    
    // 새로운 상세 이미지 업로드 처리
    if (isset($_FILES['detail_image']) && $_FILES['detail_image']['error'] == 0) {
        $upload_dir = 'img/';
        $file_extension = pathinfo($_FILES['detail_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'event_' . time() . '_detail.' . $file_extension;
        
        if (move_uploaded_file($_FILES['detail_image']['tmp_name'], $upload_dir . $new_filename)) {
            // 기존 이미지 삭제
            if (!empty($event['detail_image']) && file_exists($event['detail_image'])) {
                unlink($event['detail_image']);
            }
            $detail_image = $upload_dir . $new_filename;
        }
    }
    
    // DB 업데이트
    $update_sql = "UPDATE events SET 
                   title = '$title',
                   cinema_type = '$cinema_type',
                   start_date = '$start_date',
                   end_date = '$end_date',
                   link_url = '$link_url',
                   main_image = '$main_image',
                   detail_image = '$detail_image'
                   WHERE id = '$event_id'";
    
    if (mysqli_query($con, $update_sql)) {
        echo "<script>alert('이벤트가 수정되었습니다.'); location.href='manager_event.php';</script>";
    } else {
        echo "<script>alert('수정 중 오류가 발생했습니다.');</script>";
    }
}

mysqli_close($con);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>이벤트 수정 - Cinepals</title>
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
        
        .current_image {
            margin-bottom: 10px;
        }
        
        .current_image img {
            width: 150px;
            height: auto;
            border-radius: 4px;
            border: 1px solid #444;
        }
        
        .current_image_label {
            font-size: 12px;
            color: #888;
            display: block;
            margin-bottom: 5px;
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
                <h2 class="content_title">이벤트 수정</h2>
                
                <form class="event_form" method="POST" enctype="multipart/form-data">
                    <div class="form_group">
                        <label class="form_label">이벤트 제목 <span class="required">*</span></label>
                        <input type="text" name="title" class="form_input" required 
                               value="<?php echo htmlspecialchars($event['title']); ?>">
                    </div>
                    
                    <div class="form_group">
                        <label class="form_label">영화관 <span class="required">*</span></label>
                        <select name="cinema_type" class="form_select" required>
                            <option value="">영화관을 선택하세요</option>
                            <option value="CGV" <?php echo $event['cinema_type'] == 'CGV' ? 'selected' : ''; ?>>CGV</option>
                            <option value="롯데시네마" <?php echo $event['cinema_type'] == '롯데시네마' ? 'selected' : ''; ?>>롯데시네마</option>
                            <option value="메가박스" <?php echo $event['cinema_type'] == '메가박스' ? 'selected' : ''; ?>>메가박스</option>
                            <option value="기타" <?php echo $event['cinema_type'] == '기타' ? 'selected' : ''; ?>>기타</option>
                        </select>
                    </div>
                    
                    <div class="form_group">
                        <label class="form_label">이벤트 기간 <span class="required">*</span></label>
                        <div class="date_group">
                            <input type="date" name="start_date" class="form_input date_input" required
                                   value="<?php echo $event['start_date']; ?>">
                            <span style="color: #fff;">~</span>
                            <input type="date" name="end_date" class="form_input date_input" 
                                   value="<?php echo $event['end_date']; ?>">
                        </div>
                        <div class="help_text">종료일을 비워두면 무제한으로 설정됩니다.</div>
                    </div>
                    
                    <div class="form_group">
                        <label class="form_label">링크 URL</label>
                        <input type="url" name="link_url" class="form_input" 
                               value="<?php echo htmlspecialchars($event['link_url']); ?>">
                        <div class="help_text">이벤트 상세 페이지나 참여 링크를 입력하세요.</div>
                    </div>
                    
                    <div class="form_group">
                        <label class="form_label">메인 이미지</label>
                        <?php if (!empty($event['main_image'])): ?>
                            <div class="current_image">
                                <span class="current_image_label">현재 메인 이미지:</span>
                                <img src="<?php echo $event['main_image']; ?>" alt="현재 메인 이미지">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="main_image" class="file_input" accept="image/*">
                        <div class="help_text">새 이미지를 선택하면 기존 이미지가 교체됩니다.</div>
                    </div>
                    
                    <div class="form_group">
                        <label class="form_label">상세 이미지</label>
                        <?php if (!empty($event['detail_image'])): ?>
                            <div class="current_image">
                                <span class="current_image_label">현재 상세 이미지:</span>
                                <img src="<?php echo $event['detail_image']; ?>" alt="현재 상세 이미지">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="detail_image" class="file_input" accept="image/*">
                        <div class="help_text">새 이미지를 선택하면 기존 이미지가 교체됩니다.</div>
                    </div>
                    
                    <div class="form_buttons">
                        <button type="submit" class="btn_submit">
                            <i class="fas fa-save"></i> 수정 완료
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
</body>
</html>