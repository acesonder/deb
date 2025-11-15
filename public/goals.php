<?php
/**
 * Health Goals and Achievements
 * Gamification and progress tracking system
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$full_name = $_SESSION['full_name'];

$conn = getDBConnection();

// Calculate and update streak
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT DATE(log_date)) as days_logged
    FROM health_logs
    WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$streak_data = $result->fetch_assoc();
$stmt->close();

// Calculate current streak
$stmt = $conn->prepare("
    SELECT DATE(log_date) as log_date
    FROM health_logs
    WHERE user_id = ?
    GROUP BY DATE(log_date)
    ORDER BY log_date DESC
    LIMIT 30
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$log_dates = [];
while ($row = $result->fetch_assoc()) {
    $log_dates[] = $row['log_date'];
}
$stmt->close();

// Calculate consecutive days
$current_streak = 0;
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

if (in_array($today, $log_dates) || in_array($yesterday, $log_dates)) {
    $check_date = in_array($today, $log_dates) ? $today : $yesterday;
    
    while (in_array($check_date, $log_dates)) {
        $current_streak++;
        $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
    }
}

// Update or insert streak record
$stmt = $conn->prepare("
    INSERT INTO logging_streaks (user_id, current_streak, longest_streak, last_log_date)
    VALUES (?, ?, ?, CURDATE())
    ON DUPLICATE KEY UPDATE 
        current_streak = VALUES(current_streak),
        longest_streak = GREATEST(longest_streak, VALUES(current_streak)),
        last_log_date = VALUES(last_log_date)
");
$stmt->bind_param("iii", $user_id, $current_streak, $current_streak);
$stmt->execute();
$stmt->close();

// Get streak info
$stmt = $conn->prepare("SELECT * FROM logging_streaks WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$streak = $result->fetch_assoc();
$stmt->close();

// Check and award achievements
function awardAchievement($conn, $user_id, $type, $title, $description, $icon) {
    // Check if already earned
    $stmt = $conn->prepare("SELECT achievement_id FROM achievements WHERE user_id = ? AND achievement_type = ?");
    $stmt->bind_param("is", $user_id, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Award new achievement
        $stmt = $conn->prepare("
            INSERT INTO achievements (user_id, achievement_type, achievement_title, achievement_description, badge_icon)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issss", $user_id, $type, $title, $description, $icon);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    $stmt->close();
    return false;
}

// Award achievements based on criteria
$new_achievements = [];

// First log achievement
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM health_logs WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$log_count = $result->fetch_assoc()['count'];
$stmt->close();

if ($log_count >= 1 && awardAchievement($conn, $user_id, 'first_log', '🎉 First Log', 'Logged your first health data entry', '🎉')) {
    $new_achievements[] = 'First Log';
}

// Week streak achievement
if ($current_streak >= 7 && awardAchievement($conn, $user_id, 'week_streak', '🔥 Week Warrior', 'Maintained a 7-day logging streak', '🔥')) {
    $new_achievements[] = 'Week Warrior';
}

// Month streak achievement
if ($current_streak >= 30 && awardAchievement($conn, $user_id, 'month_streak', '🏆 Month Champion', 'Maintained a 30-day logging streak', '🏆')) {
    $new_achievements[] = 'Month Champion';
}

// Consistent logger achievement (20+ logs in 30 days)
if ($streak_data['days_logged'] >= 20 && awardAchievement($conn, $user_id, 'consistent_logger', '⭐ Consistent Logger', 'Logged health data 20+ times in 30 days', '⭐')) {
    $new_achievements[] = 'Consistent Logger';
}

// Get all achievements
$stmt = $conn->prepare("
    SELECT * FROM achievements
    WHERE user_id = ?
    ORDER BY earned_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$achievements = [];
while ($row = $result->fetch_assoc()) {
    $achievements[] = $row;
}
$stmt->close();

// Get goals
$stmt = $conn->prepare("
    SELECT * FROM health_goals
    WHERE user_id = ?
    ORDER BY is_completed ASC, target_date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$goals = [];
while ($row = $result->fetch_assoc()) {
    $goals[] = $row;
}
$stmt->close();

// Handle new goal creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_goal'])) {
    $goal_type = $_POST['goal_type'];
    $goal_title = $_POST['goal_title'];
    $goal_description = $_POST['goal_description'] ?? '';
    $target_value = $_POST['target_value'] ?? null;
    $target_date = $_POST['target_date'] ?? null;
    
    $stmt = $conn->prepare("
        INSERT INTO health_goals (user_id, goal_type, goal_title, goal_description, target_value, start_date, target_date)
        VALUES (?, ?, ?, ?, ?, CURDATE(), ?)
    ");
    $stmt->bind_param("isssds", $user_id, $goal_type, $goal_title, $goal_description, $target_value, $target_date);
    
    if ($stmt->execute()) {
        $success_message = "Goal created successfully!";
        header("Location: goals.php");
        exit;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goals & Achievements - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .streak-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            text-align: center;
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-lg);
        }
        
        .streak-number {
            font-size: 4rem;
            font-weight: 700;
            margin: var(--spacing-md) 0;
        }
        
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }
        
        .achievement-card {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .achievement-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .achievement-icon {
            font-size: 3rem;
            margin-bottom: var(--spacing-sm);
        }
        
        .achievement-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: var(--spacing-xs);
        }
        
        .achievement-desc {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: var(--spacing-sm);
        }
        
        .achievement-date {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        .goals-section {
            margin-bottom: var(--spacing-xl);
        }
        
        .goal-card {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-md);
        }
        
        .goal-card.completed {
            background: #e6f9f5;
            border-left: 4px solid #00765a;
        }
        
        .progress-bar {
            background: #e0e0e0;
            height: 24px;
            border-radius: var(--radius-md);
            overflow: hidden;
            margin: var(--spacing-md) 0;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            transition: width 0.5s ease;
        }
        
        .new-achievement-banner {
            background: #fff9c4;
            border: 2px solid #fbc02d;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .stat-box {
            background: var(--bg-card);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <aside class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h1>🏆 Goals & Achievements</h1>
                <p class="dashboard-subtitle">Track your progress and celebrate milestones</p>
            </div>
            
            <?php if (count($new_achievements) > 0): ?>
                <div class="new-achievement-banner">
                    <h2 style="margin-bottom: var(--spacing-md);">🎉 New Achievement Unlocked!</h2>
                    <p style="font-size: 1.25rem; font-weight: 600;">
                        <?php echo implode(', ', $new_achievements); ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Logging Streak -->
            <div class="streak-display">
                <h2 style="color: white; margin-bottom: var(--spacing-sm);">🔥 Current Streak</h2>
                <div class="streak-number"><?php echo $current_streak; ?></div>
                <p style="font-size: 1.25rem;">consecutive days</p>
                <p style="margin-top: var(--spacing-md); opacity: 0.9;">
                    Longest streak: <?php echo $streak['longest_streak'] ?? 0; ?> days
                </p>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo count($achievements); ?></div>
                    <div class="stat-label">Achievements</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $streak_data['days_logged']; ?></div>
                    <div class="stat-label">Days Logged (30d)</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $streak['longest_streak'] ?? 0; ?></div>
                    <div class="stat-label">Longest Streak</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo count($goals); ?></div>
                    <div class="stat-label">Active Goals</div>
                </div>
            </div>
            
            <!-- Achievements -->
            <section style="margin-bottom: var(--spacing-xl);">
                <h2 style="margin-bottom: var(--spacing-lg);">🏅 Your Achievements</h2>
                
                <?php if (count($achievements) > 0): ?>
                    <div class="achievements-grid">
                        <?php foreach ($achievements as $achievement): ?>
                            <div class="achievement-card">
                                <div class="achievement-icon"><?php echo $achievement['badge_icon']; ?></div>
                                <div class="achievement-title"><?php echo htmlspecialchars($achievement['achievement_title']); ?></div>
                                <div class="achievement-desc"><?php echo htmlspecialchars($achievement['achievement_description']); ?></div>
                                <div class="achievement-date">
                                    Earned: <?php echo date('M j, Y', strtotime($achievement['earned_date'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Start logging your health data to unlock achievements! 🎯
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Goals -->
            <section class="goals-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
                    <h2>🎯 Your Health Goals</h2>
                    <button onclick="showGoalForm()" class="btn-primary">+ New Goal</button>
                </div>
                
                <!-- New Goal Form (Hidden by default) -->
                <div id="goalForm" style="display: none; background: var(--bg-card); padding: var(--spacing-lg); border-radius: var(--radius-lg); margin-bottom: var(--spacing-lg);">
                    <h3 style="margin-bottom: var(--spacing-md);">Create New Goal</h3>
                    <form method="POST" action="goals.php">
                        <input type="hidden" name="create_goal" value="1">
                        
                        <div class="form-group">
                            <label for="goal_type">Goal Type</label>
                            <select name="goal_type" id="goal_type" class="form-input" required>
                                <option value="bp_control">Blood Pressure Control</option>
                                <option value="consistent_logging">Consistent Logging</option>
                                <option value="weight_loss">Weight Loss</option>
                                <option value="stress_reduction">Stress Reduction</option>
                                <option value="sleep_improvement">Sleep Improvement</option>
                                <option value="custom">Custom Goal</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="goal_title">Goal Title</label>
                            <input type="text" name="goal_title" id="goal_title" class="form-input" required placeholder="e.g., Lower BP to 120/80">
                        </div>
                        
                        <div class="form-group">
                            <label for="goal_description">Description (optional)</label>
                            <textarea name="goal_description" id="goal_description" class="form-input" rows="3" placeholder="Describe your goal..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="target_value">Target Value (optional)</label>
                            <input type="number" step="0.1" name="target_value" id="target_value" class="form-input" placeholder="e.g., 120 for systolic BP">
                        </div>
                        
                        <div class="form-group">
                            <label for="target_date">Target Date (optional)</label>
                            <input type="date" name="target_date" id="target_date" class="form-input">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Create Goal</button>
                            <button type="button" onclick="hideGoalForm()" class="btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <!-- Goals List -->
                <?php if (count($goals) > 0): ?>
                    <?php foreach ($goals as $goal): ?>
                        <div class="goal-card <?php echo $goal['is_completed'] ? 'completed' : ''; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <h3><?php echo htmlspecialchars($goal['goal_title']); ?></h3>
                                    <?php if ($goal['goal_description']): ?>
                                        <p style="color: var(--text-secondary); margin-top: var(--spacing-xs);">
                                            <?php echo htmlspecialchars($goal['goal_description']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <span style="padding: 4px 12px; border-radius: var(--radius-sm); background: <?php echo $goal['is_completed'] ? '#00765a' : '#667eea'; ?>; color: white; font-size: 0.875rem; font-weight: 600;">
                                    <?php echo $goal['is_completed'] ? '✓ Completed' : 'Active'; ?>
                                </span>
                            </div>
                            
                            <?php if ($goal['target_value'] && !$goal['is_completed']): ?>
                                <?php 
                                $progress = min(100, ($goal['current_value'] / $goal['target_value']) * 100);
                                ?>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%;">
                                        <?php echo round($progress); ?>%
                                    </div>
                                </div>
                                <p style="font-size: 0.875rem; color: var(--text-secondary);">
                                    Current: <?php echo $goal['current_value']; ?> / Target: <?php echo $goal['target_value']; ?>
                                </p>
                            <?php endif; ?>
                            
                            <div style="margin-top: var(--spacing-md); font-size: 0.875rem; color: var(--text-secondary);">
                                <?php if ($goal['target_date']): ?>
                                    📅 Target: <?php echo date('M j, Y', strtotime($goal['target_date'])); ?>
                                <?php endif; ?>
                                <?php if ($goal['is_completed']): ?>
                                    <br>✓ Completed: <?php echo date('M j, Y', strtotime($goal['completed_date'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        No goals yet. Create your first health goal to start tracking your progress! 🎯
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Motivational Message -->
            <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: var(--spacing-xl); border-radius: var(--radius-lg); text-align: center;">
                <h2 style="color: white; margin-bottom: var(--spacing-md);">💪 Keep Going!</h2>
                <p style="font-size: 1.125rem;">
                    <?php if ($current_streak == 0): ?>
                        Start your streak today by logging your health data!
                    <?php elseif ($current_streak < 7): ?>
                        You're on day <?php echo $current_streak; ?>! Keep logging to reach a week streak!
                    <?php elseif ($current_streak < 30): ?>
                        Amazing! You're at <?php echo $current_streak; ?> days! Can you reach 30?
                    <?php else: ?>
                        Incredible! You've maintained a <?php echo $current_streak; ?>-day streak! You're a health champion!
                    <?php endif; ?>
                </p>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        function showGoalForm() {
            document.getElementById('goalForm').style.display = 'block';
            document.getElementById('goalForm').scrollIntoView({ behavior: 'smooth' });
        }
        
        function hideGoalForm() {
            document.getElementById('goalForm').style.display = 'none';
        }
    </script>
</body>
</html>
