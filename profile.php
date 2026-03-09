<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$msg = '';
$err = '';

// --- PROSES UPDATE PROFIL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $newPassword = $_POST['new_password'];

    // Ambil data user lama
    $stmtUser = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $oldData = $stmtUser->fetch();
    
    $profilePicDb = $oldData['profile_picture']; // Default pakai foto lama

    // Proses Upload Foto Baru
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $upload_dir = __DIR__ . '/uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $tmp_name = $_FILES['profile_picture']['tmp_name'];
        $file_name = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validasi ekstensi foto
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $new_file_name = "prof_" . $userId . "_" . time() . "." . $ext;
            if (move_uploaded_file($tmp_name, $upload_dir . $new_file_name)) {
                // Hapus foto lama jika ada
                if ($profilePicDb && file_exists($upload_dir . $profilePicDb)) {
                    unlink($upload_dir . $profilePicDb);
                }
                $profilePicDb = $new_file_name;
            } else {
                $err = "Gagal mengunggah foto profil.";
            }
        } else {
            $err = "Format foto harus JPG, JPEG, atau PNG.";
        }
    }

    if (empty($err)) {
        try {
            // Update Database (dengan atau tanpa password baru)
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_picture = ?, password = ? WHERE id = ?");
                $stmt->execute([$fullName, $email, $profilePicDb, $hashedPassword, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_picture = ? WHERE id = ?");
                $stmt->execute([$fullName, $email, $profilePicDb, $userId]);
            }

            // Update Session agar nama & foto di navbar langsung berubah
            $_SESSION['full_name'] = $fullName;
            $_SESSION['profile_picture'] = $profilePicDb;

            $msg = "Profil berhasil diperbarui!";
        } catch (Exception $e) {
            $err = "Gagal memperbarui profil: " . $e->getMessage();
        }
    }
}

// Ambil data user terbaru untuk ditampilkan di form
$stmt = $pdo->prepare("SELECT u.*, j.nama_jurusan FROM users u LEFT JOIN jurusan j ON u.jurusan_id = j.id WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Atur URL foto profil (jika kosong, pakai inisial nama)
$fotoProfilUrl = '';
$hurufAwal = strtoupper(substr($user['full_name'], 0, 1));
if ($user['profile_picture']) {
    $fotoProfilUrl = 'uploads/profiles/' . $user['profile_picture'];
}

include 'includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8 animate-in fade-in duration-700 font-sans">
    
    <header class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase italic">Profil <span class="text-emerald-600">Saya</span></h2>
            <p class="text-slate-500 font-medium italic mt-1">Kelola informasi akun dan pengaturan personal Anda.</p>
        </div>
        <div class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-200 bg-slate-50 text-slate-500">
            Role: <span class="<?= $user['role'] === 'admin' ? 'text-indigo-600' : 'text-emerald-600' ?>"><?= $user['role'] ?></span>
        </div>
    </header>

    <?php if ($msg): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-3xl font-bold italic text-sm flex items-center gap-3 animate-in slide-in-from-top-2">
            <i data-lucide="check-circle" size={18}></i> <?= $msg ?>
        </div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-6 py-4 rounded-3xl font-bold italic text-sm flex items-center gap-3 animate-in slide-in-from-top-2">
            <i data-lucide="alert-circle" size={18}></i> <?= $err ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-8 rounded-[3.5rem] shadow-xl border border-slate-100 relative z-10">
        <form method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-10">
            
            <div class="w-full md:w-1/3 flex flex-col items-center gap-6">
                <div class="relative group cursor-pointer" onclick="document.getElementById('file-upload').click()">
                    <div class="w-48 h-48 rounded-full overflow-hidden border-4 border-slate-50 shadow-xl bg-emerald-100 flex items-center justify-center text-5xl font-black text-emerald-600 relative z-10" id="preview-container">
                        <?php if ($fotoProfilUrl): ?>
                            <img src="<?= $fotoProfilUrl ?>" id="img-preview" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span id="initial-preview"><?= $hurufAwal ?></span>
                            <img src="" id="img-preview" class="w-full h-full object-cover hidden">
                        <?php endif; ?>
                    </div>
                    
                    <div class="absolute inset-0 bg-slate-900/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-20">
                        <i data-lucide="camera" class="text-white w-10 h-10"></i>
                    </div>

                    <input type="file" name="profile_picture" id="file-upload" accept="image/png, image/jpeg, image/jpg" class="hidden" onchange="previewImage(event)">
                </div>
                
                <div class="text-center">
                    <h3 class="font-black text-xl text-slate-800 italic"><?= htmlspecialchars($user['full_name']) ?></h3>
                    <p class="text-slate-400 font-bold text-sm">@<?= htmlspecialchars($user['username']) ?></p>
                    <div class="mt-3 inline-block px-3 py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-500 italic">
                        <?= $user['nama_jurusan'] ?: 'Belum ada jurusan' ?>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-2/3 space-y-6 border-t md:border-t-0 md:border-l border-slate-100 pt-8 md:pt-0 md:pl-10">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Nama Lengkap</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" class="w-full p-4 rounded-2xl border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Alamat Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="w-full p-4 rounded-2xl border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700">
                    </div>

                    <div class="md:col-span-2 mt-4 pt-6 border-t border-slate-100">
                        <div class="flex items-center gap-2 mb-4">
                            <i data-lucide="lock" class="text-rose-400 w-4 h-4"></i>
                            <h4 class="font-black text-slate-700 text-sm uppercase tracking-widest">Keamanan Akun</h4>
                        </div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Password Baru <span class="text-slate-300 normal-case font-medium">(Kosongkan jika tidak ingin ganti)</span></label>
                        <input type="password" name="new_password" placeholder="Ketik password baru..." class="w-full p-4 rounded-2xl border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-rose-300 transition-all font-bold text-slate-700">
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit" name="update_profile" class="w-full sm:w-auto px-10 py-4 bg-emerald-600 text-white rounded-2xl font-black shadow-lg shadow-emerald-200 hover:bg-emerald-700 hover:-translate-y-1 transition-all active:scale-95 uppercase text-xs tracking-widest flex items-center justify-center gap-2">
                        <i data-lucide="save" size={16}></i> Simpan Perubahan
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
// Skrip untuk Live Preview Foto Profil yang di-upload
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        const preview = document.getElementById('img-preview');
        const initial = document.getElementById('initial-preview');
        
        preview.src = reader.result;
        preview.classList.remove('hidden');
        
        if(initial) {
            initial.classList.add('hidden');
        }
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>

<?php include 'includes/footer.php'; ?>