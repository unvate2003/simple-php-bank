<?php
require_once __DIR__ . '/includes/functions.php';
require_guest(); // Yêu cầu chưa đăng nhập

$page_title = "Đăng ký tài khoản";
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0 text-center">Đăng ký tài khoản mới</h4>
            </div>
            <div class="card-body">
                 <?php display_flash_message(); ?>
                 <form action="process_register.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập:</label>
                        <input type="text" class="form-control" id="username" name="username" required pattern="^[a-zA-Z0-9_]{3,20}$" title="Từ 3-20 ký tự, chỉ gồm chữ cái, số và dấu gạch dưới (_).">
                        <div class="form-text">Từ 3-20 ký tự, chỉ gồm chữ cái, số và dấu gạch dưới (_).</div>
                    </div>
                     <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                     <div class="mb-3">
                        <label for="full_name" class="form-label">Họ và tên:</label>
                        <input type="text" class="form-control" id="full_name" name="full_name">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu:</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                         <div class="form-text">Ít nhất 6 ký tự.</div>
                    </div>
                     <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu:</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                     <div class="d-grid">
                        <button type="submit" class="btn btn-success">Đăng ký</button>
                     </div>
                </form>
                 <hr>
                 <p class="text-center mb-0">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>