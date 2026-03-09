<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_page_name = basename($_SERVER['PHP_SELF']);

// Helper function untuk CSS Menu Aktif
function getMenuClass($page, $current) {
    return $page === $current 
        ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200/50 translate-x-1' 
        : 'text-slate-500 bg-transparent hover:bg-emerald-50 hover:text-emerald-600 hover:translate-x-1';
}

// Pastikan koneksi database tersedia untuk fitur notifikasi
require_once __DIR__ . '/../config/database.php';

// --- LOGIKA NOTIFIKASI ---
$userId = $_SESSION['user_id'];
$tgl_hari_ini = date('Y-m-d');
$notif_list = [];
$unread_count = 0;

// 1. PENGINGAT OTOMATIS (SISTEM) - Cek apakah sudah isi nafsiyah hari ini
if ($_SESSION['role'] === 'user') {
    try {
        $cekLapor = $pdo->prepare("SELECT id FROM nafsiyah_logs WHERE user_id = ? AND log_date = ? LIMIT 1");
        $cekLapor->execute([$userId, $tgl_hari_ini]);
        $sudahLapor = $cekLapor->fetch();

        if (!$sudahLapor) {
            $notif_list[] = [
                'type' => 'system',
                'icon' => 'alert-triangle',
                'color' => 'text-amber-500 bg-amber-50 border-amber-200',
                'title' => 'Pengingat Mutaba\'ah',
                'message' => 'Anda belum mengisi laporan amalan hari ini. Yuk, luangkan waktu sebentar untuk menjaganya!',
                'time' => 'Hari Ini',
                'link' => 'input_nafsiyah.php'
            ];
            $unread_count++;
        }
    } catch (Exception $e) {
        // Abaikan jika tabel belum ada
    }
}

