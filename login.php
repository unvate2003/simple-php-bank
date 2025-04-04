<?php
require_once __DIR__ . '/includes/functions.php';
require_guest(); // Yêu cầu chưa đăng nhập

$page_title = "Đăng nhập";
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0 text-center">Đăng nhập Hệ thống</h4>
            </div>
            <div class="card-body">
                <?php display_flash_message(); // Hiển thị thông báo lỗi/thành công ?>
                <form action="process_login.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập:</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                         </div>
                     <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Đăng nhập</button>
                     </div>
                </form>
                <hr>
                 <p class="text-center mb-0">Chưa có tài khoản? <a href="register.php">Đăng ký ngay!</a></p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>