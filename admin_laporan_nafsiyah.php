<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Proteksi Halaman Admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 1. TANGKAP FILTER DARI URL (GET)
$filter_tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$filter_jurusan = $_GET['jurusan_id'] ?? '';

// 2. AMBIL DAFTAR JURUSAN UNTUK DROPDOWN
$stmtJurusan = $pdo->query("SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
$list_jurusan = $stmtJurusan->fetchAll();

// 3. BANGUN QUERY UTAMA (SUMMARY USER)
$sql = "SELECT u.id, u.full_name, j.nama_jurusan,
        (SELECT SUM(poin_didapat) FROM nafsiyah_logs WHERE user_id = u.id AND log_date = ?) as poin_harian,
        (SELECT COUNT(id) FROM nafsiyah_logs WHERE user_id = u.id AND log_date = ?) as status_lapor
        FROM users u
        LEFT JOIN jurusan j ON u.jurusan_id = j.id
        WHERE u.role = 'user'";

$params = [$filter_tanggal, $filter_tanggal];

// Filter berdasarkan jurusan jika dipilih
if (!empty($filter_jurusan)) {
    $sql .= " AND u.jurusan_id = ?";
    $params[] = $filter_jurusan;
}

$sql .= " ORDER BY j.nama_jurusan ASC, poin_harian DESC, u.full_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporan = $stmt->fetchAll();

// 4. AMBIL DATA RINCIAN LOG UNTUK MODAL DETAIL
// Mengambil semua log di tanggal tersebut, di-join dengan nama amalannya
$stmtLogs = $pdo->prepare("
    SELECT nl.*, ni.activity_name 
    FROM nafsiyah_logs nl 
    JOIN nafsiyah_items ni ON nl.item_id = ni.id 
    WHERE nl.log_date = ? 
    ORDER BY ni.urutan ASC
");
$stmtLogs->execute([$filter_tanggal]);
$raw_logs = $stmtLogs->fetchAll();

// Kelompokkan log berdasarkan user_id agar mudah dikirim ke JavaScript
$user_logs = [];
foreach ($raw_logs as $log) {
    $user_logs[$log['user_id']][] = $log;
}

// Hitung Statistik Singkat
$total_user = count($laporan);
$sudah_lapor = count(array_filter($laporan, fn($l) => $l['status_lapor'] > 0));
$persentase = $total_user > 0 ? round(($sudah_lapor / $total_user) * 100) : 0;
?>

<div class="space-y-10 animate-in fade-in duration-700">
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase italic">Laporan <span class="text-emerald-600">Nafsiyah</span></h2>
            <p class="text-slate-500 font-medium italic mt-1">Pantau progres dan rincian ibadah harian.</p>
        </div>
        
        <div class="flex gap-4 w-full md:w-auto overflow-x-auto no-scrollbar pb-2 md:pb-0">
            <div class="bg-emerald-50 px-6 py-4 rounded-3xl border border-emerald-100 min-w-[140px]">
                <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Sudah Lapor</p>
                <div class="text-2xl font-black text-emerald-700 italic"><?= $sudah_lapor ?> <span class="text-sm text-emerald-500">org</span></div>
            </div>
            <div class="bg-indigo-50 px-6 py-4 rounded-3xl border border-indigo-100 min-w-[140px]">
                <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-1">Persentase</p>
                <div class="text-2xl font-black text-indigo-700 italic"><?= $persentase ?>%</div>
            </div>
        </div>
    </header>

    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100">
        <form method="GET" action="admin_laporan_nafsiyah.php" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full md:w-1/3">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Tanggal Laporan</label>
                <input type="date" name="tanggal" value="<?= $filter_tanggal ?>" class="w-full p-4 rounded-2xl border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700 cursor-pointer">
            </div>
            
            <div class="w-full md:w-1/3">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Filter Jurusan</label>
                <select name="jurusan_id" class="w-full p-4 rounded-2xl border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700 appearance-none cursor-pointer">
                    <option value="">Semua Jurusan</option>
                    <?php foreach($list_jurusan as $j): ?>
                        <option value="<?= $j['id'] ?>" <?= $filter_jurusan == $j['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($j['nama_jurusan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="w-full md:w-auto">
                <button type="submit" class="w-full md:w-auto px-8 py-4 bg-slate-800 text-white rounded-2xl font-black shadow-lg hover:bg-slate-900 transition-all uppercase text-xs tracking-widest flex items-center justify-center gap-2">
                    <i data-lucide="filter" size={16}></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white p-8 rounded-[3.5rem] shadow-xl border border-slate-100 overflow-hidden relative z-10">
        <div class="flex justify-between items-center mb-6 px-2">
            <h3 class="font-black text-slate-700 italic text-lg">
                Data Tanggal: <span class="text-emerald-600"><?= date('d M Y', strtotime($filter_tanggal)) ?></span>
            </h3>
            <button onclick="window.print()" class="text-slate-400 hover:text-slate-700 transition-colors">
                <i data-lucide="printer"></i>
            </button>
        </div>

        <div class="overflow-x-auto custom-scrollbar border rounded-[2.5rem]">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-6 border-b font-black text-slate-400 text-[10px] uppercase tracking-widest">No</th>
                        <th class="p-6 border-b font-black text-slate-400 text-[10px] uppercase tracking-widest">Nama Lengkap</th>
                        <th class="p-6 border-b font-black text-slate-400 text-[10px] uppercase tracking-widest">Jurusan</th>
                        <th class="p-6 border-b text-center font-black text-slate-400 text-[10px] uppercase tracking-widest">Poin</th>
                        <th class="p-6 border-b text-center font-black text-slate-400 text-[10px] uppercase tracking-widest">Status</th>
                        <th class="p-6 border-b text-center font-black text-slate-400 text-[10px] uppercase tracking-widest">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if(empty($laporan)): ?>
                        <tr>
                            <td colspan="6" class="p-10 text-center text-slate-400 font-bold italic">Tidak ada data pengguna ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no=1; foreach($laporan as $row): 
                            $sudah = $row['status_lapor'] > 0;
                            // Ambil log spesifik untuk user ini dari array yang sudah dikelompokkan
                            $log_user_ini = $user_logs[$row['id']] ?? []; 
                        ?>
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="p-6 font-bold text-slate-500"><?= $no++ ?></td>
                            <td class="p-6 font-black text-slate-800 italic"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="p-6">
                                <span class="text-sm font-bold text-slate-600">
                                    <?= $row['nama_jurusan'] ?: '<span class="text-slate-300 italic">-</span>' ?>
                                </span>
                            </td>
                            <td class="p-6 text-center">
                                <?php if($sudah): ?>
                                    <span class="text-lg font-black text-emerald-600"><?= $row['poin_harian'] ?? 0 ?> <span class="text-[10px] text-emerald-400 uppercase tracking-widest">pt</span></span>
                                <?php else: ?>
                                    <span class="text-slate-300 font-bold">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-center">
                                <?php if($sudah): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        Selesai
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-rose-50 text-rose-500 border border-rose-100">
                                        Belum
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-center">
                                <?php if($sudah): 
                                    // Encode JSON dan Nama dengan aman agar tidak merusak HTML attribute
                                    $safe_logs = htmlspecialchars(json_encode($log_user_ini), ENT_QUOTES, 'UTF-8');
                                    $safe_name = htmlspecialchars(json_encode($row['full_name']), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <button onclick="bukaModalDetail(<?= $safe_logs ?>, <?= $safe_name ?>)" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-emerald-600 hover:text-white transition-all shadow-sm">
                                        Rincian
                                    </button>
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modal-detail-log" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 sm:p-6 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2.5rem] w-full max-w-4xl shadow-2xl flex flex-col border border-slate-100 transform scale-95 transition-transform duration-300" id="modal-content-box" style="max-height: 90vh;">
        
        <div class="p-6 sm:p-8 border-b border-slate-100 flex justify-between items-center shrink-0">
            <div>
                <h3 class="text-2xl font-black text-slate-800 italic uppercase tracking-tighter">Rincian Laporan</h3>
                <p class="text-emerald-600 font-bold text-sm tracking-wide mt-1" id="detail-nama-user">Nama User</p>
            </div>
            <button onclick="tutupModalDetail()" class="w-10 h-10 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-colors">
                <i data-lucide="x" size={20}></i>
            </button>
        </div>

        <div class="p-6 sm:p-8 overflow-y-auto custom-scrollbar flex-1">
            <div class="border border-slate-100 rounded-3xl overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="p-4 border-b font-black text-slate-400 text-[10px] uppercase tracking-widest">Amalan</th>
                            <th class="p-4 border-b text-center font-black text-slate-400 text-[10px] uppercase tracking-widest">Status</th>
                            <th class="p-4 border-b font-black text-slate-400 text-[10px] uppercase tracking-widest">Catatan</th>
                            <th class="p-4 border-b text-center font-black text-slate-400 text-[10px] uppercase tracking-widest">Poin</th>
                            <th class="p-4 border-b text-center font-black text-slate-400 text-[10px] uppercase tracking-widest">Bukti</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50" id="detail-tbody">
                        </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .space-y-10, .space-y-10 * { visibility: visible; }
    .space-y-10 { position: absolute; left: 0; top: 0; width: 100%; }
    form, header button, .lucide-printer, th:last-child, td:last-child { display: none !important; }
    .bg-white { box-shadow: none !important; border: none !important; }
}
</style>

<script>
function bukaModalDetail(logs, namaUser) {
    const modal = document.getElementById('modal-detail-log');
    const contentBox = document.getElementById('modal-content-box');
    const tbody = document.getElementById('detail-tbody');
    
    // Set Nama User di Header Modal
    document.getElementById('detail-nama-user').innerText = namaUser;
    
    // Bersihkan isi tabel sebelumnya
    tbody.innerHTML = '';

    if (logs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-slate-400 font-bold italic">Rincian amalan kosong.</td></tr>`;
    } else {
        logs.forEach(log => {
            // Logika Status Badge
            let statusHTML = log.status === 'selesai' 
                ? `<span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded text-[10px] font-black uppercase tracking-widest border border-emerald-100">Selesai</span>`
                : `<span class="px-2 py-1 bg-rose-50 text-rose-500 rounded text-[10px] font-black uppercase tracking-widest border border-rose-100">Tidak</span>`;
            
            // Logika File Bukti
            let buktiHTML = `<span class="text-slate-300 text-xs italic">-</span>`;
            if (log.file_bukti && log.file_bukti !== null && log.file_bukti !== '') {
                // Asumsi folder uploads berada di root project
                let fileUrl = `uploads/nafsiyah/${log.file_bukti}`;
                buktiHTML = `
                    <a href="${fileUrl}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-xs font-black uppercase hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                        <i class="fas fa-image"></i> Lihat
                    </a>`;
            }

            // Susun baris tabel HTML
            let rowHTML = `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="p-4 font-black text-slate-800 text-sm italic">${log.activity_name}</td>
                    <td class="p-4 text-center">${statusHTML}</td>
                    <td class="p-4 text-xs font-bold text-slate-500">${log.catatan}</td>
                    <td class="p-4 text-center text-emerald-600 font-black text-sm">${log.poin_didapat}</td>
                    <td class="p-4 text-center">${buktiHTML}</td>
                </tr>
            `;
            tbody.innerHTML += rowHTML;
        });
    }

    // Tampilkan Modal dengan Animasi
    modal.classList.remove('hidden');
    // Beri sedikit delay untuk memicu CSS transition
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        contentBox.classList.remove('scale-95');
    }, 10);
}

function tutupModalDetail() {
    const modal = document.getElementById('modal-detail-log');
    const contentBox = document.getElementById('modal-content-box');
    
    // Mainkan animasi keluar
    modal.classList.add('opacity-0');
    contentBox.classList.add('scale-95');
    
    // Sembunyikan setelah animasi selesai
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// Tutup modal saat user klik di luar kotak modal
window.onclick = function(event) {
    const modal = document.getElementById('modal-detail-log');
    if (event.target == modal) {
        tutupModalDetail();
    }
}
</script>

<?php include 'includes/footer.php'; ?>