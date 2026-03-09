<?php 
/**
 * File: index.php
 * Dashboard Utama - Sinkronisasi dengan Hari Ramadhan (H-1, H-2, dst)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php'; 

// 1. Identitas User
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// 2. Konfigurasi Hari Ramadhan
// Karena kamu ingin progres di Card mengikuti pengisian H-1, H-2...
// Silakan tentukan hari keberapa yang ingin ditampilkan sebagai "Progres Utama"
$activeRamadhanDay = 1; // Ubah ke 2, 3, dst sesuai hari berjalan

// 3. Ambil Progres Qur'an
$stmt = $pdo->prepare("SELECT * FROM quran_progress WHERE user_id = ?");
$stmt->execute([$userId]);
$quran = $stmt->fetch();
$currentJuz = $quran ? (int)$quran['current_juz'] : 1;
$totalKhatam = $quran ? (int)$quran['total_khatam'] : 0;
$juzPercentage = round(($currentJuz / 30) * 100);

// 4. Hitung Statistik Habits (Sesuai Hari Ramadhan yang dipilih)
$currentMonthYear = date('Y-m-');

// Hitung total jenis amalan milik user
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM habit_types WHERE user_id = ?");
$stmtTotal->execute([$userId]);
$totalHabitsType = (int)$stmtTotal->fetchColumn();

// Ambil data untuk Card (H-1 berarti mencari tanggal 01 di database)
$dateTarget = $currentMonthYear . str_pad($activeRamadhanDay, 2, '0', STR_PAD_LEFT);
$todayCount = getDailyHabitCount($pdo, $userId, $dateTarget);
$todayPercent = ($totalHabitsType > 0) ? round(($todayCount / $totalHabitsType) * 100) : 0;

// 5. Persiapkan Data Grafik (Tetap menampilkan 31 hari)
$chartLabels = [];
$habitCounts = [];
$juzLineData = [];

for ($i = 1; $i <= 31; $i++) {
    $dateLoop = $currentMonthYear . str_pad($i, 2, '0', STR_PAD_LEFT);
    $chartLabels[] = "H-" . $i;
    $count = getDailyHabitCount($pdo, $userId, $dateLoop);
    $habitCounts[] = $count;
    $juzLineData[] = $juzPercentage; 
}
?>

<div class="p-6 lg:p-10 max-w-7xl mx-auto w-full flex-1 flex flex-col gap-8">
    <!-- GREETING SECTION -->
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl md:text-4xl font-black text-slate-800 tracking-tight serif-font italic">
                Assalamu'alaikum, <?= explode(' ', trim($userName))[0] ?>! 👋
            </h1>
            <p class="text-slate-500 text-sm md:text-base mt-2 font-medium">Pantau konsistensi ibadah dan progres spiritual Anda di bulan penuh berkah.</p>
        </div>
        <div class="flex items-center gap-3 bg-white px-5 py-3 rounded-2xl border border-slate-200 shadow-sm">
            <div class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse"></div>
            <span class="text-xs font-bold text-slate-600 uppercase tracking-widest"><?= date('d F Y') ?></span>
        </div>
    </header>

    <!-- STAT CARDS SECTION -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card Habits -->
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-emerald-100 flex items-center gap-6 transition-all hover:shadow-md hover:border-emerald-300 group">
            <div class="w-20 h-20 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 shrink-0 shadow-inner group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                <i data-lucide="check-circle" size="40" stroke-width="2.5"></i>
            </div>
            <div class="flex-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Penyelesaian Amalan</p>
                <h3 class="text-4xl font-black text-slate-800 serif-font italic"><?= $todayPercent ?>%</h3>
                <p class="text-xs text-slate-500 mt-2 font-medium">
                    <span class="text-emerald-600 font-bold"><?= $todayCount ?></span> dari <?= $totalHabitsType ?> amalan tuntas hari ini.
                </p>
            </div>
        </div>

        <!-- Card Quran (Tema Emerald / Teal Gelap) -->
        <div class="bg-gradient-to-br from-emerald-800 to-teal-900 p-8 rounded-[2rem] shadow-lg text-white flex items-center gap-6 relative overflow-hidden transition-all hover:shadow-xl group">
            <div class="w-20 h-20 bg-white/10 rounded-2xl flex items-center justify-center text-emerald-100 shrink-0 border border-white/10 group-hover:bg-white group-hover:text-emerald-900 transition-colors duration-300 relative z-10">
                <i data-lucide="book-open" size="40" stroke-width="2.5"></i>
            </div>
            <div class="relative z-10 flex-1">
                <p class="text-[10px] font-black text-emerald-200/80 uppercase tracking-[0.2em] mb-1">Capaian Tilawah</p>
                <h3 class="text-4xl font-black serif-font italic tracking-tighter">JUZ <?= $currentJuz ?></h3>
                <p class="text-xs text-emerald-100/70 mt-2 font-medium uppercase tracking-widest">
                    Total Khatam: <span class="text-white font-bold"><?= $totalKhatam ?>x</span>
                </p>
            </div>
            <i data-lucide="award" class="absolute -bottom-6 -right-6 w-32 h-32 opacity-10 rotate-12 text-white"></i>
        </div>
    </div>

    <!-- MAIN CHART SECTION -->
    <div class="bg-white p-8 md:p-10 rounded-[2rem] shadow-sm border border-slate-200 relative overflow-hidden flex-1 flex flex-col">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter serif-font italic">Analisis Progres</h2>
                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-1">Laporan 31 Hari Ramadhan</p>
            </div>
            
            <div class="flex bg-slate-50 border border-slate-200 p-1.5 rounded-xl w-full md:w-auto overflow-x-auto no-scrollbar">
                <button onclick="updateDashboardChart('all')" class="dash-filter-btn active-dash px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all whitespace-nowrap">Full</button>
                <button onclick="updateDashboardChart(1)" class="dash-filter-btn px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all whitespace-nowrap">W1</button>
                <button onclick="updateDashboardChart(2)" class="dash-filter-btn px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all whitespace-nowrap">W2</button>
                <button onclick="updateDashboardChart(3)" class="dash-filter-btn px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all whitespace-nowrap">W3</button>
                <button onclick="updateDashboardChart(4)" class="dash-filter-btn px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all whitespace-nowrap">W4</button>
                <button onclick="updateDashboardChart(5)" class="dash-filter-btn px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all whitespace-nowrap">W5</button>
            </div>
        </div>

        <style>
            .dash-filter-btn { color: #64748b; }
            .dash-filter-btn.active-dash { background: white; color: #059669; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1); }
            .no-scrollbar::-webkit-scrollbar { display: none; }
        </style>

        <div class="h-[300px] md:h-[400px] w-full">
            <canvas id="dashboardHabitChart"></canvas>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.3em] text-center md:text-left">
                Data Berdasarkan Input User: <span class="text-slate-600"><?= $userName ?></span>
            </p>
            <div class="flex items-center gap-2 text-teal-700 font-bold text-[10px] uppercase bg-teal-50 px-3 py-1.5 rounded-lg border border-teal-100">
                <i data-lucide="info" size="14"></i>
                Garis Teal menunjukkan capaian Juz Anda (<?= $juzPercentage ?>%)
            </div>
        </div>
    </div>
</div>

<script>
let dashboardChart;
const allLabels = <?= json_encode($chartLabels) ?>;
const allHabitData = <?= json_encode($habitCounts) ?>;
const allJuzData = <?= json_encode($juzLineData) ?>;
const totalHabits = <?= $totalHabitsType > 0 ? $totalHabitsType : 20 ?>;

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('dashboardHabitChart').getContext('2d');
    
    // Auto-detect view: mobile tampilkan minggu ini saja
    const todayNum = parseInt("<?= date('j') ?>");
    const initialRange = window.innerWidth < 768 ? Math.ceil(todayNum / 7) : 'all';

    dashboardChart = new Chart(ctx, {
        data: {
            labels: [], 
            datasets: [
                {
                    type: 'bar',
                    label: 'Amalan Tuntas',
                    data: [],
                    backgroundColor: '#10b981', // Emerald 500
                    borderRadius: 8,
                    barThickness: 12,
                    yAxisID: 'yHabits'
                },
                {
                    type: 'line',
                    label: 'Benchmark Juz',
                    data: [],
                    borderColor: '#0f766e', // Teal 700
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#0f766e',
                    fill: false,
                    tension: 0.3, // Membuat garis sedikit melengkung agar estetik
                    borderDash: [5, 5],
                    yAxisID: 'yJuz'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false } },
            scales: {
                yHabits: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    max: totalHabits,
                    grid: { color: '#f8fafc' },
                    ticks: { color: '#10b981', font: { weight: 'bold' } }
                },
                yJuz: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    max: 100,
                    grid: { display: false },
                    ticks: { 
                        color: '#0f766e', 
                        font: { weight: 'bold' },
                        callback: value => value + '%'
                    }
                },
                x: { 
                    grid: { display: false },
                    ticks: { 
                        font: { size: 10, weight: 'bold' },
                        maxRotation: 0,
                        minRotation: 0 
                    }
                }
            }
        }
    });

    updateDashboardChart(initialRange);
});

function updateDashboardChart(range) {
    let labels, habits, juz;

    if (range === 'all') {
        labels = allLabels;
        habits = allHabitData;
        juz = allJuzData;
        dashboardChart.data.datasets[0].barThickness = window.innerWidth < 768 ? 6 : 12;
    } else {
        const start = (range - 1) * 7;
        const end = Math.min(start + 7, 31); // Batasi max 31 hari
        labels = allLabels.slice(start, end);
        habits = allHabitData.slice(start, end);
        juz = allJuzData.slice(start, end);
        dashboardChart.data.datasets[0].barThickness = 30; // Bar tebal di mobile
    }

    // Update Data
    dashboardChart.data.labels = labels;
    dashboardChart.data.datasets[0].data = habits;
    dashboardChart.data.datasets[1].data = juz;

    // Update Visual Sumbu X
    dashboardChart.options.scales.x.ticks.autoSkip = range === 'all';
    
    dashboardChart.update();

    // Update UI Filter Button
    document.querySelectorAll('.dash-filter-btn').forEach(btn => {
        btn.classList.remove('active-dash');
        const btnVal = btn.getAttribute('onclick').match(/'?(\w+)'?/)[1];
        if (btnVal == range) btn.classList.add('active-dash');
    });
}
</script>

<?php include 'includes/footer.php'; ?>