<?php
/**
 * index.php
 * Kenil Tech Business - Freight & Logistics Management System
 * Production Entry point. Handles landing login AND fully responsive operations console.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Check if login form is submitted via standard POST (as fallback to AJAX)
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'login') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    if (!empty($user) && !empty($pass)) {
        $res = loginUser($pdo, $user, $pass);
        if ($res['success']) {
            header('Location: index.php');
            exit();
        } else {
            $login_error = $res['error'];
        }
    } else {
        $login_error = 'Please fill out all fields.';
    }
}

// Check if logout is triggered via GET query string
if (isset($_GET['logout'])) {
    logoutUser($pdo);
    header('Location: index.php');
    exit();
}

// Retrieve any auth redirection errors stored in sessions
if (isset($_SESSION['auth_error'])) {
    $login_error = $_SESSION['auth_error'];
    unset($_SESSION['auth_error']);
}

$is_logged_in = isLoggedIn();
$username = $_SESSION['user_username'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenil Tech Business | Freight & Logistics Management System</title>
    <!-- Elegant Font Import -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b;
            --primary-hover: #0f172a;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-main: #f8fafc;
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --sidebar-width: 260px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            line-height: 1.5;
        }

        /* ------------------ AUTH PAGE STYLING ------------------ */
        .auth-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }

        .auth-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 440px;
            padding: 40px 32px;
            text-align: center;
        }

        .auth-logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        .auth-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 32px;
        }

        .auth-error {
            background-color: #fef2f2;
            color: var(--danger);
            border: 1px solid #fecaca;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--primary);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            outline: none;
            transition: border-color 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            gap: 8px;
            text-decoration: none;
            min-height: 44px; /* Touch target criteria */
        }

        .btn-primary {
            background-color: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--accent-hover);
        }

        .btn-block {
            width: 100%;
        }

        /* ------------------ CONSOLE MASTER LAYOUT ------------------ */
        .workspace {
            display: flex;
            flex: 1;
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar Elements */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary);
            color: #f1f5f9;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-brand {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 32px;
            padding-left: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-brand span {
            font-size: 11px;
            background-color: var(--accent);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
        }

        .sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
        }

        .menu-link:hover, .menu-link.active {
            background-color: rgba(255, 255, 255, 0.08);
            color: white;
        }

        .menu-link.active {
            border-left: 3px solid var(--accent);
        }

        .sidebar-user {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 20px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 12px;
            padding-left: 12px;
        }

        .user-info strong {
            color: white;
            font-size: 14px;
        }

        /* Main Workspace Panel */
        .main-container {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding: 32px;
            transition: margin-left 0.3s ease;
        }

        /* Header Components */
        .top-navbar {
            display: none; /* Shown on Mobile screen only */
            align-items: center;
            justify-content: space-between;
            background-color: var(--primary);
            color: white;
            padding: 16px 20px;
            position: sticky;
            top: 0;
            z-index: 110;
        }

        .top-brand {
            font-weight: 700;
            font-size: 18px;
        }

        .hamburger-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            min-width: 44px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .page-title h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -0.025em;
        }

        .page-title p {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 4px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }

        /* ------------------ TABLE & TABS MECHANICS ------------------ */
        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        .card {
            background-color: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
        }

        /* Scrolling mechanism on Table */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: var(--bg-main);
            color: var(--text-dark);
            font-weight: 600;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        td {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            color: #334155;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Status tags */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 9999px;
            text-transform: capitalize;
        }

        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-info { background-color: #e0f2fe; color: #0369a1; }

        /* Action Buttons */
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            min-height: 32px;
            border-radius: 6px;
        }

        .btn-outline {
            border: 1px solid var(--border-color);
            background-color: white;
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--bg-main);
        }

        .actions-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        /* ------------------ MODALS STYLINGS ------------------ */
        .modal-overlay {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease-in-out;
            overflow-y: auto;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            transform: translateY(20px);
            transition: transform 0.2s ease-out;
            overflow: hidden;
        }

        .modal-overlay.active .modal-card {
            transform: translateY(0);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            background-color: var(--bg-main);
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 24px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }

        .close-btn:hover {
            background-color: var(--bg-main);
            color: var(--primary);
        }

        /* Grid utilities */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        /* ------------------ RESPONSIVE BREAKPOINTS (CRITICAL) ------------------ */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-container {
                margin-left: 0;
                padding: 24px;
            }

            .top-navbar {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .btn {
                width: 100%;
            }

            .actions-group {
                flex-direction: column;
                width: 100%;
            }

            .actions-group .btn {
                width: 100%;
            }

            .modal-card {
                max-height: 95vh;
            }
        }

        /* Utility classes */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 16px; }
        .mt-4 { margin-top: 16px; }
        .text-mono { font-family: 'JetBrains Mono', monospace; font-size: 13px; }

        .backdrop-blocker {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.4);
            z-index: 95;
        }
        .backdrop-blocker.active {
            display: block;
        }
    </style>
