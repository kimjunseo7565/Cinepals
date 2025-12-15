<?php
require_once("inc/db.php");

// 회원 ID 가져오기
$member_id = '';
if (isset($_GET['id'])) {
    $member_id = $_GET['id'];
}

if ($member_id == '') {
    echo "<script>alert('잘못된 접근입니다.'); window.close();</script>";
    exit;
}

// 회원 정보 가져오기
$member_query = "SELECT * FROM members WHERE member_id = '$member_id'";
$member_result = db_select($member_query);

if (empty($member_result)) {
    echo "<script>alert('존재하지 않는 회원입니다.'); window.close();</script>";
    exit;
}

$member = $member_result[0];

// 생년월일 처리
$birth_info = '정보 없음';
if (!empty($member['birth_date'])) {
    $birth_info = date('Y년 m월 d일', strtotime($member['birth_date']));
}

// 상태 텍스트
$status_text = ($member['status'] == 'banned') ? '정지' : '활성';
$status_color = ($member['status'] == 'banned') ? '#dc3545' : '#28a745';

// 최근 로그인
$last_login = '로그인 기록 없음';
if (!empty($member['last_login'])) {
    $last_login = date('Y-m-d H:i', strtotime($member['last_login']));
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($member['nickname']); ?> - 회원 상세 정보</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a1d24;
            color: #fff;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            background: #2a2d34;
            border-radius: 8px;
            padding: 30px;
            border: 1px solid #444;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #444;
        }

        .header h2 {
            margin: 0;
            color: #fff;
            font-size: 24px;
        }

        .profile_section {
            text-align: center;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile_image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #444;
            margin: 0 auto 15px auto;
            background: #2a2d34;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .member_name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .member_status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
        }

        .info_section {
            margin-bottom: 30px;
        }

        .info_row {
            display: flex;
            margin-bottom: 15px;
            padding: 12px 0;
            border-bottom: 2px solid #555;
            /* 기존: 1px solid #333 */
        }

        .info_label {
            width: 120px;
            font-weight: bold;
            color: #aaa;
        }

        .info_value {
            flex: 1;
            color: #fff;
        }

        .bio_section {
            background: #1a1d24;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }

        .bio_title {
            font-weight: bold;
            color: #aaa;
            margin-bottom: 10px;
        }

        .bio_content {
            color: #ccc;
            line-height: 1.5;
        }

        .close_btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 20px;
            width: 100%;
        }

        .close_btn:hover {
            background: #c82333;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>회원 상세 정보</h2>
        </div>

        <div class="profile_section">
            <?php if (!empty($member['profile_image']) && file_exists($member['profile_image'])): ?>
                <img src="<?php echo $member['profile_image']; ?>" alt="프로필 이미지" class="profile_image">
            <?php else: ?>
                <div class="profile_image">
                    <i class="fas fa-user" style="font-size: 40px; color: #888;"></i>
                </div>
            <?php endif; ?>

            <div class="member_name"><?php echo htmlspecialchars($member['nickname']); ?></div>
            <span class="member_status" style="background: <?php echo $status_color; ?>; color: white;">
                <?php echo $status_text; ?>
            </span>
        </div>

        <div class="info_section">
            <div class="info_row">
                <div class="info_label">이름:</div>
                <div class="info_value"><?php echo htmlspecialchars($member['name']); ?></div>
            </div>

            <div class="info_row">
                <div class="info_label">이메일:</div>
                <div class="info_value"><?php echo htmlspecialchars($member['email']); ?></div>
            </div>

            <div class="info_row">
                <div class="info_label">전화번호:</div>
                <div class="info_value"><?php echo !empty($member['phone']) ? htmlspecialchars($member['phone']) : '정보 없음'; ?></div>
            </div>

            <div class="info_row">
                <div class="info_label">생년월일:</div>
                <div class="info_value"><?php echo $birth_info; ?></div>
            </div>

            <div class="info_row">
                <div class="info_label">가입일:</div>
                <div class="info_value"><?php echo date('Y년 m월 d일', strtotime($member['join_date'])); ?></div>
            </div>

            <div class="info_row">
                <div class="info_label">최근 로그인:</div>
                <div class="info_value"><?php echo $last_login; ?></div>
            </div>

            <div class="info_row">
                <div class="info_label">회원 상태:</div>
                <div class="info_value" style="color: <?php echo $status_color; ?>; font-weight: bold;">
                    <?php echo $status_text; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($member['bio'])): ?>
            <div class="bio_section">
                <div class="bio_title">한줄소개</div>
                <div class="bio_content"><?php echo nl2br(htmlspecialchars($member['bio'])); ?></div>
            </div>
        <?php endif; ?>

        <button class="close_btn" onclick="window.close()">창 닫기</button>
    </div>
</body>

</html>