// 모달 요소 가져오기
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('deleteAccountModal');
    const deleteAccountBtn = document.getElementById('deleteAccountBtn');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.querySelector('.cancel_btn');

    if (!deleteAccountBtn) return;

    // 회원탈퇴 버튼 클릭 시 모달 표시
    deleteAccountBtn.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'flex';
    });

    // 닫기 버튼 클릭 시 모달 닫기
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // 취소 버튼 클릭 시 모달 닫기
    cancelBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // 모달 외부 클릭 시 닫기
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // 폼 제출 전 한번 더 확인
    const deleteAccountForm = document.getElementById('deleteAccountForm');
    if (deleteAccountForm) {
        deleteAccountForm.addEventListener('submit', function(e) {
            if (!confirm('회원탈퇴를 진행하시겠습니까? 계정 정보는 완전히 삭제되며 복구할 수 없습니다.')) {
                e.preventDefault();
            }
        });
    }
});