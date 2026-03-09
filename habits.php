<?php 
/**
 * Fail: habits.php
 * Sistem Manajemen Amalan Personal Ramadhan Pro.
 * Fitur: CRUD Amalan, Habit Grid 31 Hari, Grafik Interaktif, Responsif Mobile.
 */
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php'; 

$userId = $_SESSION['user_id'];
$todayDay = (int)date('j');
$currentMonthYear = date('Y-m-');

// 1. AMBIL DAFTAR AMALAN (Milik User + Default)
$stmt = $pdo->prepare("SELECT * FROM habit_types WHERE user_id = ? OR user_id IS NULL ORDER BY id ASC");
$stmt->execute([$userId]);
$habitTypes = $stmt->fetchAll();

// 2. AMBIL RIWAYAT PENYELESAIAN (Log Date)
$stmt = $pdo->prepare("SELECT log_date, habit_name, is_completed FROM habit_logs WHERE user_id = ? AND log_date LIKE ?");
$stmt->execute([$userId, $currentMonthYear . '%']);
$logs = $stmt->fetchAll();

// 3. PEMETAAN DATA UNTUK GRID & GRAFIK
$historyMap = [];
$dailyCounts = array_fill(1, 31, 0);

foreach ($logs as $log) {
    $day = (int)date('j', strtotime($log['log_date']));
    if ($day >= 1 && $day <= 31) {
        $historyMap[$day][$log['habit_name']] = $log['is_completed'];
        if($log['is_completed'] == 1) {
            $dailyCounts[$day]++;
        }
    }
}

?>

