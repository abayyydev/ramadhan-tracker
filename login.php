<?php
session_start();
require_once 'config/database.php';

// Pastikan zona waktu sama antara PHP dan MySQL
date_default_timezone_set('Asia/Jakarta');

// Cek mode halaman: 'email' (default), 'wa_request', atau 'wa_verify'
$login_mode = $_GET['mode'] ?? 'email'; 
$msg = "";
$error = "";

// Jika sedang dalam proses OTP, paksa ke mode verify
if (isset($_SESSION['pending_wa'])) {
    $login_mode = 'wa_verify';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- 1. LOGIN MENGGUNAKAN EMAIL & PASSWORD ---
    if (isset($_POST['login_email'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verifikasi password (pastikan user sudah set password di halaman profil)
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Email tidak ditemukan atau Password salah.";
        }
    }

    // --- 2. REQUEST OTP WHATSAPP (LUPA PASSWORD) ---
    if (isset($_POST['request_otp'])) {
        $waRaw = trim($_POST['whatsapp_number']);
        $waClean = preg_replace('/[^0-9]/', '', $waRaw);
        
        $wa62 = $waClean;
        $wa0 = $waClean;

        if (substr($waClean, 0, 1) === '0') {
            $wa62 = '62' . substr($waClean, 1);
            $wa0 = $waClean;
        } elseif (substr($waClean, 0, 2) === '62') {
            $wa62 = $waClean;
            $wa0 = '0' . substr($waClean, 2);
        } elseif (substr($waClean, 0, 1) === '8') {
            $wa62 = '62' . $waClean;
            $wa0 = '0' . $waClean;
        }

        $stmt = $pdo->prepare("SELECT id, full_name, whatsapp_number FROM users WHERE whatsapp_number = ? OR whatsapp_number = ?");
        $stmt->execute([$wa0, $wa62]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate Kode OTP 6 Digit
            $otp = rand(100000, 999999);
            // Simpan expiry 5 menit ke depan
            $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
            $stmt->execute([$otp, $expiry, $user['id']]);

            $token = "eSJDYxaMoxjNvy8vTuDy"; // Token Fonnte Anda
            $message = "No Reply*,\n\nKode OTP Pemulihan Login Ramadhan Pro Anda adalah: *$otp*\n\nKode ini berlaku selama 5 menit. Jangan berikan kode ini kepada siapapun.";

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'target' => $wa62,
                    'message' => $message,
                ),
                CURLOPT_HTTPHEADER => array("Authorization: $token"),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                $error = "CURL Error: " . $err;
            } else {
                $resData = json_decode($response, true);
                if ($resData['status'] == true) {
                    $_SESSION['pending_wa'] = $wa62;
                    $_SESSION['db_wa'] = $user['whatsapp_number'];
                    $login_mode = 'wa_verify';
                    $msg = "Kode OTP 6 digit telah dikirimkan ke WhatsApp Anda.";
                } else {
                    $error = "Fonnte Error: " . ($resData['reason'] ?? 'Gagal mengirim pesan.');
                }
            }
        } else {
            $error = "Nomor WhatsApp ($waRaw) belum terdaftar.";
        }
    }

    // --- 3. VERIFIKASI OTP WHATSAPP ---
    if (isset($_POST['verify_otp'])) {
        $waDB = $_SESSION['db_wa'];
        $otp_input = trim($_POST['otp_code']);
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("SELECT * FROM users WHERE whatsapp_number = ? AND otp_code = ? AND otp_expiry >= ?");
        $stmt->execute([$waDB, $otp_input, $now]);
        $user = $stmt->fetch();

        if ($user) {
            $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE id = ?")->execute([$user['id']]);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
            
            unset($_SESSION['pending_wa']);
            unset($_SESSION['db_wa']);
            header("Location: index.php");
            exit();
        } else {
            $login_mode = 'wa_verify';
            $error = "Kode OTP 6 digit salah atau sudah kedaluwarsa.";
        }
    }

    // --- 4. BATAL OTP (Kembali ke Email/Ganti Nomor) ---
    if (isset($_POST['cancel_otp'])) {
        unset($_SESSION['pending_wa']);
        unset($_SESSION['db_wa']);
        header("Location: login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ramadhan Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Noto Serif', serif; }
        .bg-pattern { background-image: radial-gradient(#10b981 0.5px, transparent 0.5px); background-size: 24px 24px; }
        input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body class="bg-slate-50 bg-pattern flex items-center justify-center min-h-screen p-6">
    <div class="bg-white p-10 lg:p-12 rounded-[3.5rem] shadow-2xl border border-slate-100 w-full max-w-md animate-in fade-in zoom-in duration-500">
        
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-emerald-600 rounded-[2rem] flex items-center justify-center text-white shadow-2xl shadow-emerald-100 mx-auto mb-6 transform rotate-3">
                <i data-lucide="<?= $login_mode === 'email' ? 'log-in' : 'smartphone' ?>" class="w-10 h-10"></i>
            </div>
            <h1 class="text-4xl font-black text-slate-800 italic uppercase tracking-tighter leading-none text-center">
                <?= $login_mode === 'email' ? 'Masuk' : 'Pemulihan' ?>
            </h1>
            <p class="text-slate-400 font-bold text-[10px] mt-4 uppercase tracking-[0.3em] text-center">Ramadhan Pro x AbayyyDev</p>
        </div>

        <?php if($login_mode === 'email'): ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 px-1">Alamat Email</label>
                    <input type="email" name="email" placeholder="email@contoh.com" 
                           class="w-full p-5 rounded-[1.5rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700 shadow-inner" required>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 px-1">Password</label>
                    <input type="password" name="password" placeholder="••••••••" 
                           class="w-full p-5 rounded-[1.5rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 transition-all font-bold text-slate-700 shadow-inner" required>
                </div>
                
                <button type="submit" name="login_email" class="w-full py-5 bg-emerald-600 text-white rounded-[1.5rem] font-black shadow-2xl shadow-emerald-200 hover:bg-emerald-700 hover:-translate-y-1 transition-all active:scale-95 uppercase tracking-widest text-xs">
                    Login ke Akun
                </button>
                
                <div class="text-center mt-6 space-y-3">
                    <p class="text-xs text-slate-500 font-medium italic">Lupa Password? 
                        <a href="login.php?mode=wa_request" class="text-indigo-600 font-black hover:underline not-italic ml-1">Login via WhatsApp</a>
                    </p>
                    <p class="text-xs text-slate-500 font-medium italic pt-2 border-t border-slate-100">Belum punya akun? 
                        <a href="register.php" class="text-emerald-600 font-black hover:underline not-italic ml-1">Daftar Sekarang</a>
                    </p>
                </div>
            </form>

        <?php elseif($login_mode === 'wa_request'): ?>
            <form method="POST" class="space-y-6">
                <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-2xl mb-6">
                    <p class="text-xs text-indigo-700 font-medium italic text-center">Masukkan nomor WhatsApp Anda. Kami akan mengirimkan OTP untuk masuk ke akun Anda.</p>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 px-1">Nomor WhatsApp</label>
                    <input type="text" name="whatsapp_number" placeholder="Contoh: 0812... atau 628..." 
                           class="w-full p-5 rounded-[1.5rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-indigo-500 transition-all font-bold text-slate-700 text-lg shadow-inner" required>
                </div>
                
                <button type="submit" name="request_otp" class="w-full py-5 bg-indigo-600 text-white rounded-[1.5rem] font-black shadow-2xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 transition-all active:scale-95 uppercase tracking-widest text-xs flex justify-center items-center gap-2">
                    <i data-lucide="send" size={16}></i> Kirim Kode OTP
                </button>
                
                <div class="text-center mt-4">
                    <a href="login.php" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-emerald-600 transition-colors">
                        <i data-lucide="arrow-left" class="inline w-3 h-3 mb-0.5"></i> Kembali ke Login Email
                    </a>
                </div>
            </form>

        <?php elseif($login_mode === 'wa_verify'): ?>
            <form method="POST" class="space-y-8 text-center">
                <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                    <p class="text-xs text-slate-500 font-medium mb-1 italic">Kode 6 digit terkirim ke:</p>
                    <p class="text-sm font-black text-slate-800 italic tracking-wider"><?= $_SESSION['pending_wa'] ?></p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Masukkan 6 Digit Kode</label>
                    <input type="number" name="otp_code" placeholder="000000" 
                           class="w-full p-6 rounded-[2rem] border-2 border-slate-50 bg-slate-50 outline-none focus:bg-white focus:border-emerald-500 font-black text-4xl text-center tracking-[0.4em] shadow-inner text-emerald-600" 
                           required maxlength="6" oninput="if(this.value.length > 6) this.value = this.value.slice(0, 6);" pattern="\d{6}" autocomplete="off">
                </div>

                <button type="submit" name="verify_otp" class="w-full py-5 bg-emerald-600 text-white rounded-[1.5rem] font-black shadow-2xl shadow-emerald-200 hover:bg-emerald-700 transition-all active:scale-95 uppercase tracking-widest text-xs">
                    Verifikasi & Masuk
                </button>
                
                <div class="text-center">
                    <button type="submit" name="cancel_otp" class="inline-block text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-rose-500 transition-colors border-b-2 border-transparent hover:border-rose-100 py-1">
                        Batal / Ganti Nomor
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <div class="mt-12 pt-8 border-t border-slate-50 text-center">
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                © 2026 Made by AbayyyDev
            </p>
        </div>
    </div>

    <script>
    // Inisialisasi Lucide Icons
    lucide.createIcons();

    const errorMsg = <?= json_encode($error) ?>;
    const msg = <?= json_encode($msg) ?>;

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

    if (msg) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: 'success',
            title: msg
        });
    }
    </script>
</body>
</html>