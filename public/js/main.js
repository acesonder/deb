/**
 * Main JavaScript for Deb's Health Tracker
 * Handles common functionality across pages
 */

// User menu toggle
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('userDropdown');
    
    if (userMenu && !userMenu.contains(event.target) && dropdown) {
        dropdown.classList.remove('active');
    }
});

// Close welcome guide
function closeWelcome() {
    const welcomeGuide = document.getElementById('welcomeGuide');
    if (welcomeGuide) {
        welcomeGuide.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            welcomeGuide.style.display = 'none';
        }, 300);
    }
}

// Add fadeOut animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
`;
document.head.appendChild(style);

// Mobile sidebar toggle
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Format blood pressure
function formatBP(systolic, diastolic) {
    return `${systolic}/${diastolic}`;
}

// Get BP status (normal, elevated, high)
function getBPStatus(systolic, diastolic) {
    if (systolic >= 180 || diastolic >= 120) {
        return { status: 'critical', text: 'Hypertensive Crisis', color: '#ff6b6b' };
    } else if (systolic >= 140 || diastolic >= 90) {
        return { status: 'high', text: 'High Blood Pressure', color: '#ffa502' };
    } else if (systolic >= 130 || diastolic >= 80) {
        return { status: 'elevated', text: 'Elevated', color: '#ffa502' };
    } else {
        return { status: 'normal', text: 'Normal', color: '#00c9a7' };
    }
}

// AJAX helper function
function ajaxRequest(url, method, data, callback, errorCallback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    
    if (method === 'POST') {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const response = JSON.parse(xhr.responseText);
                callback(response);
            } catch (e) {
                callback(xhr.responseText);
            }
        } else {
            if (errorCallback) {
                errorCallback(xhr.status, xhr.responseText);
            } else {
                console.error('Request failed:', xhr.status);
            }
        }
    };
    
    xhr.onerror = function() {
        if (errorCallback) {
            errorCallback(0, 'Network error');
        } else {
            console.error('Network error');
        }
    };
    
    if (method === 'POST' && data) {
        const params = Object.keys(data)
            .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(data[key]))
            .join('&');
        xhr.send(params);
    } else {
        xhr.send();
    }
}

// Show notification/toast
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#00c9a7' : type === 'error' ? '#ff6b6b' : '#0077be'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Confirm dialog
function confirmDialog(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Load alerts badge
function updateAlertsBadge() {
    ajaxRequest('api/get-alerts-count.php', 'GET', null, function(response) {
        const badge = document.getElementById('alertsBadge');
        if (badge && response.count > 0) {
            badge.textContent = response.count;
            badge.style.display = 'block';
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Update alerts badge if exists
    const alertsBadge = document.getElementById('alertsBadge');
    if (alertsBadge) {
        updateAlertsBadge();
        // Update every 60 seconds
        setInterval(updateAlertsBadge, 60000);
    }
    
    // Add form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#ff6b6b';
                } else {
                    field.style.borderColor = '#e0e0e0';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Please fill in all required fields', 'error');
            }
        });
    });
});
