function check_input() {
    // 먼저 체크박스 검사
    const ageCheck = document.querySelector('input[name="age_check"]');
    const privacyCheck = document.querySelector('input[name="privacy_check"]');
    
    if (!ageCheck.checked) {
        alert("만 14세 이상 이용약관 동의가 필요합니다.");
        ageCheck.focus();
        return false;
    }

    if (!privacyCheck.checked) {
        alert("개인정보 수집 및 이용 동의가 필요합니다.");
        privacyCheck.focus();
        return false;
    }

    // 기존 입력값 검사
    if (!document.member_form.email.value) {
        alert("이메일을 입력하세요!");
        document.member_form.email.focus();
        return false;
    }

    if (!document.member_form.pass.value) {
        alert("비밀번호를 입력하세요!");
        document.member_form.pass.focus();
        return false;
    }

    if (!document.member_form.pass_confirm.value) {
        alert("비밀번호확인을 입력하세요!");
        document.member_form.pass_confirm.focus();
        return false;
    }

    if (!document.member_form.nickname.value) {
        alert("닉네임을 입력하세요!");
        document.member_form.nickname.focus();
        return false;
    }

    if (document.member_form.pass.value != 
        document.member_form.pass_confirm.value) {
        alert("비밀번호가 일치하지 않습니다.\n다시 입력해 주세요!");
        document.member_form.pass.focus();
        document.member_form.pass.select();
        return false;
    }

    document.member_form.submit();
}