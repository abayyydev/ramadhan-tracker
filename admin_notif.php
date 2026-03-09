<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Proteksi Halaman Admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$msg = null;
$error = null;

// 1. HANDLER TAMBAH NOTIFIKASI
if (isset($_POST['kirim_notif'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);

    if (empty($title) || empty($message)) {
        $error = "Judul dan isi pesan wajib diisi!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO notifications (title, message) VALUES (?, ?)");
            $stmt->execute([$title, $message]);
            $msg = "Notifikasi berhasil disiarkan ke semua pengguna!";
        } catch (Exception $e) {
            $error = "Gagal mengirim notifikasi: " . $e->getMessage();
        }
    }
}

// 2. HANDLER HAPUS NOTIFIKASI
if (isset($_GET['delete'])) {
    $idToDelete = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$idToDelete]);
        header("Location: admin_notif.php?msg=" . urlencode("Notifikasi berhasil ditarik/dihapus!"));
        exit();
    } catch (Exception $e) {
        header("Location: admin_notif.php?err=" . urlencode("Gagal menghapus notifikasi."));
        exit();
    }
}

// Ambil riwayat notifikasi
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC");
$notifs = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto space-y-6 animate-in fade-in duration-700 font-sans">
    
    <header class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Pusat Siaran Notifikasi</h2>
            <p class="text-sm text-slate-500 mt-1">Kirim pengumuman atau info penting ke seluruh pengguna RamadhanPro.</p>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Form Kirim Notif -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 h-fit sticky top-24">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                    <i data-lucide="send" size="18"></i>
                </div>
                <h3 class="font-bold text-slate-800 text-lg">Buat Pesan Baru</h3>
            </div>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Judul Notifikasi</label>
                    <input type="text" name="title" placeholder="Cth: Info Kajian Online" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Isi Pesan</label>
                    <textarea name="message" rows="4" placeholder="Ketik pesan pengumuman di sini..." class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all resize-none" required></textarea>
                </div>
                <button type="submit" name="kirim_notif" class="w-full py-3 bg-emerald-600 text-white rounded-xl text-sm font-bold shadow-sm hover:bg-emerald-700 transition-all hover:-translate-y-0.5">
                    Kirim Sekarang
                </button>
            </form>
        </div>

        <!-- Riwayat Notif -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-800 text-lg mb-6">Riwayat Siaran</h3>
            
            <div class="space-y-4">
                <?php if(empty($notifs)): ?>
                    <div class="text-center py-10">
                        <i data-lucide="bell-off" size="48" class="mx-auto text-slate-300 mb-3"></i>
                        <p class="text-slate-500 font-medium">Belum ada notifikasi yang dikirim.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($notifs as $n): ?>
                        <div class="p-5 rounded-2xl border border-slate-100 bg-slate-50 flex flex-col sm:flex-row justify-between gap-4 group hover:bg-white hover:border-emerald-100 transition-colors">
                            <div class="flex gap-4">
                                <div class="w-10 h-10 bg-white border border-slate-200 rounded-full flex items-center justify-center text-slate-400 shrink-0 group-hover:text-emerald-500 group-hover:border-emerald-200">
                                    <i data-lucide="bell" size="18"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800"><?= htmlspecialchars($n['title']) ?></h4>
                                    <p class="text-sm text-slate-600 mt-1"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-3">
                                        <i data-lucide="clock" size="10" class="inline mb-0.5"></i> 
                                        <?= date('d M Y - H:i', strtotime($n['created_at'])) ?> WIB
                                    </p>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <button onclick="confirmDelete('admin_notif.php?delete=<?= $n['id'] ?>')" class="p-2 bg-white border border-slate-200 rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm" title="Tarik Pesan">
                                    <i data-lucide="trash-2" size="16"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($msg): ?>
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: '<?= addslashes($msg) ?>', timer: 2000, showConfirmButton: false, customClass: { popup: 'rounded-2xl' } });
        <?php endif; ?>

        <?php if ($error): ?>
        Swal.fire({ icon: 'error', title: 'Gagal!', text: '<?= addslashes($error) ?>', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-6 font-medium' } });
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: '<?= htmlspecialchars($_GET['msg']) ?>', timer: 2000, showConfirmButton: false, customClass: { popup: 'rounded-2xl' } });
        window.history.replaceState(null, null, window.location.pathname);
        <?php endif; ?>
    });

    function confirmDelete(url) {
        Swal.fire({
            title: 'Tarik Notifikasi?',
            text: "Notifikasi ini akan dihapus dari layar semua pengguna.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#cbd5e1',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-5 font-medium', cancelButton: 'rounded-lg px-5 font-medium text-slate-700' }
        }).then((result) => {
            if (result.isConfirmed) window.location.href = url;
        });
    }
</script>

<?php include 'includes/footer.php'; ?>