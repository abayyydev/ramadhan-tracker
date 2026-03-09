<?php
require_once 'includes/header.php';
require_once 'config/database.php';

// --- LOGIKA PAGINATION ---
$halaman_aktif = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit_per_halaman = 10;
$offset = ($halaman_aktif - 1) * $limit_per_halaman;

// Hitung total data
$total_data = $pdo->query("SELECT COUNT(id) FROM nafsiyah_items")->fetchColumn();
$total_halaman = ceil($total_data / $limit_per_halaman);

// Ambil data dengan kolom is_udzur & butuh_bukti
$stmt = $pdo->prepare("SELECT id, activity_name, sub_komponen, urutan, is_udzur, butuh_bukti FROM nafsiyah_items ORDER BY urutan ASC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit_per_halaman, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

$start_number = ($total_data > 0) ? $offset + 1 : 0;
$end_number = min($offset + $limit_per_halaman, $total_data);
?>

<div class="max-w-7xl mx-auto space-y-6 font-sans animate-in fade-in duration-700">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Kelola Amalan Nafsiyah</h1>
            <p class="text-sm text-slate-500 mt-1">Manajemen daftar ibadah harian dan aturan bukti</p>
        </div>
        <button type="button" id="openModalBtn"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-all shadow-sm hover:-translate-y-0.5">
            <i data-lucide="plus" size="16"></i> Tambah Amalan
        </button>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto border rounded-xl border-slate-200">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-center w-20">Urutan</th>
                        <th class="px-4 py-3">Nama Amalan</th>
                        <th class="px-4 py-3">Opsi & Poin</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i data-lucide="inbox" size="40" class="mb-3 text-slate-300"></i>
                                    <p class="font-medium text-base text-slate-500">Belum ada amalan terdaftar</p>
                                    <p class="text-sm">Silakan tambah amalan baru terlebih dahulu.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-600 font-bold text-sm border border-slate-200">
                                        <?= $item['urutan'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-100 shrink-0">
                                            <i data-lucide="list-checks" size="18"></i>
                                        </div>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-semibold text-slate-800 text-sm"><?= htmlspecialchars($item['activity_name']) ?></p>
                                                <?php if ($item['is_udzur']): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-rose-50 text-rose-600 text-[10px] font-medium rounded border border-rose-100" title="Terpengaruh Mode Udzur">
                                                        <i data-lucide="moon" size="10"></i> Udzur
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($item['butuh_bukti']): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 text-amber-600 text-[10px] font-medium rounded border border-amber-100" title="Wajib Upload Foto/File">
                                                        <i data-lucide="camera" size="10"></i> Bukti
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-xs text-slate-400 mt-1">ID: <?= $item['id'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <?php if ($item['sub_komponen']): ?>
                                            <?php
                                            $opts = explode(',', $item['sub_komponen']);
                                            foreach ($opts as $o):
                                                $parts = explode(':', $o);
                                                $label = $parts[0] ?? '';
                                                $val = $parts[1] ?? '0';
                                                ?>
                                                <span class="inline-flex items-center gap-1.5 bg-slate-50 px-2.5 py-1 rounded-lg text-xs border border-slate-200 font-medium text-slate-600">
                                                    <?= htmlspecialchars($label) ?>
                                                    <span class="font-semibold text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded border border-emerald-200"><?= $val ?> pt</span>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <!-- Aksi sekarang langsung dimunculkan (selalu terlihat) -->
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm edit-btn"
                                            data-id="<?= $item['id'] ?>"
                                            data-activity_name="<?= htmlspecialchars($item['activity_name']) ?>"
                                            data-sub_komponen="<?= htmlspecialchars($item['sub_komponen']) ?>"
                                            data-urutan="<?= $item['urutan'] ?>" 
                                            data-is_udzur="<?= $item['is_udzur'] ?>"
                                            data-butuh_bukti="<?= $item['butuh_bukti'] ?>"
                                            title="Edit Amalan">
                                            <i data-lucide="edit-3" size="14"></i>
                                        </button>
                                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm hapus-btn"
                                            data-id="<?= $item['id'] ?>"
                                            title="Hapus Amalan">
                                            <i data-lucide="trash-2" size="14"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pt-5 mt-2 flex justify-between items-center">
            <p class="text-sm text-slate-500">Menampilkan <?= $start_number ?>-<?= $end_number ?> dari <?= $total_data ?></p>
            <?php if ($total_halaman > 1): ?>
                <div class="flex gap-1.5">
                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                        <a href="?page=<?= $i ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition-all <?= $i == $halaman_aktif ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div id="dataModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl overflow-hidden transform scale-95 transition-transform duration-300" id="modalContent">

        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <div>
                <h3 id="modalTitle" class="text-lg font-bold text-slate-800">Tambah Amalan</h3>
                <p class="text-sm text-slate-500 mt-0.5">Konfigurasi detail ibadah harian</p>
            </div>
            <button id="closeModalBtn" class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm">
                <i data-lucide="x" size="16"></i>
            </button>
        </div>

        <form id="dataForm" class="flex flex-col">
            <div class="p-6 space-y-5 overflow-y-auto max-h-[65vh] custom-scrollbar">
                <input type="hidden" name="action" id="formAction" value="tambah">
                <input type="hidden" name="id" id="dataId">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5 ml-1">Nama Amalan</label>
                    <input type="text" name="activity_name" id="activity_name" required
                        class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-medium text-slate-800 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all"
                        placeholder="Contoh: Sholat Subuh Berjamaah">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Toggle Udzur -->
                    <div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-200">
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" name="is_udzur" id="is_udzur" class="sr-only peer">
                            <div class="w-10 h-5 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-rose-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                        </label>
                        <div>
                            <span class="text-sm font-semibold text-slate-700 block leading-none">Status Udzur</span>
                            <span class="text-[11px] text-slate-500 mt-1 block">Otomatis saat haid</span>
                        </div>
                    </div>

                    <!-- Toggle Butuh Bukti -->
                    <div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-200">
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" name="butuh_bukti" id="butuh_bukti" class="sr-only peer">
                            <div class="w-10 h-5 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                        </label>
                        <div>
                            <span class="text-sm font-semibold text-slate-700 block leading-none">Wajib Bukti</span>
                            <span class="text-[11px] text-slate-500 mt-1 block">Harus upload foto</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <div class="flex justify-between items-center mb-3">
                        <label class="block text-sm font-semibold text-slate-700 ml-1">Opsi & Poin</label>
                        <button type="button" id="tambahKomponenBtn" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1.5 bg-emerald-50 px-2.5 py-1.5 rounded-lg border border-emerald-100 transition-colors">
                            <i data-lucide="plus-circle" size="14"></i> Tambah Opsi
                        </button>
                    </div>
                    <div class="space-y-3" id="subKomponenContainer"></div>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5 ml-1">Urutan Tampilan</label>
                    <input type="number" name="urutan" id="urutan" required
                        class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-medium text-slate-800 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all"
                        placeholder="Angka urutan (1, 2, 3...)">
                </div>
            </div>

            <div class="p-5 border-t border-slate-100 bg-slate-50 shrink-0">
                <button type="submit" class="w-full px-4 py-3 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 shadow-sm transition-all transform active:scale-95 flex justify-center items-center gap-2">
                    <i data-lucide="save" size="16"></i> Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if(typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        const modal = document.getElementById('dataModal');
        const modalContent = document.getElementById('modalContent');
        const container = document.getElementById('subKomponenContainer');
        const form = document.getElementById('dataForm');

        const showModal = () => {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
            }, 10);
        };

        const hideModal = () => {
            modal.classList.add('opacity-0');
            modalContent.classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 300);
        };

        const addRow = (nama = '', poin = '0') => {
            const div = document.createElement('div');
            div.className = 'flex gap-2 items-center';
            div.innerHTML = `
            <input type="text" name="opt_nama[]" value="${nama}" placeholder="Label opsi (Misal: Selesai)" class="flex-1 px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium focus:border-emerald-500 focus:outline-none transition-colors">
            <input type="number" name="opt_poin[]" value="${poin}" placeholder="Poin" class="w-20 px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm text-center font-bold text-emerald-600 focus:border-emerald-500 focus:outline-none transition-colors">
            <button type="button" class="w-9 h-9 flex shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all remove-row border border-rose-100">
                <i data-lucide="x" size="14"></i>
            </button>`;
            
            container.appendChild(div);
            
            if(typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            div.querySelector('.remove-row').onclick = () => div.remove();
        };

        document.getElementById('openModalBtn').onclick = () => {
            form.reset();
            container.innerHTML = '';
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('is_udzur').checked = false;
            document.getElementById('butuh_bukti').checked = false;
            document.getElementById('modalTitle').innerText = 'Tambah Amalan';
            addRow('Selesai', '10');
            addRow('Tidak Mengerjakan', '0');
            showModal();
        };

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.onclick = () => {
                const d = btn.dataset;
                document.getElementById('formAction').value = 'edit';
                document.getElementById('dataId').value = d.id;
                document.getElementById('activity_name').value = d.activity_name;
                document.getElementById('urutan').value = d.urutan;
                document.getElementById('is_udzur').checked = d.is_udzur == "1";
                document.getElementById('butuh_bukti').checked = d.butuh_bukti == "1";
                document.getElementById('modalTitle').innerText = 'Edit Amalan';

                container.innerHTML = '';
                if (d.sub_komponen) {
                    d.sub_komponen.split(',').forEach(s => {
                        const p = s.split(':');
                        addRow(p[0], p[1]);
                    });
                }
                showModal();
            };
        });

        document.getElementById('tambahKomponenBtn').onclick = () => addRow();
        document.getElementById('closeModalBtn').onclick = hideModal;

        window.onclick = function(event) {
            if (event.target == modal) {
                hideModal();
            }
        }

        form.onsubmit = async (e) => {
            e.preventDefault();

            const names = form.querySelectorAll('input[name="opt_nama[]"]');
            const points = form.querySelectorAll('input[name="opt_poin[]"]');
            let combined = [];
            names.forEach((n, i) => { if (n.value.trim()) combined.push(`${n.value.trim()}:${points[i].value || '0'}`); });

            const formData = new FormData();
            formData.append('action', document.getElementById('formAction').value);
            formData.append('id', document.getElementById('dataId').value);
            formData.append('activity_name', document.getElementById('activity_name').value);
            formData.append('urutan', document.getElementById('urutan').value);
            formData.append('sub_komponen', combined.join(','));
            formData.append('is_udzur', document.getElementById('is_udzur').checked ? 1 : 0);
            formData.append('butuh_bukti', document.getElementById('butuh_bukti').checked ? 1 : 0);

            try {
                const res = await fetch('api/nafsiyah_api.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.status === 'success') {
                    Swal.fire({
                        icon: 'success', 
                        title: 'Berhasil', 
                        timer: 1500, 
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-2xl' }
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error', 
                        title: 'Gagal', 
                        text: json.message,
                        confirmButtonColor: '#10b981',
                        customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-6 font-medium' }
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error', 
                    title: 'Error', 
                    text: 'Terjadi kesalahan server.',
                    confirmButtonColor: '#10b981',
                    customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-6 font-medium' }
                });
            }
        };

        // Logika Hapus
        document.querySelectorAll('.hapus-btn').forEach(btn => {
            btn.onclick = () => {
                Swal.fire({
                    title: 'Hapus amalan?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
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
                        const fd = new FormData();
                        fd.append('action', 'hapus');
                        fd.append('id', btn.dataset.id);
                        fetch('api/nafsiyah_api.php', { method: 'POST', body: fd }).then(() => location.reload());
                    }
                })
            };
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>