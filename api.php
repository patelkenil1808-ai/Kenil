<?php
/**
 * api.php
 * RESTful AJAX Endpoint handler representing PHP/MySQL controller mechanics.
 * Serves secure JSON requests for Freight & Logistics workflow actions.
 */

header('Content-Type: application/json');

// Prevent direct script execution from browser window (must be called via AJAX and matched actions)
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Decode JSON Payload if posted as application/json
$raw_input = file_get_contents('php://input');
$data_payload = json_decode($raw_input, true) ?: [];

// Extract requested action from query string or payload
$action = $_GET['action'] ?? $data_payload['action'] ?? '';

if (empty($action)) {
    sendJsonError('Invalid API request: Missing action parameter.', 400);
}

try {
    switch ($action) {
        
        // ==================== AUTHENTICATION ACTIONS ====================
        
        case 'login':
            $username = trim($data_payload['username'] ?? '');
            $password = trim($data_payload['password'] ?? '');
            
            if (empty($username) || empty($password)) {
                sendJsonError('Username and password are required.');
            }
            
            $result = loginUser($pdo, $username, $password);
            if ($result['success']) {
                echo json_encode(['success' => true, 'user' => [
                    'username' => $result['username'],
                    'role' => $result['role']
                ]]);
            } else {
                sendJsonError($result['error'], 401);
            }
            break;
            
        case 'logout':
            logoutUser($pdo);
            echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
            break;

        case 'get_session':
            if (isLoggedIn()) {
                echo json_encode([
                    'loggedIn' => true,
                    'user' => [
                        'username' => $_SESSION['user_username'],
                        'role' => $_SESSION['user_role']
                    ]
                ]);
            } else {
                echo json_encode(['loggedIn' => false]);
            }
            break;

        // ==================== DAHSBOARD & DATA FETCHING ====================

        case 'get_dashboard_data':
            requireLogin();
            
            // 1. Fetch Customers List
            $cust_stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC");
            $customers = $cust_stmt->fetchAll();
            
            // 2. Fetch Active Jobs (Where is_completed = 0)
            // - If the user is Super Admin, load all active jobs (both approved live and those pending_approval).
            // - If the user is Staff, load all live jobs plus their own pending_approval creations.
            if (isSuperAdmin()) {
                $jobs_stmt = $pdo->query("SELECT j.*, c.name as customer_name 
                                        FROM jobs j 
                                        JOIN customers c ON j.customer_id = c.id 
                                        WHERE j.is_completed = 0 
                                        ORDER BY j.created_at DESC");
            } else {
                // Staff loaded view
                $jobs_stmt = $pdo->prepare("SELECT j.*, c.name as customer_name 
                                          FROM jobs j 
                                          JOIN customers c ON j.customer_id = c.id 
                                          WHERE j.is_completed = 0 
                                            AND (j.approval_status = 'live' OR j.created_by = ?)
                                          ORDER BY j.created_at DESC");
                $jobs_stmt->execute([$_SESSION['user_id']]);
            }
            $active_jobs = $jobs_stmt->fetchAll();

            // Fetch container lists for the loaded jobs
            foreach ($active_jobs as &$job) {
                $cont_stmt = $pdo->prepare("SELECT * FROM job_containers WHERE job_id = ?");
                $cont_stmt->execute([$job['id']]);
                $job['containers'] = $cont_stmt->fetchAll();
            }

            // 3. Fetch Completed Jobs history (limit 20)
            $comp_stmt = $pdo->query("SELECT j.*, c.name as customer_name 
                                     FROM jobs j 
                                     JOIN customers c ON j.customer_id = c.id 
                                     WHERE j.is_completed = 1 
                                     ORDER BY j.updated_at DESC LIMIT 20");
            $completed_jobs = $comp_stmt->fetchAll();
            
            // 4. Fetch Master Audit Logs (limit 50)
            $log_stmt = $pdo->query("SELECT a.*, u.username, u.role 
                                    FROM audit_logs a 
                                    LEFT JOIN users u ON a.user_id = u.id 
                                    ORDER BY a.timestamp DESC LIMIT 50");
            $audit_logs = $log_stmt->fetchAll();

            // 5. Gather Dashboard Statistics counts
            $statistics = [
                'total_active' => count($active_jobs),
                'pending_approval' => 0,
                'under_assessment' => 0,
                'cleared' => 0
            ];

            foreach ($active_jobs as $j) {
                if ($j['approval_status'] === 'pending_approval') {
                    $statistics['pending_approval']++;
                }
                if ($j['status'] === 'Under Assessment') {
                    $statistics['under_assessment']++;
                } else if ($j['status'] === 'Cleared') {
                    $statistics['cleared']++;
                }
            }

            echo json_encode([
                'success' => true,
                'customers' => $customers,
                'active_jobs' => $active_jobs,
                'completed_jobs' => $completed_jobs,
                'audit_logs' => $audit_logs,
                'statistics' => $statistics
            ]);
            break;

        case 'next_job_number':
            requireLogin();
            // Sequential Job ID Auto Generation in backend
            // Format example: KT/IMP/26-27/000001 (based on year of creation)
            $currentMonth = (int)date('m');
            $currentYear = (int)date('Y');
            
            if ($currentMonth >= 4) { // Fiscal year starts in April in India usually
                $fiscalStartYear = $currentYear % 100;
                $fiscalEndYear = ($currentYear + 1) % 100;
            } else {
                $fiscalStartYear = ($currentYear - 1) % 100;
                $fiscalEndYear = $currentYear % 100;
            }
            $fiscalStr = sprintf("%02d-%02d", $fiscalStartYear, $fiscalEndYear);
            
            // Count existing jobs in this fiscal year to provide next sequential integer index
            $prefix = "KT/IMP/{$fiscalStr}/";
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM jobs WHERE job_num LIKE ?");
            $stmt->execute([$prefix . '%']);
            $count = $stmt->fetch()['cnt'] ?? 0;
            $nextSeq = $count + 1;
            
            $nextJobNum = $prefix . sprintf("%06d", $nextSeq);
            echo json_encode(['success' => true, 'job_num' => $nextJobNum]);
            break;

        // ==================== BUSINESS LOGIC OPERATIONS ====================

        case 'create_customer':
            requireLogin();
            $name = trim($data_payload['name'] ?? '');
            $address = trim($data_payload['address'] ?? '');
            $gst_no = trim($data_payload['gst_no'] ?? '');
            $iec_no = trim($data_payload['iec_no'] ?? '');
            $pan_no = trim($data_payload['pan_no'] ?? '');
            $ad_code = trim($data_payload['ad_code'] ?? '');
            $ifsc_code = trim($data_payload['ifsc_code'] ?? '');
            $factory_addresses = trim($data_payload['factory_addresses'] ?? '');

            if (empty($name)) {
                sendJsonError('Customer name is required.');
            }

            $stmt = $pdo->prepare("INSERT INTO customers (name, address, gst_no, iec_no, pan_no, ad_code, ifsc_code, factory_addresses) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $address, $gst_no, $iec_no, $pan_no, $ad_code, $ifsc_code, $factory_addresses]);
            $new_id = $pdo->lastInsertId();

            logActivity($pdo, "Created new customer entry: {$name} (ID: {$new_id})");
            echo json_encode(['success' => true, 'customer_id' => $new_id]);
            break;

        case 'create_job':
            requireLogin();
            
            // Read job headers
            $temp_job_num = trim($data_payload['job_num'] ?? '');
            $transport_mode = trim($data_payload['transport_mode'] ?? 'Sea');
            $customer_id = (int)($data_payload['customer_id'] ?? 0);
            $port_loading = trim($data_payload['port_loading'] ?? '');
            $port_dest = trim($data_payload['port_dest'] ?? '');
            $port_discharge = trim($data_payload['port_discharge'] ?? '');
            $status = trim($data_payload['status'] ?? 'Under Assessment');
            $containers_array = $data_payload['containers'] ?? [];

            // Simple validation
            if (empty($customer_id) || empty($port_loading) || empty($port_dest)) {
                sendJsonError('Invalid submission: Customer and loaded ports are mandatory.');
            }

            // Begin ACID Database Transaction to guarantee dynamic save safety
            $pdo->beginTransaction();

            try {
                // Regene and lock next sequential job number fully in transactional database to prevent duplicates completely
                $currentMonth = (int)date('m');
                $currentYear = (int)date('Y');
                if ($currentMonth >= 4) {
                    $fiscalStart = $currentYear % 100;
                    $fiscalEnd = ($currentYear + 1) % 100;
                } else {
                    $fiscalStart = ($currentYear - 1) % 100;
                    $fiscalEnd = $currentYear % 100;
                }
                $fiscalStr = sprintf("%02d-%02d", $fiscalStart, $fiscalEnd);
                $prefix = "KT/IMP/{$fiscalStr}/";
                
                // Select and lock counts
                $num_stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM jobs WHERE job_num LIKE ? FOR UPDATE");
                $num_stmt->execute([$prefix . '%']);
                $count = $num_stmt->fetch()['cnt'] ?? 0;
                $nextSeq = $count + 1;
                $finalJobNum = $prefix . sprintf("%06d", $nextSeq);

                // Set Approval Status depending on authorization role
                // Staff submits as draft (approval_status = 'pending_approval')
                // Super Admin submits as 'live' automatically
                $approval_status = isSuperAdmin() ? 'live' : 'pending_approval';

                $ins_job = $pdo->prepare("INSERT INTO jobs (job_num, transport_mode, customer_id, port_loading, port_dest, port_discharge, status, is_completed, approval_status, created_by) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)");
                $ins_job->execute([
                    $finalJobNum,
                    $transport_mode,
                    $customer_id,
                    $port_loading,
                    $port_dest,
                    $port_discharge,
                    $status,
                    $approval_status,
                    $_SESSION['user_id']
                ]);
                $job_id = $pdo->lastInsertId();

                // Insert dynamic lines of associated container records
                if (!empty($containers_array)) {
                    $ins_cont = $pdo->prepare("INSERT INTO job_containers (job_id, booking_no, bl_no, container_no, seal_no, package, gross_wt) 
                                             VALUES (?, ?, ?, ?, ?, ?, ?)");
                    foreach ($containers_array as $row) {
                        $gross_wt = !empty($row['gross_wt']) ? floatval($row['gross_wt']) : null;
                        $ins_cont->execute([
                            $job_id,
                            trim($row['booking_no'] ?? ''),
                            trim($row['bl_no'] ?? ''),
                            trim($row['container_no'] ?? ''),
                            trim($row['seal_no'] ?? ''),
                            trim($row['package'] ?? ''),
                            $gross_wt
                        ]);
                    }
                }

                // Log audit action
                $log_role = isSuperAdmin() ? 'Super Admin' : 'Staff';
                logActivity($pdo, "Created new job: {$finalJobNum} as {$approval_status} (Mode: {$transport_mode}, Created by: {$log_role} )");

                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'job_id' => $job_id,
                    'job_num' => $finalJobNum,
                    'approval_status' => $approval_status
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                sendJsonError('Transact creation failed: ' . $e->getMessage(), 500);
            }
            break;

        case 'approve_job':
            requireSuperAdmin(); // Super admin authorization guard
            $job_id = (int)($data_payload['job_id'] ?? 0);
            
            if (empty($job_id)) {
                sendJsonError('Job ID is required for approval.');
            }

            // Fetch job detail for logging
            $j_stmt = $pdo->prepare("SELECT job_num FROM jobs WHERE id = ?");
            $j_stmt->execute([$job_id]);
            $job_num = $j_stmt->fetch()['job_num'] ?? '';

            if (empty($job_num)) {
                sendJsonError('Job not found.');
            }

            $stmt = $pdo->prepare("UPDATE jobs SET approval_status = 'live' WHERE id = ?");
            $stmt->execute([$job_id]);

            logActivity($pdo, "Approved Job {$job_num} from Staff draft, moving state to LIVE.");
            echo json_encode(['success' => true, 'message' => "Job {$job_num} approved and finalized."]);
            break;

        case 'reject_job':
            requireSuperAdmin();
            $job_id = (int)($data_payload['job_id'] ?? 0);
            
            if (empty($job_id)) {
                sendJsonError('Job ID is required for rejection.');
            }

            $j_stmt = $pdo->prepare("SELECT job_num FROM jobs WHERE id = ?");
            $j_stmt->execute([$job_id]);
            $job_num = $j_stmt->fetch()['job_num'] ?? '';

            if (empty($job_num)) {
                sendJsonError('Job not found.');
            }

            // Rejecting means deleting the temporary unapproved job layout
            $pdo->beginTransaction();
            try {
                $pdo->prepare("DELETE FROM job_containers WHERE job_id = ?")->execute([$job_id]);
                $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([$job_id]);
                logActivity($pdo, "Admin Rejected and deleted pending draft job: {$job_num}");
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => "Job {$job_num} rejected."]);
            } catch (Exception $e) {
                $pdo->rollBack();
                sendJsonError('Rejection failed: ' . $e->getMessage(), 500);
            }
            break;

        case 'update_status':
            requireLogin();
            $job_id = (int)($data_payload['job_id'] ?? 0);
            $new_status = trim($data_payload['status'] ?? '');

            if (empty($job_id) || empty($new_status)) {
                sendJsonError('Job ID and status are required.');
            }

            $j_stmt = $pdo->prepare("SELECT job_num, approval_status, created_by FROM jobs WHERE id = ?");
            $j_stmt->execute([$job_id]);
            $job_data = $j_stmt->fetch();

            if (!$job_data) {
                sendJsonError('Job not found.');
            }

            // Business logic: Staff changes must go back to draft status for Admin approval
            // unless the admin is editing or it's allowed.
            if (isStaff()) {
                // Mark as pending approval again
                $stmt = $pdo->prepare("UPDATE jobs SET status = ?, approval_status = 'pending_approval' WHERE id = ?");
                $stmt->execute([$new_status, $job_id]);
                logActivity($pdo, "Staff suggested update to Job {$job_data['job_num']}: status set to '{$new_status}', awaiting Admin validation.");
                $message = "Status suggestion submitted for Admin approval.";
            } else {
                // Admin has instant live activation
                $stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $job_id]);
                logActivity($pdo, "Admin updated Job {$job_data['job_num']}: status changed directly to '{$new_status}'.");
                $message = "Status updated successfully.";
            }

            echo json_encode(['success' => true, 'message' => $message]);
            break;

        case 'complete_job':
            requireLogin();
            $job_id = (int)($data_payload['job_id'] ?? 0);

            if (empty($job_id)) {
                sendJsonError('Job ID is required.');
            }

            $j_stmt = $pdo->prepare("SELECT job_num FROM jobs WHERE id = ?");
            $j_stmt->execute([$job_id]);
            $job_num = $j_stmt->fetch()['job_num'] ?? '';

            if (empty($job_num)) {
                sendJsonError('Job not found.');
            }

            // Mark job as completed, shifting off the active dashboard
            $stmt = $pdo->prepare("UPDATE jobs SET is_completed = 1, status = 'Cleared' WHERE id = ?");
            $stmt->execute([$job_id]);

            logActivity($pdo, "Customs Cleared and Finished Job {$job_num}. Moved to Completed archiving.");
            echo json_encode(['success' => true, 'message' => "Job {$job_num} has been cleared and marked as completed."]);
            break;

        case 'delete_job':
            requireSuperAdmin(); // Super Admin authorization only
            $job_id = (int)($data_payload['job_id'] ?? 0);

            if (empty($job_id)) {
                sendJsonError('Job ID is required.');
            }

            $j_stmt = $pdo->prepare("SELECT job_num FROM jobs WHERE id = ?");
            $j_stmt->execute([$job_id]);
            $job_num = $j_stmt->fetch()['job_num'] ?? '';

            if (empty($job_num)) {
                sendJsonError('Job not found.');
            }

            $pdo->beginTransaction();
            try {
                // Cascades will handle delete in job_containers on DB side, but deleting here securely as well
                $pdo->prepare("DELETE FROM job_containers WHERE job_id = ?")->execute([$job_id]);
                $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([$job_id]);
                logActivity($pdo, "Admin deleted Job: {$job_num}");
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => "Job {$job_num} has been permanently deleted."]);
            } catch (Exception $e) {
                $pdo->rollBack();
                sendJsonError('Deletion failed: ' . $e->getMessage(), 500);
            }
            break;

        default:
            sendJsonError('Invalid Action payload requested.', 404);
            break;
    }

} catch (PDOException $ex) {
    sendJsonError('System Database Engine breakdown: ' . $ex->getMessage(), 500);
}
