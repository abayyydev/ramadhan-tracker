<?php
/**
 * Fail: api/save_habit.php
 * Menyimpan log centang amalan.
 */
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir']);
        exit;
    }

    $userId = $_SESSION['user_id']; 
    $habitName = $_POST['habit_name'];
    $day = (int)$_POST['day'];
    $isCompleted = (int)$_POST['is_completed'];
    
    // Logika tanggal: Menggunakan bulan dan tahun berjalan
    $logDate = date('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);

    try {
        $stmt = $pdo->prepare("SELECT id FROM habit_logs WHERE user_id = ? AND log_date = ? AND habit_name = ?");
        $stmt->execute([$userId, $logDate, $habitName]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE habit_logs SET is_completed = ? WHERE id = ?");
            $stmt->execute([$isCompleted, $exists['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO habit_logs (user_id, log_date, habit_name, is_completed) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $logDate, $habitName, $isCompleted]);
        }
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}