// 2. NOTIFIKASI DARI ADMIN (Database)
try {
    $stmtAdminNotif = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
    $adminNotifs = $stmtAdminNotif->fetchAll();

    foreach ($adminNotifs as $an) {
        $notif_list[] = [
            'type' => 'admin',
            'icon' => 'bell-ring',
            'color' => 'text-indigo-500 bg-indigo-50 border-indigo-200',
            'title' => htmlspecialchars($an['title']),
            'message' => htmlspecialchars($an['message']),
            'time' => date('d M', strtotime($an['created_at'])),
            'link' => '#'
        ];
        $unread_count++; 
    }
} catch (Exception $e) {
    // Abaikan jika tabel notifications belum dibuat
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ramadhan Pro - Digital Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FONT INTER UNTUK KESAN FORMAL & MODERN -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .sidebar-fixed { width: 280px; height: 100vh; position: fixed; left: 0; top: 0; background: white; z-index: 50; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .main-content { margin-left: 280px; width: calc(100% - 280px); min-height: 100vh; display: flex; flex-direction: column; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 40; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.show { display: block; opacity: 1; }
        .custom-scrollbar::-webkit-scrollbar { height: 5px; width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        @media (max-width: 1023px) { .sidebar-fixed { transform: translateX(-100%); box-shadow: none; } .sidebar-fixed.open { transform: translateX(0); box-shadow: 4px 0 24px rgba(0,0,0,0.1); } .main-content { margin-left: 0; width: 100%; } }
        
        /* Animasi Dropdown */
        .dropdown-animate { transform-origin: top right; transition: all 0.2s ease-out; }
        .dropdown-hidden { opacity: 0; transform: scale(0.95); pointer-events: none; }
        .dropdown-visible { opacity: 1; transform: scale(1); pointer-events: auto; }
    </style>
</head>
<body>

<div id="sidebar-overlay" class="sidebar-overlay lg:hidden" onclick="toggleSidebar()"></div>

<div class="flex">
    
    <!-- SIDEBAR KIRI -->
    <aside id="sidebar" class="sidebar-fixed border-r border-slate-100 flex flex-col">
        <!-- Logo Area -->
        <div class="h-20 flex items-center px-8 border-b border-slate-50 shrink-0">
            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 mr-3 border border-emerald-100">
                <i data-lucide="moon" size="22" class="fill-current text-emerald-200"></i>
            </div>
            <div class="leading-none">
                <span class="font-black text-xl text-slate-800 tracking-tight block">Ramadhan<span class="text-emerald-500">Pro</span></span>
                <span class="font-bold text-[9px] text-slate-400 uppercase tracking-widest">Digital Tracker</span>
            </div>
        </div>

        <!-- Menu Navigasi -->
        <nav class="p-4 space-y-1.5 flex-1 overflow-y-auto custom-scrollbar pb-8">
            <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest px-4 mb-3 mt-2">Menu Utama</p>
            
            <a href="index.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-300 font-bold uppercase text-[10px] tracking-widest <?= getMenuClass('index.php', $current_page_name) ?>">
                <i data-lucide="layout-dashboard" size="18"></i> Dashboard
            </a>
            <a href="input_nafsiyah.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-300 font-bold uppercase text-[10px] tracking-widest <?= getMenuClass('input_nafsiyah.php', $current_page_name) ?>">
                <i data-lucide="list-checks" size="18"></i> Input Nafsiyah
            </a>
            <a href="riwayat_nafsiyah.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-300 font-bold uppercase text-[10px] tracking-widest <?= getMenuClass('riwayat_nafsiyah.php', $current_page_name) ?>">
                <i data-lucide="history" size="18"></i> Riwayat Nafsiyah
            </a>
            <a href="habits.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-300 font-bold uppercase text-[10px] tracking-widest <?= getMenuClass('habits.php', $current_page_name) ?>">
                <i data-lucide="check-square" size="18"></i> Habit Tracker
            </a>
            <a href="quran.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-300 font-bold uppercase text-[10px] tracking-widest <?= getMenuClass('quran.php', $current_page_name) ?>">
                <i data-lucide="book-open" size="18"></i> Khatam Qur'an
            </a>
             <a href="leaderboard.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-300 font-bold uppercase text-[10px] tracking-widest <?= getMenuClass('leaderboard.php', $current_page_name) ?>">
                <i data-lucide="trophy" size="18"></i> Peringkat
            </a>
            
            <?php if($_SESSION['role'] === 'admin'): ?>
            <div class="pt-6 pb-2">
                <div class="h-px w-full bg-slate-100 mb-6"></div>
                <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest px-4 mb-3 flex items-center gap-2">
                    <i data-lucide="shield-check" size="14"></i> Administrasi
                </p>
                
                <a href="admin_users.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-300 font-bold uppercase text-[10px] tracking-widest <?= getMenuClass('admin_users.php', $current_page_name) ?>">
                    <i data-lucide="users" size="18"></i> Kelola User
                </a>
                <a href="nafsiyah.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-300 font-bold uppercase text-[10px] tracking-widest <?= getMenuClass('nafsiyah.php', $current_page_name) ?>">
                    <i data-lucide="activity" size="18"></i> Kelola Nafsiyah
                </a>
                <a href="admin_laporan_nafsiyah.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-300 font-bold uppercase text-[10px] tracking-widest <?= getMenuClass('admin_laporan_nafsiyah.php', $current_page_name) ?>">
                    <i data-lucide="file-bar-chart" size="18"></i> Laporan
                </a>
                <a href="admin_content.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-300 font-bold uppercase text-[10px] tracking-widest <?= getMenuClass('admin_content.php', $current_page_name) ?>">
                    <i data-lucide="settings" size="18"></i> Konten Web
                </a>
                <a href="admin_notif.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-300 font-bold uppercase text-[10px] tracking-widest <?= getMenuClass('admin_notif.php', $current_page_name) ?>">
                    <i data-lucide="megaphone" size="18"></i> Siaran Notifikasi
                </a>
            </div>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- AREA KONTEN UTAMA -->
    <div class="main-content">
        
        <!-- HEADER ATAS -->
        <header class="bg-white/80 backdrop-blur-xl border-b border-slate-100 sticky top-0 z-30 px-5 lg:px-10 h-20 flex justify-between items-center transition-all">
            
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2.5 bg-white border border-slate-200 text-slate-500 rounded-xl hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition-all shadow-sm">
                    <i data-lucide="menu" size="20"></i>
                </button>
                
                <div class="hidden lg:flex items-center gap-2 bg-emerald-50/50 border border-emerald-100 text-emerald-700 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest">
                    <i data-lucide="calendar" size="14"></i> RAMADHAN 1446 H
                </div>
            </div>

            <!-- AREA KANAN HEADER (Notifikasi & Profil) -->
            <div class="flex items-center gap-2 sm:gap-4">
                
                <!-- WIDGET NOTIFIKASI -->
                <div class="relative" id="notif-dropdown-container">
                    <button onclick="toggleNotifDropdown()" class="relative p-2.5 bg-white border border-slate-200 text-slate-500 rounded-full hover:bg-slate-50 hover:text-emerald-600 transition-all shadow-sm focus:outline-none">
                        <i data-lucide="bell" size="20"></i>
                        <?php if($unread_count > 0): ?>
                            <!-- Badge Merah -->
                            <span class="absolute top-0 right-0 flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500 border-2 border-white"></span>
                            </span>
                        <?php endif; ?>
                    </button>

                    <!-- Dropdown Panel Notifikasi -->
                    <div id="notif-menu" class="dropdown-animate dropdown-hidden absolute right-0 mt-3 w-80 sm:w-96 bg-white rounded-3xl shadow-2xl border border-slate-100 z-50 overflow-hidden">
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                            <h4 class="font-bold text-slate-800">Notifikasi</h4>
                            <span class="text-[10px] font-black text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-lg uppercase tracking-widest"><?= count($notif_list) ?> Baru</span>
                        </div>
                        <div class="max-h-80 overflow-y-auto custom-scrollbar">
                            <?php if(empty($notif_list)): ?>
                                <div class="p-6 text-center text-slate-400">
                                    <i data-lucide="bell-off" size="32" class="mx-auto mb-2 opacity-50"></i>
                                    <p class="text-xs font-medium">Belum ada notifikasi untuk Anda.</p>
                                </div>
                            <?php else: ?>
                                <div class="divide-y divide-slate-50">
                                    <?php foreach($notif_list as $notif): ?>
                                        <a href="<?= $notif['link'] ?>" class="p-4 flex gap-4 hover:bg-slate-50 transition-colors group block">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 border <?= $notif['color'] ?>">
                                                <i data-lucide="<?= $notif['icon'] ?>" size="18"></i>
                                            </div>
                                            <div>
                                                <h5 class="text-sm font-bold text-slate-800 group-hover:text-emerald-600 transition-colors"><?= $notif['title'] ?></h5>
                                                <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed"><?= $notif['message'] ?></p>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-2"><?= $notif['time'] ?></p>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- GARIS PEMISAH -->
                <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>

                <!-- WIDGET PROFIL -->
                <div class="relative inline-block text-left" id="profile-dropdown-container">
                    <button onclick="toggleProfileDropdown()" class="flex items-center gap-3 p-1.5 pr-4 rounded-full hover:bg-slate-50 border border-transparent hover:border-slate-100 transition-all group focus:outline-none">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-bold text-slate-800 leading-none group-hover:text-emerald-600 transition-colors"><?= htmlspecialchars($_SESSION['full_name']) ?></p>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-1.5"><?= $_SESSION['role'] ?></p>
                        </div>
                        
                        <?php 
                        $fotoBawaanUrl = '';
                        if (!empty($_SESSION['profile_picture']) && file_exists(__DIR__ . '/../uploads/profiles/' . $_SESSION['profile_picture'])) {
                            $fotoBawaanUrl = 'uploads/profiles/' . $_SESSION['profile_picture'];
                        }
                        ?>
                        
                        <?php if ($fotoBawaanUrl): ?>
                            <img src="<?= $fotoBawaanUrl ?>" class="w-10 h-10 rounded-full object-cover shadow-sm border-2 border-white ring-1 ring-slate-200 group-hover:ring-emerald-300 transition-all">
                        <?php else: ?>
                            <?php 
                            $nameParts = explode(' ', trim($_SESSION['full_name']));
                            $initial = strtoupper(substr($nameParts[0], 0, 1));
                            if (count($nameParts) > 1) $initial .= strtoupper(substr($nameParts[1], 0, 1));
                            ?>
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 text-white rounded-full flex items-center justify-center font-bold shadow-sm border-2 border-white ring-1 ring-slate-200 group-hover:ring-emerald-300 transition-all text-sm tracking-tight">
                                <?= $initial ?>
                            </div>
                        <?php endif; ?>
                        
                        <i data-lucide="chevron-down" size="16" class="text-slate-400 hidden sm:block group-hover:text-emerald-500 transition-colors"></i>
                    </button>

                    <!-- Dropdown Profil -->
                    <div id="dropdown-menu" class="dropdown-animate dropdown-hidden absolute right-0 mt-3 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 z-50">
                        <div class="p-2 space-y-1">
                            <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-xs font-bold text-slate-600 hover:bg-emerald-50 hover:text-emerald-600 rounded-xl transition-all">
                                <i data-lucide="user-cog" size="16"></i> Profil Saya
                            </a>
                            <div class="h-px bg-slate-100 my-1"></div>
                            <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="flex items-center gap-3 px-4 py-3 text-xs font-bold text-rose-500 hover:bg-rose-50 rounded-xl transition-all">
                                <i data-lucide="log-out" size="16"></i> Keluar Akun
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- KONTEN HALAMAN INJEKSI -->
        <main class="p-5 lg:p-10 max-w-7xl mx-auto w-full flex-1">