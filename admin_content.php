<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Logic untuk Tambah/Hapus Habit atau Tips
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_habit'])) {
        $name = $_POST['habit_name'];
        $icon = $_POST['habit_icon'];
        $pdo->prepare("INSERT INTO habit_types (name, icon) VALUES (?, ?)")->execute([$name, $icon]);
        $msg = "Amalan Baru Berhasil Ditambahkan";
    }
    
    if (isset($_POST['add_tip'])) {
        $title = $_POST['tip_title'];
        $content = $_POST['tip_content'];
        $type = $_POST['tip_type'];
        $pdo->prepare("INSERT INTO tips_khatam (title, content, strategy_type) VALUES (?, ?, ?)")->execute([$title, $content, $type]);
        $msg = "Strategi Baru Berhasil Ditambahkan";
    }
}

$habitTypes = $pdo->query("SELECT * FROM habit_types ORDER BY id ASC")->fetchAll();
$tips = $pdo->query("SELECT * FROM tips_khatam ORDER BY strategy_type ASC")->fetchAll();
?>

<div class="space-y-12 animate-in slide-in-from-bottom-4 duration-700">
    <header class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100">
        <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase italic">Kelola <span class="text-emerald-600">Konten</span></h2>
        <p class="text-slate-500 font-medium italic mt-2">Atur daftar amalan harian dan strategi khatam Al-Qur'an.</p>
    </header>

    <?php if(isset($msg)): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-3xl font-bold italic text-sm">
        <?= $msg ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        
        <!-- Manage Habits Section -->
        <section class="space-y-6">
            <div class="bg-white p-8 rounded-[3.5rem] shadow-xl border border-slate-100 h-full">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="font-black text-xl text-slate-800 uppercase italic">Daftar Amalan</h3>
                    <button onclick="document.getElementById('modal-habit').classList.toggle('hidden')" class="p-2 bg-emerald-600 text-white rounded-xl shadow-lg hover:scale-110 transition-all">
                        <i data-lucide="plus"></i>
                    </button>
                </div>
                
                <div class="space-y-3 h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                    <?php foreach($habitTypes as $h): ?>
                    <div class="p-5 bg-slate-50 rounded-3xl border border-slate-100 flex justify-between items-center group transition-all hover:bg-white hover:border-emerald-200">
                        <div class="flex items-center gap-4">
                            <div class="p-2 bg-white rounded-xl text-emerald-600 shadow-sm">
                                <i data-lucide="<?= $h['icon'] ?>" size={18}></i>
                            </div>
                            <span class="font-bold text-sm text-slate-700 italic"><?= $h['name'] ?></span>
                        </div>
                        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="text-slate-400 hover:text-red-500"><i data-lucide="trash-2" size={14}></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Manage Tips Section -->
        <section class="space-y-6">
            <div class="bg-indigo-950 p-8 rounded-[3.5rem] shadow-2xl text-white h-full relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="font-black text-xl uppercase italic">Strategi Khatam</h3>
                        <button onclick="document.getElementById('modal-tip').classList.toggle('hidden')" class="p-2 bg-white/20 backdrop-blur-md text-white rounded-xl hover:bg-white hover:text-indigo-900 transition-all">
                            <i data-lucide="plus"></i>
                        </button>
                    </div>

                    <div class="space-y-4 h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php foreach($tips as $t): ?>
                        <div class="p-6 bg-white/10 rounded-[2rem] border border-white/10 hover:border-white/30 transition-all">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest"><?= $t['strategy_type'] ?> Khatam</span>
                                <button class="text-white/30 hover:text-red-400"><i data-lucide="trash-2" size={14}></i></button>
                            </div>
                            <h5 class="font-black text-lg italic tracking-tight"><?= $t['title'] ?></h5>
                            <p class="text-xs font-medium text-indigo-200/80 italic mt-2">"<?= $t['content'] ?>"</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <i data-lucide="settings" class="absolute -bottom-10 -right-10 w-64 h-64 text-white opacity-5 rotate-12"></i>
            </div>
        </section>

    </div>
</div>

<!-- Modal Habit (Simplified) -->
<div id="modal-habit" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-6">
    <div class="bg-white p-10 rounded-[3rem] w-full max-w-md shadow-2xl animate-in zoom-in duration-300">
        <h3 class="text-2xl font-black text-slate-800 italic uppercase mb-6">Tambah Amalan</h3>
        <form method="POST" class="space-y-4">
            <input type="text" name="habit_name" placeholder="Nama Amalan (Contoh: Tahajud)" class="w-full p-4 rounded-2xl border bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 font-bold" required>
            <input type="text" name="habit_icon" placeholder="Icon Lucide (Contoh: moon)" class="w-full p-4 rounded-2xl border bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 font-bold" required>
            <div class="flex gap-3 mt-8">
                <button type="submit" name="add_habit" class="flex-1 py-4 bg-emerald-600 text-white rounded-2xl font-black shadow-lg shadow-emerald-200">SIMPAN</button>
                <button type="button" onclick="document.getElementById('modal-habit').classList.add('hidden')" class="px-6 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black">BATAL</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tip (Simplified) -->
<div id="modal-tip" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-6">
    <div class="bg-white p-10 rounded-[3rem] w-full max-w-md shadow-2xl animate-in zoom-in duration-300">
        <h3 class="text-2xl font-black text-slate-800 italic uppercase mb-6">Tambah Strategi</h3>
        <form method="POST" class="space-y-4">
            <input type="text" name="tip_title" placeholder="Judul Strategi" class="w-full p-4 rounded-2xl border bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 font-bold" required>
            <textarea name="tip_content" placeholder="Isi Strategi..." class="w-full p-4 rounded-2xl border bg-slate-50 h-32 outline-none focus:bg-white focus:border-emerald-500 font-bold" required></textarea>
            <select name="tip_type" class="w-full p-4 rounded-2xl border bg-slate-50 outline-none font-bold">
                <option value="1x">1x Khatam</option>
                <option value="2x">2x Khatam</option>
                <option value="3x">3x Khatam</option>
            </select>
            <div class="flex gap-3 mt-8">
                <button type="submit" name="add_tip" class="flex-1 py-4 bg-emerald-600 text-white rounded-2xl font-black shadow-lg shadow-emerald-200">SIMPAN</button>
                <button type="button" onclick="document.getElementById('modal-tip').classList.add('hidden')" class="px-6 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black">BATAL</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>