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

<?php include 'includes/footer.php'; ?>