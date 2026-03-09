<?php
/**
 * Fail: api/manage_habits.php
 * Menghandle penambahan, pengeditan, dan penghapusan nama amalan personal.
 */
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir, silakan login ulang']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';

    try {
        // 1. AKSI TAMBAH
        if ($action === 'add') {
            $name = trim($_POST['name']);
            if (empty($name)) throw new Exception("Nama amalan tidak boleh kosong");

            $stmt = $pdo->prepare("INSERT INTO habit_types (user_id, name, icon) VALUES (?, ?, 'circle')");
            $stmt->execute([$userId, $name]);
            echo json_encode(['status' => 'success']);
        } 
        
        // 2. AKSI EDIT (PENTING: Pastikan ini ada!)
       elseif ($action === 'edit') {
    $id = (int)$_POST['id'];
    $newName = trim($_POST['name']);
    if (empty($newName)) throw new Exception("Nama amalan baru tidak boleh kosong");

    // 1. Ambil nama lama terlebih dahulu sebelum diupdate
    $stmtOld = $pdo->prepare("SELECT name FROM habit_types WHERE id = ? AND user_id = ?");
    $stmtOld->execute([$id, $userId]);
    $oldName = $stmtOld->fetchColumn();

    if (!$oldName) throw new Exception("Data tidak ditemukan");

    $pdo->beginTransaction(); // Gunakan transaksi agar data konsisten
    try {
        // 2. Update nama di master amalan
        $stmt = $pdo->prepare("UPDATE habit_types SET name = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$newName, $id, $userId]);

        // 3. Update semua riwayat centang yang menggunakan nama lama
        $stmtLog = $pdo->prepare("UPDATE habit_logs SET habit_name = ? WHERE habit_name = ? AND user_id = ?");
        $stmtLog->execute([$newName, $oldName, $userId]);

        $pdo->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

        // 3. AKSI HAPUS
       elseif ($action === 'delete') {
    $id = (int)$_POST['id'];

    // 1. Ambil nama amalan yang akan dihapus
    $stmtName = $pdo->prepare("SELECT name FROM habit_types WHERE id = ? AND user_id = ?");
    $stmtName->execute([$id, $userId]);
    $habitName = $stmtName->fetchColumn();

    if ($habitName) {
        $pdo->beginTransaction();
        try {
            // 2. Hapus dari master amalan
            $stmt = $pdo->prepare("DELETE FROM habit_types WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);

            // 3. Hapus semua log centang terkait agar kalkulasi kembali normal
            $stmtDelLog = $pdo->prepare("DELETE FROM habit_logs WHERE habit_name = ? AND user_id = ?");
            $stmtDelLog->execute([$habitName, $userId]);

            $pdo->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
        
        else {
            throw new Exception("Aksi tidak valid");
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}