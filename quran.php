<?php 
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php'; 

$userId = $_SESSION['user_id'];

// Ambil data progres terbaru dari database
$stmt = $pdo->prepare("SELECT * FROM quran_progress WHERE user_id = ?");
$stmt->execute([$userId]);
$quran = $stmt->fetch();

// Inisialisasi jika data belum ada (untuk user baru)
if (!$quran) {
    $pdo->prepare("INSERT INTO quran_progress (user_id, current_juz, current_surah, total_khatam) VALUES (?, 1, NULL, 0)")->execute([$userId]);
    $currentJuz = 1;
    $currentSurah = null;
    $totalKhatam = 0;
} else {
    $currentJuz = (int)$quran['current_juz'];
    $currentSurah = $quran['current_surah'] ? (int)$quran['current_surah'] : null;
    $totalKhatam = (int)$quran['total_khatam'];
}

$progressPercent = round(($currentJuz / 30) * 100);
$tips = $pdo->query("SELECT * FROM tips_khatam ORDER BY strategy_type ASC")->fetchAll();
?>

<div class="max-w-5xl mx-auto space-y-10 animate-in slide-in-from-bottom-8 duration-700">
    <header class="bg-white p-10 rounded-[3.5rem] shadow-xl border border-slate-100 flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase leading-tight italic">
                Laporan Khatam <br/><span class="text-emerald-600 underline decoration-emerald-100 underline-offset-8">Al-Qur'an</span>
            </h2>
            <p class="text-slate-500 font-medium italic mt-4 text-sm tracking-widest uppercase">Target: Fokus Juz per Hari</p>
        </div>
        
        <div id="quran-status" class="hidden absolute top-4 right-10 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border transition-all shadow-sm z-20">
            <span id="quran-status-text">Menyimpan...</span>
        </div>

        <div class="flex items-center gap-8 bg-slate-50 px-10 py-8 rounded-[2.5rem] border border-slate-100 shadow-inner">
            <div class="text-right">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Capaian</p>
                <p id="total-khatam-display" class="text-5xl font-black text-indigo-900 leading-none italic"><?= $totalKhatam ?>x</p>
            </div>
            <div class="w-[1px] h-12 bg-slate-200"></div>
            <div class="w-16 h-16 bg-indigo-900 rounded-[1.5rem] flex items-center justify-center text-white shadow-2xl shadow-indigo-100">
                <i data-lucide="award" size={32} stroke-width={2.5}></i>
            </div>
        </div>
    </header>

    <div id="reset-banner" class="<?= $currentJuz == 30 ? '' : 'hidden' ?> bg-gradient-to-br from-indigo-900 to-indigo-800 p-10 rounded-[3rem] flex flex-col md:flex-row items-center justify-between gap-8 shadow-2xl border-4 border-white">
        <div class="text-white text-center md:text-left">
            <h3 class="text-4xl font-black uppercase tracking-tighter italic">Alhamdulillah! 🎉</h3>
            <p class="font-bold mt-4 opacity-80 text-lg italic">Anda telah menyelesaikan Juz 30. Mulai putaran khatam baru?</p>
        </div>
        <button onclick="resetKhatam()" class="bg-white text-indigo-900 px-12 py-5 rounded-[1.5rem] font-black text-xl flex items-center gap-3 shadow-2xl hover:scale-105 transition-all active:scale-95">
            <i data-lucide="rotate-ccw"></i> RESET SEKARANG
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <div class="lg:col-span-2 bg-white p-10 rounded-[4rem] shadow-xl border border-slate-100">
            <div class="flex justify-between items-center mb-12 px-2">
                <h3 class="font-black text-xl text-slate-800 uppercase tracking-tighter italic">Pilih Juz Terakhir</h3>
                <div class="flex items-center gap-3">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Progress:</span>
                    <span id="progress-percent-text" class="text-2xl font-black text-emerald-600 italic"><?= $progressPercent ?>%</span>
                </div>
            </div>
            
            <div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-10 gap-3">
                <?php for($i=1; $i<=30; $i++): 
                    $isActive = $i <= $currentJuz;
                ?>
                <button 
                    id="juz-btn-<?= $i ?>" 
                    onclick="updateJuz(<?= $i ?>)"
                    class="juz-btn h-14 rounded-2xl text-sm font-black transition-all border-2 
                    <?= $isActive ? 'bg-emerald-600 text-white border-emerald-600 shadow-xl scale-110 z-10' : 'bg-white border-slate-100 text-slate-400 hover:border-emerald-200' ?>">
                    <?= $i ?>
                </button>
                <?php endfor; ?>
            </div>
            
            <div class="mt-12 p-6 bg-indigo-50 rounded-[2rem] border border-indigo-100 flex items-center gap-5 text-indigo-700">
                <i data-lucide="info" size={28} class="shrink-0"></i>
                <p class="text-xs font-bold italic leading-relaxed">Klik angka Juz yang telah selesai Anda baca. Pilih juga surat terakhir sebagai penanda bacaan Anda hari ini.</p>
            </div>
        </div>

        <div class="space-y-8 flex flex-col">
            <div class="bg-indigo-950 p-10 rounded-[3rem] text-white relative overflow-hidden shadow-2xl flex-1 flex flex-col justify-center">
                <div class="relative z-10">
                    <p class="text-[10px] font-black opacity-40 uppercase tracking-[0.4em] mb-3">Posisi Tilawah</p>
                    <h4 class="text-6xl font-black mb-8 tracking-tighter italic">JUZ <span id="juz-display-text"><?= $currentJuz ?></span></h4>
                    
                    <div class="space-y-4">
                        <div class="bg-white/10 p-6 rounded-[2rem] backdrop-blur-xl border border-white/10 shadow-inner">
                            <p class="text-[10px] font-black opacity-40 uppercase tracking-widest mb-1 text-indigo-100">Titik Awal Juz</p>
                            <p id="surah-name" class="font-black text-2xl tracking-tight italic">Memuat...</p>
                        </div>
                        
                        <div class="bg-indigo-900/50 p-5 rounded-[2rem] border border-indigo-800 shadow-inner">
                            <label class="block text-[10px] font-black opacity-70 uppercase tracking-widest mb-3 text-indigo-100">Penanda Terakhir Dibaca</label>
                            <div class="flex flex-col gap-3">
                                <select id="surah-selector" onchange="updateSurah(this.value)" class="w-full bg-indigo-800 text-white rounded-xl px-4 py-3 outline-none border border-indigo-700 text-xs font-bold appearance-none cursor-pointer hover:bg-indigo-700 transition-colors">
                                    <option value="">Memuat surat...</option>
                                </select>
                                
                                <a id="btn-baca" href="#" target="_blank" class="w-full py-3 bg-emerald-500 hover:bg-emerald-400 text-white rounded-xl font-black text-xs uppercase tracking-widest text-center transition-all shadow-lg flex items-center justify-center gap-2">
                                    <i data-lucide="book-open" size={16}></i> Baca Surat Ini
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
                <i data-lucide="book-open" class="absolute -bottom-16 -right-16 w-80 h-80 text-white opacity-5 rotate-12 pointer-events-none"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-12 rounded-[4rem] shadow-xl border border-slate-100">
        <h3 class="font-black text-2xl text-slate-800 mb-12 uppercase flex items-center gap-4 italic tracking-tighter">
            <div class="w-10 h-2 bg-amber-400 rounded-full shadow-sm shadow-amber-100"></div> 
            Strategi Khatam
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($tips as $tip): ?>
            <div class="p-8 rounded-[2.5rem] border bg-slate-50 hover:bg-white hover:border-emerald-200 transition-all group">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest"><?= $tip['strategy_type'] ?> Khatam</span>
                    <i data-lucide="trending-up" size={14} class="text-slate-300 group-hover:text-emerald-500 transition-colors"></i>
                </div>
                <h5 class="font-black text-xl text-slate-800 mb-3 italic tracking-tight"><?= $tip['title'] ?></h5>
                <p class="text-sm text-slate-500 font-medium italic leading-relaxed opacity-80">"<?= $tip['content'] ?>"</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
