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

// --- LOGIKA FILTER TANGGAL ---
$filterDate = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

// Siapkan query dasar
$queryStr = "
    SELECT nl.*, ni.activity_name, ni.is_udzur 
    FROM nafsiyah_logs nl 
    JOIN nafsiyah_items ni ON nl.item_id = ni.id 
    WHERE nl.user_id = ? 
";
$queryParams = [$userId];

// Tambahkan kondisi filter jika ada tanggal yang dipilih
if (!empty($filterDate)) {
    $queryStr .= " AND nl.log_date = ? ";
    $queryParams[] = $filterDate;
}

$queryStr .= " ORDER BY nl.log_date DESC, ni.urutan ASC";

$stmt = $pdo->prepare($queryStr);
$stmt->execute($queryParams);
$rawLogs = $stmt->fetchAll();

// Kelompokkan data berdasarkan tanggal agar mudah ditampilkan
$groupedLogs = [];
$availableDates = [];

// Ambil semua tanggal unik untuk menghitung total hari laporan
$stmtDates = $pdo->prepare("SELECT DISTINCT log_date FROM nafsiyah_logs WHERE user_id = ? ORDER BY log_date DESC");
$stmtDates->execute([$userId]);
$availableDates = $stmtDates->fetchAll(PDO::FETCH_COLUMN);

foreach ($rawLogs as $log) {
    $date = $log['log_date'];
    if (!isset($groupedLogs[$date])) {
        $groupedLogs[$date] = [
            'total_poin' => 0,
            'items' => []
        ];
    }
    $groupedLogs[$date]['items'][] = $log;
    $groupedLogs[$date]['total_poin'] += (int)$log['poin_didapat'];
}

