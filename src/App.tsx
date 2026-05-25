import React, { useState, useEffect } from "react";
import { 
  Building2, 
  FileLock2, 
  LayoutDashboard, 
  Database, 
  FolderPlus, 
  UserPlus, 
  CheckCircle2, 
  XOctagon, 
  History, 
  Anchor, 
  Plane, 
  Users, 
  ClipboardList, 
  Trash2, 
  ExternalLink, 
  Copy, 
  Check, 
  Smartphone, 
  Laptop, 
  Menu, 
  X 
} from "lucide-react";
import { motion, AnimatePresence } from "motion/react";

// --- TYPES DECLARATIONS ---
interface Customer {
  id: number;
  name: string;
  address: string;
  gstNo: string;
  iecNo: string;
  panNo: string;
  adCode: string;
  ifscCode: string;
  factoryAddresses: string;
}

interface Container {
  bookingNo: string;
  blNo: string;
  containerNo: string;
  sealNo: string;
  packageDesc: string;
  grossWt: number;
}

interface Job {
  id: number;
  jobNum: string;
  transportMode: "Sea" | "Air";
  customerId: number;
  customerName: string;
  portLoading: string;
  portDest: string;
  portDischarge: string;
  status: string;
  isCompleted: boolean;
  approvalStatus: "live" | "pending_approval";
  createdBy: string;
  createdAt: string;
  containers: Container[];
}

interface AuditLog {
  id: number;
  user: string;
  role: "super_admin" | "staff";
  action: string;
  timestamp: string;
}

// Initial Mock Seed Data matching database.sql
const INITIAL_CUSTOMERS: Customer[] = [
  {
    id: 1,
    name: "Global Trade Corp",
    address: "102 Skyline Business Park, Mumbai, MH",
    gstNo: "27AAACG1234A1Z1",
    iecNo: "0102030405",
    panNo: "AAACG1234A",
    adCode: "AD65784321",
    ifscCode: "KKBK0000123",
    factoryAddresses: "Plot 45, MIDC Industrial Area, Pune"
  },
  {
    id: 2,
    name: "Aero Cargo Logistics",
    address: "Unit B, cargo Terminal-2, Delhi Airport",
    gstNo: "07BBBCG5678B1Z2",
    iecNo: "0504030201",
    panNo: "BBBCG5678B",
    adCode: "AD71243125",
    ifscCode: "HDFC0000543",
    factoryAddresses: "Warehouse 12, Palam Extension, New Delhi"
  }
];

const INITIAL_JOBS: Job[] = [
  {
    id: 1,
    jobNum: "KT/IMP/26-27/000001",
    transportMode: "Sea",
    customerId: 1,
    customerName: "Global Trade Corp",
    portLoading: "Nhava Sheva (INNSA)",
    portDischarge: "Port of Jebel Ali (AEJEA)",
    portDest: "Dubai Mainland Warehouse",
    status: "Under Assessment",
    isCompleted: false,
    approvalStatus: "live",
    createdBy: "super_admin",
    createdAt: "2026-05-25 09:12:44",
    containers: [
      {
        bookingNo: "BKG9012432",
        blNo: "BLIN912044",
        containerNo: "MSKU4731295",
        sealNo: "SL901234",
        packageDesc: "Industrial Machinery Parts",
        grossWt: 14250.5
      }
    ]
  },
  {
    id: 2,
    jobNum: "KT/IMP/26-27/000002",
    transportMode: "Air",
    customerId: 2,
    customerName: "Aero Cargo Logistics",
    portLoading: "IGI Airport, Delhi (DEL)",
    portDischarge: "Heathrow Airport, London (LHR)",
    portDest: "West London Distribution Center",
    status: "Cleared",
    isCompleted: false,
    approvalStatus: "live",
    createdBy: "super_admin",
    createdAt: "2026-05-25 10:45:12",
    containers: [
      {
        bookingNo: "AWB80182431",
        blNo: "AWB-LHR-902",
        containerNo: "LD3-AERO23",
        sealNo: "SEAL-A8",
        packageDesc: "Automotive chip electronic components",
        grossWt: 1820.0
      }
    ]
  }
];

const INITIAL_LOGS: AuditLog[] = [
  {
    id: 1,
    user: "super_admin",
    role: "super_admin",
    action: "System database initiated with MySQL default schema values.",
    timestamp: "2026-05-25 08:30:00"
  },
  {
    id: 2,
    user: "super_admin",
    role: "super_admin",
    action: "User 'super_admin' logged in successfully.",
    timestamp: "2026-05-25 08:31:05"
  }
];

