<header class="main-header">
    <div class="header-content">
        <div class="header-left">
            <h1 class="header-title">Deb's Health Tracker</h1>
        </div>
        
        <div class="header-right">
            <div class="user-menu">
                <button class="user-menu-toggle" onclick="toggleUserMenu()" aria-label="User menu">
                    <span class="user-icon">👤</span>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <span class="user-role">(<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                </button>
                
                <div class="user-dropdown" id="userDropdown">
                    <a href="settings.php" class="dropdown-item">
                        <span>⚙️</span> Settings
                    </a>
                    <a href="help.php" class="dropdown-item">
                        <span>❓</span> Help
                    </a>
                    <hr class="dropdown-divider">
                    <a href="logout.php" class="dropdown-item">
                        <span>🚪</span> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
