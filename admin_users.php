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

// 1. HANDLER TAMBAH USER (CREATE)
if (isset($_POST['create_user'])) {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $waRaw = trim($_POST['whatsapp_number']);
    $jurusanId = !empty($_POST['jurusan_id']) ? $_POST['jurusan_id'] : null;
    $role = $_POST['role'];

    // Normalisasi WA
    $wa = preg_replace('/[^0-9]/', '', $waRaw);
    if (substr($wa, 0, 1) === '0') $wa = '62' . substr($wa, 1);
    elseif (substr($wa, 0, 1) === '8') $wa = '62' . $wa;

    try {
        // Cek duplikasi
        $check = $pdo->prepare("SELECT id FROM users WHERE whatsapp_number = ? OR email = ?");
        $check->execute([$wa, $email]);
        
        if ($check->fetch()) {
            $error = "Nomor WhatsApp atau Email ini sudah terdaftar!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, whatsapp_number, username, role, jurusan_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fullName, $email, $wa, $wa, $role, $jurusanId]);
            $newUserId = $pdo->lastInsertId();
            
            // Inisialisasi quran progress
            $pdo->prepare("INSERT INTO quran_progress (user_id) VALUES (?)")->execute([$newUserId]);
            $msg = "Pengguna baru berhasil ditambahkan!";
        }
    } catch (Exception $e) {
        $error = "Gagal menambahkan data: " . $e->getMessage();
    }
}

// 2. HANDLER UPDATE USER (UPDATE)
if (isset($_POST['update_user'])) {
    $id = $_POST['user_id'];
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $jurusanId = !empty($_POST['jurusan_id']) ? $_POST['jurusan_id'] : null;
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, jurusan_id = ?, role = ? WHERE id = ?");
        $stmt->execute([$fullName, $email, $jurusanId, $role, $id]);
        $msg = "Data pengguna berhasil diperbarui!";
    } catch (Exception $e) {
        $error = "Gagal memperbarui data.";
    }
}

// 3. HANDLER HAPUS USER (DELETE)
if (isset($_GET['delete'])) {
    $idToDelete = $_GET['delete'];
    if ($idToDelete != $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$idToDelete]);
            header("Location: admin_users.php?msg=" . urlencode("Pengguna berhasil dihapus secara permanen!"));
            exit();
        } catch (Exception $e) {
            header("Location: admin_users.php?err=" . urlencode("Gagal menghapus pengguna."));
            exit();
        }
    } else {
        header("Location: admin_users.php?err=" . urlencode("Anda tidak dapat menghapus akun Anda sendiri!"));
        exit();
    }
}

