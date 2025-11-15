/**
 * Dashboard specific JavaScript
 */

// Load dashboard statistics
function loadDashboardStats() {
    // Load latest blood pressure
    ajaxRequest('api/get-latest-bp.php', 'GET', null, function(response) {
        if (response.success && response.data) {
            const bpValue = document.getElementById('bpValue');
            if (bpValue) {
                bpValue.textContent = formatBP(response.data.systolic, response.data.diastolic);
                
                const bpStatus = getBPStatus(response.data.systolic, response.data.diastolic);
                bpValue.style.color = bpStatus.color;
                
                const latestBP = document.getElementById('latestBP');
                if (latestBP) {
                    latestBP.title = bpStatus.text;
                }
            }
        }
    });
    
    // Load latest heart rate
    ajaxRequest('api/get-latest-hr.php', 'GET', null, function(response) {
        if (response.success && response.data) {
            const hrValue = document.getElementById('hrValue');
            if (hrValue) {
                hrValue.textContent = response.data.heart_rate;
            }
        }
    });
    
    // Load logs count for this week
    ajaxRequest('api/get-logs-count.php', 'GET', null, function(response) {
        if (response.success) {
            const logsCount = document.getElementById('logsCount');
            if (logsCount) {
                logsCount.textContent = response.count;
            }
        }
    });
    
    // Load unread alerts count
    ajaxRequest('api/get-alerts-count.php', 'GET', null, function(response) {
        if (response.success) {
            const alertsValue = document.getElementById('alertsValue');
            if (alertsValue) {
                alertsValue.textContent = response.count;
                
                if (response.count > 0) {
                    alertsValue.style.color = '#ff6b6b';
                }
            }
        }
    });
}

// Load recent activity
function loadRecentActivity() {
    ajaxRequest('api/get-recent-activity.php', 'GET', null, function(response) {
        const activityList = document.getElementById('recentActivityList');
        if (!activityList) return;
        
        if (response.success && response.data && response.data.length > 0) {
            activityList.innerHTML = '';
            response.data.forEach(activity => {
                const activityItem = document.createElement('div');
                activityItem.className = 'activity-item';
                activityItem.innerHTML = `
                    <div class="activity-date">${formatDate(activity.log_date)}</div>
                    <div class="activity-content">
                        ${activity.systolic_bp && activity.diastolic_bp ? 
                            `<strong>BP:</strong> ${formatBP(activity.systolic_bp, activity.diastolic_bp)} mmHg` : ''}
                        ${activity.heart_rate ? 
                            `<br><strong>HR:</strong> ${activity.heart_rate} bpm` : ''}
                        ${activity.symptoms ? 
                            `<br><strong>Symptoms:</strong> ${activity.symptoms}` : ''}
                    </div>
                `;
                activityList.appendChild(activityItem);
            });
        } else {
            activityList.innerHTML = '<p class="loading">No recent activity. Start logging your health data!</p>';
        }
    });
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadRecentActivity();
    
    // Refresh stats every 5 minutes
    setInterval(loadDashboardStats, 300000);
});