include 'includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-6 animate-in fade-in duration-700 font-sans pb-10">
    
    <!-- HEADER HALAMAN -->
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Riwayat Laporan Mutaba'ah</h2>
            <p class="text-sm text-slate-500 mt-1">Pantau grafik ibadah harian dan evaluasi amalan Anda.</p>
        </div>
        <div class="bg-emerald-50 px-5 py-3 rounded-xl border border-emerald-100 flex items-center gap-3">
            <div class="w-8 h-8 bg-emerald-600 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="calendar-days" size="16"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">Total Hari</p>
                <p class="font-black text-emerald-800 leading-none"><?= count($availableDates) ?> Hari Laporan</p>
            </div>
        </div>
    </header>

    <!-- FILTER AREA -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-2 text-sm font-semibold text-slate-600">
            <i data-lucide="calendar-search" size="18" class="text-emerald-500"></i> Pilih Tanggal:
        </div>
        
        <form method="GET" action="" class="flex w-full sm:w-auto gap-2">
            <!-- Menggunakan input date bawaan browser -->
            <input type="date" name="filter_date" value="<?= htmlspecialchars($filterDate) ?>" 
                   class="flex-1 sm:w-48 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer">
            
            <button type="submit" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-bold shadow-sm hover:bg-emerald-700 transition-colors">
                Cari
            </button>
            
            <?php if (!empty($filterDate)): ?>
                <a href="riwayat_nafsiyah.php" class="px-4 py-2.5 bg-slate-100 text-slate-500 hover:text-rose-500 hover:bg-rose-50 rounded-xl text-sm font-bold shadow-sm transition-colors flex items-center justify-center" title="Reset Filter">
                    <i data-lucide="x" size="18"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- KONTEN RIWAYAT -->
    <?php if (empty($groupedLogs)): ?>
        <div class="bg-white rounded-2xl p-12 text-center shadow-sm border border-slate-100">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                <i data-lucide="folder-open" size="32"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-700 mb-2">
                <?= empty($filterDate) ? 'Belum Ada Riwayat' : 'Tidak Ada Data di Tanggal Ini' ?>
            </h3>
            <p class="text-sm text-slate-500">
                <?= empty($filterDate) ? 'Anda belum pernah mengisi laporan Nafsiyah. Yuk, mulai isi hari ini!' : 'Anda belum mengisi laporan pada tanggal tersebut. Silakan pilih tanggal lain atau reset filter.' ?>
            </p>
            <?php if (empty($filterDate)): ?>
                <a href="input_nafsiyah.php" class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition-all">
                    <i data-lucide="pen-line" size="16"></i> Isi Laporan Sekarang
                </a>
            <?php else: ?>
                 <a href="riwayat_nafsiyah.php" class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition-all">
                    Tampilkan Semua
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($groupedLogs as $date => $data): 
                $tanggalIndo = date('d F Y', strtotime($date));
                $isUdzurDay = false;
                foreach ($data['items'] as $it) {
                    if ($it['catatan'] === "Udzur Syar'i") {
                        $isUdzurDay = true;
                        break;
                    }
                }
            ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden group">
                    <!-- Tanggal Header -->
                    <div class="bg-slate-50 border-b border-slate-100 px-6 py-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white rounded-lg shadow-sm border border-slate-200 flex flex-col items-center justify-center text-emerald-600 shrink-0">
                                <span class="text-[9px] font-bold uppercase leading-none"><?= date('M', strtotime($date)) ?></span>
                                <span class="text-sm font-black leading-none mt-0.5"><?= date('d', strtotime($date)) ?></span>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800"><?= $tanggalIndo ?></h3>
                                <?php if ($isUdzurDay): ?>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-rose-500 mt-0.5">
                                        <i data-lucide="moon" size="12"></i> Mode Udzur Syar'i
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500 font-medium mt-0.5">Laporan Harian</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Poin</p>
                            <p class="text-xl font-black text-emerald-600"><?= $data['total_poin'] ?></p>
                        </div>
                    </div>

                    <!-- Detail Amalan -->
                    <div class="p-5 sm:p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($data['items'] as $item): 
                                $isSelesai = ($item['status'] === 'selesai');
                                $isUdzurItem = ($item['catatan'] === "Udzur Syar'i");
                            ?>
                                <div class="flex items-start gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl border <?= $isSelesai ? 'border-emerald-100 bg-emerald-50/30' : 'border-slate-100 bg-slate-50' ?>">
                                    <!-- Ikon Status -->
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5 <?= $isSelesai ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-200 text-slate-400' ?>">
                                        <?php if ($isUdzurItem): ?>
                                            <i data-lucide="moon" size="14"></i>
                                        <?php elseif ($isSelesai): ?>
                                            <i data-lucide="check" size="14" stroke-width="3"></i>
                                        <?php else: ?>
                                            <i data-lucide="x" size="14" stroke-width="3"></i>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Info Amalan -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold truncate <?= $isSelesai ? 'text-slate-800' : 'text-slate-500 line-through decoration-slate-300' ?>" title="<?= htmlspecialchars($item['activity_name']) ?>">
                                            <?= htmlspecialchars($item['activity_name']) ?>
                                        </p>
                                        
                                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                            <!-- Badge Status / Catatan -->
                                            <?php if ($isUdzurItem): ?>
                                                <span class="text-[10px] font-semibold bg-rose-100 text-rose-600 px-2 py-0.5 rounded uppercase tracking-wider">Udzur</span>
                                            <?php else: ?>
                                                <span class="text-[10px] font-semibold <?= $isSelesai ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' ?> px-2 py-0.5 rounded uppercase tracking-wider whitespace-nowrap">
                                                    <?= htmlspecialchars($item['catatan']) ?>
                                                </span>
                                            <?php endif; ?>

                                            <!-- Badge Poin -->
                                            <span class="text-[10px] font-bold text-amber-600 flex items-center gap-1 shrink-0">
                                                <i data-lucide="star" size="10" class="fill-amber-500"></i> <?= $item['poin_didapat'] ?> pt
                                            </span>
                                        </div>

                                        <!-- Tombol Lihat Bukti -->
                                        <?php if ($isSelesai && !empty($item['file_bukti']) && !$isUdzurItem): ?>
                                            <a href="uploads/nafsiyah/<?= htmlspecialchars($item['file_bukti']) ?>" target="_blank" class="inline-flex items-center gap-1.5 mt-2 text-[10px] font-bold text-blue-600 hover:text-blue-800 transition-colors bg-blue-50 px-2.5 py-1 rounded-md border border-blue-100 w-fit">
                                                <i data-lucide="image" size="12"></i> Lihat Bukti
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>