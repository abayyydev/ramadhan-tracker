<?php
/**
 * Fail: api/update_quran.php
 * Menghandle update progres Juz, Surat (Penanda), dan Reset Khatam via AJAX.
 */
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validasi Sesi Pengguna
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir, silakan login kembali.']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $isReset = isset($_POST['reset']) && $_POST['reset'] === 'true';

    try {
        if ($isReset) {
            /**
             * LOGIKA RESET KHATAM
             * 1. Tambah kolom total_khatam sebanyak +1
             * 2. Kembalikan current_juz ke angka 1
             * 3. Kosongkan penanda surat (current_surah = NULL)
             */
            $stmt = $pdo->prepare("UPDATE quran_progress SET total_khatam = total_khatam + 1, current_juz = 1, current_surah = NULL WHERE user_id = ?");
            $success = $stmt->execute([$userId]);
            
        } else {
            /**
             * LOGIKA UPDATE JUZ & SURAT DINAMIS
             * Mengecek data apa saja yang dikirim oleh Javascript (Juz saja, Surat saja, atau Keduanya)
             */
            $updateFields = [];
            $params = [];

            // Jika ada perubahan Juz
            if (isset($_POST['current_juz'])) {
                $updateFields[] = "current_juz = ?";
                $params[] = (int)$_POST['current_juz'];
            }

            // Jika ada perubahan Surat (Penanda)
            if (array_key_exists('current_surah', $_POST)) {
                if ($_POST['current_surah'] === 'null' || trim($_POST['current_surah']) === '') {
                    // Jika JS mengirim 'null' (misal saat pindah Juz baru)
                    $updateFields[] = "current_surah = NULL";
                } else {
                    // Jika user secara spesifik memilih surat dari dropdown
                    $updateFields[] = "current_surah = ?";
                    $params[] = (int)$_POST['current_surah'];
                }
            }

            // Eksekusi Query jika ada data yang mau diupdate
            if (!empty($updateFields)) {
                $params[] = $userId; // Parameter terakhir untuk WHERE user_id = ?
                
                $sql = "UPDATE quran_progress SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                $success = $stmt->execute($params);
            } else {
                // Jika request POST masuk tapi tidak membawa data apa-apa
                throw new Exception('Tidak ada data progres yang diterima.');
            }
        }

        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Progres berhasil diperbarui.']);
        } else {
            throw new Exception('Gagal memperbarui data di database.');
        }

    } catch (Exception $e) {
        // Berikan respon error jika terjadi kegagalan sistem/database
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    // Jika diakses secara langsung via browser (metode GET)
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan.']);
}