let currentTotalKhatam = <?= $totalKhatam ?>;
let savedSurah = <?= $currentSurah ? $currentSurah : 'null' ?>;

// Fungsi untuk Tampilkan Notif Menyimpan
function showSaveStatus(statusClass, text) {
    const status = document.getElementById('quran-status');
    const statusText = document.getElementById('quran-status-text');
    status.className = `absolute top-4 right-10 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border z-20 block ${statusClass}`;
    statusText.innerText = text;
    setTimeout(() => status.classList.add('hidden'), 2000);
}

// Ambil Data Surat di Dalam Juz
async function fetchSurah(juz) {
    if(juz <= 0) juz = 1;
    try {
        const res = await fetch(`https://api.alquran.cloud/v1/juz/${juz}/quran-uthmani`);
        const data = await res.json();
        
        // Filter unik surat yang ada di juz tersebut
        const surahsInJuz = [];
        const surahMap = new Set();
        
        data.data.ayahs.forEach(ayah => {
            if(!surahMap.has(ayah.surah.number)) {
                surahMap.add(ayah.surah.number);
                surahsInJuz.push(ayah.surah);
            }
        });

        // Tampilkan info titik awal Juz
        const firstSurah = surahsInJuz[0];
        document.getElementById('surah-name').innerText = firstSurah.englishName;

        // Isi Dropdown Surat
        const selector = document.getElementById('surah-selector');
        selector.innerHTML = '';
        
        // Jika surat yang disimpan sebelumnya tidak ada di juz ini, jadikan surat pertama sbg default
        if(savedSurah === null || !surahMap.has(savedSurah)) {
            savedSurah = firstSurah.number; 
        }

        surahsInJuz.forEach(surah => {
            const isSelected = (surah.number === savedSurah) ? 'selected' : '';
            selector.innerHTML += `<option value="${surah.number}" ${isSelected}>Surah ${surah.englishName}</option>`;
        });

        // Update Link Tombol Baca
        document.getElementById('btn-baca').href = `https://quran.com/${savedSurah}`;

    } catch(e) { 
        document.getElementById('surah-name').innerText = "Gagal Memuat";
        document.getElementById('surah-selector').innerHTML = '<option>Network Error</option>';
    }
}

