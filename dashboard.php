<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

global $conn; // Sử dụng biến kết nối
require_login();

$user_id = get_current_user_id();
$account = null;
$recent_transactions = [];
$error_message = null;

try {
    // Lấy thông tin tài khoản chính bằng mysqli
    $sql_acc = "SELECT id, account_number, balance FROM accounts WHERE user_id = ? LIMIT 1";
    $stmt_acc = mysqli_prepare($conn, $sql_acc);
    if ($stmt_acc) {
        mysqli_stmt_bind_param($stmt_acc, 'i', $user_id);
        mysqli_stmt_execute($stmt_acc);
        $result_acc = mysqli_stmt_get_result($stmt_acc);
        $account = mysqli_fetch_assoc($result_acc); // Lấy 1 dòng kết quả
        mysqli_stmt_close($stmt_acc);
    } else {
         throw new Exception("Lỗi chuẩn bị lấy tài khoản: " . mysqli_error($conn));
    }

    if ($account) {
        // Lấy 5 giao dịch gần nhất bằng mysqli
        $sql_trans = "SELECT timestamp, transaction_type, amount, description FROM transactions WHERE account_id = ? ORDER BY timestamp DESC LIMIT 5";
        $stmt_trans = mysqli_prepare($conn, $sql_trans);
         if ($stmt_trans) {
             mysqli_stmt_bind_param($stmt_trans, 'i', $account['id']);
             mysqli_stmt_execute($stmt_trans);
             $result_trans = mysqli_stmt_get_result($stmt_trans);
             $recent_transactions = mysqli_fetch_all($result_trans, MYSQLI_ASSOC); // Lấy tất cả các dòng
             mysqli_stmt_close($stmt_trans);
         } else {
              throw new Exception("Lỗi chuẩn bị lấy giao dịch: " . mysqli_error($conn));
         }
    }
    // Không cần đặt $error_message nếu $account null ở đây, sẽ kiểm tra trong HTML

} catch (Exception $e) { // Bắt Exception chung
    error_log("Dashboard Error: " . $e->getMessage());
    $error_message = "Lỗi khi tải dữ liệu bảng điều khiển.";
}

$page_title = "Bảng điều khiển";
include __DIR__ . '/includes/header.php';
?>
<div class="container">
    <h1 class="mb-4">Chào mừng trở lại, <?php echo htmlspecialchars(get_current_username()); ?>!</h1>

    <?php display_flash_message(); ?>

    <?php
    // Hiển thị lỗi nếu có hoặc nếu không tìm thấy tài khoản
    if ($error_message) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    } elseif (!$account) {
        echo '<div class="alert alert-warning">Không tìm thấy thông tin tài khoản. Bạn đã đăng ký tài khoản ngân hàng chưa?</div>';
    }
    ?>

    <?php if ($account): // Chỉ hiển thị nếu tài khoản tồn tại ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white"><h5 class="mb-0">Thông tin Tài khoản</h5></div>
            <div class="card-body">
                <p class="card-text"><strong>Số tài khoản:</strong> <?php echo htmlspecialchars($account['account_number']); ?></p>
                <p class="card-text fs-4">
                    <strong>Số dư hiện tại:</strong>
                    <span class="fw-bold text-success" id="balance-display"><?php echo format_currency($account['balance']); ?></span>
                    <button class="btn btn-sm btn-outline-secondary" id="refresh-balance-btn" title="Làm mới số dư">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/></svg>
                    </button>
                </p>
                <a href="transfer.php" class="btn btn-primary">Chuyển khoản</a>
                <a href="history.php" class="btn btn-outline-primary">Xem đầy đủ lịch sử</a>
            </div>
        </div>

        <div class="card shadow-sm">
             <div class="card-header"><h5 class="mb-0">Giao dịch gần đây</h5></div>
            <div class="card-body p-0">
                 <?php if (!empty($recent_transactions)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr><th>Thời gian</th><th>Loại GD</th><th>Số tiền</th><th>Nội dung</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_transactions as $tx): ?>
                                    <tr>
                                        <td><?php echo date("d/m/Y H:i", strtotime($tx['timestamp'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch($tx['transaction_type']) {
                                                    case 'TRANSFER_OUT': echo 'danger'; break; case 'WITHDRAWAL': echo 'warning'; break;
                                                    case 'TRANSFER_IN': echo 'success'; break; case 'DEPOSIT': echo 'info'; break;
                                                    case 'REGISTER_BONUS': echo 'primary'; break; default: echo 'secondary';
                                                }
                                            ?>"><?php echo htmlspecialchars($tx['transaction_type']); ?></span>
                                        </td>
                                        <td class="<?php echo (in_array($tx['transaction_type'], ['TRANSFER_OUT', 'WITHDRAWAL'])) ? 'text-danger fw-bold' : 'text-success'; ?>">
                                            <?php echo (in_array($tx['transaction_type'], ['TRANSFER_OUT', 'WITHDRAWAL'])) ? '-' : '+'; ?>
                                            <?php echo format_currency($tx['amount']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($tx['description']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                 <?php else: ?>
                    <p class="text-center p-3">Chưa có giao dịch nào gần đây.</p>
                 <?php endif; ?>
            </div>
        </div>
    <?php endif; // Kết thúc kiểm tra $account ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>