<!-- Library Tambahan: SweetAlert2 & Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="space-y-10 animate-in fade-in duration-700">
    <!-- HEADER & FORM TAMBAH -->
    <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase italic">My <span class="text-emerald-600">Habits</span></h2>
            <p class="text-slate-500 font-medium italic mt-1 uppercase tracking-widest text-[10px]">Atur amalan personal Anda selama Ramadhan.</p>
        </div>
        
        <form id="addHabitForm" class="flex gap-2 w-full lg:w-auto">
            <input type="text" id="newHabitName" placeholder="Tulis amalan baru..." 
                   class="flex-1 lg:w-64 p-4 rounded-2xl border bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 font-bold text-sm transition-all shadow-inner">
            <button type="submit" class="bg-emerald-600 text-white p-4 rounded-2xl shadow-lg hover:bg-emerald-700 transition-all active:scale-95 shadow-emerald-100">
                <i data-lucide="plus"></i>
            </button>
        </form>
    </header>

    <!-- SECTION ANALISIS (CHART) -->
    <div class="bg-white p-6 md:p-10 rounded-[2.5rem] md:rounded-[4rem] shadow-xl border border-slate-100 transition-transform hover:scale-[1.01]">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
            <h3 class="font-black text-xl flex items-center gap-4 text-slate-800 uppercase tracking-tighter italic">
                <div class="w-2 h-10 bg-emerald-600 rounded-full shadow-sm shadow-emerald-200"></div> 
                Analisis Progres
            </h3>
            
            <!-- Filter Grafik -->
            <div class="flex bg-slate-100 p-1.5 rounded-2xl w-full md:w-auto overflow-x-auto no-scrollbar border">
                <button onclick="updateChartRange('all')" class="filter-btn active-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all whitespace-nowrap">Full View</button>
                <button onclick="updateChartRange(1)" class="filter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">W1</button>
                <button onclick="updateChartRange(2)" class="filter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">W2</button>
                <button onclick="updateChartRange(3)" class="filter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">W3</button>
                <button onclick="updateChartRange(4)" class="filter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">W4</button>
            </div>
        </div>
        <div class="h-72 w-full">
            <canvas id="habitFullChart"></canvas>
        </div>
    </div>

    <!-- HABIT GRID (DESKTOP) -->
    <div class="hidden lg:block bg-white p-8 rounded-[3.5rem] shadow-xl border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar border rounded-[2.5rem]">
            <table class="w-full text-left border-collapse min-w-[1400px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-6 border-b font-black text-slate-400 text-[10px] uppercase sticky left-0 bg-slate-50 z-20 shadow-sm">Daftar Amalan</th>
                        <?php for($i=1; $i<=31; $i++): ?>
                        <th class="p-4 border-b text-center font-black text-[10px] <?= $i == $todayDay ? 'text-emerald-600 bg-emerald-50/50' : 'text-slate-300' ?>">H-<?= $i ?></th>
                        <?php endfor; ?>
                        <th class="p-6 border-b text-center font-black text-slate-400 text-[10px] uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($habitTypes as $habit): ?>
                    <tr class="group hover:bg-slate-50/50" data-habit-id="<?= $habit['id'] ?>">
                        <td class="p-6 border-b font-black text-sm text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_15px_rgba(0,0,0,0.03)] group-hover:bg-slate-50 transition-colors">
                            <span class="italic"><?= $habit['name'] ?></span>
                        </td>
                        <?php for($i=1; $i<=31; $i++): 
                            $isChecked = isset($historyMap[$i][$habit['name']]) && $historyMap[$i][$habit['name']] == 1;
                        ?>
                        <td class="p-3 border-b text-center cursor-pointer transition-all" 
                            onclick="toggleHabit(<?= htmlspecialchars(json_encode($habit['name']), ENT_QUOTES) ?>, <?= $i ?>, this)">
                            <div class="habit-box w-9 h-9 mx-auto rounded-xl flex items-center justify-center transition-all border-2 
                                <?= $isChecked ? 'bg-emerald-500 border-emerald-500 text-white shadow-lg shadow-emerald-100' : 'bg-white border-slate-100 hover:border-emerald-200' ?>">
                                <?php if($isChecked) echo '<i data-lucide="check" size="16" stroke-width="4"></i>'; ?>
                            </div>
                        </td>
                        <?php endfor; ?>
                        <td class="p-6 border-b text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="editHabit(<?= $habit['id'] ?>, '<?= htmlspecialchars(addslashes($habit['name']), ENT_QUOTES) ?>')" class="p-2 text-slate-300 hover:text-emerald-500 transition-all hover:scale-125">
                                    <i data-lucide="pencil" size="16"></i>
                                </button>
                                <button onclick="deleteHabit(<?= $habit['id'] ?>, this)" class="p-2 text-slate-300 hover:text-red-500 transition-all hover:scale-125">
                                    <i data-lucide="trash-2" size="16"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- HABIT CARDS (MOBILE) -->
    <div class="lg:hidden space-y-6">
        <?php foreach($habitTypes as $habit): ?>
        <div class="bg-white rounded-[2.5rem] p-6 border border-slate-100 shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <h4 class="font-black text-slate-800 italic uppercase text-sm tracking-tight"><?= $habit['name'] ?></h4>
                <div class="flex gap-1">
                    <button onclick="editHabit(<?= $habit['id'] ?>, '<?= htmlspecialchars(addslashes($habit['name']), ENT_QUOTES) ?>')" class="p-2 text-slate-400"><i data-lucide="pencil" size="16"></i></button>
                    <button onclick="deleteHabit(<?= $habit['id'] ?>, this)" class="p-2 text-slate-400"><i data-lucide="trash-2" size="16"></i></button>
                </div>
            </div>
            <div class="grid grid-cols-7 gap-3">
                <?php for($i=1; $i<=31; $i++): 
                    $isChecked = isset($historyMap[$i][$habit['name']]) && $historyMap[$i][$habit['name']] == 1;
                ?>
                <div class="flex flex-col items-center gap-1">
                    <span class="text-[8px] font-bold <?= $i == $todayDay ? 'text-emerald-600' : 'text-slate-400' ?>"><?= $i ?></span>
                    <div onclick="toggleHabit(<?= htmlspecialchars(json_encode($habit['name']), ENT_QUOTES) ?>, <?= $i ?>, this)"
                         class="habit-box w-9 h-9 rounded-xl flex items-center justify-center border-2 transition-all
                         <?= $isChecked ? 'bg-emerald-500 border-emerald-500 text-white shadow-md' : 'bg-white border-slate-100' ?>">
                        <?php if($isChecked) echo '<i data-lucide="check" size="14" stroke-width="4"></i>'; ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- NOTIFIKASI SAVE STATUS -->
<div id="save-status" class="hidden fixed bottom-10 right-10 z-[100] px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] border shadow-2xl animate-bounce">
    <span id="save-status-text">Menyimpan...</span>
</div>

<style>
    .filter-btn { color: #94a3b8; font-family: 'Noto Serif', serif; }
    .filter-btn.active-btn { background: white; color: #10b981; box-shadow: 0 4px 12px -2px rgba(0,0,0,0.08); }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .custom-scrollbar::-webkit-scrollbar { height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>

<script>
// --- GLOBAL STATE ---
let habitChart;
const allLabels = Array.from({length: 31}, (_, i) => `H-${i+1}`);
let dailyData = <?= json_encode(array_values($dailyCounts)) ?>;
const todayDay = <?= (int)$todayDay ?>;
const totalHabits = <?= count($habitTypes) > 0 ? count($habitTypes) : 10 ?>;

// --- 1. INITIALIZE CHART ---
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('habitFullChart').getContext('2d');
    habitChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [], 
            datasets: [{
                label: 'Amalan Tuntas',
                data: [],
                backgroundColor: (c) => {
                    const label = c.chart.data.labels[c.dataIndex];
                    if(!label) return '#10b981';
                    const dayNum = parseInt(label.replace('H-', ''));
                    return dayNum === todayDay ? '#4f46e5' : '#10b981';
                },
                borderRadius: 8,
                barThickness: 12
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { 
                    beginAtZero: true, 
                    max: totalHabits, 
                    grid: { color: '#f1f5f9' }, 
                    ticks: { stepSize: 1, font: { weight: 'bold' } } 
                },
                x: { grid: { display: false }, ticks: { font: { size: 9 } } }
            }
        }
    });

    // Auto-filter: Jika layar kecil, tampilkan minggu aktif saja
    updateChartRange(window.innerWidth < 1024 ? Math.ceil(todayDay / 7) : 'all');
    lucide.createIcons();
});