export default function App() {
  // Navigation & Role states
  const [currentTab, setCurrentTab] = useState<"dashboard" | "shippers" | "approvals" | "logs" | "sources">("dashboard");
  const [currentUser, setCurrentUser] = useState<"super_admin" | "staff_user">("super_admin");
  const [sidebarOpen, setSidebarOpen] = useState(false);

  // Core Entity States (Persisted in LocalStorage for amazing user trial)
  const [customers, setCustomers] = useState<Customer[]>(() => {
    const saved = localStorage.getItem("kt_customers");
    return saved ? JSON.parse(saved) : INITIAL_CUSTOMERS;
  });
  
  const [jobs, setJobs] = useState<Job[]>(() => {
    const saved = localStorage.getItem("kt_jobs");
    return saved ? JSON.parse(saved) : INITIAL_JOBS;
  });

  const [auditLogs, setAuditLogs] = useState<AuditLog[]>(() => {
    const saved = localStorage.getItem("kt_logs");
    return saved ? JSON.parse(saved) : INITIAL_LOGS;
  });

  // Modal control states
  const [showJobModal, setShowJobModal] = useState(false);
  const [showCustomerModal, setShowCustomerModal] = useState(false);

  // Form states for Create Job
  const [newJobMode, setNewJobMode] = useState<"Sea" | "Air">("Sea");
  const [newJobCust, setNewJobCust] = useState<string>("");
  const [newJobPortLoading, setNewJobPortLoading] = useState("");
  const [newJobPortDischarge, setNewJobPortDischarge] = useState("");
  const [newJobPortDest, setNewJobPortDest] = useState("");
  const [newJobCustomsStatus, setNewJobCustomsStatus] = useState("Under Assessment");
  const [newJobContainers, setNewJobContainers] = useState<Container[]>([
    { bookingNo: "", blNo: "", containerNo: "", sealNo: "", packageDesc: "", grossWt: 0 }
  ]);

  // Form states for Create Customer
  const [newCustName, setNewCustName] = useState("");
  const [newCustAddress, setNewCustAddress] = useState("");
  const [newCustGst, setNewCustGst] = useState("");
  const [newCustIec, setNewCustIec] = useState("");
  const [newCustPan, setNewCustPan] = useState("");
  const [newCustAd, setNewCustAd] = useState("");
  const [newCustIfsc, setNewCustIfsc] = useState("");
  const [newCustFactories, setNewCustFactories] = useState("");

  // Source files viewer state
  const [selectedSourceFile, setSelectedSourceFile] = useState<"sql" | "db" | "auth" | "api" | "index">("index");
  const [copiedFile, setCopiedFile] = useState<string | null>(null);

  // Sync to local storage
  useEffect(() => {
    localStorage.setItem("kt_customers", JSON.stringify(customers));
  }, [customers]);

  useEffect(() => {
    localStorage.setItem("kt_jobs", JSON.stringify(jobs));
  }, [jobs]);

  useEffect(() => {
    localStorage.setItem("kt_logs", JSON.stringify(auditLogs));
  }, [auditLogs]);

  // Automatic Fiscal Year Alphanumeric Job ID Calculator (KT/IMP/26-27/000003)
  const getNextJobNum = () => {
    const startYear = 26; // representing current fiscal segment '26-27'
    const endYear = 27;
    const prefix = `KT/IMP/${startYear}-${endYear}/`;
    const count = jobs.length;
    return `${prefix}${String(count + 1).padStart(6, "0")}`;
  };

  // Log audit helper
  const addLog = (user: string, role: "super_admin" | "staff", action: string) => {
    const newLog: AuditLog = {
      id: auditLogs.length + 1,
      user,
      role,
      action,
      timestamp: new Date().toISOString().replace("T", " ").substring(0, 19)
    };
    setAuditLogs(prev => [newLog, ...prev]);
  };

  // Role switching action
  const switchUser = (role: "super_admin" | "staff_user") => {
    setCurrentUser(role);
    const friendlyRole = role === "super_admin" ? "Super Admin" : "Operations Staff";
    addLog(role, role === "super_admin" ? "super_admin" : "staff", `Switched security credentials to role: ${friendlyRole}`);
    // If we're on approvals but switch to staff, kick back to dashboard
    if (role === "staff_user" && currentTab === "approvals") {
      setCurrentTab("dashboard");
    }
  };

  // Create Job submit
  const handleCreateJob = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newJobCust) return;

    const matchedCust = customers.find(c => c.id === Number(newJobCust));
    if (!matchedCust) return;

    const nextJobIdHex = getNextJobNum();
    
    // Aligns with requirement "Staff can only create jobs and submit edits as drafts (approval_status = 'pending_approval')"
    const approvalStatus: "live" | "pending_approval" = currentUser === "super_admin" ? "live" : "pending_approval";

    const newlyCreatedJob: Job = {
      id: jobs.length + 1,
      jobNum: nextJobIdHex,
      transportMode: newJobMode,
      customerId: matchedCust.id,
      customerName: matchedCust.name,
      portLoading: newJobPortLoading,
      portDischarge: newJobPortDischarge,
      portDest: newJobPortDest,
      status: newJobCustomsStatus,
      isCompleted: false,
      approvalStatus,
      createdBy: currentUser,
      createdAt: new Date().toISOString().replace("T", " ").substring(0, 19),
      containers: newJobContainers.filter(c => c.bookingNo || c.containerNo)
    };

    setJobs(prev => [newlyCreatedJob, ...prev]);
    
    const userRoleText = currentUser === "super_admin" ? "Super Admin" : "Operations Staff";
    const statusTextDecimal = approvalStatus === "pending_approval" ? "a draft awaiting Admin authorization" : "a Live Job instantly";
    addLog(
      currentUser, 
      currentUser === "super_admin" ? "super_admin" : "staff", 
      `Logged new freight job ${nextJobIdHex} for customer ${matchedCust.name} as ${statusTextDecimal}.`
    );

    // Reset Form
    setNewJobMode("Sea");
    setNewJobCust("");
    setNewJobPortLoading("");
    setNewJobPortDischarge("");
    setNewJobPortDest("");
    setNewJobCustomsStatus("Under Assessment");
    setNewJobContainers([{ bookingNo: "", blNo: "", containerNo: "", sealNo: "", packageDesc: "", grossWt: 0 }]);
    setShowJobModal(false);
  };

  // Add container helper row
  const handleAddContainerRow = () => {
    setNewJobContainers(prev => [
      ...prev,
      { bookingNo: "", blNo: "", containerNo: "", sealNo: "", packageDesc: "", grossWt: 0 }
    ]);
  };

  // Modify container field
  const handleContainerFieldChange = (index: number, field: keyof Container, value: string | number) => {
    setNewJobContainers(prev => {
      const copy = [...prev];
      copy[index] = { ...copy[index], [field]: value };
      return copy;
    });
  };

  // Remove container row
  const handleRemoveContainerRow = (index: number) => {
    setNewJobContainers(prev => prev.filter((_, i) => i !== index));
  };

  // Create Shippers submit
  const handleCreateCustomer = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCustName) return;

    const newCust: Customer = {
      id: customers.length + 1,
      name: newCustName,
      address: newCustAddress,
      gstNo: newCustGst,
      iecNo: newCustIec,
      panNo: newCustPan,
      adCode: newCustAd,
      ifscCode: newCustIfsc,
      factoryAddresses: newCustFactories
    };

    setCustomers(prev => [...prev, newCust]);
    addLog(currentUser, currentUser === "super_admin" ? "super_admin" : "staff", `Onboarded new secure client: ${newCustName} (GST Registered).`);

    // Reset Form
    setNewCustName("");
    setNewCustAddress("");
    setNewCustGst("");
    setNewCustIec("");
    setNewCustPan("");
    setNewCustAd("");
    setNewCustIfsc("");
    setNewCustFactories("");
    setShowCustomerModal(false);
  };

  // Admin approvals Actions
  const handleApproveJob = (jobId: number) => {
    setJobs(prev => prev.map(job => {
      if (job.id === jobId) {
        return { ...job, approvalStatus: "live" };
      }
      return job;
    }));
    const targetJob = jobs.find(j => j.id === jobId);
    if (targetJob) {
      addLog("super_admin", "super_admin", `Approved freight draft ${targetJob.jobNum}. Cleared to system console.`);
    }
  };

  const handleRejectDraft = (jobId: number) => {
    const targetJob = jobs.find(j => j.id === jobId);
    setJobs(prev => prev.filter(j => j.id !== jobId));
    if (targetJob) {
      addLog("super_admin", "super_admin", `Rejected staff cargo suggestion ${targetJob.jobNum}. Permanent deletions triggered.`);
    }
  };

  // Complete Job action (Finish/Customs Cleared)
  const handleCompleteJob = (jobId: number) => {
    setJobs(prev => prev.map(job => {
      if (job.id === jobId) {
        return { ...job, isCompleted: true, status: "Cleared" };
      }
      return job;
    }));
    const targetJob = jobs.find(j => j.id === jobId);
    if (targetJob) {
      addLog(currentUser, currentUser === "super_admin" ? "super_admin" : "staff", `Customs Cleared final checkpoint. Completed shipping for job ${targetJob.jobNum}.`);
    }
  };

  // Delete Job action (Admin only)
  const handleDeleteJob = (jobId: number) => {
    const targetJob = jobs.find(j => j.id === jobId);
    setJobs(prev => prev.filter(j => j.id !== jobId));
    if (targetJob) {
      addLog("super_admin", "super_admin", `Deleted console job cargo records: ${targetJob.jobNum}`);
    }
  };

  // Edit status in list
  const handleStatusChangeInList = (jobId: number, nextStatus: string) => {
    const targetJob = jobs.find(j => j.id === jobId);
    if (!targetJob) return;

    if (currentUser === "staff_user") {
      // Suggest status - marks as pending_approval again
      setJobs(prev => prev.map(job => {
        if (job.id === jobId) {
          return { ...job, status: nextStatus, approvalStatus: "pending_approval" };
        }
        return job;
      }));
      addLog("staff_user", "staff", `Suggested customs status edit to '${nextStatus}' on job ${targetJob.jobNum}. Awaiting Admin verification.`);
    } else {
      // Direct update
      setJobs(prev => prev.map(job => {
        if (job.id === jobId) {
          return { ...job, status: nextStatus };
        }
        return job;
      }));
      addLog("super_admin", "super_admin", `Admin directly changed customs stage to '${nextStatus}' for job ${targetJob.jobNum}.`);
    }
  };

  // Statistics
  const activeCount = jobs.filter(j => !j.isCompleted).length;
  const pendingCount = jobs.filter(j => j.approvalStatus === "pending_approval").length;
  const underAssessmentCount = jobs.filter(j => !j.isCompleted && j.status === "Under Assessment").length;
  const clearedCount = jobs.filter(j => !j.isCompleted && j.status === "Cleared").length;

  // Filter jobs visible to current user
  const visibleActiveJobs = jobs.filter(job => {
    if (job.isCompleted) return false;
    if (currentUser === "super_admin") return true; // sees all, live & drafts
    // Staff sees live jobs OR their own created drafts
    return job.approvalStatus === "live" || job.createdBy === currentUser;
  });

  // Source texts for copy paste capability
  const getSourceFileCode = () => {
    switch (selectedSourceFile) {
      case "sql":
        return `-- database.sql
-- Create Database
CREATE DATABASE IF NOT EXISTS kenil_tech_logistics;
USE kenil_tech_logistics;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin', 'staff') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Customers Table
CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  address TEXT,
  gst_no VARCHAR(15), ...`;
      case "db":
        return `<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kenil_tech_logistics');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 DB Error');
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}`;
      case "auth":
        return `<?php
session_start();
require_once 'db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'super_admin';
}
function requireLogin() {
    if (!isLoggedIn()) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'Please login']);
        exit();
    }
}`;
      case "api":
        return `<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';

$action = $_GET['action'] ?? '';
switch($action) {
    case 'create_job':
        // Generate automatic sequential KT Job ID
        // Start transaction and save to jobs + job_containers...
        break;
}`;
      case "index":
        return `<!-- index.php -->
<?php
session_start();
require_once 'auth.php';
if (!isLoggedIn()) {
    // Show beautiful responsive CSS login screen...
}
?>`;
    }
  };

  const handleCopyCode = (filename: string, text: string) => {
    navigator.clipboard.writeText(text);
    setCopiedFile(filename);
    setTimeout(() => setCopiedFile(null), 2000);
  };

  return (
    <div className="min-height-screen bg-slate-50 flex flex-col font-sans text-slate-900 overflow-x-hidden antialiased">
      
      {/* --- TOP STICKY SYSTEM PANEL --- */}
      <div className="bg-slate-900 border-b border-indigo-950/40 text-slate-100 flex flex-col sm:flex-row items-center justify-between px-6 py-3 shrink-0 gap-3 z-50">
        <div className="flex items-center gap-3">
          <div className="h-6 w-6 rounded-md bg-indigo-500 flex items-center justify-center font-mono font-bold text-xs">KT</div>
          <span className="font-semibold text-sm tracking-tight">Kenil Tech Freight Console</span>
          <span className="text-[11px] bg-slate-800 text-indigo-300 font-medium px-2 py-0.5 rounded-full border border-indigo-900/40">Demo Environment</span>
        </div>
        
        {/* State Session toggler */}
        <div className="flex items-center gap-2">
          <span className="text-xs text-slate-400">Switch Security Role:</span>
          <div className="inline-flex bg-slate-950 rounded-lg p-1 border border-slate-800">
            <button
              onClick={() => switchUser("super_admin")}
              className={`px-3 py-1 rounded-md text-xs font-medium cursor-pointer transition-all ${
                currentUser === "super_admin"
                  ? "bg-rose-500 text-white shadow-sm"
                  : "text-slate-400 hover:text-slate-200"
              }`}
            >
              👑 Super Admin
            </button>
            <button
              onClick={() => switchUser("staff_user")}
              className={`px-3 py-1 rounded-md text-xs font-medium cursor-pointer transition-all ${
                currentUser === "staff_user"
                  ? "bg-indigo-500 text-white shadow-sm"
                  : "text-slate-400 hover:text-slate-200"
              }`}
            >
              👷 Operations Staff
            </button>
          </div>
        </div>
      </div>

      {/* --- MOBILE NAVBAR --- */}
      <div className="lg:hidden flex items-center justify-between bg-slate-900 text-slate-150 px-5 py-4 border-t border-slate-850 sticky top-0 z-40">
        <div className="font-bold text-base text-white tracking-wide">Kenil Tech Logistics</div>
        <button 
          onClick={() => setSidebarOpen(prev => !prev)}
          className="p-2 -mr-2 bg-slate-850 text-white rounded-lg cursor-pointer"
          aria-label="Toggle Menu"
        >
          {sidebarOpen ? <X size={20} /> : <Menu size={20} />}
        </button>
      </div>

      <div className="flex flex-1 relative min-h-0">
        
        {/* --- PERSISTENT MOBILE DRAWER / SIDEBAR --- */}
        <aside className={`
          fixed top-[110px] sm:top-[53px] lg:top-0 bottom-0 left-0 z-35 bg-slate-900 text-slate-200 w-64 p-6 flex flex-col border-r border-slate-800 transition-transform duration-300 ease-in-out lg:translate-x-0
          ${sidebarOpen ? "translate-x-0" : "-translate-x-full lg:translate-x-0"}
        `}>
          <div className="hidden lg:flex items-center gap-2 mb-8 px-2">
            <Building2 className="text-indigo-400 text-xl" size={24} />
            <div className="font-bold text-base tracking-tight text-white">Kenil Tech console</div>
          </div>

          <div className="text-xs font-semibold text-slate-500 px-2 uppercase tracking-wider mb-3">Freight Office</div>
          <nav className="space-y-1.5 flex-1">
            <button
              onClick={() => { setCurrentTab("dashboard"); setSidebarOpen(false); }}
              className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all text-left cursor-pointer ${
                currentTab === "dashboard" ? "bg-indigo-600 text-white" : "text-slate-400 hover:bg-slate-800/60 hover:text-white"
              }`}
            >
              <LayoutDashboard size={18} />
              Operations Panel
            </button>
            
            <button
              onClick={() => { setCurrentTab("shippers"); setSidebarOpen(false); }}
              className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all text-left cursor-pointer ${
                currentTab === "shippers" ? "bg-indigo-600 text-white" : "text-slate-400 hover:bg-slate-800/60 hover:text-white"
              }`}
            >
              <Users size={18} />
              Registered Shippers
            </button>

            {currentUser === "super_admin" && (
              <button
                onClick={() => { setCurrentTab("approvals"); setSidebarOpen(false); }}
                className={`w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-all text-left cursor-pointer ${
                  currentTab === "approvals" ? "bg-rose-600 text-white" : "text-slate-400 hover:bg-slate-800/60 hover:text-white"
                }`}
              >
                <div className="flex items-center gap-3">
                  <FileLock2 size={18} />
                  <span>Admin Approvals</span>
                </div>
                {pendingCount > 0 && (
                  <span className="bg-rose-500 text-white text-[10px] font-bold h-5 px-1.5 rounded-full flex items-center justify-center animate-pulse">
                    {pendingCount}
                  </span>
                )}
              </button>
            )}

            <button
              onClick={() => { setCurrentTab("logs"); setSidebarOpen(false); }}
              className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all text-left cursor-pointer ${
                currentTab === "logs" ? "bg-indigo-600 text-white" : "text-slate-400 hover:bg-slate-800/60 hover:text-white"
              }`}
            >
              <History size={18} />
              Operation Audit logs
            </button>

            <button
              onClick={() => { setCurrentTab("sources"); setSidebarOpen(false); }}
              className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all text-left cursor-pointer border border-dashed border-slate-750 mt-4 ${
                currentTab === "sources" ? "bg-indigo-650 text-white" : "text-slate-400 hover:bg-indigo-950/40 hover:text-white"
              }`}
            >
              <Database size={18} className="text-amber-400" />
              <span>cPanel PHP Sources</span>
            </button>
          </nav>

          <div className="border-t border-slate-800 pt-5 mt-5">
            <div className="px-2 py-3 bg-slate-950/60 rounded-lg border border-slate-800/50">
              <div className="text-[11px] text-slate-500 tracking-wider font-semibold uppercase">Active User Account</div>
              <div className="font-semibold text-slate-200 mt-1 truncate">{currentUser === "super_admin" ? "super_admin" : "staff_user_1"}</div>
              <div className="text-[11px] text-slate-400 mt-0.5 capitalize">{currentUser === "super_admin" ? "Super Admin" : "Operations Agent"}</div>
            </div>
          </div>
        </aside>

        {/* --- MAIN SCROLL CONTENT AREA --- */}
        <main className="flex-1 lg:pl-64 flex flex-col min-h-0 bg-slate-50">
          <div className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
            
            <AnimatePresence mode="wait">
              
              {/* === DASHBOARD PANEL === */}
              {currentTab === "dashboard" && (
                <motion.div
                  key="dashboard"
                  initial={{ opacity: 0, y: 12 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -12 }}
                  transition={{ duration: 0.2 }}
                  className="space-y-6"
                >
                  <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                      <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Active Freight Shipments</h1>
                      <p className="text-sm text-slate-500">Live customs logs processing terminal for Kenil Tech fleet.</p>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                      <button
                        onClick={() => setShowCustomerModal(true)}
                        className="btn btn-outline border-slate-200 shadow-sm bg-white hover:bg-slate-50 flex items-center gap-2 cursor-pointer text-xs"
                      >
                        <UserPlus size={15} /> Onboard Shipper
                      </button>
                      <button
                        onClick={() => setShowJobModal(true)}
                        className="btn bg-indigo-600 text-white shadow-sm hover:bg-indigo-700 flex items-center gap-2 cursor-pointer text-xs font-semibold"
                      >
                        <FolderPlus size={15} /> + Initialize Freight Job
                      </button>
                    </div>
                  </div>

                  {/* Active Numbers Cards Grid */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                      <div className="text-xs font-bold text-slate-400 uppercase tracking-widest text-muted">Active Freight Jobs</div>
                      <div className="text-4xl font-extrabold text-slate-900 mt-1.5 font-mono">{activeCount}</div>
                    </div>
                    <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                      <div className="text-xs font-bold text-slate-400 uppercase tracking-widest text-muted">Approval Requests</div>
                      <div className="text-4xl font-extrabold text-amber-500 mt-1.5 font-mono">{pendingCount}</div>
                    </div>
                    <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                      <div className="text-xs font-bold text-slate-400 uppercase tracking-widest text-muted">Under customs Audit</div>
                      <div className="text-4xl font-extrabold text-slate-700 mt-1.5 font-mono">{underAssessmentCount}</div>
                    </div>
                    <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                      <div className="text-xs font-bold text-slate-400 uppercase tracking-widest text-muted">Customs Cleared</div>
                      <div className="text-4xl font-extrabold text-emerald-600 mt-1.5 font-mono">{clearedCount}</div>
                    </div>
                  </div>

                  {/* Active Freight Terminal List Card */}
                  <div className="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
                    <div className="px-6 py-4 border-b border-slate-150 flex items-center justify-between flex-wrap gap-3 bg-slate-50/50">
                      <div className="font-bold text-sm text-slate-800">Live Logistics Cargo Terminal</div>
                      <div className="text-xs text-slate-500">Jobs stay active until cleared + finished</div>
                    </div>
                    
                    <div className="overflow-x-auto">
                      <table className="w-full text-left border-collapse text-sm">
                        <thead className="bg-slate-50 text-slate-700 text-xs uppercase tracking-wider font-semibold">
                          <tr>
                            <th className="px-5 py-3.5 border-b border-slate-200">Alphanumeric Job ID</th>
                            <th className="px-5 py-3.5 border-b border-slate-200">Registered Shipper</th>
                            <th className="px-5 py-3.5 border-b border-slate-200">Ports Route</th>
                            <th className="px-5 py-3.5 border-b border-slate-200">Transit Mode</th>
                            <th className="px-5 py-3.5 border-b border-slate-200">Customs Stage</th>
                            <th className="px-5 py-3.5 border-b border-slate-200">Workflow State</th>
                            <th className="px-5 py-3.5 border-b border-slate-200 text-right">Actions</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {visibleActiveJobs.length === 0 ? (
                            <tr>
                              <td colSpan={7} className="px-5 py-12 text-center text-slate-400 font-medium">
                                No active logistics fowarding jobs registered. Click + Initialize Freight Job to onboard immediately.
                              </td>
                            </tr>
                          ) : (
                            visibleActiveJobs.map((job) => {
                              return (
                                <tr key={job.id} className="hover:bg-slate-50/30 transition-colors">
                                  <td className="px-5 py-4 font-bold font-mono text-indigo-600 whitespace-nowrap">{job.jobNum}</td>
                                  <td className="px-5 py-4 font-semibold text-slate-900">{job.customerName}</td>
                                  <td className="px-5 py-4 whitespace-nowrap">
                                    <div className="font-semibold text-xs text-slate-800">Pol: {job.portLoading}</div>
                                    <div className="text-[11px] text-slate-400 mt-0.5">➔ Discharge: {job.portDischarge} ➔ Dest: {job.portDest}</div>
                                  </td>
                                  <td className="px-5 py-4 whitespace-nowrap">
                                    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${
                                      job.transportMode === "Sea" ? "bg-cyan-50 text-cyan-700 border border-cyan-150" : "bg-emerald-50 text-emerald-700 border border-emerald-150"
                                    }`}>
                                      {job.transportMode === "Sea" ? <Anchor size={12} /> : <Plane size={12} />}
                                      {job.transportMode}
                                    </span>
                                  </td>
                                  <td className="px-5 py-4 whitespace-nowrap">
                                    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                                      job.status === "Cleared" ? "bg-emerald-100 text-emerald-800" : "bg-amber-100 text-amber-850"
                                    }`}>
                                      {job.status}
                                    </span>
                                  </td>
                                  <td className="px-5 py-4 whitespace-nowrap">
                                    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border ${
                                      job.approvalStatus === "live" 
                                        ? "bg-emerald-50 text-emerald-700 border-emerald-200" 
                                        : "bg-orange-50 text-orange-700 border-orange-200 animate-pulse"
                                    }`}>
                                      {job.approvalStatus === "live" ? "Live in Console" : "Pending Approval"}
                                    </span>
                                  </td>
                                  <td className="px-5 py-4 whitespace-nowrap text-right">
                                    {job.approvalStatus === "pending_approval" ? (
                                      <span className="text-xs text-slate-400 font-semibold italic">Requires Admin Approval</span>
                                    ) : (
                                      <div className="flex items-center gap-2 justify-end">
                                        <select
                                          value={job.status}
                                          onChange={(e) => handleStatusChangeInList(job.id, e.target.value)}
                                          className="text-xs border border-slate-200 rounded-md py-1 px-2.5 bg-white text-slate-800 outline-none hover:border-slate-350 cursor-pointer"
                                        >
                                          <option value="Under Assessment">Under Assessment</option>
                                          <option value="Goods Examination">Examining Goods</option>
                                          <option value="Duty Payment Pending">Duty Pending</option>
                                          <option value="Cleared">Cleared</option>
                                        </select>
                                        <button
                                          onClick={() => handleCompleteJob(job.id)}
                                          className="text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-1 px-2.5 rounded shadow-xs cursor-pointer min-h-[32px] flex items-center"
                                        >
                                          Finish/Cleared
                                        </button>
                                        {currentUser === "super_admin" && (
                                          <button
                                            onClick={() => handleDeleteJob(job.id)}
                                            className="text-xs border border-rose-200 text-rose-600 hover:bg-rose-50 p-1 rounded transition-colors cursor-pointer"
                                            title="Permanently Delete Job"
                                          >
                                            <Trash2 size={15} />
                                          </button>
                                        )}
                                      </div>
                                    )}
                                  </td>
                                </tr>
                              );
                            })
                          )}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </motion.div>
              )}

              {/* === REGISTERED SHIPPERS PANEL === */}
              {currentTab === "shippers" && (
                <motion.div
                  key="shippers"
                  initial={{ opacity: 0, y: 12 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -12 }}
                  className="space-y-6"
                >
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                      <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Onboarded Corporate Shippers</h1>
                      <p className="text-sm text-slate-500">Master registered exporters & statutory regulatory business information.</p>
                    </div>
                    <button
                      onClick={() => setShowCustomerModal(true)}
                      className="btn bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm flex items-center justify-center gap-2 text-xs font-semibold cursor-pointer"
                    >
                      <UserPlus size={16} /> Add New Client Profile
                    </button>
                  </div>

                  <div className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                    <div className="overflow-x-auto">
                      <table className="w-full text-left border-collapse text-sm">
                        <thead className="bg-slate-50 text-slate-700 text-xs uppercase tracking-wider font-semibold border-b border-slate-250">
                          <tr>
                            <th className="px-5 py-3.5">Company Registered Profile</th>
                            <th className="px-5 py-3.5">GST Registration & PAN</th>
                            <th className="px-5 py-3.5 font-mono">IEC No</th>
                            <th className="px-5 py-3.5 font-mono">AD Code</th>
                            <th className="px-5 py-3.5 font-mono">IFSC Bank Code</th>
                            <th className="px-5 py-3.5">CFS / Delivery Factories</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {customers.length === 0 ? (
                            <tr>
                              <td colSpan={6} className="px-5 py-12 text-center text-slate-400">
                                No exporters registered yet. Press Add New Client Profile to onboard.
                              </td>
                            </tr>
                          ) : (
                            customers.map((c) => (
                              <tr key={c.id} className="hover:bg-slate-50/20">
                                <td className="px-5 py-4">
                                  <div className="font-bold text-slate-900">{c.name}</div>
                                  <div className="text-xs text-slate-400 mt-0.5 truncate max-w-[200px]">{c.address || "No office address"}</div>
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap">
                                  <div className="text-xs text-slate-700 font-mono">GST: {c.gstNo || "N/A"}</div>
                                  <div className="text-xs text-slate-400 font-mono mt-0.5">PAN: {c.panNo || "N/A"}</div>
                                </td>
                                <td className="px-5 py-4 font-mono font-medium whitespace-nowrap text-slate-700">{c.iecNo || "N/A"}</td>
                                <td className="px-5 py-4 font-mono font-medium whitespace-nowrap text-slate-700">{c.adCode || "N/A"}</td>
                                <td className="px-5 py-4 font-mono font-medium whitespace-nowrap text-slate-800">{c.ifscCode || "N/A"}</td>
                                <td className="px-5 py-4 text-xs text-slate-500 max-w-[220px]">
                                  <p className="line-clamp-2" title={c.factoryAddresses}>{c.factoryAddresses || "Unlisted"}</p>
                                </td>
                              </tr>
                            ))
                          )}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </motion.div>
              )}

              {/* === ADMIN ACTIONS APPROVALS PANEL === */}
              {currentTab === "approvals" && currentUser === "super_admin" && (
                <motion.div
                  key="approvals"
                  initial={{ opacity: 0, y: 12 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -12 }}
                  className="space-y-6"
                >
                  <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Administrative Duty Approvals</h1>
                    <p className="text-sm text-slate-500">Secure authorization of operations jobs submitted as drafts by staff clerks.</p>
                  </div>

                  <div className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                    <div className="px-6 py-4 border-b border-slate-150 flex items-center justify-between bg-slate-50/50">
                      <div className="font-bold text-sm text-slate-800 flex items-center gap-2">
                        <FileLock2 className="text-rose-500" size={18} />
                        Review Pending Job Outlines
                      </div>
                      <span className="text-xs text-slate-500 font-mono">Actions registered in Audit Journal</span>
                    </div>

                    <div className="overflow-x-auto">
                      <table className="w-full text-left border-collapse text-sm">
                        <thead className="bg-slate-50 text-slate-700 text-xs uppercase tracking-wider font-semibold border-b border-slate-200">
                          <tr>
                            <th className="px-5 py-3.5">Draft ID</th>
                            <th className="px-5 py-3.5">Assigned Customer</th>
                            <th className="px-5 py-3.5">Ports Route</th>
                            <th className="px-5 py-3.5">Mode</th>
                            <th className="px-5 py-3.5">Suggested status</th>
                            <th className="px-5 py-3.5 text-right">Verdicts</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {jobs.filter(j => j.approvalStatus === "pending_approval").length === 0 ? (
                            <tr>
                              <td colSpan={6} className="px-5 py-12 text-center text-emerald-600 font-semibold bg-emerald-50/20">
                                ✓ No pending draft authorizations from operational staff. Complete system safe.
                              </td>
                            </tr>
                          ) : (
                            jobs.filter(j => j.approvalStatus === "pending_approval").map((job) => (
                              <tr key={job.id} className="hover:bg-slate-50/30">
                                <td className="px-5 py-4 font-bold font-mono text-rose-600 whitespace-nowrap">{job.jobNum}</td>
                                <td className="px-5 py-4 font-bold text-slate-800">{job.customerName}</td>
                                <td className="px-5 py-4 whitespace-nowrap text-xs">
                                  <div>POL: {job.portLoading}</div>
                                  <div className="text-slate-400 mt-0.5">Discharge: {job.portDischarge} ➔ Dest: {job.portDest}</div>
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap">
                                  <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                                    {job.transportMode}
                                  </span>
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap">
                                  <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    {job.status}
                                  </span>
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap text-right">
                                  <div className="inline-flex gap-2">
                                    <button
                                      onClick={() => handleApproveJob(job.id)}
                                      className="text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-1.5 px-3 rounded-lg shadow-sm flex items-center gap-1 cursor-pointer"
                                    >
                                      <CheckCircle2 size={13} /> Approve Job
                                    </button>
                                    <button
                                      onClick={() => handleRejectDraft(job.id)}
                                      className="text-xs bg-rose-550 hover:bg-rose-650 text-white font-semibold py-1.5 px-3 rounded-lg shadow-sm flex items-center gap-1 cursor-pointer"
                                    >
                                      <XOctagon size={13} /> Reject Draft
                                    </button>
                                  </div>
                                </td>
                              </tr>
                            ))
                          )}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </motion.div>
              )}

              {/* === IMMUTABLE AUDIT LOGS TRAIL === */}
              {currentTab === "logs" && (
                <motion.div
                  key="logs"
                  initial={{ opacity: 0, y: 12 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -12 }}
                  className="space-y-6"
                >
                  <div className="flex items-center justify-between flex-wrap gap-4">
                    <div>
                      <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Immutable Operations Journal</h1>
                      <p className="text-sm text-slate-500">Security compliant activity logger for freight registrations, edits and credentials.</p>
                    </div>
                    <button
                      onClick={() => {
                        setAuditLogs(INITIAL_LOGS);
                      }}
                      className="text-xs border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 px-3 py-1.5 rounded-lg shadow-xs cursor-pointer font-semibold"
                    >
                      Reset Ledger Data
                    </button>
                  </div>

                  <div className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                    <div className="overflow-x-auto">
                      <table className="w-full text-left border-collapse text-sm">
                        <thead className="bg-slate-50 text-slate-700 text-xs uppercase tracking-wider font-semibold border-b border-slate-250">
                          <tr>
                            <th className="px-5 py-3.5 text-muted font-mono w-16">Log #</th>
                            <th className="px-5 py-3.5">User accounts</th>
                            <th className="px-5 py-3.5">Security role</th>
                            <th className="px-5 py-3.5">Actions Description</th>
                            <th className="px-5 py-3.5 text-right whitespace-nowrap">Timestamp</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {auditLogs.map((log) => (
                            <tr key={log.id} className="hover:bg-slate-50/10">
                              <td className="px-5 py-4 font-mono text-xs text-slate-400">{log.id}</td>
                              <td className="px-5 py-4 font-bold text-slate-800">{log.user}</td>
                              <td className="px-5 py-4 whitespace-nowrap">
                                <span className={`inline-flex px-2.5 py-0.5 text-[11px] font-bold rounded-full border ${
                                  log.role === "super_admin" 
                                    ? "bg-rose-50 text-rose-700 border-rose-150" 
                                    : "bg-indigo-50 text-indigo-700 border-indigo-150"
                                }`}>
                                  {log.role === "super_admin" ? "Super Admin" : "Operations"}
                                </span>
                              </td>
                              <td className="px-5 py-4 text-slate-650 text-xs sm:text-sm font-medium">{log.action}</td>
                              <td className="px-5 py-4 font-mono text-[11.5px] text-slate-400 text-right whitespace-nowrap">{log.timestamp}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </motion.div>
              )}

              {/* === SOURCE FILES EXPORT CODE PANEL === */}
              {currentTab === "sources" && (
                <motion.div
                  key="sources"
                  initial={{ opacity: 0, y: 12 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -12 }}
                  className="space-y-6"
                >
                  <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                      <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <Database className="text-amber-500" size={26} />
                        cPanel / Apache XAMPP Deployment Export
                      </h1>
                      <p className="text-sm text-slate-500">
                        These production quality PHP & MySQL files are generated and live in the workspace root. Ready to deploy with standard hosting setups.
                      </p>
                    </div>
                    
                    <div className="text-xs bg-indigo-50 text-indigo-800 rounded-lg p-3 border border-indigo-150 max-w-sm">
                      💡 Ensure you import <strong className="font-bold">database.sql</strong> inside your phpMyAdmin dashboard, then configure credentials inside DB settings.
                    </div>
                  </div>

                  {/* Horizontal source file tabs selector */}
                  <div className="flex flex-wrap border-b border-slate-200 gap-1.5">
                    {[
                      { key: "sql", name: "database.sql (MySQL Schema)", icon: "💾" },
                      { key: "db", name: "db.php (PDO Config)", icon: "🔌" },
                      { key: "auth", name: "auth.php (RBAC Middleware)", icon: "🛡️" },
                      { key: "api", name: "api.php (RESTful AJAX)", icon: "⚡" },
                      { key: "index", name: "index.php (Responsive Front)", icon: "🌐" }
                    ].map((tab) => (
                      <button
                        key={tab.key}
                        onClick={() => setSelectedSourceFile(tab.key as any)}
                        className={`px-4 py-2.5 rounded-t-lg font-semibold text-xs tracking-tight flex items-center gap-2 border-t-2 cursor-pointer transition-all ${
                          selectedSourceFile === tab.key
                            ? "bg-slate-900 text-white border-indigo-500"
                            : "bg-slate-100 hover:bg-slate-200 text-slate-500 border-transparent"
                        }`}
                      >
                        <span>{tab.icon}</span> {tab.name}
                      </button>
                    ))}
                  </div>

                  {/* Main File Box with content */}
                  <div className="bg-slate-950 rounded-xl relative overflow-hidden border border-slate-800 flex flex-col max-h-[600px]">
                    <div className="bg-slate-900 px-5 py-3 border-b border-slate-850 flex items-center justify-between text-xs text-slate-400 font-mono">
                      <span>File Name: <strong className="text-slate-200 font-bold">{selectedSourceFile === "sql" ? "database.sql" : `${selectedSourceFile}.php`}</strong></span>
                      <button
                        onClick={() => handleCopyCode(selectedSourceFile, getSourceFileCode() || "")}
                        className="bg-slate-800 hover:bg-slate-700 text-white text-[11px] font-bold py-1 px-3.5 rounded-md flex items-center gap-1.5 cursor-pointer shadow-sm"
                      >
                        {copiedFile === selectedSourceFile ? (
                          <>
                            <Check size={14} className="text-emerald-400" />
                            Copied!
                          </>
                        ) : (
                          <>
                            <Copy size={14} />
                            Copy Code Block
                          </>
                        )}
                      </button>
                    </div>

                    <div className="p-5 font-mono text-xs text-slate-300 overflow-y-auto overflow-x-auto text-left leading-relaxed whitespace-pre select-all bg-slate-950">
                      <code>{getSourceFileCode()}</code>
                    </div>

                    <div className="bg-slate-900/60 px-5 py-4 border-t border-slate-850 flex items-center justify-between text-[11px] text-slate-500">
                      <span>Complete PHP script available inside project package for folder exports.</span>
                      <span className="font-mono text-amber-500 bg-amber-950/20 px-2 py-0.5 rounded">Standard Web Dev Native Setup</span>
                    </div>
                  </div>
                </motion.div>
              )}

            </AnimatePresence>

          </div>
        </main>
      </div>

      {/* ==================== CREATE JOB MODAL INTERFACE ==================== */}
      <AnimatePresence>
        {showJobModal && (
          <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 z-100 overflow-y-auto">
            <motion.div 
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              className="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden"
            >
              <div className="px-6 py-4.5 border-b border-slate-150 flex items-center justify-between bg-slate-50">
                <div>
                  <h3 className="font-bold text-base text-slate-850">Onboard Logistics Freight Job</h3>
                  <p className="text-xs text-slate-400">Initialize a cargo consignment route mapping</p>
                </div>
                <button 
                  onClick={() => setShowJobModal(false)}
                  className="p-1 rounded-md text-slate-400 hover:bg-slate-200/50 hover:text-slate-800 cursor-pointer"
                >
                  <X size={20} />
                </button>
              </div>

              <form onSubmit={handleCreateJob} className="flex-1 overflow-y-auto p-6 space-y-6 text-left">
                {/* Headers Grid */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-650 tracking-wide block">Automatic Sequential Job ID</label>
                    <input
                      type="text"
                      readOnly
                      value={getNextJobNum()}
                      className="w-full text-xs font-mono font-bold bg-slate-100/70 border border-slate-200 rounded-lg p-2.5 text-indigo-700 outline-none select-none cursor-not-allowed"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-650 tracking-wide block">Select Registered Customer</label>
                    <select
                      value={newJobCust}
                      onChange={(e) => setNewJobCust(e.target.value)}
                      required
                      className="w-full text-xs border border-slate-250 rounded-lg p-2.5 bg-white text-slate-800 outline-none hover:border-slate-350"
                    >
                      <option value="">-- Choose Account --</option>
                      {customers.map(c => (
                        <option key={c.id} value={c.id}>{c.name}</option>
                      ))}
                    </select>
                  </div>

                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-650 tracking-wide block">Transport Mode</label>
                    <select
                      value={newJobMode}
                      onChange={(e) => setNewJobMode(e.target.value as any)}
                      className="w-full text-xs border border-slate-250 rounded-lg p-2.5 bg-white text-slate-800 outline-none hover:border-slate-350"
                    >
                      <option value="Sea">Sea Voyage (MSK/CMA)</option>
                      <option value="Air">Air Carrier (Cargo Packet)</option>
                    </select>
                  </div>
                </div>

                {/* Ports route mapping */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-650 tracking-wide block">Loading Port / CFS Site</label>
                    <input
                      type="text"
                      required
                      placeholder="e.g., JNPT (Nhava Sheva)"
                      value={newJobPortLoading}
                      onChange={(e) => setNewJobPortLoading(e.target.value)}
                      className="w-full text-xs border border-slate-250 rounded-lg p-2.5 bg-white text-slate-800 outline-none hover:border-slate-350"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-650 tracking-wide block">Port of Discharge</label>
                    <input
                      type="text"
                      required
                      placeholder="e.g., Jebel Ali, Dubai"
                      value={newJobPortDischarge}
                      onChange={(e) => setNewJobPortDischarge(e.target.value)}
                      className="w-full text-xs border border-slate-250 rounded-lg p-2.5 bg-white text-slate-800 outline-none hover:border-slate-350"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-650 tracking-wide block">Final Destination Address</label>
                    <input
                      type="text"
                      required
                      placeholder="e.g., Al Aweer Warehouse DF"
                      value={newJobPortDest}
                      onChange={(e) => setNewJobPortDest(e.target.value)}
                      className="w-full text-xs border border-slate-250 rounded-lg p-2.5 bg-white text-slate-800 outline-none hover:border-slate-350"
                    />
                  </div>
                </div>

                {/* Customs Status */}
                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-650 tracking-wide block">Initial Assessment Status</label>
                  <select
                    value={newJobCustomsStatus}
                    onChange={(e) => setNewJobCustomsStatus(e.target.value)}
                    className="w-full text-xs border border-slate-250 rounded-lg p-2.5 bg-white text-slate-800 outline-none hover:border-slate-350"
                  >
                    <option value="Under Assessment">Under Assessment (Customs Entry)</option>
                    <option value="Goods Examination">Physical Examining Phase</option>
                    <option value="Duty Payment Pending">Statutory Tax Duty Outstanding</option>
                    <option value="Cleared">Cleared & Released for CFS Gate Out</option>
                  </select>
                </div>

                {/* Sub-item table list matching job_containers database row additions */}
                <div className="border-t border-slate-200 pt-5 space-y-3.5">
                  <div className="flex items-center justify-between">
                    <div>
                      <h4 className="text-xs font-bold text-slate-800 uppercase tracking-widest text-muted">Cargo Shipping Line Containers / Cargo Packing</h4>
                      <p className="text-[11px] text-slate-400 mt-0.5">Define one or multiple container credentials as submitted onto cPanel table logic.</p>
                    </div>
                    <button
                      type="button"
                      onClick={handleAddContainerRow}
                      className="text-xs border border-indigo-200 text-indigo-700 bg-indigo-50 hover:bg-slate-100 font-semibold py-1 px-3 rounded cursor-pointer"
                    >
                      + Add Cargo Unit
                    </button>
                  </div>

                  <div className="overflow-x-auto border border-slate-200 rounded-lg bg-slate-50/30">
                    <table className="w-full text-left font-sans text-xs border-collapse">
                      <thead className="bg-slate-100 text-slate-700 border-b border-slate-200 text-[11px] font-semibold text-center uppercase tracking-wide">
                        <tr>
                          <th className="px-3 py-2 text-left">Booking No</th>
                          <th className="px-3 py-2 text-left">BL / Airway No</th>
                          <th className="px-3 py-2 text-left">Container No</th>
                          <th className="px-3 py-2 text-left">Seal No</th>
                          <th className="px-3 py-2 text-left">Package Details</th>
                          <th className="px-3 py-2 text-left">Gross Wt (Kgs)</th>
                          <th className="px-3 py-2 w-10"></th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-150 bg-white">
                        {newJobContainers.map((c, idx) => (
                          <tr key={idx}>
                            <td className="p-1 px-2">
                              <input
                                type="text"
                                required
                                placeholder="e.g. BKG-901"
                                value={c.bookingNo}
                                onChange={(e) => handleContainerFieldChange(idx, "bookingNo", e.target.value)}
                                className="w-full text-xs border border-slate-200 rounded py-1 px-2 bg-slate-50/50 hover:bg-white outline-none"
                              />
                            </td>
                            <td className="p-1 px-2">
                              <input
                                type="text"
                                required
                                placeholder="e.g. HLCU-098"
                                value={c.blNo}
                                onChange={(e) => handleContainerFieldChange(idx, "blNo", e.target.value)}
                                className="w-full text-xs border border-slate-200 rounded py-1 px-2 bg-slate-50/50 hover:bg-white outline-none"
                              />
                            </td>
                            <td className="p-1 px-2">
                              <input
                                type="text"
                                placeholder="e.g. MSKU9876543"
                                value={c.containerNo}
                                onChange={(e) => handleContainerFieldChange(idx, "containerNo", e.target.value)}
                                className="w-full text-xs border border-slate-200 rounded py-1 px-2 bg-slate-50/50 hover:bg-white outline-none"
                              />
                            </td>
                            <td className="p-1 px-2">
                              <input
                                type="text"
                                placeholder="e.g. SEAL-9321"
                                value={c.sealNo}
                                onChange={(e) => handleContainerFieldChange(idx, "sealNo", e.target.value)}
                                className="w-full text-xs border border-slate-200 rounded py-1 px-2 bg-slate-50/50 hover:bg-white outline-none"
                              />
                            </td>
                            <td className="p-1 px-2">
                              <input
                                type="text"
                                placeholder="e.g. 10 wooden boxes"
                                value={c.packageDesc}
                                onChange={(e) => handleContainerFieldChange(idx, "packageDesc", e.target.value)}
                                className="w-full text-xs border border-slate-200 rounded py-1 px-2 bg-slate-50/50 hover:bg-white outline-none"
                              />
                            </td>
                            <td className="p-1 px-2">
                              <input
                                type="number"
                                step="0.001"
                                placeholder="10500"
                                value={c.grossWt || ""}
                                onChange={(e) => handleContainerFieldChange(idx, "grossWt", Number(e.target.value))}
                                className="w-full text-xs border border-slate-200 rounded py-1 px-2 bg-slate-50/50 hover:bg-white outline-none"
                              />
                            </td>
                            <td className="p-1 px-2 text-center">
                              {newJobContainers.length > 1 && (
                                <button
                                  type="button"
                                  onClick={() => handleRemoveContainerRow(idx)}
                                  className="text-rose-500 hover:bg-rose-50 p-1 rounded cursor-pointer"
                                >
                                  &times;
                                </button>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>

                {/* Footer Buttons */}
                <div className="border-t border-slate-200 pt-5 flex items-center justify-end gap-3.5 bg-slate-50 -mx-6 -mb-6 p-6">
                  <button
                    type="button"
                    onClick={() => setShowJobModal(false)}
                    className="text-xs font-bold border border-slate-250 hover:bg-slate-100 text-slate-700 py-2.5 px-4.5 rounded-lg cursor-pointer"
                  >
                    Cancel Launch
                  </button>
                  
                  {currentUser === "super_admin" ? (
                    <button
                      type="submit"
                      className="text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 px-4.5 rounded-lg cursor-pointer shadow-sm"
                    >
                      Instant Launch Live Job
                    </button>
                  ) : (
                    <button
                      type="submit"
                      className="text-xs font-bold bg-orange-600 hover:bg-orange-700 text-white py-2.5 px-4.5 rounded-lg cursor-pointer shadow-sm animate-pulse"
                    >
                      Submit Job as Staff Draft
                    </button>
                  )}
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ==================== CREATE CUSTOMER MODAL INTERFACE ==================== */}
      <AnimatePresence>
        {showCustomerModal && (
          <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 z-100 overflow-y-auto">
            <motion.div 
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              className="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-xl max-h-[92vh] flex flex-col overflow-hidden"
            >
              <div className="px-6 py-4.5 border-b border-slate-150 flex items-center justify-between bg-slate-50">
                <div>
                  <h3 className="font-bold text-base text-slate-850">Register Exporter (Shipper Detail)</h3>
                  <p className="text-xs text-slate-400">Onboard customer identification numbers</p>
                </div>
                <button 
                  onClick={() => setShowCustomerModal(false)}
                  className="p-1 rounded-md text-slate-400 hover:bg-slate-200/50 hover:text-slate-800 cursor-pointer"
                >
                  <X size={20} />
                </button>
              </div>

              <form onSubmit={handleCreateCustomer} className="flex-1 overflow-y-auto p-6 space-y-4.5 text-left">
                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-650 tracking-wide block">Company Registered Corporate Name</label>
                  <input
                    type="text"
                    required
                    placeholder="e.g. Kenil Industries Pvt Ltd"
                    value={newCustName}
                    onChange={(e) => setNewCustName(e.target.value)}
                    className="w-full text-xs border border-slate-250 rounded-lg p-2.5 bg-white text-slate-800 outline-none hover:border-slate-350"
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-650 tracking-wide block">Billing Headquarters Address</label>
                  <textarea
                    rows={2}
                    placeholder="Corporate headquarters Office address..."
                    value={newCustAddress}
                    onChange={(e) => setNewCustAddress(e.target.value)}
                    className="w-full text-xs border border-slate-250 rounded-lg p-2.5 bg-white text-slate-800 outline-none hover:border-slate-350 resize-none"
                  />
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-650 tracking-wide block">GSTIN Register No</label>
                    <input
                      type="text"
                      placeholder="e.g. 24AAAKN1808P1Z5"
                      value={newCustGst}
                      onChange={(e) => setNewCustGst(e.target.value)}
                      className="w-full text-xs font-mono border border-slate-250 rounded-lg p-2.5 bg-white text-slate-800 outline-none hover:border-slate-350"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-650 tracking-wide block">IEC Code (Import Export)</label>
                    <input
                      type="text"
                      placeholder="10 digit license code"
                      value={newCustIec}
                      onChange={(e) => setNewCustIec(e.target.value)}
                      className="w-full text-xs font-mono border border-slate-250 rounded-lg p-2.5 bg-white text-slate-800 outline-none hover:border-slate-350"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-3 gap-3">
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-650 tracking-wide block font-semibold">PAN Card</label>
                    <input
                      type="text"
                      placeholder="PAN Number"
                      value={newCustPan}
                      onChange={(e) => setNewCustPan(e.target.value)}
                      className="w-full text-xs font-mono border border-slate-250 rounded-lg p-2.5 relative bg-white text-slate-800 outline-none hover:border-slate-350"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-650 tracking-wide block font-semibold">AD Code</label>
                    <input
                      type="text"
                      placeholder="AD Code"
                      value={newCustAd}
                      onChange={(e) => setNewCustAd(e.target.value)}
                      className="w-full text-xs font-mono border border-slate-250 rounded-lg p-2.5 relative bg-white text-slate-800 outline-none hover:border-slate-350"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-650 tracking-wide block font-semibold">IFSC Code</label>
                    <input
                      type="text"
                      placeholder="Bank IFSC Code"
                      value={newCustIfsc}
                      onChange={(e) => setNewCustIfsc(e.target.value)}
                      className="w-full text-xs font-mono border border-slate-250 rounded-lg p-2.5 relative bg-white text-slate-800 outline-none hover:border-slate-350"
                    />
                  </div>
                </div>

                <div className="space-y-1">
                  <label className="text-xs font-bold text-slate-650 tracking-wide block">CFS / Warehouse Delivery Factory Sites</label>
                  <textarea
                    rows={2}
                    placeholder="List delivery addresses or processing factory units..."
                    value={newCustFactories}
                    onChange={(e) => setNewCustFactories(e.target.value)}
                    className="w-full text-xs border border-slate-250 rounded-lg p-2.5 bg-white text-slate-800 outline-none hover:border-slate-350 resize-none"
                  />
                </div>

                {/* Footer Buttons */}
                <div className="border-t border-slate-200 pt-5 flex items-center justify-end gap-3.5 bg-slate-50 -mx-6 -mb-6 p-6">
                  <button
                    type="button"
                    onClick={() => setShowCustomerModal(false)}
                    className="text-xs font-bold border border-slate-250 hover:bg-slate-100 text-slate-700 py-2.5 px-4.5 rounded-lg cursor-pointer"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className="text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 px-4.5 rounded-lg cursor-pointer shadow-sm"
                  >
                    Onboard Corporate Client
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

    </div>
  );
}
