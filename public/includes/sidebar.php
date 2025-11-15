<nav class="sidebar-nav">
    <ul class="nav-list">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <span class="nav-icon">🏠</span>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="log-health.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'log-health.php' ? 'active' : ''; ?>">
                <span class="nav-icon">➕</span>
                <span class="nav-text">Log Health Data</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="health-history.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'health-history.php' ? 'active' : ''; ?>">
                <span class="nav-icon">📋</span>
                <span class="nav-text">Health History</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="quick-log.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'quick-log.php' ? 'active' : ''; ?>">
                <span class="nav-icon">⚡</span>
                <span class="nav-text">Quick Log</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="analytics.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                <span class="nav-icon">📈</span>
                <span class="nav-text">Analytics</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="analytics-enhanced.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'analytics-enhanced.php' ? 'active' : ''; ?>">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Advanced Analytics</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="goals.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'goals.php' ? 'active' : ''; ?>">
                <span class="nav-icon">🎯</span>
                <span class="nav-text">Goals & Achievements</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="medications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'medications.php' ? 'active' : ''; ?>">
                <span class="nav-icon">💊</span>
                <span class="nav-text">Medications</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="stroke-warnings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stroke-warnings.php' ? 'active' : ''; ?>">
                <span class="nav-icon">⚠️</span>
                <span class="nav-text">Stroke Warnings</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="alerts.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'alerts.php' ? 'active' : ''; ?>">
                <span class="nav-icon">🔔</span>
                <span class="nav-text">Alerts</span>
                <span class="badge" id="alertsBadge"></span>
            </a>
        </li>
        
        <?php if ($role === 'admin' || $role === 'patient'): ?>
        <li class="nav-divider"></li>
        
        <li class="nav-item">
            <a href="manage-access.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-access.php' ? 'active' : ''; ?>">
                <span class="nav-icon">👥</span>
                <span class="nav-text">Manage Access</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($role === 'admin'): ?>
        <li class="nav-item">
            <a href="admin-panel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-panel.php' ? 'active' : ''; ?>">
                <span class="nav-icon">🔧</span>
                <span class="nav-text">Admin Panel</span>
            </a>
        </li>
        <?php endif; ?>
        
        <li class="nav-divider"></li>
        
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <span class="nav-icon">⚙️</span>
                <span class="nav-text">Settings</span>
            </a>
        </li>
    </ul>
</nav>