// --- 2. LOGIKA FILTER GRAFIK ---
function updateChartRange(range) {
    let newData, newLabels;
    if (range === 'all') {
        newData = dailyData.slice(0, 31);
        newLabels = allLabels.slice(0, 31);
        habitChart.data.datasets[0].barThickness = window.innerWidth < 768 ? 6 : 12;
    } else {
        const start = (range - 1) * 7;
        newData = dailyData.slice(start, start + 7);
        newLabels = allLabels.slice(start, start + 7);
        habitChart.data.datasets[0].barThickness = 30; 
    }
    habitChart.data.labels = newLabels;
    habitChart.data.datasets[0].data = newData;
    habitChart.update();

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active-btn');
        if (btn.getAttribute('onclick').includes(`'${range}'`) || btn.getAttribute('onclick').includes(`${range}`)) btn.classList.add('active-btn');
    });
}

// --- 3. TOGGLE HABIT (SINKRON KE GRAFIK) ---
async function toggleHabit(habitName, day, el) {
    const box = el.classList.contains('habit-box') ? el : el.querySelector('.habit-box');
    const status = document.getElementById('save-status');
    const isNowChecked = !box.classList.contains('bg-emerald-500');
    
    // Optimistic UI Update
    if(isNowChecked) {
        box.className = "habit-box w-9 h-9 mx-auto rounded-xl flex items-center justify-center transition-all border-2 bg-emerald-500 border-emerald-500 text-white shadow-lg scale-110 shadow-emerald-100";
        box.innerHTML = '<i data-lucide="check" size="16" stroke-width="4"></i>';
        dailyData[day-1]++;
    } else {
        box.className = "habit-box w-9 h-9 mx-auto rounded-xl flex items-center justify-center transition-all border-2 bg-white border-slate-100 hover:border-emerald-200";
        box.innerHTML = '';
        dailyData[day-1] = Math.max(0, dailyData[day-1] - 1);
    }
    
    // Sync Chart & Icons
    const currentFilter = document.querySelector('.active-btn').getAttribute('onclick').match(/'?(\w+)'?/)[1];
    updateChartRange(currentFilter);
    lucide.createIcons();

    status.className = "fixed bottom-10 right-10 z-[100] px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] border bg-emerald-50 text-emerald-700 border-emerald-200 block animate-bounce";
    
    const formData = new FormData();
    formData.append('habit_name', habitName);
    formData.append('day', day);
    formData.append('is_completed', isNowChecked ? 1 : 0);
    
    await fetch('api/save_habit.php', { method: 'POST', body: formData });
    setTimeout(() => status.classList.add('hidden'), 1500);
}

// --- 4. CRUD OPERATIONS (TAMBAH/EDIT/HAPUS) ---

// Tambah Habit
document.getElementById('addHabitForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = document.getElementById('newHabitName');
    const name = input.value.trim();
    if(!name) return;

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('name', name);

    try {
        const res = await fetch('api/manage_habits.php', { method: 'POST', body: formData });
        const result = await res.json();
        if(result.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Amalan ditambahkan.', confirmButtonColor: '#10b981', timer: 1500 })
            .then(() => location.reload());
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Koneksi bermasalah.' });
    }
});

// Edit Habit
async function editHabit(id, oldName) {
    const { value: newName } = await Swal.fire({
        title: 'Edit Amalan',
        input: 'text',
        inputValue: oldName,
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Simpan',
        inputValidator: (v) => !v && 'Nama tidak boleh kosong!'
    });

    if (newName && newName !== oldName) {
        const formData = new FormData();
        formData.append('action', 'edit');
        formData.append('id', id);
        formData.append('name', newName);

        try {
            const res = await fetch('api/manage_habits.php', { method: 'POST', body: formData });
            const result = await res.json();
            if(result.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Diperbarui!', timer: 1000, showConfirmButton: false }).then(() => location.reload());
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Gagal edit.' });
        }
    }
}

// Hapus Habit
async function deleteHabit(id, btn) {
    const r = await Swal.fire({
        title: 'Hapus Amalan?',
        text: "Riwayat centang amalan ini akan terhapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    });

    if (r.isConfirmed) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        const res = await fetch('api/manage_habits.php', { method: 'POST', body: formData });
        const data = await res.json();
        if(data.status === 'success') {
            const el = btn.closest('tr') || btn.closest('.bg-white.rounded-[2.5rem]');
            el.remove();
            Swal.fire({ icon: 'success', title: 'Terhapus', timer: 1000, showConfirmButton: false });
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>