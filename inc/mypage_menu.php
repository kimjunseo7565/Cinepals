            <aside class="side_menu">
                <div class="menu_list">
                    <a href="mypage.php" class="menu_item">마이페이지(프로필,회원정보)</a>
                    <a href="my_comments.php" class="menu_item">리뷰</a>
                    <a href="mypage_movies.php" class="menu_item">영화목록</a>
                    <a href="#" class="menu_item" id="deleteAccountBtn">회원탈퇴</a>
                    <a href="logout.php" class="menu_item">로그아웃</a>
                </div>
            </aside>

            <div id="deleteAccountModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3>회원탈퇴</h3>
                    <p>정말로 탈퇴하시겠습니까?</p>
                    <p class="info-text">※ 회원탈퇴 시 개인정보는 모두 삭제되나, 작성한 리뷰와 게시글은 '탈퇴한 회원'으로 표시되어 유지됩니다.</p>
                    <form id="deleteAccountForm" action="member_delete.php" method="POST">
                        <div class="form_group">
                            <label for="password">비밀번호 확인</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="button_group">
                            <button type="submit" class="delete_btn">탈퇴하기</button>
                            <button type="button" class="cancel_btn">취소</button>
                        </div>
                    </form>
                </div>
            </div>