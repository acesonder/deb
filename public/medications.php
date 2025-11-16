<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$full_name = $_SESSION['full_name'];

$success = '';
$error = '';

// Handle medication actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once __DIR__ . '/../includes/logger.php';
    $conn = getDBConnection();
    
    if ($_POST['action'] === 'add_medication') {
        $med_name = !empty($_POST['medication_name']) ? trim($_POST['medication_name']) : '';
        $dosage = !empty($_POST['dosage']) ? trim($_POST['dosage']) : null;
        $frequency = !empty($_POST['frequency']) ? trim($_POST['frequency']) : null;
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $prescribing_doctor = !empty($_POST['prescribing_doctor']) ? trim($_POST['prescribing_doctor']) : null;
        $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
        
        if (empty($med_name)) {
            $error = 'Medication name is required.';
            logValidationError('medications', ['error' => 'Medication name required'], $user_id);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO medications 
                (user_id, medication_name, dosage, frequency, start_date, prescribing_doctor, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issssss", $user_id, $med_name, $dosage, $frequency, $start_date, $prescribing_doctor, $notes);
            
            if ($stmt->execute()) {
                $success = 'Medication added successfully!';
                logFormSubmission('medications-add', true, $user_id, ['medication' => $med_name]);
            } else {
                $error = 'Failed to add medication.';
                logFormSubmission('medications-add', false, $user_id, ['database_error' => $conn->error]);
            }
            $stmt->close();
        }
    } elseif ($_POST['action'] === 'deactivate_medication') {
        $med_id = $_POST['medication_id'] ?? 0;
        $end_date = date('Y-m-d');
        
        $stmt = $conn->prepare("UPDATE medications SET is_active = FALSE, end_date = ? WHERE medication_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $end_date, $med_id, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Medication marked as inactive.';
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'reactivate_medication') {
        $med_id = $_POST['medication_id'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE medications SET is_active = TRUE, end_date = NULL WHERE medication_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $med_id, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Medication reactivated.';
        }
        $stmt->close();
    }
    
    $conn->close();
}

// Get medications
$conn = getDBConnection();

// Active medications
$stmt = $conn->prepare("
    SELECT * FROM medications 
    WHERE user_id = ? AND is_active = TRUE 
    ORDER BY medication_name
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$active_meds = [];
while ($row = $result->fetch_assoc()) {
    $active_meds[] = $row;
}
$stmt->close();

// Inactive medications
$stmt = $conn->prepare("
    SELECT * FROM medications 
    WHERE user_id = ? AND is_active = FALSE 
    ORDER BY end_date DESC
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$inactive_meds = [];
while ($row = $result->fetch_assoc()) {
    $inactive_meds[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medications - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .med-section {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .med-section h2 {
            margin-bottom: var(--spacing-md);
            color: var(--primary-color);
        }
        
        .med-card {
            background: var(--bg-primary);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
            border-left: 4px solid var(--primary-color);
        }
        
        .med-card.inactive {
            border-left-color: var(--text-light);
            opacity: 0.7;
        }
        
        .med-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: var(--spacing-sm);
        }
        
        .med-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .med-detail {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            color: var(--text-secondary);
            margin-bottom: var(--spacing-xs);
        }
        
        .med-detail-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
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
                <h1>💊 Medications</h1>
                <p class="dashboard-subtitle">Manage your medication schedule</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Add Medication Form -->
            <div class="med-section">
                <h2>Add New Medication</h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_medication">
                    
                    <div class="form-group">
                        <label for="medication_name">Medication Name *</label>
                        <input type="text" id="medication_name" name="medication_name" required 
                               placeholder="e.g., Lisinopril">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-md);">
                        <div class="form-group">
                            <label for="dosage">Dosage</label>
                            <input type="text" id="dosage" name="dosage" 
                                   placeholder="e.g., 10mg">
                        </div>
                        
                        <div class="form-group">
                            <label for="frequency">Frequency</label>
                            <input type="text" id="frequency" name="frequency" 
                                   placeholder="e.g., Once daily">
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="prescribing_doctor">Prescribing Doctor</label>
                        <input type="text" id="prescribing_doctor" name="prescribing_doctor" 
                               placeholder="e.g., Dr. Smith">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  placeholder="Special instructions, side effects to watch for, etc."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Medication</button>
                </form>
            </div>
            
            <!-- Active Medications -->
            <div class="med-section">
                <h2>Active Medications (<?php echo count($active_meds); ?>)</h2>
                
                <?php if (count($active_meds) > 0): ?>
                    <?php foreach ($active_meds as $med): ?>
                        <div class="med-card">
                            <div class="med-header">
                                <div>
                                    <div class="med-name"><?php echo htmlspecialchars($med['medication_name']); ?></div>
                                    <div class="med-detail">
                                        <?php if ($med['dosage']): ?>
                                            <span class="med-detail-item">
                                                <strong>💊 Dosage:</strong> <?php echo htmlspecialchars($med['dosage']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($med['frequency']): ?>
                                            <span class="med-detail-item">
                                                <strong>🕐 Frequency:</strong> <?php echo htmlspecialchars($med['frequency']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($med['prescribing_doctor']): ?>
                                        <div class="med-detail">
                                            <span class="med-detail-item">
                                                <strong>👨‍⚕️ Doctor:</strong> <?php echo htmlspecialchars($med['prescribing_doctor']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($med['start_date']): ?>
                                        <div class="med-detail">
                                            <span class="med-detail-item">
                                                <strong>📅 Started:</strong> <?php echo date('M j, Y', strtotime($med['start_date'])); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($med['notes']): ?>
                                        <div style="margin-top: var(--spacing-sm); color: var(--text-secondary);">
                                            <strong>Notes:</strong> <?php echo htmlspecialchars($med['notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="deactivate_medication">
                                        <input type="hidden" name="medication_id" value="<?php echo $med['medication_id']; ?>">
                                        <button type="submit" class="btn" style="background: var(--text-light); color: white;"
                                                onclick="return confirm('Mark this medication as no longer active?')">
                                            Stop Taking
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: var(--spacing-lg);">
                        No active medications. Add your current medications above.
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Inactive Medications -->
            <?php if (count($inactive_meds) > 0): ?>
                <div class="med-section">
                    <h2>Past Medications</h2>
                    
                    <?php foreach ($inactive_meds as $med): ?>
                        <div class="med-card inactive">
                            <div class="med-header">
                                <div>
                                    <div class="med-name"><?php echo htmlspecialchars($med['medication_name']); ?></div>
                                    <div class="med-detail">
                                        <?php if ($med['start_date']): ?>
                                            <span class="med-detail-item">
                                                <strong>Started:</strong> <?php echo date('M j, Y', strtotime($med['start_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($med['end_date']): ?>
                                            <span class="med-detail-item">
                                                <strong>Ended:</strong> <?php echo date('M j, Y', strtotime($med['end_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="reactivate_medication">
                                        <input type="hidden" name="medication_id" value="<?php echo $med['medication_id']; ?>">
                                        <button type="submit" class="btn btn-secondary">Reactivate</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Info Box -->
            <div class="med-section" style="background: var(--bg-primary);">
                <h3>💡 Medication Tips</h3>
                <ul style="color: var(--text-secondary); line-height: 1.8;">
                    <li>Take medications as prescribed by your doctor</li>
                    <li>Set reminders to take medications at the same time each day</li>
                    <li>Keep track of side effects and report them to your doctor</li>
                    <li>Never stop taking medications without consulting your doctor</li>
                    <li>Store medications properly and check expiration dates</li>
                    <li>Inform all healthcare providers about all medications you're taking</li>
                </ul>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