// Ambil semua data user beserta relasi jurusannya
$stmt = $pdo->query("
    SELECT u.*, j.nama_jurusan,
    (SELECT current_juz FROM quran_progress WHERE user_id = u.id) as juz 
    FROM users u 
    LEFT JOIN jurusan j ON u.jurusan_id = j.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// Ambil daftar jurusan untuk dropdown di modal
$list_jurusan = $pdo->query("SELECT * FROM jurusan ORDER BY nama_jurusan ASC")->fetchAll();
?>

<div class="space-y-6 animate-in fade-in duration-700 font-sans">
    
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Kelola Pengguna</h2>
            <p class="text-sm text-slate-500 mt-1">Sistem Manajemen Pengguna RamadhanPro</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="openAddModal()" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm hover:bg-emerald-700 transition-all flex items-center gap-2 hover:-translate-y-0.5">
                <i data-lucide="user-plus" size="16"></i> Tambah Pengguna
            </button>
            <a href="admin_jurusan.php" class="bg-white border border-slate-200 text-slate-600 px-5 py-2.5 rounded-xl font-semibold shadow-sm hover:bg-slate-50 transition-all text-sm flex items-center gap-2">
                <i data-lucide="book-open" size="16"></i> Kelola Jurusan
            </a>
        </div>
    </header>

    <!-- Table Users -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="flex items-center gap-3 mb-4 px-2">
            <div class="bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-100 flex items-center gap-2">
                <i data-lucide="users" class="text-emerald-600" size="16"></i>
                <span class="font-bold text-emerald-800 text-sm"><?= count($users) ?> Akun Terdaftar</span>
            </div>
        </div>

        <div class="overflow-x-auto border rounded-xl border-slate-200">
            <table class="w-full text-left border-collapse text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-4 border-b font-semibold text-slate-500">Info Pengguna</th>
                        <th class="p-4 border-b font-semibold text-slate-500">Jurusan</th>
                        <th class="p-4 border-b font-semibold text-slate-500 text-center">Peran</th>
                        <th class="p-4 border-b font-semibold text-slate-500 text-center">Progres</th>
                        <th class="p-4 border-b text-center font-semibold text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach($users as $u): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-emerald-600 rounded-full flex items-center justify-center text-white font-bold shadow-sm shrink-0">
                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($u['full_name']) ?></p>
                                    <p class="text-[11px] text-slate-500 font-medium"><?= htmlspecialchars($u['email'] ?: 'Belum ada email') ?></p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">@<?= htmlspecialchars($u['username']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="font-medium text-slate-600 text-sm">
                                <?= htmlspecialchars($u['nama_jurusan'] ?: '-') ?>
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest border <?= $u['role'] === 'admin' ? 'bg-indigo-50 text-indigo-600 border-indigo-100' : 'bg-slate-50 text-slate-500 border-slate-200' ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <span class="inline-flex items-center justify-center bg-emerald-50 text-emerald-700 font-bold text-xs px-2.5 py-1 rounded-lg border border-emerald-100">
                                Juz <?= $u['juz'] ?? 1 ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <!-- Aksi langsung dimunculkan -->
                            <div class="flex justify-center gap-2">
                                <button onclick='openEditModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, "UTF-8") ?>)' class="p-2 bg-white border border-slate-200 rounded-lg text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm" title="Edit">
                                    <i data-lucide="edit-3" size="16"></i>
                                </button>
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <button onclick="confirmDelete('admin_users.php?delete=<?= $u['id'] ?>')" class="p-2 bg-white border border-slate-200 rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm" title="Hapus">
                                    <i data-lucide="trash-2" size="16"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH USER -->
<div id="modal-tambah" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-xl transform scale-95 transition-transform duration-300" id="modal-tambah-content">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-2xl">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Tambah Pengguna</h3>
            </div>
            <button type="button" onclick="closeModal('modal-tambah')" class="text-slate-400 hover:text-rose-500 transition-colors">
                <i data-lucide="x" size="20"></i>
            </button>
        </div>
        
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Lengkap</label>
                <input type="text" name="full_name" placeholder="John Doe" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nomor WhatsApp</label>
                <input type="text" name="whatsapp_number" placeholder="0812..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                <input type="email" name="email" placeholder="email@contoh.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jurusan</label>
                    <select name="jurusan_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all appearance-none bg-white">
                        <option value="">-- Pilih --</option>
                        <?php foreach($list_jurusan as $j): ?>
                            <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['nama_jurusan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Peran</label>
                    <select name="role" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all appearance-none bg-white">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex gap-3">
                <button type="submit" name="create_user" class="flex-1 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold shadow-sm hover:bg-emerald-700 transition-all">Simpan Baru</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT USER -->
<div id="modal-edit" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-xl transform scale-95 transition-transform duration-300" id="modal-edit-content">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-2xl">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Edit Pengguna</h3>
            </div>
            <button type="button" onclick="closeModal('modal-edit')" class="text-slate-400 hover:text-rose-500 transition-colors">
                <i data-lucide="x" size="20"></i>
            </button>
        </div>
        
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="user_id" id="edit-user-id">
            
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Lengkap</label>
                <input type="text" name="full_name" id="edit-full-name" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                <input type="email" name="email" id="edit-email" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jurusan</label>
                    <select name="jurusan_id" id="edit-jurusan" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all appearance-none bg-white">
                        <option value="">-- Pilih --</option>
                        <?php foreach($list_jurusan as $j): ?>
                            <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['nama_jurusan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Peran Sistem</label>
                    <select name="role" id="edit-role" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all appearance-none bg-white">
                        <option value="user">User Biasa</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex gap-3">
                <button type="submit" name="update_user" class="flex-1 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold shadow-sm hover:bg-emerald-700 transition-all">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Impor SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // --- FUNGSI MODAL ---
    function openAddModal() {
        const modal = document.getElementById('modal-tambah');
        const content = document.getElementById('modal-tambah-content');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
        }, 10);
    }

    function openEditModal(user) {
        document.getElementById('edit-user-id').value = user.id;
        document.getElementById('edit-full-name').value = user.full_name;
        document.getElementById('edit-email').value = user.email || '';
        document.getElementById('edit-jurusan').value = user.jurusan_id || '';
        document.getElementById('edit-role').value = user.role;
        
        const modal = document.getElementById('modal-edit');
        const content = document.getElementById('modal-edit-content');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
        }, 10);
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        const content = document.getElementById(modalId + '-content');
        modal.classList.add('opacity-0');
        content.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    window.onclick = function(event) {
        if (event.target.id === 'modal-tambah') closeModal('modal-tambah');
        if (event.target.id === 'modal-edit') closeModal('modal-edit');
    }

    // --- FUNGSI SWEETALERT ---
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($msg): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= addslashes($msg) ?>',
            timer: 2000,
            showConfirmButton: false,
            customClass: { popup: 'rounded-2xl' }
        });
        <?php endif; ?>

        <?php if ($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: '<?= addslashes($error) ?>',
            confirmButtonColor: '#10b981',
            customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-6 font-medium' }
        });
        <?php endif; ?>

        // Menangkap pesan sukses dari URL (setelah redirect hapus)
        <?php if (isset($_GET['msg'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= htmlspecialchars($_GET['msg']) ?>',
            timer: 2000,
            showConfirmButton: false,
            customClass: { popup: 'rounded-2xl' }
        });
        // Bersihkan URL agar pesan tidak muncul lagi saat di-refresh
        window.history.replaceState(null, null, window.location.pathname);
        <?php endif; ?>

        <?php if (isset($_GET['err'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Peringatan',
            text: '<?= htmlspecialchars($_GET['err']) ?>',
            confirmButtonColor: '#10b981',
            customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-6 font-medium' }
        });
        window.history.replaceState(null, null, window.location.pathname);
        <?php endif; ?>
    });

    // --- KONFIRMASI HAPUS (SWEETALERT) ---
    function confirmDelete(url) {
        Swal.fire({
            title: 'Hapus pengguna?',
            text: "Aksi ini akan menghapus akun dan semua data pelaporannya secara permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#cbd5e1',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            customClass: { 
                popup: 'rounded-2xl', 
                confirmButton: 'rounded-lg px-5 font-medium',
                cancelButton: 'rounded-lg px-5 font-medium text-slate-700'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>

=======
<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Proteksi Halaman Admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 1. HANDLER UPDATE USER
if (isset($_POST['update_user'])) {
    $id = $_POST['user_id'];
    $fullName = $_POST['full_name'];
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ? WHERE id = ?");
        $stmt->execute([$fullName, $role, $id]);
        $msg = "Data user berhasil diperbarui!";
    } catch (Exception $e) {
        $error = "Gagal memperbarui data.";
    }
}

// 2. HANDLER HAPUS USER
if (isset($_GET['delete'])) {
    $idToDelete = $_GET['delete'];
    if ($idToDelete != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$idToDelete]);
        header("Location: admin_users.php?msg=User Berhasil Dihapus");
        exit();
    }
}

