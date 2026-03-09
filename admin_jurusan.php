<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Proteksi Halaman Admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// HANDLER TAMBAH/EDIT JURUSAN
if (isset($_POST['save_jurusan'])) {
    $id = $_POST['jurusan_id'] ?? null;
    $nama = $_POST['nama_jurusan'];

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE jurusan SET nama_jurusan = ? WHERE id = ?");
            $stmt->execute([$nama, $id]);
            $msg = "Jurusan berhasil diperbarui!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO jurusan (nama_jurusan) VALUES (?)");
            $stmt->execute([$nama]);
            $msg = "Jurusan baru berhasil ditambahkan!";
        }
    } catch (Exception $e) {
        $error = "Gagal menyimpan data.";
    }
}

// HANDLER HAPUS JURUSAN
if (isset($_GET['delete'])) {
    $idToDelete = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM jurusan WHERE id = ?");
        $stmt->execute([$idToDelete]);
        header("Location: admin_jurusan.php?msg=Jurusan Berhasil Dihapus");
        exit();
    } catch (Exception $e) {
        // Jika gagal, mungkin karena datanya sedang dipakai di tabel users (foreign key constraint)
        header("Location: admin_jurusan.php?err=Gagal dihapus, jurusan sedang digunakan oleh user.");
        exit();
    }
}

$jurusan_list = $pdo->query("SELECT * FROM jurusan ORDER BY nama_jurusan ASC")->fetchAll();
?>

<div class="space-y-10 animate-in fade-in duration-700">
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase italic">Kelola <span class="text-emerald-600">Jurusan</span></h2>
            <p class="text-slate-500 font-medium italic mt-1">Sistem Manajemen Jurusan / Program Studi.</p>
        </div>
        <button onclick="openJurusanModal()" class="bg-emerald-600 text-white px-6 py-3 rounded-2xl font-black shadow-lg hover:bg-emerald-700 hover:-translate-y-1 transition-all uppercase text-xs tracking-widest flex items-center gap-2">
            <i data-lucide="plus" size={16}></i> Tambah Jurusan
        </button>
    </header>

    <?php if(isset($msg) || isset($_GET['msg'])): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-3xl font-bold italic text-sm flex items-center gap-3 animate-in slide-in-from-top-2">
        <i data-lucide="check-circle" size={18}></i> <?= $msg ?? $_GET['msg'] ?>
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['err'])): ?>
    <div class="bg-rose-50 border border-rose-200 text-rose-700 px-6 py-4 rounded-3xl font-bold italic text-sm flex items-center gap-3 animate-in slide-in-from-top-2">
        <i data-lucide="alert-circle" size={18}></i> <?= $_GET['err'] ?>
    </div>
    <?php endif; ?>

    <div class="bg-white p-8 rounded-[3.5rem] shadow-xl border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar border rounded-[2.5rem]">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-6 border-b font-black text-slate-400 text-[10px] uppercase tracking-widest">No</th>
                        <th class="p-6 border-b font-black text-slate-400 text-[10px] uppercase tracking-widest">Nama Jurusan</th>
                        <th class="p-6 border-b text-center font-black text-slate-400 text-[10px] uppercase tracking-widest">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php $no=1; foreach($jurusan_list as $j): ?>
                    <tr class="group hover:bg-slate-50/50 transition-colors">
                        <td class="p-6 font-bold text-slate-500"><?= $no++ ?></td>
                        <td class="p-6 font-black text-slate-800 italic"><?= $j['nama_jurusan'] ?></td>
                        <td class="p-6">
                            <div class="flex justify-center gap-2">
                                <button onclick='openJurusanModal(<?= json_encode($j) ?>)' class="p-3 bg-white border border-slate-100 rounded-xl text-blue-500 hover:shadow-xl hover:scale-110 transition-all">
                                    <i data-lucide="edit-3" size={16}></i>
                                </button>
                                <a href="admin_jurusan.php?delete=<?= $j['id'] ?>" onclick="return confirm('Hapus jurusan ini?')" class="p-3 bg-white border border-slate-100 rounded-xl text-red-500 hover:shadow-xl hover:scale-110 transition-all">
                                    <i data-lucide="trash-2" size={16}></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modal-jurusan" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-md p-6">
    <div class="bg-white p-10 rounded-[3rem] w-full max-w-md shadow-2xl animate-in zoom-in duration-300 border border-slate-100">
        <div class="text-center mb-8">
            <h3 class="text-3xl font-black text-slate-800 italic uppercase tracking-tighter" id="modal-title">Tambah <span class="text-emerald-600">Jurusan</span></h3>
        </div>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="jurusan_id" id="edit-jurusan-id">
            
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Nama Jurusan</label>
                <input type="text" name="nama_jurusan" id="edit-nama-jurusan" placeholder="Cth: Informatika" class="w-full p-5 rounded-[1.5rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700" required>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" name="save_jurusan" class="flex-1 py-5 bg-emerald-600 text-white rounded-[1.5rem] font-black shadow-2xl shadow-emerald-200 hover:bg-emerald-700 hover:-translate-y-1 transition-all active:scale-95 uppercase text-xs tracking-widest">
                    Simpan
                </button>
                <button type="button" onclick="closeJurusanModal()" class="px-8 py-5 bg-slate-100 text-slate-500 rounded-[1.5rem] font-black hover:bg-slate-200 transition-all uppercase text-xs tracking-widest">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openJurusanModal(data = null) {
    if (data) {
        document.getElementById('modal-title').innerHTML = 'Edit <span class="text-emerald-600">Jurusan</span>';
        document.getElementById('edit-jurusan-id').value = data.id;
        document.getElementById('edit-nama-jurusan').value = data.nama_jurusan;
    } else {
        document.getElementById('modal-title').innerHTML = 'Tambah <span class="text-emerald-600">Jurusan</span>';
        document.getElementById('edit-jurusan-id').value = '';
        document.getElementById('edit-nama-jurusan').value = '';
    }
    document.getElementById('modal-jurusan').classList.remove('hidden');
}

function closeJurusanModal() {
    document.getElementById('modal-jurusan').classList.add('hidden');
}

window.onclick = function(event) {
    const modal = document.getElementById('modal-jurusan');
    if (event.target == modal) closeJurusanModal();
}
</script>

<?php include 'includes/footer.php'; ?>