<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

global $conn; // Sử dụng biến kết nối

require_guest();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Lấy và Validate dữ liệu ---
    $username = trim($_POST['username'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation (Giữ nguyên logic)
    $errors = [];
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = 'Vui lòng điền đầy đủ các trường bắt buộc.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
         $errors[] = 'Tên đăng nhập không hợp lệ (3-20 ký tự, chữ, số, _).';
    }
    if (!$email) {
         $errors[] = 'Địa chỉ email không hợp lệ.';
    }
    if (strlen($password) < 6) {
         $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    }

    if (!empty($errors)) {
        set_flash_message('error', implode('<br>', $errors));
        redirect('register.php');
    }

    // --- Kiểm tra Username và Email đã tồn tại chưa ---
    $sql_check = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    if (!$stmt_check) {
         error_log("MySQLi prepare error (check user): " . mysqli_error($conn));
         set_flash_message('error', 'Lỗi hệ thống (DBPC). Vui lòng thử lại.');
         redirect('register.php');
    }
    mysqli_stmt_bind_param($stmt_check, 'ss', $username, $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        mysqli_stmt_close($stmt_check);
        set_flash_message('error', 'Tên đăng nhập hoặc Email đã được sử dụng.');
        redirect('register.php');
    }
    mysqli_stmt_close($stmt_check);

    // --- Hash mật khẩu ---
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // --- Bắt đầu Transaction ---
    mysqli_begin_transaction($conn);

    try {
        // 1. Tạo User
        $sql_user = "INSERT INTO users (username, password_hash, email, full_name) VALUES (?, ?, ?, ?)";
        $stmt_user = mysqli_prepare($conn, $sql_user);
        if (!$stmt_user) throw new Exception("Lỗi chuẩn bị tạo user: " . mysqli_error($conn));
        mysqli_stmt_bind_param($stmt_user, 'ssss', $username, $password_hash, $email, $full_name);
        if (!mysqli_stmt_execute($stmt_user)) throw new Exception("Lỗi thực thi tạo user: " . mysqli_stmt_error($stmt_user));
        $user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_user);
        if (!$user_id) throw new Exception("Không thể lấy user ID sau khi tạo.");

        // 2. Tạo Số tài khoản
        $account_number = generate_account_number($conn); // Sử dụng hàm đã sửa

        // 3. Tạo Account
        $bonus_amount = defined('REGISTER_BONUS_AMOUNT') ? REGISTER_BONUS_AMOUNT : 0;
        $sql_account = "INSERT INTO accounts (user_id, account_number, balance) VALUES (?, ?, ?)";
        $stmt_account = mysqli_prepare($conn, $sql_account);
         if (!$stmt_account) throw new Exception("Lỗi chuẩn bị tạo account: " . mysqli_error($conn));
        mysqli_stmt_bind_param($stmt_account, 'isd', $user_id, $account_number, $bonus_amount);
        if (!mysqli_stmt_execute($stmt_account)) throw new Exception("Lỗi thực thi tạo account: " . mysqli_stmt_error($stmt_account));
        $account_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_account);
        if (!$account_id) throw new Exception("Không thể lấy account ID sau khi tạo.");

        // 4. Ghi log giao dịch tiền thưởng (nếu có)
        if ($bonus_amount > 0) {
             $sql_trans = "INSERT INTO transactions (account_id, transaction_type, amount, description) VALUES (?, 'REGISTER_BONUS', ?, ?)";
             $stmt_trans = mysqli_prepare($conn, $sql_trans);
             if (!$stmt_trans) throw new Exception("Lỗi chuẩn bị tạo transaction bonus: " . mysqli_error($conn));
             $desc_bonus = 'Tiền thưởng đăng ký';
             mysqli_stmt_bind_param($stmt_trans, 'ids', $account_id, $bonus_amount, $desc_bonus);
             if (!mysqli_stmt_execute($stmt_trans)) throw new Exception("Lỗi thực thi tạo transaction bonus: " . mysqli_stmt_error($stmt_trans));
             mysqli_stmt_close($stmt_trans);
        }

        // --- Commit Transaction ---
        mysqli_commit($conn);

        set_flash_message('success', 'Đăng ký thành công! STK: ' . htmlspecialchars($account_number) . '. Vui lòng đăng nhập.');
        redirect('login.php');

    } catch (Exception $e) {
         mysqli_rollback($conn); // Rollback nếu có lỗi
         error_log("Registration Error: " . $e->getMessage());
         set_flash_message('error', 'Đã xảy ra lỗi trong quá trình đăng ký. Vui lòng thử lại.'); // Giấu lỗi chi tiết
         redirect('register.php');
    }

} else {
    redirect('register.php');
}
?>