// Ambil semua data user
$stmt = $pdo->query("SELECT u.*, 
    (SELECT current_juz FROM quran_progress WHERE user_id = u.id) as juz 
    FROM users u ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<div class="space-y-10 animate-in fade-in duration-700">
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase italic">Kelola <span class="text-emerald-600">Pengguna</span></h2>
            <p class="text-slate-500 font-medium italic mt-1">Sistem Manajemen Pengguna RamadhanPro.</p>
        </div>
        <div class="bg-indigo-50 px-6 py-3 rounded-2xl border border-indigo-100 flex items-center gap-3">
            <i data-lucide="users" class="text-indigo-600"></i>
            <span class="font-black text-indigo-900"><?= count($users) ?> Akun Terdaftar</span>
        </div>
    </header>

    <?php if(isset($msg) || isset($_GET['msg'])): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-3xl font-bold italic text-sm flex items-center gap-3 animate-in slide-in-from-top-2">
        <i data-lucide="check-circle" size={18}></i> <?= $msg ?? $_GET['msg'] ?>
    </div>
    <?php endif; ?>

    <!-- Table Users -->
    <div class="bg-white p-8 rounded-[3.5rem] shadow-xl border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar border rounded-[2.5rem]">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-6 border-b font-black text-slate-400 text-[10px] uppercase tracking-widest">Info Pengguna</th>
                        <th class="p-6 border-b font-black text-slate-400 text-[10px] uppercase tracking-widest">Peran</th>
                        <th class="p-6 border-b font-black text-slate-400 text-[10px] uppercase tracking-widest text-center">Progres Quran</th>
                        <th class="p-6 border-b font-black text-slate-400 text-[10px] uppercase tracking-widest">Tanggal Gabung</th>
                        <th class="p-6 border-b text-center font-black text-slate-400 text-[10px] uppercase tracking-widest">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($users as $u): ?>
                    <tr class="group hover:bg-slate-50/50 transition-colors">
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center text-white font-black shadow-lg">
                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-black text-slate-800 italic"><?= $u['full_name'] ?></p>
                                    <p class="text-xs text-slate-400 font-medium tracking-wide">@<?= $u['username'] ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter border <?= $u['role'] === 'admin' ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-slate-50 text-slate-500 border-slate-100' ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td class="p-6 text-center">
                            <span class="font-black text-sm text-blue-600 italic">Juz <?= $u['juz'] ?? 1 ?></span>
                        </td>
                        <td class="p-6 text-xs font-bold text-slate-400 italic">
                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                        </td>
                        <td class="p-6">
                            <div class="flex justify-center gap-2">
                                <button onclick='openEditModal(<?= json_encode($u) ?>)' class="p-3 bg-white border border-slate-100 rounded-xl text-blue-500 hover:shadow-xl hover:scale-110 transition-all">
                                    <i data-lucide="edit-3" size={16}></i>
                                </button>
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <a href="admin_users.php?delete=<?= $u['id'] ?>" onclick="return confirm('Hapus user ini selamanya?')" class="p-3 bg-white border border-slate-100 rounded-xl text-red-500 hover:shadow-xl hover:scale-110 transition-all">
                                    <i data-lucide="trash-2" size={16}></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL EDIT USER -->
<div id="modal-edit" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-md p-6">
    <div class="bg-white p-10 rounded-[3rem] w-full max-w-md shadow-2xl animate-in zoom-in duration-300 border border-slate-100">
        <div class="text-center mb-8">
            <h3 class="text-3xl font-black text-slate-800 italic uppercase tracking-tighter">Edit <span class="text-emerald-600">User</span></h3>
            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-2">Perbarui Profil Pengguna</p>
        </div>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="user_id" id="edit-user-id">
            
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Nama Lengkap</label>
                <input type="text" name="full_name" id="edit-full-name" class="w-full p-5 rounded-[1.5rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700" required>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Peran Sistem</label>
                <select name="role" id="edit-role" class="w-full p-5 rounded-[1.5rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700 appearance-none">
                    <option value="user">User Biasa</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" name="update_user" class="flex-1 py-5 bg-emerald-600 text-white rounded-[1.5rem] font-black shadow-2xl shadow-emerald-200 hover:bg-emerald-700 hover:-translate-y-1 transition-all active:scale-95 uppercase text-xs tracking-widest">
                    Simpan Perubahan
                </button>
                <button type="button" onclick="closeModal()" class="px-8 py-5 bg-slate-100 text-slate-500 rounded-[1.5rem] font-black hover:bg-slate-200 transition-all uppercase text-xs tracking-widest">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(user) {
    document.getElementById('edit-user-id').value = user.id;
    document.getElementById('edit-full-name').value = user.full_name;
    document.getElementById('edit-role').value = user.role;
    document.getElementById('modal-edit').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal-edit').classList.add('hidden');
}

// Tutup modal jika klik di luar area modal
window.onclick = function(event) {
    const modal = document.getElementById('modal-edit');
    if (event.target == modal) closeModal();
}
</script>

>>>>>>> ae0dab13a02e0ba818f0c15d094a24e01943f8bd
<?php include 'includes/footer.php'; ?>