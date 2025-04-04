<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

global $conn; // Sử dụng biến kết nối
require_login();
$user_id = get_current_user_id();

$transactions = [];
$account = null;
$error_message = null;
$limit = 20; // Giao dịch mỗi trang
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $limit;
$total_transactions = 0;
$total_pages = 1;

try {
    // Lấy tài khoản chính bằng mysqli
    $sql_acc = "SELECT id, account_number FROM accounts WHERE user_id = ? LIMIT 1";
    $stmt_acc = mysqli_prepare($conn, $sql_acc);
    if ($stmt_acc) {
        mysqli_stmt_bind_param($stmt_acc, 'i', $user_id);
        mysqli_stmt_execute($stmt_acc);
        $result_acc = mysqli_stmt_get_result($stmt_acc);
        $account = mysqli_fetch_assoc($result_acc);
        mysqli_stmt_close($stmt_acc);
    } else {
         throw new Exception("Lỗi chuẩn bị lấy tài khoản: " . mysqli_error($conn));
    }

    if ($account) {
        $account_id = $account['id'];

        // Đếm tổng số giao dịch để phân trang bằng mysqli
        $sql_count = "SELECT COUNT(*) FROM transactions WHERE account_id = ?";
        $stmt_count = mysqli_prepare($conn, $sql_count);
        if($stmt_count){
            mysqli_stmt_bind_param($stmt_count, 'i', $account_id);
            mysqli_stmt_execute($stmt_count);
            mysqli_stmt_bind_result($stmt_count, $count_result); // Bind kết quả đếm
            mysqli_stmt_fetch($stmt_count); // Fetch kết quả vào biến đã bind
            $total_transactions = $count_result;
            mysqli_stmt_close($stmt_count);
            $total_pages = ceil($total_transactions / $limit);
            // Đảm bảo trang hiện tại không vượt quá tổng số trang
             if ($page > $total_pages && $total_pages > 0) {
                $page = $total_pages;
                $offset = ($page - 1) * $limit; // Tính lại offset
            } elseif ($page < 1) {
                 $page = 1;
                 $offset = 0;
            }

        } else {
             throw new Exception("Lỗi chuẩn bị đếm giao dịch: " . mysqli_error($conn));
        }


        // Lấy giao dịch cho trang hiện tại bằng mysqli
        $sql_trans = "SELECT t.timestamp, t.transaction_type, t.amount, t.description, related.account_number as related_account_number
                      FROM transactions t
                      LEFT JOIN accounts related ON t.related_account_id = related.id
                      WHERE t.account_id = ?
                      ORDER BY t.timestamp DESC
                      LIMIT ? OFFSET ?";
        $stmt_trans = mysqli_prepare($conn, $sql_trans);
         if ($stmt_trans) {
             // 'iii' = integer (account_id), integer (limit), integer (offset)
             mysqli_stmt_bind_param($stmt_trans, 'iii', $account_id, $limit, $offset);
             mysqli_stmt_execute($stmt_trans);
             $result_trans = mysqli_stmt_get_result($stmt_trans);
             $transactions = mysqli_fetch_all($result_trans, MYSQLI_ASSOC); // Lấy tất cả dòng
             mysqli_stmt_close($stmt_trans);
         } else {
              throw new Exception("Lỗi chuẩn bị lấy danh sách giao dịch: " . mysqli_error($conn));
         }
    }
    // Không set lỗi nếu account null ở đây

} catch (Exception $e) {
    error_log("History Error: " . $e->getMessage());
    $error_message = "Lỗi khi truy vấn lịch sử giao dịch.";
}

$page_title = "Lịch sử giao dịch";
include __DIR__ . '/includes/header.php';
?>
<div class="container">
    <h1 class="mb-4">Lịch sử giao dịch</h1>

    <?php display_flash_message(); ?>

    <?php
    // Hiển thị lỗi hoặc thông tin tài khoản
    if ($error_message) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    } elseif ($account) {
        echo '<p class="lead">Số tài khoản: ' . htmlspecialchars($account['account_number']) . '</p>';
    } else {
         echo '<div class="alert alert-warning">Không tìm thấy tài khoản để xem lịch sử.</div>';
    }
    ?>

    <?php if (!empty($transactions)): ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Thời gian</th><th>Loại GD</th><th>Số tiền</th><th>TK Liên quan</th><th>Nội dung</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): ?>
                                <tr>
                                    <td><?php echo date("d/m/Y H:i:s", strtotime($tx['timestamp'])); ?></td>
                                    <td>
                                         <span class="badge bg-<?php
                                            switch($tx['transaction_type']) { /* ... switch case như cũ ... */ }
                                        ?>"><?php echo htmlspecialchars($tx['transaction_type']); ?></span>
                                    </td>
                                    <td class="<?php echo (in_array($tx['transaction_type'], ['TRANSFER_OUT', 'WITHDRAWAL'])) ? 'text-danger fw-bold' : 'text-success'; ?>">
                                        <?php echo (in_array($tx['transaction_type'], ['TRANSFER_OUT', 'WITHDRAWAL'])) ? '-' : '+'; ?>
                                        <?php echo format_currency($tx['amount']); ?>
                                    </td>
                                    <td><?php echo $tx['related_account_number'] ? htmlspecialchars($tx['related_account_number']) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($tx['description']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center flex-wrap"> <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Trước</a>
                    </li>
                    <?php
                    // Logic hiển thị phân trang thông minh hơn (ví dụ: chỉ hiển thị vài trang xung quanh trang hiện tại)
                    $range = 2; // Số trang hiển thị trước và sau trang hiện tại
                    $start = max(1, $page - $range);
                    $end = min($total_pages, $page + $range);

                    if ($start > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                        if ($start > 2) {
                           echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor;

                     if ($end < $total_pages) {
                         if ($end < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                         }
                         echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'">'.$total_pages.'</a></li>';
                    }
                    ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Sau</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php elseif(!$error_message && $account): // Chỉ hiển thị "chưa có giao dịch" nếu không có lỗi và có tài khoản ?>
        <div class="alert alert-secondary">Chưa có giao dịch nào trong lịch sử.</div>
    <?php endif; ?>
    <div class="mt-3">
        <a href="dashboard.php" class="btn btn-outline-secondary">Quay lại Bảng điều khiển</a>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>