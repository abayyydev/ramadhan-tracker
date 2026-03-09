<?php
// PASTIKAN session sudah dimulai. Jika error undefined session, hapus tanda // di bawah ini:
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";
date_default_timezone_set('Asia/Jakarta');

$id_user = $_SESSION['user_id'];
$tgl = date('Y-m-d');

// --- PROSES SIMPAN LAPORAN ---
$cek = $pdo->prepare("SELECT COUNT(id) FROM nafsiyah_logs WHERE user_id = ? AND log_date = ?");
$cek->execute([$id_user, $tgl]);
$done = $cek->fetchColumn() > 0;

if (!$done && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $total_poin_hari_ini = 0;
        $is_haid = isset($_POST['mode_haid']) ? 1 : 0;

        // Ambil data user
        $stmtUser = $pdo->prepare("SELECT total_poin, streak_count, terakhir_lapor FROM users WHERE id = ?");
        $stmtUser->execute([$id_user]);
        $userData = $stmtUser->fetch();

        // PERBAIKAN PATH FOLDER UPLOAD 
        $upload_dir = __DIR__ . '/uploads/nafsiyah/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Ambil daftar amalan
        $items = $pdo->query("SELECT * FROM nafsiyah_items ORDER BY urutan ASC")->fetchAll();

        foreach ($items as $item) {
            $id_item = $item['id'];
            $is_kena_udzur = ($item['is_udzur'] == 1);
            $butuh_bukti = ($item['butuh_bukti'] == 1);
            
            $file_name_db = null;

            if ($is_haid && $is_kena_udzur) {
                $catatan = "Udzur Syar'i";
                $skor = 5;
                $st = 'selesai';
            } else {
                if (isset($_POST['item'][$id_item])) {
                    $p = explode('|', $_POST['item'][$id_item]);
                    $catatan = $p[0];
                    $skor = (int) $p[1];
                    $st = (in_array($catatan, ['Tidak', 'Absen', 'Tidur', 'Makan', 'Tidak Mengerjakan'])) ? 'tidak_selesai' : 'selesai';
                    
                    // PROSES UPLOAD FILE BUKTI
                    if ($st == 'selesai' && $butuh_bukti && isset($_FILES['bukti']['name'][$id_item]) && $_FILES['bukti']['error'][$id_item] == 0) {
                        $tmp_name = $_FILES['bukti']['tmp_name'][$id_item];
                        $ext = strtolower(pathinfo($_FILES['bukti']['name'][$id_item], PATHINFO_EXTENSION));
                        
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                            $new_file_name = "bukti_" . $id_user . "_" . $id_item . "_" . time() . "." . $ext;
                            if (move_uploaded_file($tmp_name, $upload_dir . $new_file_name)) {
                                $file_name_db = $new_file_name; 
                            }
                        }
                    }
                } else {
                    continue;
                }
            }

            $total_poin_hari_ini += $skor;
            $pdo->prepare("INSERT INTO nafsiyah_logs (user_id, item_id, log_date, status, catatan, poin_didapat, file_bukti) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$id_user, $id_item, $tgl, $st, $catatan, $skor, $file_name_db]);
        }

        // Update Poin & Streak
       // Update Poin & Streak
        $tgl_kemarin = date('Y-m-d', strtotime("-1 day"));
        
        // Pastikan poin saat ini berupa angka, bukan NULL
        $poin_sekarang = (int)($userData['total_poin'] ?? 0);
        $total_poin_baru = $poin_sekarang + $total_poin_hari_ini;

        // Logika Streak yang lebih cerdas
        $streak_sekarang = (int)($userData['streak_count'] ?? 0);
        $terakhir_lapor = $userData['terakhir_lapor'];

        if ($terakhir_lapor == $tgl) {
            // Jika hari ini sudah lapor (kasus jarang karena sudah dicek di awal, tapi buat jaga-jaga)
            $new_streak = $streak_sekarang;
        } elseif ($terakhir_lapor == $tgl_kemarin) {
            // Lanjut streak dari kemarin
            $new_streak = $streak_sekarang + 1;
        } else {
            // Bolong lapor atau baru pertama kali lapor -> Mulai streak dari 1
            $new_streak = 1;
        }

        // Eksekusi Update ke tabel users
        $stmtUpdateUser = $pdo->prepare("UPDATE users SET total_poin = ?, terakhir_lapor = ?, streak_count = ? WHERE id = ?");
        $stmtUpdateUser->execute([$total_poin_baru, $tgl, $new_streak, $id_user]);

        $pdo->commit();
        
        header('Location: input_nafsiyah.php?status=done');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// MASUKKAN HEADER DI BAWAH PROSES LOGIKA
include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto space-y-6 font-sans pb-10">

    <?php if ($done): ?>
        <div class="bg-white rounded-[3rem] p-12 text-center shadow-xl border border-slate-100 mt-6 animate-in zoom-in duration-500">
            <div class="w-24 h-24 bg-emerald-50 rounded-3xl flex items-center justify-center mx-auto mb-8 text-emerald-500 shadow-inner">
                <i data-lucide="check-circle" size="48" stroke-width="2.5"></i>
            </div>
            <!-- Dihilangkan class 'italic' -->
            <h1 class="text-4xl font-black text-slate-800 mb-3 tracking-tighter uppercase">Laporan Terkirim! 🎉</h1>
            <p class="text-slate-500 mb-10 max-w-md mx-auto font-medium">Poin dan streak kamu sudah diperbarui. Semoga istiqomah selalu dalam ketaatan!</p>
            <a href="dashboard.php" class="inline-flex items-center gap-3 px-10 py-4 bg-emerald-600 text-white rounded-2xl font-black uppercase tracking-widest transition-all shadow-lg shadow-emerald-200 hover:bg-emerald-700 hover:-translate-y-1">
                <i data-lucide="arrow-left" size="18"></i> Kembali ke Dashboard
            </a>
        </div>
    <?php else: ?>

        <form action="" method="POST" id="formNafsiyah" enctype="multipart/form-data" class="animate-in fade-in duration-700">

            <!-- Banner Mode Udzur -->
            <div class="relative flex flex-col sm:flex-row justify-between items-center bg-rose-50 rounded-[2rem] p-6 sm:px-8 mb-8 border border-rose-100 shadow-sm overflow-hidden">
                <div class="absolute -right-4 -top-4 opacity-10">
                    <i data-lucide="moon" size="120" class="text-rose-500"></i>
                </div>
                <div class="flex items-center gap-5 relative z-10">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-rose-500 shadow-sm border border-rose-100 shrink-0">
                        <i data-lucide="moon" size="24" stroke-width="2.5"></i>
                    </div>
                    <div>
                        <!-- Dihilangkan class 'italic' -->
                        <h3 class="font-black text-rose-700 text-lg tracking-tight">Mode Udzur Syar'i (Haid)</h3>
                        <p class="text-xs font-bold text-rose-500/80 uppercase tracking-widest mt-0.5">Otomatisasi amalan ibadah mahdhah</p>
                    </div>
                </div>
                <label for="modeHaid" class="flex items-center cursor-pointer mt-5 sm:mt-0 relative z-10">
                    <input type="checkbox" id="modeHaid" name="mode_haid" class="sr-only peer">
                    <div class="w-16 h-9 bg-rose-200/50 rounded-full peer-checked:bg-rose-500 after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:rounded-full after:h-7 after:w-7 after:transition-all after:shadow-sm peer-checked:after:translate-x-[28px]"></div>
                </label>
            </div>

            <!-- Kartu Utama Mutaba'ah -->
            <div class="bg-white rounded-[3rem] p-8 sm:p-12 mb-6 shadow-xl border border-slate-100 relative overflow-hidden">
                
                <!-- Dihilangkan class 'italic' -->
                <h2 class="text-3xl font-black text-slate-800 mb-10 flex items-center gap-4 tracking-tighter uppercase">
                    <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-inner shrink-0">
                        <i data-lucide="clipboard-check" size="24"></i>
                    </span>
                    Laporan Yaumiyah
                </h2>

                <div class="space-y-6">
                    <?php
                    $q = $pdo->query("SELECT * FROM nafsiyah_items ORDER BY urutan ASC")->fetchAll();
                    foreach ($q as $i):
                        $activity = $i['activity_name'];
                        $is_kena_udzur = ($i['is_udzur'] == 1);
                        $butuh_bukti = ($i['butuh_bukti'] == 1);

                        // Icon Logic (Migrasi ke Lucide)
                        $iconName = 'star';
                        $colorClass = 'from-emerald-400 to-emerald-600 shadow-emerald-200 text-white';
                        
                        if (stripos($activity, 'Sholat') !== false) {
                            $iconName = 'activity'; 
                            $colorClass = 'from-indigo-400 to-indigo-600 shadow-indigo-200 text-white';
                        } elseif (stripos($activity, 'Quran') !== false || stripos($activity, 'Tadarus') !== false) {
                            $iconName = 'book-open';
                            $colorClass = 'from-teal-400 to-teal-600 shadow-teal-200 text-white';
                        }
                        
                        $opts = !empty($i['sub_komponen']) ? explode(',', $i['sub_komponen']) : ["Selesai:10", "Tidak:0"];
                        ?>

                        <div class="group relative bg-slate-50 rounded-[2rem] p-6 border border-slate-100 transition-all hover:bg-white hover:shadow-lg hover:border-emerald-100 dark:bg-dark-surface2/50 dark:border-slate-700 amalan-item <?php echo $is_kena_udzur ? 'udzur-target' : ''; ?>">
                            
                            <div class="flex items-start sm:items-center justify-between mb-5 flex-col sm:flex-row gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br <?php echo $colorClass; ?> flex items-center justify-center shadow-lg shrink-0">
                                        <i data-lucide="<?php echo $iconName; ?>" size="24"></i>
                                    </div>
                                    <div>
                                        <!-- Dihilangkan class 'italic' -->
                                        <h3 class="font-black text-slate-800 text-lg"><?php echo htmlspecialchars($activity); ?></h3>
                                        <?php if ($butuh_bukti): ?>
                                            <span class="inline-flex items-center gap-1 mt-1.5 text-[9px] bg-amber-100 text-amber-700 px-2.5 py-1 rounded-md uppercase font-black tracking-widest bukti-badge">
                                                <i data-lucide="camera" size="12"></i> Wajib Bukti
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="normal-options grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-4">
                                <?php foreach ($opts as $o):
                                    $x = explode(':', $o);
                                    $l = trim($x[0]);
                                    $s = $x[1] ?? 0;
                                    ?>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="item[<?php echo $i['id']; ?>]" value="<?php echo $l . '|' . $s; ?>" class="peer sr-only" required>
                                        <div class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-200 bg-white transition-all duration-200 ease-in-out peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:border-slate-300">
                                            
                                            <div class="w-5 h-5 shrink-0 rounded-full border-2 border-slate-300 flex items-center justify-center transition-all duration-200 ease-in-out peer-checked:border-emerald-500 peer-checked:bg-emerald-500 text-transparent peer-checked:text-white">
                                                <i data-lucide="check" stroke-width="4" class="w-3 h-3"></i>
                                            </div>
                                            
                                            <span class="font-bold text-sm text-slate-600 transition-colors peer-checked:text-emerald-700"><?php echo $l; ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- Area Upload Bukti -->
                            <?php if ($butuh_bukti): ?>
                                <div class="mt-5 pt-5 border-t border-slate-200 normal-options file-upload-area">
                                    <label class="flex items-center gap-2 text-[10px] font-black text-slate-400 mb-3 uppercase tracking-widest">
                                        <i data-lucide="upload" size="14"></i> Upload Bukti (Foto/PDF)
                                    </label>
                                    <input type="file" name="bukti[<?php echo $i['id']; ?>]" accept="image/*,.pdf" 
                                           class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:tracking-widest file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200 transition-all cursor-pointer border-2 border-dashed border-slate-200 rounded-2xl bg-white p-2">
                                </div>
                            <?php endif; ?>

                            <!-- Tampilan Saat Mode Udzur Aktif -->
                            <div class="udzur-view hidden mt-4">
                                <div class="flex items-center gap-4 p-5 bg-rose-50 border border-rose-100 rounded-2xl">
                                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-rose-500 shadow-sm shrink-0">
                                        <i data-lucide="moon" size="20"></i>
                                    </div>
                                    <div>
                                        <div class="font-black text-rose-700 text-sm uppercase tracking-widest">Sedang Udzur Syar'i</div>
                                        <div class="text-xs text-rose-500 font-bold mt-0.5">Otomatis tercatat (5 poin) tanpa perlu bukti.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tombol Submit -->
            <button type="submit" class="w-full py-5 bg-emerald-600 text-white rounded-[2rem] font-black text-sm sm:text-base uppercase tracking-widest shadow-2xl shadow-emerald-200 hover:bg-emerald-700 hover:-translate-y-1 transition-all active:scale-95 flex justify-center items-center gap-3">
                <i data-lucide="send" size="20"></i> Kirim Laporan Hari Ini
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
    const modeHaidCheckbox = document.getElementById('modeHaid');

    if (modeHaidCheckbox) {
        const udzurCards = document.querySelectorAll('.udzur-target');
        
        function toggleUdzur(isHaid) {
            udzurCards.forEach(card => {
                const normalOpts = card.querySelectorAll('.normal-options');
                const udzurView = card.querySelector('.udzur-view');
                const inputs = card.querySelectorAll('input[type="radio"]');
                const badge = card.querySelector('.bukti-badge');

                if (isHaid) {
                    normalOpts.forEach(el => el.classList.add('hidden'));
                    udzurView.classList.remove('hidden');
                    if(badge) badge.classList.add('hidden');
                    inputs.forEach(r => r.removeAttribute('required'));
                } else {
                    normalOpts.forEach(el => el.classList.remove('hidden'));
                    udzurView.classList.add('hidden');
                    if(badge) badge.classList.remove('hidden');
                    inputs.forEach(r => r.setAttribute('required', 'required'));
                }
            });
        }
        modeHaidCheckbox.addEventListener('change', (e) => toggleUdzur(e.target.checked));
    }
</script>

<?php include 'includes/footer.php'; ?>