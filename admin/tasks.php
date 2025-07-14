<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/email.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$message = '';
$messageType = '';

// Handle task actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_task') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assigned_to = $_POST['assigned_to'] ?? 0;
        $deadline = $_POST['deadline'] ?? '';
        
        if (empty($title) || empty($assigned_to)) {
            $message = 'Title and assigned user are required';
            $messageType = 'error';
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO tasks (title, description, assigned_to, created_by, deadline) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$title, $description, $assigned_to, $_SESSION['user_id'], $deadline ?: null])) {
                    // Get assigned user's email and info for notification
                    $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
                    $stmt->execute([$assigned_to]);
                    $assigned_user = $stmt->fetch();
                    
                    // Get creator's name
                    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $creator = $stmt->fetch();
                    
                    // Send email notification
                    $emailService = new EmailService();
                    $email_sent = $emailService->sendTaskAssignment(
                        $assigned_user['email'],
                        $assigned_user['full_name'],
                        $title,
                        $description,
                        $deadline,
                        $creator['full_name']
                    );
                    
                    if ($email_sent) {
                        $message = 'Task created successfully and notification email sent';
                        $messageType = 'success';
                    } else {
                        $message = 'Task created successfully, but failed to send notification email';
                        $messageType = 'success'; // Still success since task was created
                    }
                } else {
                    $message = 'Failed to create task';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'update_status') {
        $task_id = $_POST['task_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if ($task_id && in_array($status, ['Pending', 'In Progress', 'Completed'])) {
            try {
                $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $task_id])) {
                    $message = 'Task status updated successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update task status';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_task') {
        $task_id = $_POST['task_id'] ?? 0;
        
        if ($task_id) {
            try {
                $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
                if ($stmt->execute([$task_id])) {
                    $message = 'Task deleted successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to delete task';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get all users for assignment dropdown
$stmt = $conn->prepare("SELECT id, full_name, username FROM users WHERE role = 'user' ORDER BY full_name");
$stmt->execute();
$users = $stmt->fetchAll();

// Get all tasks with user information
$stmt = $conn->prepare("
    SELECT t.*, 
           u.full_name as assigned_user, 
           u.username as assigned_username,
           c.full_name as created_by_name
    FROM tasks t 
    JOIN users u ON t.assigned_to = u.id 
    JOIN users c ON t.created_by = c.id 
    ORDER BY t.created_at DESC
");
$stmt->execute();
$tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - Task Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn { padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-small { padding: 4px 8px; font-size: 12px; }
        .form-group { margin-bottom: 15px; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 80px; resize: vertical; }
        .task-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .tasks-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tasks-table th, .tasks-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .tasks-table th { background: #f8f9fa; font-weight: bold; }
        .tasks-table tr:hover { background: #f8f9fa; }
        .message { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-progress { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .task-actions { display: flex; gap: 5px; flex-wrap: wrap; }
        .deadline { font-size: 12px; color: #6c757d; }
        .deadline.overdue { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Task Management</h1>
            <div>
                <a href="../index.php" class="btn">Back to Dashboard</a>
                <a href="users.php" class="btn">Manage Users</a>
                <a href="email_test.php" class="btn">Test Email</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <!-- Add Task Form -->
        <div class="task-form">
            <h3>Create New Task</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_task">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="title">Task Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="assigned_to">Assign To:</label>
                        <select id="assigned_to" name="assigned_to" required>
                            <option value="">Select User</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['username']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="deadline">Deadline:</label>
                        <input type="date" id="deadline" name="deadline">
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" placeholder="Task details and requirements..."></textarea>
                </div>
                <button type="submit" class="btn btn-success">Create Task</button>
            </form>
        </div>
        
        <!-- Tasks List -->
        <h3>All Tasks (<?= count($tasks) ?>)</h3>
        <?php if (empty($tasks)): ?>
            <p>No tasks created yet. Create your first task above!</p>
        <?php else: ?>
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th>Created By</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= $task['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($task['title']) ?></strong>
                            <?php if ($task['description']): ?>
                                <br><small style="color: #6c757d;"><?= htmlspecialchars(substr($task['description'], 0, 100)) ?><?= strlen($task['description']) > 100 ? '...' : '' ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($task['assigned_user']) ?><br><small>@<?= htmlspecialchars($task['assigned_username']) ?></small></td>
                        <td>
                            <span class="status-badge status-<?= strtolower(str_replace(' ', '', $task['status'])) ?>">
                                <?= $task['status'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($task['deadline']): ?>
                                <?php
                                $deadline = new DateTime($task['deadline']);
                                $now = new DateTime();
                                $isOverdue = $deadline < $now && $task['status'] !== 'Completed';
                                ?>
                                <span class="deadline <?= $isOverdue ? 'overdue' : '' ?>">
                                    <?= $deadline->format('M j, Y') ?>
                                    <?php if ($isOverdue): ?><br><small>OVERDUE</small><?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="deadline">No deadline</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($task['created_by_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($task['created_at'])) ?></td>
                        <td>
                            <div class="task-actions">
                                <!-- Status Update -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <select name="status" onchange="this.form.submit()" class="btn btn-small">
                                        <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                </form>
                                
                                <!-- Delete Task -->
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this task?')">
                                    <input type="hidden" name="action" value="delete_task">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
