<?php
/**
 * Fail: register.php
 * Halaman Registrasi Ramadhan Pro (Email, Password, WhatsApp, & Jurusan).
 */
require_once 'config/database.php';

$error = "";
$success = "";

// Ambil daftar jurusan untuk dropdown
try {
    $stmtJurusan = $pdo->query("SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
    $list_jurusan = $stmtJurusan->fetchAll();
} catch (Exception $e) {
    $list_jurusan = []; // Jaga-jaga jika tabel jurusan belum ada
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : "";
    $waRaw = isset($_POST['whatsapp_number']) ? trim($_POST['whatsapp_number']) : "";
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = isset($_POST['password']) ? $_POST['password'] : "";
    $jurusanId = isset($_POST['jurusan_id']) ? $_POST['jurusan_id'] : null;
    
    if (empty($fullName) || empty($waRaw) || empty($email) || empty($password) || empty($jurusanId)) {
        $error = "Semua kolom wajib diisi!";
    } else {
        // Normalisasi Nomor WA ke format 62 agar konsisten di DB & API Fonnte
        $wa = preg_replace('/[^0-9]/', '', $waRaw);
        if (substr($wa, 0, 1) === '0') {
            $wa = '62' . substr($wa, 1);
        } elseif (substr($wa, 0, 1) === '8') {
            $wa = '62' . $wa;
        }

        try {
            // Cek apakah nomor WA atau Email sudah terdaftar
            $check = $pdo->prepare("SELECT id FROM users WHERE whatsapp_number = ? OR email = ?");
            $check->execute([$wa, $email]);
            
            if ($check->fetch()) {
                $error = "Nomor WhatsApp atau Email ini sudah terdaftar!";
            } else {
                // Enkripsi Password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert data user baru termasuk Email, Password, dan Jurusan
                // username kita isi dengan nomor wa agar tidak kosong (bisa juga email)
                $stmt = $pdo->prepare("INSERT INTO users (full_name, whatsapp_number, username, email, password, jurusan_id, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
                $execute = $stmt->execute([$fullName, $wa, $wa, $email, $hashedPassword, $jurusanId]);
                
                if ($execute) {
                    $userId = $pdo->lastInsertId();
                    // Inisialisasi Progres Quran untuk user baru
                    $pdo->prepare("INSERT INTO quran_progress (user_id) VALUES (?)")->execute([$userId]);
                    $success = "Registrasi berhasil! Silakan login menggunakan Email dan Password Anda.";
                } else {
                    $error = "Gagal menyimpan data ke database.";
                }
            }
        } catch (Exception $e) {
            $error = "Kesalahan Sistem: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Ramadhan Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Noto Serif', serif; }
        .bg-pattern { background-image: radial-gradient(#10b981 0.5px, transparent 0.5px); background-size: 24px 24px; }
    </style>
</head>
<body class="bg-slate-50 bg-pattern flex items-center justify-center min-h-screen p-4 sm:p-6">
    <div class="bg-white p-8 lg:p-10 rounded-[3.5rem] shadow-2xl border border-slate-100 w-full max-w-xl animate-in fade-in zoom-in duration-500 my-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-emerald-600 rounded-[2rem] flex items-center justify-center text-white shadow-2xl shadow-emerald-100 mx-auto mb-6 transform -rotate-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
            </div>
            <h1 class="text-3xl sm:text-4xl font-black text-slate-800 italic uppercase tracking-tighter leading-none">Daftar Akun</h1>
            <p class="text-slate-400 font-bold text-[10px] mt-4 uppercase tracking-[0.3em]">Ramadhan Pro x AbayyyDev</p>
        </div>

        <form method="POST" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Nama Lengkap</label>
                    <input type="text" name="full_name" placeholder="Masukkan Nama Lengkap" 
                           class="w-full p-4 rounded-[1.5rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700 shadow-inner" required>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Alamat Email</label>
                    <input type="email" name="email" placeholder="email@contoh.com" 
                           class="w-full p-4 rounded-[1.5rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700 shadow-inner" required>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Password</label>
                    <input type="password" name="password" placeholder="Buat Password" 
                           class="w-full p-4 rounded-[1.5rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700 shadow-inner" required>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Program Studi / Jurusan</label>
                    <select name="jurusan_id" class="w-full p-4 rounded-[1.5rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700 shadow-inner appearance-none cursor-pointer" required>
                        <option value="">-- Pilih Jurusan Anda --</option>
                        <?php foreach($list_jurusan as $j): ?>
                            <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['nama_jurusan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Nomor WhatsApp</label>
                    <input type="text" name="whatsapp_number" placeholder="Contoh: 08123456789" 
                           class="w-full p-4 rounded-[1.5rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700 shadow-inner" required>
                    <p class="text-[9px] text-slate-400 mt-2 ml-2 italic">* Digunakan untuk pemulihan akun (Lupa Password).</p>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full py-5 bg-emerald-600 text-white rounded-[1.5rem] font-black shadow-2xl shadow-emerald-200 hover:bg-emerald-700 hover:-translate-y-1 transition-all active:scale-95 uppercase tracking-widest text-xs">
                    Buat Akun Sekarang
                </button>
            </div>
            
            <div class="text-center mt-4">
                <p class="text-xs text-slate-500 font-medium">Sudah punya akun? 
                    <a href="login.php" class="text-emerald-600 font-black hover:underline italic">Login Di Sini</a>
                </p>
            </div>
        </form>

        <div class="mt-10 pt-6 border-t border-slate-50 text-center">
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                © 2026 Crafted by AbayyyDev
            </p>
        </div>
    </div>

    <script>
    const errorMsg = <?= json_encode($error) ?>;
    const successMsg = <?= json_encode($success) ?>;

    if (errorMsg) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: errorMsg,
            confirmButtonColor: '#10b981',
            customClass: {
                popup: 'rounded-[2rem]',
                confirmButton: 'rounded-xl px-8 font-bold'
            }
        });
    }

    if (successMsg) {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: successMsg,
            confirmButtonColor: '#10b981',
            customClass: {
                popup: 'rounded-[2rem]',
                confirmButton: 'rounded-xl px-8 font-bold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'login.php';
            }
        });
    }
    </script>
</body>
</html>