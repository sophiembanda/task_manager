<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is regular user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$message = '';
$messageType = '';

// Handle task status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $task_id = $_POST['task_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        // Verify task belongs to current user and status is valid
        if ($task_id && in_array($status, ['Pending', 'In Progress', 'Completed'])) {
            try {
                // First check if task belongs to current user
                $stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_to = ?");
                $stmt->execute([$task_id, $_SESSION['user_id']]);
                
                if ($stmt->fetch()) {
                    // Update task status
                    $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND assigned_to = ?");
                    if ($stmt->execute([$status, $task_id, $_SESSION['user_id']])) {
                        $message = 'Task status updated successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to update task status';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Task not found or access denied';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get user's tasks
$stmt = $conn->prepare("
    SELECT t.*, 
           c.full_name as created_by_name
    FROM tasks t 
    JOIN users c ON t.created_by = c.id 
    WHERE t.assigned_to = ?
    ORDER BY 
        CASE 
            WHEN t.status = 'Pending' THEN 1
            WHEN t.status = 'In Progress' THEN 2
            WHEN t.status = 'Completed' THEN 3
        END,
        t.deadline IS NULL,
        t.deadline ASC,
        t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll();

// Get task statistics
$stats = [
    'total' => count($tasks),
    'pending' => count(array_filter($tasks, fn($t) => $t['status'] === 'Pending')),
    'in_progress' => count(array_filter($tasks, fn($t) => $t['status'] === 'In Progress')),
    'completed' => count(array_filter($tasks, fn($t) => $t['status'] === 'Completed')),
    'overdue' => 0
];

// Count overdue tasks
foreach ($tasks as $task) {
    if ($task['deadline'] && $task['status'] !== 'Completed') {
        $deadline = new DateTime($task['deadline']);
        $now = new DateTime();
        if ($deadline < $now) {
            $stats['overdue']++;
        }
    }
}

// Get current user info
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - Task Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn { padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 4px 8px; font-size: 12px; }
        
        /* Statistics Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #007bff; }
        .stat-card.pending { border-left-color: #ffc107; }
        .stat-card.progress { border-left-color: #17a2b8; }
        .stat-card.completed { border-left-color: #28a745; }
        .stat-card.overdue { border-left-color: #dc3545; }
        .stat-number { font-size: 2em; font-weight: bold; margin-bottom: 5px; }
        .stat-label { color: #6c757d; font-size: 14px; }
        
        /* Tasks */
        .task-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 15px; }
        .task-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .task-header { display: flex; justify-content: between; align-items: flex-start; margin-bottom: 10px; }
        .task-title { font-size: 1.2em; font-weight: bold; color: #333; margin: 0; flex: 1; }
        .task-meta { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .task-description { color: #666; margin: 10px 0; line-height: 1.5; }
        .task-actions { display: flex; gap: 10px; align-items: center; }
        
        /* Status badges */
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-inprogress { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        
        /* Deadline indicators */
        .deadline { font-size: 12px; color: #6c757d; }
        .deadline.overdue { color: #dc3545; font-weight: bold; }
        .deadline.due-soon { color: #ffc107; font-weight: bold; }
        
        /* Message styles */
        .message { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        
        /* Empty state */
        .empty-state { text-align: center; padding: 40px; color: #6c757d; }
        .empty-state h3 { margin-top: 0; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .task-header { flex-direction: column; }
            .task-meta { flex-direction: column; gap: 5px; }
            .task-actions { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>My Tasks</h1>
            <div>
                <span style="margin-right: 20px;">Welcome, <?= htmlspecialchars($user['full_name']) ?></span>
                <a href="../index.php" class="btn">Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <!-- Task Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Tasks</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card progress">
                <div class="stat-number"><?= $stats['in_progress'] ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card completed">
                <div class="stat-number"><?= $stats['completed'] ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <?php if ($stats['overdue'] > 0): ?>
            <div class="stat-card overdue">
                <div class="stat-number"><?= $stats['overdue'] ?></div>
                <div class="stat-label">Overdue</div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Tasks List -->
        <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <h3>No Tasks Assigned</h3>
                <p>You don't have any tasks assigned yet. Check back later or contact your administrator.</p>
            </div>
        <?php else: ?>
            <h3>Your Tasks (<?= count($tasks) ?>)</h3>
            
            <?php foreach ($tasks as $task): ?>
                <?php
                // Calculate deadline status
                $deadlineClass = '';
                $deadlineText = '';
                if ($task['deadline']) {
                    $deadline = new DateTime($task['deadline']);
                    $now = new DateTime();
                    $diff = $deadline->diff($now);
                    
                    if ($deadline < $now && $task['status'] !== 'Completed') {
                        $deadlineClass = 'overdue';
                        $deadlineText = 'OVERDUE';
                    } elseif ($diff->days <= 2 && $deadline > $now) {
                        $deadlineClass = 'due-soon';
                        $deadlineText = 'DUE SOON';
                    }
                }
                ?>
                
                <div class="task-card">
                    <div class="task-header">
                        <h4 class="task-title"><?= htmlspecialchars($task['title']) ?></h4>
                    </div>
                    
                    <div class="task-meta">
                        <div>
                            <span class="status-badge status-<?= strtolower(str_replace(' ', '', $task['status'])) ?>">
                                <?= $task['status'] ?>
                            </span>
                        </div>
                        
                        <?php if ($task['deadline']): ?>
                            <div class="deadline <?= $deadlineClass ?>">
                                📅 <?= date('M j, Y', strtotime($task['deadline'])) ?>
                                <?php if ($deadlineText): ?>
                                    <strong><?= $deadlineText ?></strong>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="color: #6c757d; font-size: 12px;">
                            Created by <?= htmlspecialchars($task['created_by_name']) ?> on <?= date('M j, Y', strtotime($task['created_at'])) ?>
                        </div>
                    </div>
                    
                    <?php if ($task['description']): ?>
                        <div class="task-description">
                            <?= nl2br(htmlspecialchars($task['description'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="task-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                            <select name="status" onchange="this.form.submit()" class="btn btn-small">
                                <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </form>
                        
                        <small style="color: #6c757d;">
                            Last updated: <?= date('M j, Y g:i A', strtotime($task['updated_at'])) ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
