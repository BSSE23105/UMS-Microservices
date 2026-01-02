<?php
// student_messages.php
require_once __DIR__ . '/../src/bootstrap.php';

if (!isset($_SESSION['student'])) {
    header("Location: /index.php");
    exit;
}

require __DIR__ . '/../src/db.php';

/**
 * Safe HTML escape for PHP 8.1+ (prevents null deprecation warnings).
 */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Safe string for operations like substr/strlen/nl2br.
 */
function s($value): string
{
    return (string)($value ?? '');
}

$student_id = (int)($_SESSION['student']['id'] ?? 0);

// Allow only these tabs
$active_tab = $_GET['tab'] ?? 'inbox';
$active_tab = in_array($active_tab, ['inbox', 'sent'], true) ? $active_tab : 'inbox';

// Handle message deletion
if (isset($_POST['delete_message'])) {
    $message_id = (int)($_POST['message_id'] ?? 0);

    $tab = $_POST['tab'] ?? 'inbox';
    $tab = in_array($tab, ['inbox', 'sent'], true) ? $tab : 'inbox';

    if ($message_id > 0) {
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: student_messages.php?tab=" . urlencode($tab));
    exit;
}

// Fetch inbox messages
$inbox = $conn->query("
    SELECT m.*, 
           a.name AS admin_name,
           f.name AS faculty_name
    FROM messages m
    LEFT JOIN admins a ON m.sender_id = a.id AND m.sender_type = 'admin'
    LEFT JOIN faculty f ON m.sender_id = f.id AND m.sender_type = 'faculty'
    WHERE m.receiver_id = $student_id AND m.receiver_type = 'student'
    ORDER BY m.sent_at DESC
");

// Fetch sent messages
$sent = $conn->query("
    SELECT m.*,
           a.name AS admin_name,
           f.name AS faculty_name
    FROM messages m
    LEFT JOIN admins a ON m.receiver_id = a.id AND m.receiver_type = 'admin'
    LEFT JOIN faculty f ON m.receiver_id = f.id AND m.receiver_type = 'faculty'
    WHERE m.sender_id = $student_id AND m.sender_type = 'student'
    ORDER BY m.sent_at DESC
");

// Count unread messages
$unread_result = $conn->query("
    SELECT COUNT(*) AS unread_count
    FROM messages 
    WHERE receiver_id = $student_id 
    AND receiver_type = 'student'
    AND is_read = 0
");
$unread_row = $unread_result ? $unread_result->fetch_assoc() : null;
$unread_count = (int)($unread_row['unread_count'] ?? 0);

// Student info (avoid null warnings)
$studentName  = s($_SESSION['student']['name'] ?? '');
$studentEmail = s($_SESSION['student']['email'] ?? '');
$studentInitial = strtoupper(substr($studentName !== '' ? $studentName : 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Messages | UMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'itu-primary': '#1a365d',
                        'itu-secondary': '#2c5282',
                        'itu-accent': '#4299e1',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
        }

        .sidebar {
            transition: all 0.3s ease;
        }

        .message-card {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        .message-card.unread {
            border-left-color: #4299e1;
            background-color: #f0f9ff;
        }

        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            color: white;
        }

        .admin-avatar {
            background: linear-gradient(135deg, #fa709a, #fee140);
        }

        .faculty-avatar {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .student-avatar {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
        }

        .tab-active {
            border-bottom: 3px solid #1a365d;
            color: #1a365d;
            font-weight: 600;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .content-preview {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .unread-badge {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #4299e1;
            margin-left: 5px;
        }
    </style>
</head>
<body class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <div class="sidebar w-64 bg-itu-primary text-white flex flex-col">
        <div class="p-4 flex items-center justify-center border-b border-itu-secondary">
            <div class="text-xl font-bold">University MS</div>
        </div>
        <div class="flex-1 py-4">
            <nav>
                <a href="/student_dashboard.php" class="block py-3 px-6 hover:bg-itu-secondary">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="/courses/enroll_courses.php" class="block py-3 px-6 hover:bg-itu-secondary">
                    <i class="fas fa-book mr-3"></i>Enroll In Course
                </a>
                <a href="student_messages.php" class="block py-3 px-6 bg-itu-secondary">
                    <i class="fas fa-envelope mr-3"></i>Messages
                </a>
            </nav>
        </div>
        <div class="p-4 border-t border-itu-secondary">
            <a href="/logout.php" class="flex items-center text-red-300 hover:text-red-100">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-y-auto">
        <!-- Top Navbar -->
        <header class="bg-white shadow">
            <div class="flex justify-between items-center px-6 py-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">Student Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="p-2 text-gray-600 hover:text-itu-primary">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-full"></span>
                        </button>
                    </div>
                    <div class="flex items-center">
                        <div class="mr-3 text-right">
                            <p class="text-sm font-medium"><?= e($studentName) ?></p>
                            <p class="text-xs text-gray-500"><?= e($studentEmail) ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-green-500 to-teal-400 flex items-center justify-center text-white font-bold">
                            <?= e($studentInitial) ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6">
            <div class="bg-white rounded-lg shadow fade-in">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-xl font-semibold itu-primary">Message Management</h3>
                    <p class="text-sm text-gray-600">Manage your inbox and sent messages</p>
                </div>

                <!-- Action Bar -->
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <div class="flex space-x-4">
                        <button id="inbox-tab" class="<?= $active_tab === 'inbox' ? 'tab-active text-itu-primary' : 'text-gray-600' ?> px-4 py-2">
                            Inbox
                            <?php if ($unread_count > 0): ?>
                                <span class="bg-itu-accent text-white text-xs rounded-full px-2 py-1 ml-2"><?= (int)$unread_count ?> unread</span>
                            <?php endif; ?>
                        </button>
                        <button id="sent-tab" class="<?= $active_tab === 'sent' ? 'tab-active text-itu-primary' : 'text-gray-600' ?> px-4 py-2">Sent</button>
                    </div>
                    <div class="flex space-x-2">
                        <a href="compose_message_student.php" class="bg-itu-primary hover:bg-itu-secondary text-white px-4 py-2 rounded-lg flex items-center">
                            <i class="fas fa-plus mr-2"></i> Compose
                        </a>
                    </div>
                </div>

                <!-- Messages Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From / To</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>

                        <!-- Inbox -->
                        <tbody id="inbox-container" class="bg-white divide-y divide-gray-200 <?= $active_tab === 'inbox' ? '' : 'hidden' ?>">
                            <?php if ($inbox && $inbox->num_rows > 0): ?>
                                <?php while ($message = $inbox->fetch_assoc()):
                                    $is_unread = ((int)($message['is_read'] ?? 0) === 0);
                                    $sender_type = s($message['sender_type'] ?? '');

                                    // LEFT JOIN columns can be NULL -> provide defaults
                                    if ($sender_type === 'admin') {
                                        $name = s($message['admin_name'] ?? 'Admin');
                                        $initials = 'A';
                                        $avatar_class = 'admin-avatar';
                                    } else {
                                        $name = s($message['faculty_name'] ?? 'Faculty');
                                        $initials = strtoupper(substr($name !== '' ? $name : 'F', 0, 1));
                                        $avatar_class = 'faculty-avatar';
                                    }

                                    $subject = s($message['subject'] ?? '');
                                    $content = s($message['content'] ?? '');
                                    $preview = substr($content, 0, 100);
                                    $sentAtRaw = s($message['sent_at'] ?? '');
                                    $sentAt = $sentAtRaw !== '' ? date('M j', strtotime($sentAtRaw)) : '-';
                                ?>
                                <tr class="message-card <?= $is_unread ? 'unread' : '' ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="avatar <?= e($avatar_class) ?>">
                                                <?= e(strtoupper($initials)) ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?= e($name) ?></div>
                                                <div class="text-xs text-gray-500 capitalize"><?= e($sender_type) ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium <?= $is_unread ? 'text-itu-primary font-bold' : 'text-gray-900' ?>">
                                            <?= e($subject) ?>
                                            <?php if ($is_unread): ?>
                                                <span class="unread-badge"></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 content-preview max-w-md">
                                            <?= nl2br(e($preview)) ?>
                                            <?php if (strlen($content) > 100): ?>...<?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= e($sentAt) ?></div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="view_message_student.php?id=<?= (int)$message['id'] ?>&tab=inbox" class="text-itu-primary hover:text-itu-secondary mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="message_id" value="<?= (int)$message['id'] ?>">
                                            <input type="hidden" name="tab" value="inbox">
                                            <button type="submit" name="delete_message" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                            <p class="text-lg">Your inbox is empty</p>
                                            <p class="mt-2">No messages have been received yet</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                        <!-- Sent -->
                        <tbody id="sent-container" class="bg-white divide-y divide-gray-200 <?= $active_tab === 'sent' ? '' : 'hidden' ?>">
                            <?php if ($sent && $sent->num_rows > 0): ?>
                                <?php while ($message = $sent->fetch_assoc()):
                                    $receiver_type = s($message['receiver_type'] ?? '');

                                    // LEFT JOIN columns can be NULL -> provide defaults
                                    if ($receiver_type === 'admin') {
                                        $name = s($message['admin_name'] ?? 'Admin');
                                        $initials = 'A';
                                        $avatar_class = 'admin-avatar';
                                    } else {
                                        $name = s($message['faculty_name'] ?? 'Faculty');
                                        $initials = strtoupper(substr($name !== '' ? $name : 'F', 0, 1));
                                        $avatar_class = 'faculty-avatar';
                                    }

                                    $subject = s($message['subject'] ?? '');
                                    $content = s($message['content'] ?? '');
                                    $preview = substr($content, 0, 100);
                                    $sentAtRaw = s($message['sent_at'] ?? '');
                                    $sentAt = $sentAtRaw !== '' ? date('M j', strtotime($sentAtRaw)) : '-';
                                ?>
                                <tr class="message-card">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="avatar <?= e($avatar_class) ?>">
                                                <?= e(strtoupper($initials)) ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?= e($name) ?></div>
                                                <div class="text-xs text-gray-500 capitalize"><?= e($receiver_type) ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= e($subject) ?></div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 content-preview max-w-md">
                                            <?= nl2br(e($preview)) ?>
                                            <?php if (strlen($content) > 100): ?>...<?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= e($sentAt) ?></div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="view_message_student.php?id=<?= (int)$message['id'] ?>&tab=sent" class="text-itu-primary hover:text-itu-secondary mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="message_id" value="<?= (int)$message['id'] ?>">
                                            <input type="hidden" name="tab" value="sent">
                                            <button type="submit" name="delete_message" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-paper-plane text-4xl text-gray-300 mb-3"></i>
                                            <p class="text-lg">No sent messages</p>
                                            <p class="mt-2">You haven't sent any messages yet</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white border-t py-4 px-6">
            <div class="flex justify-between items-center">
                <p class="text-sm text-gray-600">© 2023 University Management System. All rights reserved.</p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-itu-primary">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-itu-primary">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-itu-primary">
                        <i class="fab fa-linkedin"></i>
                    </a>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Tab switching functionality (client-side view only)
        document.getElementById('inbox-tab').addEventListener('click', function() {
            this.classList.add('tab-active', 'text-itu-primary');
            this.classList.remove('text-gray-600');

            document.getElementById('sent-tab').classList.remove('tab-active', 'text-itu-primary');
            document.getElementById('sent-tab').classList.add('text-gray-600');

            document.getElementById('inbox-container').classList.remove('hidden');
            document.getElementById('sent-container').classList.add('hidden');

            history.replaceState(null, null, 'student_messages.php?tab=inbox');
        });

        document.getElementById('sent-tab').addEventListener('click', function() {
            this.classList.add('tab-active', 'text-itu-primary');
            this.classList.remove('text-gray-600');

            document.getElementById('inbox-tab').classList.remove('tab-active', 'text-itu-primary');
            document.getElementById('inbox-tab').classList.add('text-gray-600');

            document.getElementById('sent-container').classList.remove('hidden');
            document.getElementById('inbox-container').classList.add('hidden');

            history.replaceState(null, null, 'student_messages.php?tab=sent');
        });

        document.querySelectorAll('button[name="delete_message"]').forEach(button => {
            button.addEventListener('click', (e) => {
                if (!confirm('Are you sure you want to delete this message?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