// Update Saat User Memilih Surat Lain di Dropdown
async function updateSurah(surahNum) {
    savedSurah = parseInt(surahNum);
    document.getElementById('btn-baca').href = `https://quran.com/${savedSurah}`;

    // Simpan penanda ke DB
    const formData = new FormData();
    formData.append('current_surah', surahNum);
    
    try {
        showSaveStatus("bg-amber-50 text-amber-600 border-amber-200 animate-pulse", "Menyimpan...");
        const response = await fetch('api/update_quran.php', { method: 'POST', body: formData });
        const result = await response.json();
        if(result.status === 'success') {
            showSaveStatus("bg-emerald-50 text-emerald-700 border-emerald-200", "Ditandai");
        }
    } catch(e) {
        showSaveStatus("bg-red-50 text-red-600 border-red-200", "Gagal!");
    }
}

// Update Saat User Klik Juz
async function updateJuz(val) {
    showSaveStatus("bg-amber-50 text-amber-600 border-amber-200 animate-pulse", "Menyimpan...");

    // UI Update Seketika
    document.querySelectorAll('.juz-btn').forEach((btn, i) => {
        const num = i + 1;
        if(num <= val) {
            btn.className = "juz-btn h-14 rounded-2xl text-sm font-black transition-all border-2 shadow-xl bg-emerald-600 text-white border-emerald-600 scale-110 z-10";
        } else {
            btn.className = "juz-btn h-14 rounded-2xl text-sm font-black transition-all border-2 bg-white border-slate-100 text-slate-400 hover:border-emerald-200";
        }
    });

    document.getElementById('juz-display-text').innerText = val;
    document.getElementById('progress-percent-text').innerText = Math.round((val / 30) * 100) + '%';
    
    const resetBanner = document.getElementById('reset-banner');
    if(val === 30) {
        resetBanner.classList.remove('hidden');
        resetBanner.classList.add('animate-in', 'zoom-in');
    } else {
        resetBanner.classList.add('hidden');
    }

    // Reset savedSurah karena juz berganti, biarkan fetchSurah() yg menentukannya ke surat pertama
    savedSurah = null; 
    await fetchSurah(val);

    // Simpan Juz dan Surat Pertama ke Database
    const formData = new FormData();
    formData.append('current_juz', val);
    formData.append('current_surah', savedSurah);
    
    try {
        const response = await fetch('api/update_quran.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if(result.status === 'success') {
            showSaveStatus("bg-emerald-50 text-emerald-700 border-emerald-200", "Tersimpan");
        }
    } catch (e) {
        showSaveStatus("bg-red-50 text-red-600 border-red-200", "Gagal!");
    }
}

async function resetKhatam() {
    showSaveStatus("bg-indigo-50 text-indigo-600 border-indigo-200 animate-bounce", "Mereset...");

    const formData = new FormData();
    formData.append('reset', 'true');
    
    try {
        const response = await fetch('api/update_quran.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if(result.status === 'success') {
            currentTotalKhatam++;
            document.getElementById('total-khatam-display').innerText = currentTotalKhatam + 'x';
            document.getElementById('reset-banner').classList.add('hidden');
            updateJuz(1); 
            
            showSaveStatus("bg-emerald-50 text-emerald-700 border-emerald-200", "Khatam Tercatat!");
        }
    } catch (e) {
        console.error("Gagal reset");
    }
}

// Saat halaman dimuat
document.addEventListener('DOMContentLoaded', () => { 
    fetchSurah(<?= $currentJuz ?>); 
});
</script>

<?php include 'includes/footer.php'; ?>