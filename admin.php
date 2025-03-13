<?php
session_start(); // Start session

// Check if user is logged in and is an admin
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: expired");  // Redirect to expired page if not admin
    exit();
}

// Database Connection
$host = 'localhost';
$user = 'root;
$pass = 'root';
$dbname = 'cloudbox';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Database connection failed: " . $conn->connect_error);
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 60);

$username = $_SESSION['username'];
$userid = $_SESSION['user_id'];

// Handle user deletion
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $user_id = intval($_GET['delete_user']);
    // Don't allow admin to delete themselves
    if ($user_id != $userid) {
        $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->bind_param("i", $user_id);
        if ($deleteStmt->execute()) {
            echo "<p id='message' style='color:green;'>User deleted successfully.</p>";
        } else {
            echo "<p id='message' style='color:red;'>Error deleting user.</p>";
        }
    } else {
        echo "<p id='message' style='color:red;'>You cannot delete yourself!</p>";
    }
}

// Handle admin promotion/demotion
if (isset($_GET['toggle_admin']) && is_numeric($_GET['toggle_admin'])) {
    $user_id = intval($_GET['toggle_admin']);
    // Don't allow admin to demote themselves
    if ($user_id != $userid) {
        // First check current admin status
        $checkStmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
        $checkStmt->bind_param("i", $user_id);
        $checkStmt->execute();
        $checkStmt->bind_result($is_admin);
        $checkStmt->fetch();
        $checkStmt->close();
        
        // Toggle admin status
        $new_status = $is_admin ? 0 : 1;
        $updateStmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $new_status, $user_id);
        if ($updateStmt->execute()) {
            echo "<p id='message' style='color:green;'>User admin status updated successfully.</p>";
        } else {
            echo "<p id='message' style='color:red;'>Error updating user admin status.</p>";
        }
    } else {
        echo "<p id='message' style='color:red;'>You cannot change your own admin status!</p>";
    }
}

