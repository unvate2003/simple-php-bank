<?php
// --- PHẦN PHP Ở ĐẦU FILE transfer.php CỦA BẠN GIỮ NGUYÊN ---
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

global $conn;
require_login();
$user_id = get_current_user_id();
$source_account = null;
$error_message = null;

try {
    // Sử dụng mysqli để lấy tài khoản nguồn
    $sql = "SELECT id, account_number, balance FROM accounts WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $source_account = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    } else {
         throw new Exception("Lỗi chuẩn bị lấy tài khoản nguồn: " . mysqli_error($conn));
    }

    if (!$source_account && !$error_message) {
        $error_message = "Không tìm thấy tài khoản nguồn của bạn.";
    }
} catch (Exception $e) {
    error_log("Transfer Page Error: " . $e->getMessage());
    $error_message = "Lỗi khi tải thông tin tài khoản nguồn.";
}

$page_title = "Chuyển khoản";
include __DIR__ . '/includes/header.php'; // Include header ở đây
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-sm">
                 <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Thực hiện Chuyển khoản</h4>
                </div>
                <div class="card-body">
                    <?php display_flash_message(); // Hiển thị thông báo ?>
                    <?php if ($error_message): // Hiển thị lỗi nếu có ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <?php if ($source_account): // Chỉ hiển thị form nếu có tài khoản nguồn ?>
                        <div class="alert alert-info">
                            <p class="mb-1"><strong>Tài khoản nguồn:</strong> <?php echo htmlspecialchars($source_account['account_number']); ?></p>
                            <p class="mb-0"><strong>Số dư khả dụng:</strong> <span id="available-balance-transfer"><?php echo format_currency($source_account['balance']); ?></span></p>
                        </div>

                        <form action="process_transfer.php" method="POST" onsubmit="return confirm('Xác nhận thực hiện chuyển khoản? Hành động này không thể hoàn tác.');">
                            <input type="hidden" name="source_account_id" value="<?php echo $source_account['id']; ?>">

                            <div class="mb-3">
                                <label for="recipient_account_number" class="form-label">Số tài khoản nhận:</label>
                                <input type="text" class="form-control" id="recipient_account_number" name="recipient_account_number" required pattern="[0-9]+" title="Chỉ nhập số tài khoản hợp lệ">
                            </div>
                            <div class="mb-3">
                                <label for="amount" class="form-label">Số tiền:</label>
                                <input type="number" class="form-control" id="amount" name="amount" required min="1000" step="1000" placeholder="Tối thiểu 1,000 VND">
                            </div>
                             <div class="mb-3">
                                <label for="description" class="form-label">Nội dung chuyển khoản:</label>
                                <textarea class="form-control" id="description" name="description" rows="3" maxlength="250"></textarea>
                            </div>
                             <div class="d-grid">
                                <button type="submit" class="btn btn-primary" <?php echo ($source_account['balance'] <= 0) ? 'disabled' : ''; ?> >Xác nhận chuyển khoản</button>
                             </div>
                              <?php if ($source_account['balance'] <= 0): ?>
                                <p class="text-danger text-center mt-2 small">Số dư không đủ để thực hiện giao dịch.</p>
                             <?php endif; ?>
                        </form>
                    <?php else: // Hiển thị thông báo nếu không có tài khoản nguồn ?>
                         <p class="text-danger">Không thể thực hiện chuyển khoản do không tìm thấy tài khoản nguồn.</p>
                         <a href="dashboard.php" class="btn btn-secondary">Quay lại Bảng điều khiển</a>
                    <?php endif; ?>
                </div>
            </div>
             <div class="mt-3 text-center">
                 <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">Quay lại Bảng điều khiển</a>
            </div>
        </div>
    </div>
</div>
<?php
// Dòng include footer nên nằm ở cuối cùng
include __DIR__ . '/includes/footer.php';
?>