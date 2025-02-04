<aside class="sidebar">
    <nav class="sidebar-menu">
        <ul>
            <li class="<?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
                <a href="/dashboard">Dashboard</a>
            </li>
            <?php if ($userRole == 'admin'): ?>
                <li class="<?php echo ($activePage == 'users') ? 'active' : ''; ?>">
                    <a href="/users">Manage Users</a>
                </li>
                <li class="<?php echo ($activePage == 'logs') ? 'active' : ''; ?>">
                    <a href="/logs">View Logs</a>
                </li>
                <li class="<?php echo ($activePage == 'reports') ? 'active' : ''; ?>">
                    <a href="/reports">Generate Reports</a>
                </li>
            <?php endif; ?>
            <?php if ($userRole == 'user'): ?>
                <li class="<?php echo ($activePage == 'documents') ? 'active' : ''; ?>">
                    <a href="/documents">My Documents</a>
                </li>
                <li class="<?php echo ($activePage == 'profile') ? 'active' : ''; ?>">
                    <a href="/profile">My Profile</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

<style>
/* Ensure accessibility & responsive behavior */
.sidebar {
    width: 250px;
    background-color: #333;
    color: #fff;
    position: fixed;
    height: 100%;
    overflow: auto;
}

.sidebar-nav ul {
    list-style-type: none;
    padding: 0;
}

.sidebar-nav ul li {
    padding: 15px;
    text-align: left;
}

.sidebar-nav ul li a {
    color: #fff;
    text-decoration: none;
    display: block;
}

.sidebar-nav ul li a:hover {
    background-color: #575757;
}

@media screen and (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .sidebar-nav ul li {
        float: left;
    }
    .sidebar-nav ul li a {
        text-align: center;
        padding: 10px;
    }
}
</style>
