<?php
// Nạp file config nếu chưa có
if (!defined('DB_HOST')) {
    $configPath = __DIR__ . '/config.php';
    if (file_exists($configPath)) {
         require_once $configPath;
    } else {
        error_log("FATAL ERROR: Database configuration file not found at " . $configPath);
        die("Lỗi cấu hình hệ thống. Vui lòng liên hệ quản trị viên.");
    }
}

// Kết nối đến MySQL bằng mysqli
// Sử dụng @ để tự xử lý lỗi kết nối thay vì để PHP hiển thị lỗi mặc định
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Kiểm tra kết nối
if (!$conn) {
    // Log lỗi chi tiết
    error_log("MySQLi Connection Error (" . mysqli_connect_errno() . "): " . mysqli_connect_error());
    // Hiển thị thông báo chung
    die("Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.");
}

// Thiết lập bộ ký tự kết nối (Rất quan trọng!)
if (!mysqli_set_charset($conn, DB_CHARSET)) {
     error_log("MySQLi Error loading character set " . DB_CHARSET . ": " . mysqli_error($conn));
     // Không nhất thiết phải die, nhưng ghi log là quan trọng
}

// Biến $conn là tài nguyên kết nối, sử dụng global trong các file khác
?>