<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

global $conn; // Sử dụng biến kết nối

header('Content-Type: application/json');
start_session();

$action = $_GET['action'] ?? '';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu xác thực.']);
    http_response_code(401); exit;
}

$user_id = get_current_user_id();

switch ($action) {
    case 'getBalance':
        $sql = "SELECT balance FROM accounts WHERE user_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
             error_log("AJAX getBalance Prepare Error: " . mysqli_error($conn));
             echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu (P).']);
             http_response_code(500); exit;
        }
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        if (!mysqli_stmt_execute($stmt)) {
             error_log("AJAX getBalance Execute Error: " . mysqli_stmt_error($stmt));
             mysqli_stmt_close($stmt);
             echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu (E).']);
             http_response_code(500); exit;
        }
        $result = mysqli_stmt_get_result($stmt);
        $account = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($account) {
            echo json_encode(['success' => true, 'balance' => (float)$account['balance'], 'formattedBalance' => format_currency($account['balance']) ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.']);
            http_response_code(404);
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        http_response_code(400);
        break;
}
exit;
?>