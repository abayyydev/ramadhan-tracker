<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// 1. Ambil semua data user untuk di-ranking (Hanya role 'user')
$stmt = $pdo->query("
    SELECT id, full_name, total_poin, streak_count, profile_picture 
    FROM users 
    WHERE role = 'user' 
    ORDER BY total_poin DESC, streak_count DESC, full_name ASC
");
$allUsers = $stmt->fetchAll();

// 2. Cari data dan ranking user yang sedang login
$currentUserRank = '-';
$currentUserData = null;

foreach ($allUsers as $index => $u) {
    if ($u['id'] == $userId) {
        $currentUserRank = $index + 1;
        $currentUserData = $u;
        break;
    }
}

// Jika admin yang melihat, beri data dummy agar tidak error
if (!$currentUserData) {
    $currentUserData = [
        'full_name' => $_SESSION['full_name'] ?? 'Admin',
        'total_poin' => 0,
        'streak_count' => 0,
        'profile_picture' => $_SESSION['profile_picture'] ?? null
    ];
}

// 3. Siapkan Top 3
$rank1 = $allUsers[0] ?? null;
$rank2 = $allUsers[1] ?? null;
$rank3 = $allUsers[2] ?? null;

// 4. Siapkan Top 10 untuk daftar lengkap
$top10 = array_slice($allUsers, 0, 10);

// Fungsi pembantu untuk merender Foto Profil / Inisial (Diperbaiki)
function renderAvatar($user, $sizeClass = 'w-16 h-16', $textClass = 'text-xl', $borderClass = 'border-4 border-white') {
    if (!$user) return '';
    
    // Ambil 1 atau 2 huruf inisial (Contoh: Budi Santoso -> BS)
    $nameParts = explode(' ', trim($user['full_name']));
    $initial = strtoupper(substr($nameParts[0], 0, 1));
    if (count($nameParts) > 1) {
        $initial .= strtoupper(substr($nameParts[1], 0, 1));
    }

    // Cek dengan akurat apakah file foto fisik ada di folder
    $hasProfilePic = false;
    if (!empty($user['profile_picture'])) {
        $filePath = __DIR__ . '/uploads/profiles/' . $user['profile_picture'];
        if (file_exists($filePath)) {
            $hasProfilePic = true;
        }
    }

    if ($hasProfilePic) {
        return '<img src="uploads/profiles/'.$user['profile_picture'].'" class="'.$sizeClass.' rounded-full object-cover '.$borderClass.' shadow-sm">';
    } else {
        // Fallback: Tampilkan Inisial dengan Tema Emerald Formal
        return '<div class="'.$sizeClass.' rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white flex items-center justify-center font-black '.$textClass.' '.$borderClass.' shadow-sm tracking-tighter">'.$initial.'</div>';
    }
}

// Fungsi pembantu untuk Badge Level
function getBadge($poin) {
    if ($poin >= 500) return '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[9px] font-black uppercase tracking-widest"><i class="fas fa-star mr-1"></i> Veteran</span>';
    if ($poin >= 100) return '<span class="px-2 py-0.5 bg-teal-100 text-teal-700 rounded text-[9px] font-black uppercase tracking-widest"><i class="fas fa-shield-alt mr-1"></i> Active</span>';
    return '<span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[9px] font-black uppercase tracking-widest"><i class="fas fa-seedling mr-1"></i> Newbie</span>';
}

include 'includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8 animate-in fade-in duration-700 pb-10">
    
    <!-- TOP BANNER (Info User Saat Ini) -->
    <div class="bg-white rounded-[2rem] p-5 shadow-sm border border-slate-100 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Garis gradien di atas card disesuaikan dengan tema Emerald -->
        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-300"></div>
        
        <div class="flex items-center gap-4 w-full md:w-auto">
            <?= renderAvatar($currentUserData, 'w-14 h-14', 'text-xl', 'border-0') ?>
            <div>
                <h2 class="text-xl font-black text-slate-800 tracking-tight"><?= htmlspecialchars($currentUserData['full_name']) ?></h2>
                <p class="text-xs font-bold text-slate-400">Terus tingkatkan ibadahmu!</p>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <div class="bg-emerald-50 px-5 py-3 rounded-2xl flex flex-col items-center justify-center min-w-[90px] border border-emerald-100">
                <span class="text-lg font-black text-emerald-600">#<?= $currentUserRank ?></span>
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Ranking</span>
            </div>
            <div class="bg-orange-50 px-5 py-3 rounded-2xl flex flex-col items-center justify-center min-w-[90px] border border-orange-100">
                <span class="text-lg font-black text-orange-600">🔥 <?= $currentUserData['streak_count'] ?></span>
                <span class="text-[9px] font-black text-orange-500 uppercase tracking-widest">Streak</span>
            </div>
        </div>
    </div>

    <!-- TOMBOL KEMBALI -->
    <div>
        <a href="dashboard.php" class="inline-flex items-center gap-2 px-6 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-full text-xs font-bold uppercase tracking-widest shadow-sm hover:bg-slate-50 hover:text-emerald-600 hover:scale-105 transition-all">
            <i data-lucide="arrow-left" size="16"></i> Kembali ke Dashboard
        </a>
    </div>

    <!-- PODIUM TOP 3 -->
    <div class="flex flex-col md:flex-row justify-center items-end gap-4 md:gap-6 pt-10">
        
        <!-- RANK 2 (Kiri) -->
        <?php if ($rank2): ?>
        <div class="w-full md:w-1/3 bg-white border-t-[6px] border-slate-300 rounded-[2.5rem] p-6 text-center shadow-lg relative order-2 md:order-1 hover:-translate-y-2 transition-transform duration-300">
            <div class="absolute -top-10 left-1/2 -translate-x-1/2 relative inline-block">
                <?= renderAvatar($rank2, 'w-20 h-20', 'text-2xl', 'border-4 border-slate-300') ?>
                <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-6 h-6 bg-slate-400 text-white rounded-full flex items-center justify-center text-xs font-black border-2 border-white">2</div>
            </div>
            <h3 class="mt-4 text-lg font-black text-slate-800 tracking-tight"><?= htmlspecialchars($rank2['full_name']) ?></h3>
            <p class="text-sm font-bold text-slate-500 mb-4"><?= $rank2['total_poin'] ?> Poin</p>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-50 text-orange-500 text-[10px] font-black uppercase tracking-widest border border-orange-100">
                🔥 <?= $rank2['streak_count'] ?> Hari
            </div>
        </div>
        <?php endif; ?>

        <!-- RANK 1 (Tengah) -->
        <?php if ($rank1): ?>
        <div class="w-full md:w-1/3 bg-amber-50 border-t-[6px] border-amber-400 rounded-[2.5rem] p-8 text-center shadow-2xl relative order-1 md:order-2 z-10 hover:-translate-y-2 transition-transform duration-300 md:-translate-y-6">
            <i class="fas fa-crown text-amber-400 text-4xl absolute -top-8 left-1/2 -translate-x-1/2 drop-shadow-md"></i>
            <div class="absolute -top-10 left-1/2 -translate-x-1/2 relative inline-block mt-2">
                <?= renderAvatar($rank1, 'w-24 h-24', 'text-3xl', 'border-4 border-amber-400') ?>
                <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-8 h-8 bg-amber-500 text-white rounded-full flex items-center justify-center text-sm font-black border-2 border-white shadow-sm">1</div>
            </div>
            <h3 class="mt-4 text-xl font-black text-slate-800 tracking-tight"><?= htmlspecialchars($rank1['full_name']) ?></h3>
            <p class="text-sm font-bold text-amber-600 mb-4"><?= $rank1['total_poin'] ?> Poin</p>
            <div class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-orange-100 text-orange-600 text-[10px] font-black uppercase tracking-widest border border-orange-200">
                🔥 <?= $rank1['streak_count'] ?> Hari
            </div>
        </div>
        <?php endif; ?>

        <!-- RANK 3 (Kanan) -->
        <?php if ($rank3): ?>
        <div class="w-full md:w-1/3 bg-white border-t-[6px] border-orange-300 rounded-[2.5rem] p-6 text-center shadow-lg relative order-3 hover:-translate-y-2 transition-transform duration-300">
            <div class="absolute -top-10 left-1/2 -translate-x-1/2 relative inline-block">
                <?= renderAvatar($rank3, 'w-20 h-20', 'text-2xl', 'border-4 border-orange-300') ?>
                <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-6 h-6 bg-orange-400 text-white rounded-full flex items-center justify-center text-xs font-black border-2 border-white">3</div>
            </div>
            <h3 class="mt-4 text-lg font-black text-slate-800 tracking-tight"><?= htmlspecialchars($rank3['full_name']) ?></h3>
            <p class="text-sm font-bold text-slate-500 mb-4"><?= $rank3['total_poin'] ?> Poin</p>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-50 text-orange-500 text-[10px] font-black uppercase tracking-widest border border-orange-100">
                🔥 <?= $rank3['streak_count'] ?> Hari
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- DAFTAR PERINGKAT LENGKAP -->
    <div class="bg-white p-8 rounded-[3.5rem] shadow-xl border border-slate-100 mt-8">
        <div class="flex justify-between items-center mb-6 px-2 border-b border-slate-100 pb-4">
            <h3 class="text-xl font-black text-slate-800 flex items-center gap-3">
                <i data-lucide="list-ordered" class="text-emerald-500"></i> Peringkat Lengkap
            </h3>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Top 10 Pejuang</span>
        </div>

        <div class="space-y-2">
            <?php foreach ($top10 as $index => $u): 
                $rank = $index + 1;
                $isMe = ($u['id'] == $userId);
            ?>
            <div class="flex items-center justify-between p-4 rounded-2xl transition-all <?= $isMe ? 'bg-emerald-50 border border-emerald-100' : 'hover:bg-slate-50 border border-transparent' ?>">
                
                <!-- Info Kiri (Rank + Avatar + Nama) -->
                <div class="flex items-center gap-4 sm:gap-6">
                    <!-- Ikon Medali/Angka -->
                    <div class="w-8 text-center flex-shrink-0">
                        <?php if ($rank === 1): ?>
                            <i class="fas fa-medal text-2xl text-amber-400 drop-shadow-sm"></i>
                        <?php elseif ($rank === 2): ?>
                            <i class="fas fa-medal text-2xl text-slate-400 drop-shadow-sm"></i>
                        <?php elseif ($rank === 3): ?>
                            <i class="fas fa-medal text-2xl text-orange-400 drop-shadow-sm"></i>
                        <?php else: ?>
                            <span class="text-lg font-black text-slate-400">#<?= $rank ?></span>
                        <?php endif; ?>
                    </div>

                    <?= renderAvatar($u, 'w-12 h-12', 'text-lg', 'border-0') ?>

                    <div>
                        <h4 class="font-black text-slate-800 text-sm sm:text-base flex items-center gap-2 tracking-tight">
                            <?= htmlspecialchars($u['full_name']) ?>
                            <?php if($isMe): ?>
                                <span class="px-2 py-0.5 bg-emerald-600 text-white rounded text-[8px] uppercase tracking-widest">You</span>
                            <?php endif; ?>
                        </h4>
                        <div class="flex items-center gap-2 mt-1">
                            <?= getBadge($u['total_poin']) ?>
                            <span class="text-[10px] font-bold text-orange-500 uppercase tracking-widest flex items-center gap-1">
                                <i class="fas fa-fire"></i> <?= $u['streak_count'] ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Info Kanan (Poin) -->
                <div class="text-right">
                    <div class="text-xl font-black text-slate-800"><?= $u['total_poin'] ?></div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Poin</div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>