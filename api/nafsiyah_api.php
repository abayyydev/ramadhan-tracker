<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');


$response = ['status' => 'error', 'message' => 'Aksi tidak valid.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Ambil data umum
    $activity_name = $_POST['activity_name'] ?? '';
    $sub_komponen = !empty($_POST['sub_komponen']) ? $_POST['sub_komponen'] : null;
    $urutan = $_POST['urutan'] ?? 0;
    $is_udzur = (isset($_POST['is_udzur']) && $_POST['is_udzur'] == '1') ? 1 : 0;
    // FITUR BARU: Ambil nilai butuh bukti
    $butuh_bukti = (isset($_POST['butuh_bukti']) && $_POST['butuh_bukti'] == '1') ? 1 : 0;

    // --- AKSI TAMBAH ---
    if ($_POST['action'] === 'tambah') {
        if (empty($activity_name) || !is_numeric($urutan)) {
            $response['message'] = 'Nama Aktivitas dan Urutan wajib diisi.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO nafsiyah_items (activity_name, sub_komponen, urutan, is_udzur, butuh_bukti) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$activity_name, $sub_komponen, $urutan, $is_udzur, $butuh_bukti]);
                $response = ['status' => 'success', 'message' => 'Item baru berhasil ditambahkan.'];
            } catch (PDOException $e) {
                $response['message'] = "Database error: " . $e->getMessage();
            }
        }
    }

    // --- AKSI EDIT ---
    elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'] ?? '';
        if (empty($activity_name) || !is_numeric($urutan) || empty($id)) {
            $response['message'] = 'Semua field wajib diisi dengan benar.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE nafsiyah_items SET activity_name = ?, sub_komponen = ?, urutan = ?, is_udzur = ?, butuh_bukti = ? WHERE id = ?");
                $stmt->execute([$activity_name, $sub_komponen, $urutan, $is_udzur, $butuh_bukti, $id]);
                $response = ['status' => 'success', 'message' => 'Item berhasil diperbarui.'];
            } catch (PDOException $e) {
                $response['message'] = "Database error: " . $e->getMessage();
            }
        }
    }

    // --- AKSI HAPUS ---
    elseif ($_POST['action'] === 'hapus') {
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM nafsiyah_items WHERE id = ?");
                $stmt->execute([$id]);
                $response = ['status' => 'success', 'message' => 'Item berhasil dihapus.'];
            } catch (PDOException $e) {
                $response['message'] = 'Gagal menghapus item.';
            }
        }
    }
}

echo json_encode($response);