</head>
<body>

<?php if (!$is_logged_in): ?>
    <!-- ==================== LOGIN VIEW ==================== -->
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">Kenil Tech Logistics</div>
            <div class="auth-subtitle">Freight & Logistics Management Console</div>
            
            <?php if (!empty($login_error)): ?>
                <div class="auth-error"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            
            <form action="index.php" method="POST">
                <input type="hidden" name="act" value="login">
                
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input class="form-control" type="text" id="username" name="username" required placeholder="e.g., super_admin" autocomplete="username">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" required placeholder="Enter password" autocomplete="current-password">
                </div>

                <button class="btn btn-primary btn-block" type="submit">Sign In securely</button>
            </form>
            
            <div style="margin-top: 24px; font-size: 11px; color: #475569; text-align: left; background: #f1f5f9; padding: 12px; border-radius: 6px;">
                <p style="font-weight:600; margin-bottom: 4px;">Demo Accounts (as seeded in database.sql):</p>
                <p>• Super Admin: username <code>super_admin</code> / password <code>admin123</code></p>
                <p>• Staff User: username <code>staff_user</code> / password <code>staff123</code></p>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ==================== CONSOLE WORKSPACE ==================== -->
    <div class="top-navbar">
        <div class="top-brand">KT Logistics</div>
        <button class="hamburger-btn" id="menuToggle" aria-label="Toggle Navigation Menu">☰</button>
    </div>

    <!-- Background dimming backdrop on mobile when sidebar drawer is menu open -->
    <div class="backdrop-blocker" id="backdropBlocker"></div>

    <div class="workspace">
        <!-- Persistent responsive sidebar drawer -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                Kenil Tech <span>Console</span>
            </div>
            
            <nav class="sidebar-menu">
                <a class="menu-link active" data-target="dashboard">
                    <i>📊</i> Dashboard
                </a>
                <a class="menu-link" data-target="shippers">
                    <i>🏢</i> Customers Shippers
                </a>
                <?php if (isSuperAdmin()): ?>
                    <a class="menu-link" data-target="approvals">
                        <i>🛡️</i> Admin Actions
                    </a>
                <?php endif; ?>
                <a class="menu-link" data-target="audit-logs">
                    <i>📜</i> Operation Logs
                </a>
            </nav>

            <div class="sidebar-user">
                <div class="user-info">
                    <span>Logged in as</span>
                    <strong><?php echo htmlspecialchars($username); ?></strong>
                    <span style="font-size: 11px; margin-top:2px;" class="badge <?php echo isSuperAdmin() ? 'badge-danger' : 'badge-info'; ?>">
                        <?php echo isSuperAdmin() ? 'Super Admin' : 'Operations Staff'; ?>
                    </span>
                </div>
                <!-- Redirect standard logout linking to destroy session safely -->
                <a class="btn btn-outline btn-block btn-sm mb-4 text-center" href="index.php?logout=1" style="color: #f1f5f9; border-color: rgba(255,255,255,0.2);">Logout</a>
            </div>
        </aside>

        <!-- Main Workspace Pane -->
        <main class="main-container">
            
            <!-- Dashboard statistics panel dynamically initialized by API -->
            <div class="tab-panel active" id="dashboard">
                <div class="page-header">
                    <div class="page-title">
                        <h1>Operations Logistics Dashboard</h1>
                        <p>Real-time active freights tracking and customs clearance processing</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" id="openCreateJobBtn">+ New Freight Job</button>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-label">Active Jobs</span>
                        <span class="stat-value" id="stat-active">0</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Pending Approvals</span>
                        <span class="stat-value" id="stat-pending">0</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Under Assessment</span>
                        <span class="stat-value" id="stat-assessment">0</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Customs Cleared</span>
                        <span class="stat-value" id="stat-cleared">0</span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Live Freight Consignments</div>
                        <span style="font-size: 13px; color: var(--text-muted)">Jobs remain in console until cleared & marked complete</span>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Job ID</th>
                                    <th>Customer Name</th>
                                    <th>Route Details</th>
                                    <th>Mode</th>
                                    <th>Customs Clearance Status</th>
                                    <th>Workflow Role Step</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="jobsTableBody">
                                <tr>
                                    <td colspan="7" class="text-center" style="padding: 40px; color: var(--text-muted)">Loading real-time logs database...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Shippers and Customers master tab -->
            <div class="tab-panel" id="shippers">
                <div class="page-header">
                    <div class="page-title">
                        <h1>Customer Profile Master</h1>
                        <p>Onboard and manage shipper credentials, GST, PAN, AD codes and factory sites</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" id="openOnboardBtn">+ Add New Customer</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Corporate Accounts</div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>GST / PAN Details</th>
                                    <th>IEC No</th>
                                    <th>AD Code</th>
                                    <th>IFSC Bank Code</th>
                                    <th>Registered Addresses & Factories</th>
                                </tr>
                            </thead>
                            <tbody id="customersTableBody">
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 40px; color: var(--text-muted)">Loading customers...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Approvals and authorization center -->
            <?php if (isSuperAdmin()): ?>
                <div class="tab-panel" id="approvals">
                    <div class="page-header">
                        <div class="page-title">
                            <h1>Administrative Guard Approvals</h1>
                            <p>Verify, approve, or reject operational edits submitted by staff users</p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Pending Administration Audits</div>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Job ID</th>
                                        <th>Shipper</th>
                                        <th>Route Details</th>
                                        <th>Mode</th>
                                        <th>Suggested Status</th>
                                        <th class="text-right">Verdicts</th>
                                    </tr>
                                </thead>
                                <tbody id="approvalsTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center" style="padding: 40px; color: var(--text-muted)">Safe. No unapproved drafts.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Activity Logs Audit Trail panel -->
            <div class="tab-panel" id="audit-logs">
                <div class="page-header">
                    <div class="page-title">
                        <h1>Immutable Auditor Trails</h1>
                        <p>Complete regulatory tracking of internal workflow changes and logins</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Operational Journal Logs</div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Log ID</th>
                                    <th>User Accounts</th>
                                    <th>Authorized Role</th>
                                    <th>Details Log Action</th>
                                    <th>Triggered Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <tr>
                                    <td colspan="5" class="text-center" style="padding: 40px; color: var(--text-muted)">Loading logs journal...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- ==================== CREATE JOB MODAL WRAPPER ==================== -->
    <div class="modal-overlay" id="createJobModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="card-title">Onboard Logistics Freight Job</h3>
                <button class="close-btn" id="closeJobModalBtn">&times;</button>
            </div>
            <form id="createJobForm">
                <div class="modal-body">
                    <div class="grid-3 mb-4">
                        <div class="form-group">
                            <label class="form-label">Auto-Generated Job ID</label>
                            <input class="form-control text-mono" type="text" id="jobNumField" readonly style="background-color: var(--bg-main);" value="Generating...">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jobCustomer">Select Registered Customer</label>
                            <select class="form-control" id="jobCustomer" required>
                                <option value="">-- Choose Account --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jobMode">Transport Mode</label>
                            <select class="form-control" id="jobMode" required>
                                <option value="Sea">Sea Shipment</option>
                                <option value="Air">Air Consignment</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid-3 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="jobPortLoading">Loading Port / Location</label>
                            <input class="form-control" type="text" id="jobPortLoading" required placeholder="e.g., Nhava Sheva (INNSA)">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jobPortDischarge">Discharge Port</label>
                            <input class="form-control" type="text" id="jobPortDischarge" required placeholder="e.g., Port of Jebel Ali (AEJEA)">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jobPortDest">Final Destination</label>
                            <input class="form-control" type="text" id="jobPortDest" required placeholder="e.g., Dubai Mainland Warehouse">
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label" for="jobCustomsStatus">Initial Customs State</label>
                        <select class="form-control" id="jobCustomsStatus">
                            <option value="Under Assessment">Under Assessment (Customs)</option>
                            <option value="Goods Examination">Examining Goods</option>
                            <option value="Duty Payment Pending">Duty Payment Pending</option>
                            <option value="Cleared">Cleared for Delivery</option>
                        </select>
                    </div>

                    <!-- Dynamic Sub-table input: Container / Booking packages data -->
                    <div style="border-top:1px solid var(--border-color); padding-top:16px; margin-top:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span class="form-label" style="margin-bottom:0px;">Logistics Cargo Line Items / Containers</span>
                            <button type="button" class="btn btn-outline btn-sm" id="addContainerRowBtn">+ Add Cargo Unit</button>
                        </div>
                        <div class="table-responsive">
                            <table style="font-size:12px;">
                                <thead>
                                    <tr>
                                        <th>Booking No</th>
                                        <th>BL / AWB No</th>
                                        <th>Container No</th>
                                        <th>Seal No</th>
                                        <th>Package Desc</th>
                                        <th>Gross Wt (Kgs)</th>
                                        <th style="width: 44px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="containerRowsTbody">
                                    <!-- Rows added dynamically via javascript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="cancelJobModalBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveJobBtn">
                        <?php echo isSuperAdmin() ? 'Launch Live Job' : 'Submit Job as Staff Draft'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- ==================== ONBOARD CUSTOMER MODAL WRAPPER ==================== -->
    <div class="modal-overlay" id="onboardModal">
        <div class="modal-card" style="max-width:650px;">
            <div class="modal-header">
                <h3 class="card-title">Register Customer Account</h3>
                <button class="close-btn" id="closeOnboardModalBtn">&times;</button>
            </div>
            <form id="onboardCustomerForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="custName">Company Registered Corporate Name</label>
                        <input class="form-control" type="text" id="custName" required placeholder="e.g., Reliance Industries Ltd">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="custAddress">Billing / Office Address</label>
                        <textarea class="form-control" id="custAddress" rows="2" placeholder="Corporate headquarters detail..."></textarea>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label" for="custGst">GSTIN Registration No</label>
                            <input class="form-control text-mono" type="text" id="custGst" placeholder="e.g., 27AAACG1234A1Z1">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="custIec">IEC No (License)</label>
                            <input class="form-control text-mono" type="text" id="custIec" placeholder="Import Export Code (10 digits)">
                        </div>
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label class="form-label" for="custPan">PAN Card</label>
                            <input class="form-control text-mono" type="text" id="custPan" placeholder="e.g., AAACG1234A">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="custAd">AD Code</label>
                            <input class="form-control text-mono" type="text" id="custAd" placeholder="Authorized Dealer Code">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="custIfsc">IFSC Bank Code</label>
                            <input class="form-control text-mono" type="text" id="custIfsc" placeholder="e.g., BARB0INNSA1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="custFactories">CFS / Warehouse Factory Delivery Addresses</label>
                        <textarea class="form-control" id="custFactories" rows="2" placeholder="List destinations / delivery warehouses..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="cancelOnboardBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Onboard Corporate Client</button>
                </div>
            </form>
        </div>
    </div>


    <!-- ==================== VANILLA AJAX SCRIPTS WIREUP ==================== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- COLLAPSIBLE NAVIGATION DRAWER (MOBILE) ---
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const backdropBlocker = document.getElementById('backdropBlocker');

            function toggleSidebar() {
                sidebar.classList.toggle('active');
                backdropBlocker.classList.toggle('active');
            }

            if (menuToggle) {
                menuToggle.addEventListener('click', toggleSidebar);
            }
            if (backdropBlocker) {
                backdropBlocker.addEventListener('click', toggleSidebar);
            }

            // --- NAVIGATION TABS ---
            const menuLinks = document.querySelectorAll('.menu-link');
            const tabPanels = document.querySelectorAll('.tab-panel');

            menuLinks.forEach(link => {
                link.addEventListener('click', () => {
                    menuLinks.forEach(l => l.classList.remove('active'));
                    tabPanels.forEach(p => p.classList.remove('active'));

                    link.classList.add('active');
                    const target = link.getAttribute('data-target');
                    document.getElementById(target).classList.add('active');

                    // If we open in mobile view, retract sidebar auto
                    if (window.innerWidth <= 1024) {
                        sidebar.classList.remove('active');
                        backdropBlocker.classList.remove('active');
                    }
                });
            });

            // --- MASTER DATA HOLDER STORE ---
            let appData = {
                customers: [],
                activeJobs: [],
                completedJobs: [],
                statistics: {}
            };

            // Fetch central database and reload panels
            function refreshAllData() {
                fetch('api.php?action=get_dashboard_data')
                    .then(response => {
                        if (!response.ok) throw new Error('Network dashboard failure');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            appData.customers = data.customers;
                            appData.activeJobs = data.active_jobs;
                            appData.completedJobs = data.completed_jobs;
                            appData.statistics = data.statistics;

                            renderStats();
                            renderJobs();
                            renderCustomers();
                            renderLogs(data.audit_logs);
                            <?php if (isSuperAdmin()): ?>
                            renderPendingApprovals();
                            <?php endif; ?>
                            populateCustomerDropdown();
                        }
                    })
                    .catch(e => {
                        console.error('Core logistics database API failure:', e);
                    });
            }

            // --- RENDER STATISTICS ---
            function renderStats() {
                const stats = appData.statistics;
                document.getElementById('stat-active').textContent = stats.total_active || 0;
                document.getElementById('stat-pending').textContent = stats.pending_approval || 0;
                document.getElementById('stat-assessment').textContent = stats.under_assessment || 0;
                document.getElementById('stat-cleared').textContent = stats.cleared || 0;
            }

            // --- POPULATE DROP-DOWN IN FORM ---
            function populateCustomerDropdown() {
                const selectElement = document.getElementById('jobCustomer');
                if (!selectElement) return;
                
                // Keep the default first option, delete others
                selectElement.innerHTML = '<option value="">-- Choose Account --</option>';
                
                appData.customers.forEach(cust => {
                    const opt = document.createElement('option');
                    opt.value = cust.id;
                    opt.textContent = cust.name;
                    selectElement.appendChild(opt);
                });
            }

            // --- RENDER ACTIVE CONSIGNMENTS TABLE ---
            function renderJobs() {
                const tbody = document.getElementById('jobsTableBody');
                if (!tbody) return;

                if (appData.activeJobs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="padding: 40px; color: var(--text-muted)">No active cargo consignments in transit. Set up a cargo above.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                appData.activeJobs.forEach(job => {
                    const tr = document.createElement('tr');
                    
                    // Route visual output
                    const routeHtml = `
                        <div style="font-weight: 600;">Port of Loading: ${escapeHtml(job.port_loading)}</div>
                        <div style="font-size: 11px; color: var(--text-muted);">
                           ➔ Discharge: ${escapeHtml(job.port_discharge)} ➔ Dest: ${escapeHtml(job.port_dest)}
                        </div>
                    `;

                    // Status Badge Class setting
                    let badgeClass = 'badge-warning';
                    if (job.status === 'Cleared') badgeClass = 'badge-success';
                    else if (job.status.includes('Assessment')) badgeClass = 'badge-info';

                    // Approval Action requirements
                    // Staff can suggest edits, admin can finalize
                    let actionButtonsHtml = '';
                    if (job.approval_status === 'pending_approval') {
                        actionButtonsHtml = `
                            <span class="badge badge-warning text-mono" style="padding: 4px 10px;">Awaiting Super-Admin Approval</span>
                        `;
                    } else {
                        // Job is live. User can update its state
                        actionButtonsHtml = `
                            <div class="actions-group">
                                <select class="form-control text-mono" style="font-size:12px; padding: 4px 8px; width:150px; min-height:32px;" onchange="updateJobStatus(${job.id}, this.value)">
                                    <option value="Under Assessment" ${job.status === 'Under Assessment' ? 'selected' : ''}>Under Assessment</option>
                                    <option value="Goods Examination" ${job.status === 'Goods Examination' ? 'selected' : ''}>Examining Goods</option>
                                    <option value="Duty Payment Pending" ${job.status === 'Duty Payment Pending' ? 'selected' : ''}>Duty Pending</option>
                                    <option value="Cleared" ${job.status === 'Cleared' ? 'selected' : ''}>Cleared</option>
                                </select>
                                <button class="btn btn-primary btn-sm" onclick="completeJob(${job.id})">Finish/Cleared</button>
                                <?php if (isSuperAdmin()): ?>
                                    <button class="btn btn-outline btn-sm" style="color:var(--danger); border-color:var(--danger); background:none;" onclick="deleteJob(${job.id})">Delete</button>
                                <?php endif; ?>
                            </div>
                        `;
                    }

                    tr.innerHTML = `
                        <td class="text-mono font-bold" style="color: var(--accent); font-weight: 700;">${escapeHtml(job.job_num)}</td>
                        <td>
                            <div style="font-weight: 600;">${escapeHtml(job.customer_name)}</div>
                        </td>
                        <td>${routeHtml}</td>
                        <td>
                            <span class="badge ${job.transport_mode === 'Sea' ? 'badge-info' : 'badge-success'}">${escapeHtml(job.transport_mode)}</span>
                        </td>
                        <td>
                            <span class="badge ${badgeClass}">${escapeHtml(job.status)}</span>
                        </td>
                        <td>
                            <span class="badge ${job.approval_status === 'live' ? 'badge-success' : 'badge-danger'}">
                                ${job.approval_status === 'live' ? 'Live System' : 'Draft Approval'}
                            </span>
                        </td>
                        <td class="text-right">${actionButtonsHtml}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            // --- RENDER CUSTOMER ONBOARD ACCOUNTS ---
            function renderCustomers() {
                const tbody = document.getElementById('customersTableBody');
                if (!tbody) return;

                if (appData.customers.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center" style="padding: 40px; color: var(--text-muted)">No registered shippers available in master database. Create a client profile above.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                appData.customers.forEach(cust => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <div style="font-weight: 700; color: var(--primary);">${escapeHtml(cust.name)}</div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top:2px;">Headquarters: ${escapeHtml(cust.address || 'N/A')}</div>
                        </td>
                        <td>
                            <div style="font-size: 12px;"><span style="color:var(--text-muted);">GST:</span> <span class="text-mono">${escapeHtml(cust.gst_no || 'N/A')}</span></div>
                            <div style="font-size: 12px; margin-top:2px;"><span style="color:var(--text-muted);">PAN:</span> <span class="text-mono">${escapeHtml(cust.pan_no || 'N/A')}</span></div>
                        </td>
                        <td class="text-mono">${escapeHtml(cust.iec_no || 'N/A')}</td>
                        <td class="text-mono">${escapeHtml(cust.ad_code || 'N/A')}</td>
                        <td class="text-mono">${escapeHtml(cust.ifsc_code || 'N/A')}</td>
                        <td style="font-size:12px; max-width: 250px;">
                            <p style="color:#475569;">${escapeHtml(cust.factory_addresses || 'Factory site unlisted')}</p>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            // --- RENDER PENDING APPROVALS LIST (SUPER-ADMIN ONLY) ---
            <?php if (isSuperAdmin()): ?>
            function renderPendingApprovals() {
                const tbody = document.getElementById('approvalsTableBody');
                if (!tbody) return;

                const pendingJobs = appData.activeJobs.filter(j => j.approval_status === 'pending_approval');

                if (pendingJobs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center" style="padding: 40px; color: var(--success); font-weight: 500;">✓ Safe. No unapproved client drafts pending from operational staff.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                pendingJobs.forEach(job => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="text-mono" style="color: var(--accent); font-weight:700;">${escapeHtml(job.job_num)}</td>
                        <td style="font-weight: 600;">${escapeHtml(job.customer_name)}</td>
                        <td>Port Loading: ${escapeHtml(job.port_loading)} ➔ Port Discharge: ${escapeHtml(job.port_discharge)}</td>
                        <td><span class="badge ${job.transport_mode === 'Sea' ? 'badge-info' : 'badge-success'}">${escapeHtml(job.transport_mode)}</span></td>
                        <td><span class="badge badge-warning">${escapeHtml(job.status)}</span></td>
                        <td class="text-right">
                            <div class="actions-group" style="justify-content: flex-end;">
                                <button class="btn btn-primary btn-sm" onclick="approveJob(${job.id})">Approve Job (Go Live)</button>
                                <button class="btn btn-outline btn-sm" style="color:var(--danger); border-color:var(--danger); background:none;" onclick="rejectJob(${job.id})">Reject Draft</button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
            <?php endif; ?>

            // --- RENDER AUDIT LOGS ---
            function renderLogs(logs) {
                const tbody = document.getElementById('logsTableBody');
                if (!tbody) return;

                if (!logs || logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center" style="padding: 40px; color: var(--text-muted)">Log register is empty.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                logs.forEach(log => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="text-mono text-muted">${log.id}</td>
                        <td style="font-weight:600;">${escapeHtml(log.username || 'System Automation')}</td>
                        <td>
                            <span class="badge ${log.role === 'super_admin' ? 'badge-danger' : 'badge-info'}">
                                ${escapeHtml(log.role || 'Service')}
                            </span>
                        </td>
                        <td>${escapeHtml(log.action_description)}</td>
                        <td class="text-mono" style="font-size:12px; color: var(--text-muted)">${escapeHtml(log.timestamp)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            // --- ESCAPE HTML STRINGS ---
            function escapeHtml(str) {
                if (!str) return '';
                return str.toString()
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // --- SYSTEM API WORKFLOW ACTIONS ---

            window.updateJobStatus = function(jobId, newStatus) {
                fetch('api.php?action=update_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: jobId, status: newStatus })
                })
                .then(res => res.json())
                .then(data => {
                    refreshAllData();
                    if (data.success) {
                        alert(data.message || 'Customs status logged successfully.');
                    } else if (data.error) {
                        alert(data.error);
                    }
                });
            };

            window.completeJob = function(jobId) {
                if (!confirm('Mark this cargo completed? It will be cleared and shifted to the operations archives.')) return;
                fetch('api.php?action=complete_job', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: jobId })
                })
                .then(res => res.json())
                .then(data => {
                    refreshAllData();
                    if (data.success) {
                        alert(data.message);
                    } else if (data.error) {
                        alert(data.error);
                    }
                });
            };

            <?php if (isSuperAdmin()): ?>
            window.approveJob = function(jobId) {
                fetch('api.php?action=approve_job', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: jobId })
                })
                .then(res => res.json())
                .then(data => {
                    refreshAllData();
                    if (data.success) {
                        alert(data.message);
                    } else if (data.error) {
                        alert(data.error);
                    }
                });
            };

            window.rejectJob = function(jobId) {
                if (!confirm('Are you sure you want to reject and block this staff cargo draft? This deletes the draft completely.')) return;
                fetch('api.php?action=reject_job', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: jobId })
                })
                .then(res => res.json())
                .then(data => {
                    refreshAllData();
                    if (data.success) {
                        alert(data.message);
                    } else if (data.error) {
                        alert(data.error);
                    }
                });
            };

            window.deleteJob = function(jobId) {
                if (!confirm('Are you absolutely sure you want to permanently delete this operational consignments and related container files? This is irreversible.')) return;
                fetch('api.php?action=delete_job', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: jobId })
                })
                .then(res => res.json())
                .then(data => {
                    refreshAllData();
                    if (data.success) {
                        alert(data.message);
                    } else if (data.error) {
                        alert(data.error);
                    }
                });
            };
            <?php endif; ?>

            // ==================== MODALS INJECTION & MANIPULATION ====================
            const createJobModal = document.getElementById('createJobModal');
            const openCreateJobBtn = document.getElementById('openCreateJobBtn');
            const closeJobModalBtn = document.getElementById('closeJobModalBtn');
            const cancelJobModalBtn = document.getElementById('cancelJobModalBtn');
            const containerRowsTbody = document.getElementById('containerRowsTbody');

            // Set up dynamic rows adding
            const addContainerRowBtn = document.getElementById('addContainerRowBtn');

            function addContainerRow(bookingNo='', blNo='', containerNo='', sealNo='', pack='', wt='') {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input class="form-control" style="font-size:11px; padding:6px;" type="text" placeholder="BOOKING123" value="${bookingNo}" name="booking_no[]"></td>
                    <td><input class="form-control" style="font-size:11px; padding:6px;" type="text" placeholder="BL45678" value="${blNo}" name="bl_no[]"></td>
                    <td><input class="form-control" style="font-size:11px; padding:6px;" type="text" placeholder="MSKU9876543" value="${containerNo}" name="container_no[]" max="11"></td>
                    <td><input class="form-control" style="font-size:11px; padding:6px;" type="text" placeholder="SEAL765" value="${sealNo}" name="seal_no[]"></td>
                    <td><input class="form-control" style="font-size:11px; padding:6px;" type="text" placeholder="Pallets, boxes" value="${pack}" name="package[]"></td>
                    <td><input class="form-control" style="font-size:11px; padding:6px;" type="number" step="0.001" placeholder="12500.5" value="${wt}" name="gross_wt[]"></td>
                    <td><button type="button" class="btn btn-outline btn-sm delete-row-btn" style="color:var(--danger); border-color:var(--danger); padding:4px 8px; min-height:32px;">×</button></td>
                `;
                containerRowsTbody.appendChild(tr);

                tr.querySelector('.delete-row-btn').addEventListener('click', () => {
                    tr.remove();
                });
            }

            if (addContainerRowBtn) {
                addContainerRowBtn.addEventListener('click', () => addContainerRow());
            }

            // Open Modal trigger
            if (openCreateJobBtn) {
                openCreateJobBtn.addEventListener('click', () => {
                    // Fetch next sequential job ID first
                    fetch('api.php?action=next_job_number')
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('jobNumField').value = data.job_num;
                            }
                        });
                    
                    // Reset and add 1 default cargo container item row
                    containerRowsTbody.innerHTML = '';
                    addContainerRow();
                    
                    createJobModal.classList.add('active');
                });
            }

            function closeCreateJob() {
                createJobModal.classList.remove('active');
                document.getElementById('createJobForm').reset();
            }

            if (closeJobModalBtn) closeJobModalBtn.addEventListener('click', closeCreateJob);
            if (cancelJobModalBtn) cancelJobModalBtn.addEventListener('click', closeCreateJob);

            // SAVE JOB AJAX
            const createJobForm = document.getElementById('createJobForm');
            if (createJobForm) {
                createJobForm.addEventListener('submit', (e) => {
                    e.preventDefault();

                    const job_num = document.getElementById('jobNumField').value;
                    const customer_id = document.getElementById('jobCustomer').value;
                    const transport_mode = document.getElementById('jobMode').value;
                    const port_loading = document.getElementById('jobPortLoading').value;
                    const port_discharge = document.getElementById('jobPortDischarge').value;
                    const port_dest = document.getElementById('jobPortDest').value;
                    const status = document.getElementById('jobCustomsStatus').value;

                    // Group containers matching PHP loop structures
                    const containers = [];
                    const rows = containerRowsTbody.querySelectorAll('tr');
                    rows.forEach(r => {
                        const booking_no = r.querySelector('[name="booking_no[]"]').value;
                        const bl_no = r.querySelector('[name="bl_no[]"]').value;
                        const container_no = r.querySelector('[name="container_no[]"]').value;
                        const seal_no = r.querySelector('[name="seal_no[]"]').value;
                        const package = r.querySelector('[name="package[]"]').value;
                        const gross_wt = r.querySelector('[name="gross_wt[]"]').value;

                        if (booking_no || bl_no || container_no) {
                            containers.push({ booking_no, bl_no, container_no, seal_no, package, gross_wt });
                        }
                    });

                    fetch('api.php?action=create_job', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            job_num, customer_id, transport_mode, port_loading, port_discharge, port_dest, status, containers
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.approval_status === 'pending_approval' 
                                ? 'Suggested cargo submitted for Administrative Review.' 
                                : 'Live cargo record created instantly!');
                            closeCreateJob();
                            refreshAllData();
                        } else if (data.error) {
                            alert(data.error);
                        }
                    });
                });
            }

            // --- ONBOARD CUSTOMER SHIPPERS MODAL WRAPPER ---
            const onboardModal = document.getElementById('onboardModal');
            const openOnboardBtn = document.getElementById('openOnboardBtn');
            const closeOnboardModalBtn = document.getElementById('closeOnboardModalBtn');
            const cancelOnboardBtn = document.getElementById('cancelOnboardBtn');

            function closeOnboard() {
                onboardModal.classList.remove('active');
                document.getElementById('onboardCustomerForm').reset();
            }

            if (openOnboardBtn) {
                openOnboardBtn.addEventListener('click', () => onboardModal.classList.add('active'));
            }
            if (closeOnboardModalBtn) closeOnboardModalBtn.addEventListener('click', closeOnboard);
            if (cancelOnboardBtn) cancelOnboardBtn.addEventListener('click', closeOnboard);

            // SAVE ONBOARDED CUSTOMER
            const onboardCustomerForm = document.getElementById('onboardCustomerForm');
            if (onboardCustomerForm) {
                onboardCustomerForm.addEventListener('submit', (e) => {
                    e.preventDefault();

                    const name = document.getElementById('custName').value;
                    const address = document.getElementById('custAddress').value;
                    const gst_no = document.getElementById('custGst').value;
                    const iec_no = document.getElementById('custIec').value;
                    const pan_no = document.getElementById('custPan').value;
                    const ad_code = document.getElementById('custAd').value;
                    const ifsc_code = document.getElementById('custIfsc').value;
                    const factory_addresses = document.getElementById('custFactories').value;

                    fetch('api.php?action=create_customer', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            name, address, gst_no, iec_no, pan_no, ad_code, ifsc_code, factory_addresses
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('Customer registered in master listings.');
                            closeOnboard();
                            refreshAllData();
                        } else if (data.error) {
                            alert(data.error);
                        }
                    });
                });
            }

            // --- FIRST RUN BOOTSTRAP ---
            refreshAllData();
        });
    </script>
<?php endif; ?>

</body>
</html>
