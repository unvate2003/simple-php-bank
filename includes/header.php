<?php
if (!isset($page_title)) {
    $page_title = defined('SITE_NAME') ? SITE_NAME : 'Simple Bank';
}
start_session();
$username = get_current_username();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Simple Bank'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="<?php echo defined('BASE_URL') ? BASE_URL : '/'; ?>"><?php echo defined('SITE_NAME') ? SITE_NAME : 'Simple Bank'; ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (is_logged_in()): ?>
          <li class="nav-item"><a class="nav-link active" href="dashboard.php">Bảng điều khiển</a></li>
          <li class="nav-item"><a class="nav-link" href="transfer.php">Chuyển khoản</a></li>
          <li class="nav-item"><a class="nav-link" href="history.php">Lịch sử GD</a></li>
           <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">Chào, <?php echo htmlspecialchars($username); ?></a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUser">
                <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
              </ul>
            </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active' : ''; ?>" href="login.php">Đăng nhập</a></li>
          <li class="nav-item"><a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'register.php') ? 'active' : ''; ?>" href="register.php">Đăng ký</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container mt-4">