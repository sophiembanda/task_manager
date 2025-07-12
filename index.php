<?php
require_once 'config/config.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Check if any admin user exists
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$stmt->execute();
$result = $stmt->fetch();

// If no admin exists, redirect to setup
if ($result['count'] == 0) {
    header('Location: setup.php');
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager - Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .welcome { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Task Manager</h1>
            <div>
                <span class="welcome">Welcome, <?= htmlspecialchars($user['full_name']) ?> (<?= ucfirst($user['role']) ?>)</span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <div class="dashboard">
            <?php if ($user['role'] === 'admin'): ?>
                <h2>Admin Dashboard</h2>
                <p>You have full access to manage users and tasks.</p>
                <a href="admin/users.php" class="btn">Manage Users</a>
                <a href="admin/tasks.php" class="btn">Manage Tasks</a>
            <?php else: ?>
                <h2>User Dashboard</h2>
                <p>View and manage your assigned tasks.</p>
                <a href="user/tasks.php" class="btn">My Tasks</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
