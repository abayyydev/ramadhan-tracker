<?php
require_once 'config/database.php';

try {
    // 1. Ambil semua User ID yang aktif
    $userStmt = $pdo->query("SELECT id FROM users");
    $allUsers = $userStmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Ambil semua amalan default (yang user_id-nya NULL)
    $defaultHabitStmt = $pdo->query("SELECT name, icon FROM habit_types WHERE user_id IS NULL");
    $defaultHabits = $defaultHabitStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($defaultHabits)) {
        die("Tidak ada amalan default yang ditemukan dengan user_id NULL.");
    }

    $count = 0;
    $pdo->beginTransaction();

    foreach ($allUsers as $userId) {
        foreach ($defaultHabits as $habit) {
            // Cek dulu apakah user ini sudah punya amalan tersebut agar tidak duplikat
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM habit_types WHERE user_id = ? AND name = ?");
            $checkStmt->execute([$userId, $habit['name']]);
            
            if ($checkStmt->fetchColumn() == 0) {
                // Jika belum ada, masukkan
                $insertStmt = $pdo->prepare("INSERT INTO habit_types (user_id, name, icon) VALUES (?, ?, ?)");
                $insertStmt->execute([$userId, $habit['name'], $habit['icon']]);
                $count++;
            }
        }
    }

    $pdo->commit();
    echo "Berhasil menyalin amalan! Total $count baris baru ditambahkan untuk " . count($allUsers) . " user.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Gagal: " . $e->getMessage());
}