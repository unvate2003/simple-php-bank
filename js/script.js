document.addEventListener('DOMContentLoaded', function() {

    // Làm mới số dư trên dashboard
    const refreshButton = document.getElementById('refresh-balance-btn');
    const balanceDisplay = document.getElementById('balance-display');
    const availableBalanceTransfer = document.getElementById('available-balance-transfer'); // Số dư trên trang transfer

    function fetchBalance() {
         if (!balanceDisplay && !availableBalanceTransfer) return; // Không có element để cập nhật

         const currentText = balanceDisplay ? balanceDisplay.textContent : (availableBalanceTransfer ? availableBalanceTransfer.textContent : '');
         if (balanceDisplay) balanceDisplay.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang tải...';
         if (availableBalanceTransfer) availableBalanceTransfer.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang tải...';

         fetch('ajax_handler.php?action=getBalance')
             .then(response => {
                 if (!response.ok) {
                     throw new Error('Network response error ' + response.status);
                 }
                 return response.json();
             })
             .then(data => {
                 if (data.success && data.formattedBalance !== undefined) {
                     if (balanceDisplay) balanceDisplay.textContent = data.formattedBalance;
                     if (availableBalanceTransfer) availableBalanceTransfer.textContent = data.formattedBalance;
                 } else {
                     const errorMsg = 'Lỗi: ' + (data.message || 'Không thể lấy số dư');
                     if (balanceDisplay) balanceDisplay.textContent = errorMsg;
                     if (availableBalanceTransfer) availableBalanceTransfer.textContent = errorMsg;
                     console.warn("Balance fetch failed:", data.message);
                 }
             })
             .catch(error => {
                 console.error('Fetch error:', error);
                 const errorText = 'Lỗi khi làm mới';
                 if (balanceDisplay) balanceDisplay.textContent = errorText;
                 if (availableBalanceTransfer) availableBalanceTransfer.textContent = errorText;
             });
    }

    if (refreshButton) {
        refreshButton.addEventListener('click', fetchBalance);
    }

    // Nếu có form chuyển tiền, có thể gọi fetchBalance một lần khi load trang
    if (availableBalanceTransfer) {
        // fetchBalance(); // Bạn có thể bỏ comment dòng này nếu muốn load số dư qua AJAX ngay khi vào trang transfer
    }

    // --- (Tùy chọn) Xử lý thêm phía client ---
    // Ví dụ: Validate form chuyển tiền bằng JS trước khi submit
    // Ví dụ: Hiển thị tên người nhận khi nhập số tài khoản (cần AJAX call khác)

});