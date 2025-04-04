<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

global $conn; // Sử dụng biến kết nối

require_login();
$user_id = get_current_user_id();

// QUAN TRỌNG: Kiểm tra CSRF token ở đây trong ứng dụng thực tế!

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Lấy và Validate dữ liệu ---
    $source_account_id = filter_input(INPUT_POST, 'source_account_id', FILTER_VALIDATE_INT);
    $recipient_account_number = filter_input(INPUT_POST, 'recipient_account_number', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');

    // Validation (Giữ nguyên)
    $errors = [];
    if (!$source_account_id) $errors[] = 'Tài khoản nguồn không hợp lệ.';
    if (!$recipient_account_number || !preg_match('/^[0-9]+$/', $recipient_account_number)) $errors[] = 'Số tài khoản nhận không hợp lệ.';
    if ($amount === false || $amount <= 0) $errors[] = 'Số tiền không hợp lệ.';
    if (mb_strlen($description) > 250) $errors[] = 'Nội dung chuyển khoản quá dài (tối đa 250 ký tự).';

    if (!empty($errors)) {
        set_flash_message('error', implode('<br>', $errors));
        redirect('transfer.php');
    }

    // --- Bắt đầu Transaction ---
    mysqli_begin_transaction($conn);
    try {
        // 1. Lấy và Khóa tài khoản nguồn (FOR UPDATE)
        $sql_source = "SELECT id, user_id, balance, account_number FROM accounts WHERE id = ? AND user_id = ? FOR UPDATE";
        $stmt_source = mysqli_prepare($conn, $sql_source);
        if (!$stmt_source) throw new Exception("Lỗi chuẩn bị (nguồn)");
        mysqli_stmt_bind_param($stmt_source, 'ii', $source_account_id, $user_id);
        mysqli_stmt_execute($stmt_source);
        mysqli_stmt_bind_result($stmt_source, $s_id, $s_user_id, $s_balance, $s_account_number);
        if (!mysqli_stmt_fetch($stmt_source)) { mysqli_stmt_close($stmt_source); throw new Exception("Tài khoản nguồn không hợp lệ/không thuộc về bạn."); }
        $source_account = ['id' => $s_id, 'user_id' => $s_user_id, 'balance' => $s_balance, 'account_number' => $s_account_number];
        mysqli_stmt_close($stmt_source);

        if ($source_account['balance'] < $amount) throw new Exception("Số dư không đủ (" . format_currency($source_account['balance']) . ").");

        // 2. Tìm tài khoản nhận
        $sql_recipient = "SELECT id, account_number FROM accounts WHERE account_number = ?";
        $stmt_recipient = mysqli_prepare($conn, $sql_recipient);
        if (!$stmt_recipient) throw new Exception("Lỗi chuẩn bị (nhận)");
        mysqli_stmt_bind_param($stmt_recipient, 's', $recipient_account_number);
        mysqli_stmt_execute($stmt_recipient);
        $result_recipient = mysqli_stmt_get_result($stmt_recipient);
        $recipient_account = mysqli_fetch_assoc($result_recipient);
        mysqli_stmt_close($stmt_recipient);

        if (!$recipient_account) throw new Exception("Số tài khoản nhận không tồn tại.");
        if ($recipient_account['id'] == $source_account['id']) throw new Exception("Không thể tự chuyển khoản cho chính mình.");
        $recipient_account_id = $recipient_account['id'];

        // 3. Trừ tiền nguồn
        $sql_update_source = "UPDATE accounts SET balance = balance - ? WHERE id = ?";
        $stmt_update_source = mysqli_prepare($conn, $sql_update_source);
        if (!$stmt_update_source) throw new Exception("Lỗi chuẩn bị (trừ tiền)");
        mysqli_stmt_bind_param($stmt_update_source, 'di', $amount, $source_account_id);
        if (!mysqli_stmt_execute($stmt_update_source)) throw new Exception("Lỗi thực thi (trừ tiền): " . mysqli_stmt_error($stmt_update_source));
        mysqli_stmt_close($stmt_update_source);

        // 4. Cộng tiền nhận
        $sql_update_recipient = "UPDATE accounts SET balance = balance + ? WHERE id = ?";
        $stmt_update_recipient = mysqli_prepare($conn, $sql_update_recipient);
        if (!$stmt_update_recipient) throw new Exception("Lỗi chuẩn bị (cộng tiền)");
        mysqli_stmt_bind_param($stmt_update_recipient, 'di', $amount, $recipient_account_id);
        if (!mysqli_stmt_execute($stmt_update_recipient)) throw new Exception("Lỗi thực thi (cộng tiền): " . mysqli_stmt_error($stmt_update_recipient));
        mysqli_stmt_close($stmt_update_recipient);

        // 5. Ghi log nguồn (TRANSFER_OUT)
        $desc_source = "Chuyển tới STK " . htmlspecialchars($recipient_account['account_number']) . ". ND: " . $description;
        $sql_log_source = "INSERT INTO transactions (account_id, related_account_id, transaction_type, amount, description) VALUES (?, ?, 'TRANSFER_OUT', ?, ?)";
        $stmt_log_source = mysqli_prepare($conn, $sql_log_source);
        if (!$stmt_log_source) throw new Exception("Lỗi chuẩn bị (log nguồn)");
        mysqli_stmt_bind_param($stmt_log_source, 'iids', $source_account_id, $recipient_account_id, $amount, $desc_source);
        if (!mysqli_stmt_execute($stmt_log_source)) throw new Exception("Lỗi thực thi (log nguồn): " . mysqli_stmt_error($stmt_log_source));
        mysqli_stmt_close($stmt_log_source);

        // 6. Ghi log nhận (TRANSFER_IN)
        $desc_recipient = "Nhận từ STK " . htmlspecialchars($source_account['account_number']) . ". ND: " . $description;
        $sql_log_recipient = "INSERT INTO transactions (account_id, related_account_id, transaction_type, amount, description) VALUES (?, ?, 'TRANSFER_IN', ?, ?)";
        $stmt_log_recipient = mysqli_prepare($conn, $sql_log_recipient);
        if (!$stmt_log_recipient) throw new Exception("Lỗi chuẩn bị (log nhận)");
        mysqli_stmt_bind_param($stmt_log_recipient, 'iids', $recipient_account_id, $source_account_id, $amount, $desc_recipient);
        if (!mysqli_stmt_execute($stmt_log_recipient)) throw new Exception("Lỗi thực thi (log nhận): " . mysqli_stmt_error($stmt_log_recipient));
        mysqli_stmt_close($stmt_log_recipient);

        // --- Commit Transaction ---
        mysqli_commit($conn);

        set_flash_message('success', 'Chuyển khoản thành công ' . format_currency($amount) . ' tới STK ' . htmlspecialchars($recipient_account['account_number']));
        redirect('dashboard.php');

    } catch (Exception $e) {
        mysqli_rollback($conn); // Rollback
        error_log("Transfer Process Error: " . $e->getMessage());
        // Hiển thị lỗi chung cho người dùng, lỗi cụ thể hơn có thể dùng trong môi trường dev
        set_flash_message('error', 'Giao dịch thất bại. ' . htmlspecialchars($e->getMessage())); // Có thể hiển thị lỗi cụ thể hơn nếu muốn
        // set_flash_message('error', 'Giao dịch thất bại. Vui lòng thử lại hoặc liên hệ hỗ trợ.');
        redirect('transfer.php');
    }

} else {
    redirect('transfer.php');
}
?>