// Handle viewing user files
$view_user_id = isset($_GET['view_files']) && is_numeric($_GET['view_files']) ? intval($_GET['view_files']) : null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudBOX - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-section {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        table, th, td {
            border: 1px solid #e0e0e0;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: #e0e7ff;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
            font-size: 14px;
        }
        
        .view-btn {
            background-color: #3b82f6;
            color: white;
        }
        
        .admin-btn {
            background-color: #8b5cf6;
            color: white;
        }
        
        .delete-btn {
            background-color: #ef4444;
            color: white;
        }
        
        .back-btn {
            background-color: #6b7280;
            color: white;
            margin-bottom: 20px;
        }
        
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: #ffffff;
            border-left: 5px solid #4f46e5;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            flex: 1;
            min-width: 200px;
        }
        
        .stat-title {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            color: #1f2937;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="logo">
            <img src="logo.png" alt="CloudBOX Logo" height="40">
        </div>
        <h1>CloudBOX</h1>
        <div class="search-bar">
            <input type="text" placeholder="Search here...">
        </div>
    </div>
    
    <nav class="dashboard-nav">
        <a href="home">📊 Dashboard</a>
        <a href="drive">📁 My Drive</a>
        <a href="admin">👑 Admin Panel</a>
        <a href="#">📋 Recent</a>
        <a href="#">⭐ Favourites</a>
        <a href="#">🗑️ Trash</a>
        <a href="logout">🚪 Logout</a>
    </nav>

    <main>
        <h1>Admin Dashboard</h1>
        <p>Welcome, Admin <?= htmlspecialchars($username) ?>!</p>
        
        <?php if ($view_user_id): ?>
            <!-- View User Files Section -->
            <a href="admin" class="action-btn back-btn">← Back to Admin Dashboard</a>
            <?php
            $userStmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $userStmt->bind_param("i", $view_user_id);
            $userStmt->execute();
            $userStmt->bind_result($user_username);
            $userStmt->fetch();
            $userStmt->close();
            ?>
            <div class="admin-section">
                <h2>Files for User: <?= htmlspecialchars($user_username) ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>File ID</th>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Type</th>
                            <th>Upload Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $filesStmt = $conn->prepare("SELECT id, filename, file_size, file_type, created_at FROM files WHERE user_id = ?");
                        $filesStmt->bind_param("i", $view_user_id);
                        $filesStmt->execute();
                        $result = $filesStmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while ($file = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $file['id'] . "</td>";
                                echo "<td>" . htmlspecialchars($file['filename']) . "</td>";
                                echo "<td>" . number_format($file['file_size']) . " bytes</td>";
                                echo "<td>" . htmlspecialchars($file['file_type']) . "</td>";
                                echo "<td>" . ($file['created_at'] ?? 'N/A') . "</td>";
                                echo "<td>
                                    <a href='download.php?id={$file['id']}&admin={$userid}' class='action-btn view-btn'>Download</a>
                                    <a href='?delete_file={$file['id']}&view_files={$view_user_id}' class='action-btn delete-btn' onclick='return confirm(\"Are you sure you want to delete this file?\");'>Delete</a>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No files found for this user</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- System Stats Section -->
            <div class="stats-container">
                <?php
                // Total Users
                $userCount = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
                // Total Files
                $fileCount = $conn->query("SELECT COUNT(*) FROM files")->fetch_row()[0];
                // Total Storage Used
                $totalStorage = $conn->query("SELECT SUM(file_size) FROM files")->fetch_row()[0];
                // Admin Count
                $adminCount = $conn->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetch_row()[0];
                ?>
                <div class="stat-card">
                    <div class="stat-title">TOTAL USERS</div>
                    <div class="stat-value"><?= $userCount ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">TOTAL FILES</div>
                    <div class="stat-value"><?= $fileCount ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">STORAGE USED</div>
                    <div class="stat-value"><?= number_format($totalStorage / (1024 * 1024), 2) ?> MB</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">ADMINS</div>
                    <div class="stat-value"><?= $adminCount ?></div>
                </div>
            </div>
            
            <!-- User Management Section -->
            <div class="admin-section">
                <h2>User Management</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Storage Used</th>
                            <th>Admin Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT u.id, u.username, u.email, u.full_name, u.is_admin, 
                                              (SELECT SUM(file_size) FROM files WHERE user_id = u.id) as storage 
                                              FROM users u ORDER BY u.id");
                        
                        while ($user = $result->fetch_assoc()) {
                            $storageInMB = number_format(($user['storage'] ?? 0) / (1024 * 1024), 2);
                            $adminStatus = $user['is_admin'] == 1 ? 'Admin' : 'User';
                            $adminBtnText = $user['is_admin'] == 1 ? 'Remove Admin' : 'Make Admin';
                            
                            echo "<tr>";
                            echo "<td>" . $user['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                            echo "<td>" . $storageInMB . " MB</td>";
                            echo "<td>" . $adminStatus . "</td>";
                            echo "<td>
                                <a href='?view_files={$user['id']}' class='action-btn view-btn'>View Files</a>
                                <a href='?toggle_admin={$user['id']}' class='action-btn admin-btn'>{$adminBtnText}</a>
                                <a href='?delete_user={$user['id']}' class='action-btn delete-btn' onclick='return confirm(\"Are you sure you want to delete this user? All their files will be deleted as well.\");'>Delete</a>
                            </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- System Logs Section (you can implement this if needed) -->
            <div class="admin-section">
                <h2>Recent System Activity</h2>
                <p>This section could display login attempts, file uploads, deletions, etc.</p>
                <!-- You'd need to create a logging system for this -->
            </div>
        <?php endif; ?>
    </main>

    <script>
    // Hide messages after 3 seconds
    setTimeout(function() {
        var messageElement = document.getElementById('message');
        if (messageElement) {
            messageElement.style.display = 'none';
        }
    }, 3000);
    </script>
</body>
</html>
