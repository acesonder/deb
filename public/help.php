<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$role = getCurrentUserRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .help-section {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .help-section h2 {
            color: var(--primary-color);
            margin-bottom: var(--spacing-md);
        }
        
        .faq-item {
            margin-bottom: var(--spacing-lg);
        }
        
        .faq-question {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--spacing-xs);
        }
        
        .faq-answer {
            color: var(--text-secondary);
            line-height: 1.6;
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
                <h1>❓ Help & Support</h1>
                <p class="dashboard-subtitle">Get assistance using the health tracker</p>
            </div>
            
            <!-- Getting Started -->
            <div class="help-section">
                <h2>🚀 Getting Started</h2>
                <ol style="color: var(--text-secondary); line-height: 1.8;">
                    <li><strong>Log In:</strong> Use your username and password/access code</li>
                    <li><strong>View Dashboard:</strong> See your health overview and recent activity</li>
                    <li><strong>Log Health Data:</strong> Record your blood pressure, symptoms, and more daily</li>
                    <li><strong>View Analytics:</strong> Track trends and patterns in your health data</li>
                    <li><strong>Monitor Alerts:</strong> Check for important health notifications</li>
                </ol>
            </div>
            
            <!-- New Features -->
            <div class="help-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2 style="color: white;">🆕 New Advanced Features</h2>
                <div style="line-height: 1.8;">
                    <p><strong>⚡ Quick Log Templates:</strong> Use pre-defined templates for fast health data entry. Perfect for daily routines!</p>
                    <p><strong>📊 Advanced Analytics:</strong> Interactive charts show your BP trends, heart rate patterns, and mood distribution with predictive analytics.</p>
                    <p><strong>🎯 Goals & Achievements:</strong> Set health goals, track your logging streaks, and earn achievement badges.</p>
                    <p><strong>💊 Medication Effectiveness:</strong> Analyze how your medications impact your health metrics over time.</p>
                    <p><strong>📁 Data Export:</strong> Export your health data to CSV or Excel format for sharing with doctors.</p>
                    <p><strong>🔮 Predictive Analytics:</strong> See if your blood pressure is trending up, down, or staying stable.</p>
                    <p><strong>❤️ Risk Assessment:</strong> Get cardiovascular risk scores based on your health data.</p>
                </div>
            </div>
            
            <!-- FAST Protocol -->
            <div class="help-section">
                <h2>⚠️ FAST Stroke Warning Signs</h2>
                <p style="color: var(--text-secondary); margin-bottom: var(--spacing-md);">
                    Remember FAST to identify stroke symptoms:
                </p>
                <ul style="color: var(--text-secondary); line-height: 1.8;">
                    <li><strong>F - Face:</strong> Does one side of the face droop when smiling?</li>
                    <li><strong>A - Arms:</strong> Does one arm drift downward when raising both?</li>
                    <li><strong>S - Speech:</strong> Is speech slurred or strange?</li>
                    <li><strong>T - Time:</strong> If you see any of these signs, call 911 immediately!</li>
                </ul>
                <div style="background: #ffe6e6; padding: var(--spacing-md); border-radius: var(--radius-md); margin-top: var(--spacing-md); border-left: 4px solid var(--danger);">
                    <strong style="color: var(--danger);">⚠️ IMPORTANT:</strong> Call 911 immediately if you experience ANY stroke symptoms. Time is critical!
                </div>
            </div>
            
            <!-- FAQ -->
            <div class="help-section">
                <h2>💡 Frequently Asked Questions</h2>
                
                <div class="faq-item">
                    <div class="faq-question">How often should I log my health data?</div>
                    <div class="faq-answer">
                        Daily logging is recommended for accurate trend analysis. Try to log at the same time each day for consistency.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">What is a normal blood pressure reading?</div>
                    <div class="faq-answer">
                        Normal: Less than 120/80 mmHg<br>
                        Elevated: 120-129/less than 80 mmHg<br>
                        High (Stage 1): 130-139/80-89 mmHg<br>
                        High (Stage 2): 140+/90+ mmHg<br>
                        Crisis: 180+/120+ mmHg (Seek immediate medical attention)
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">How do I change my password?</div>
                    <div class="faq-answer">
                        Go to Settings → Change Password section. You'll need your current password to set a new one.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">Can family members view my health data?</div>
                    <div class="faq-answer">
                        Yes! Go to "Manage Access" to create viewer accounts for family members or doctors. They'll be able to view your health data but cannot make changes.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">What do the different alert types mean?</div>
                    <div class="faq-answer">
                        <strong>Critical:</strong> Requires immediate attention (e.g., very high blood pressure)<br>
                        <strong>Warning:</strong> Important but not urgent (e.g., elevated blood pressure)<br>
                        <strong>Info:</strong> General information or reminders
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">Is this app a replacement for medical care?</div>
                    <div class="faq-answer">
                        <strong>No.</strong> This app is designed to supplement, not replace, professional medical care. Always consult with healthcare professionals for medical advice. In case of emergency, call 911 immediately.
                    </div>
                </div>
            </div>
            
            <!-- Blood Pressure Guide -->
            <div class="help-section">
                <h2>🩺 Understanding Blood Pressure</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--bg-primary);">
                            <th style="padding: var(--spacing-sm); text-align: left;">Category</th>
                            <th style="padding: var(--spacing-sm); text-align: left;">Systolic (top)</th>
                            <th style="padding: var(--spacing-sm); text-align: left;">Diastolic (bottom)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: var(--spacing-sm);">✅ Normal</td>
                            <td style="padding: var(--spacing-sm);">Less than 120</td>
                            <td style="padding: var(--spacing-sm);">Less than 80</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: var(--spacing-sm);">⚠️ Elevated</td>
                            <td style="padding: var(--spacing-sm);">120-129</td>
                            <td style="padding: var(--spacing-sm);">Less than 80</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: var(--spacing-sm);">⚠️ High BP (Stage 1)</td>
                            <td style="padding: var(--spacing-sm);">130-139</td>
                            <td style="padding: var(--spacing-sm);">80-89</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: var(--spacing-sm);">🚨 High BP (Stage 2)</td>
                            <td style="padding: var(--spacing-sm);">140 or higher</td>
                            <td style="padding: var(--spacing-sm);">90 or higher</td>
                        </tr>
                        <tr>
                            <td style="padding: var(--spacing-sm);">🚨 Hypertensive Crisis</td>
                            <td style="padding: var(--spacing-sm);">180 or higher</td>
                            <td style="padding: var(--spacing-sm);">120 or higher</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Contact -->
            <div class="help-section" style="background: var(--bg-primary);">
                <h2>📞 Need More Help?</h2>
                <p style="color: var(--text-secondary); line-height: 1.8;">
                    If you need additional assistance, please contact your system administrator.<br><br>
                    <strong>Emergency:</strong> If you're experiencing a medical emergency, call 911 immediately.<br>
                    <strong>Technical Issues:</strong> Contact your admin for support with the application.
                </p>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
