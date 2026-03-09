<?php
// Letakkan di paling atas untuk memastikan waktu sinkron
date_default_timezone_set('Asia/Jakarta');

function getDailyHabitCount($pdo, $userId, $date) {
    // Menggunakan DATE() agar aman jika kolom log_date bertipe DATETIME
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM habit_logs WHERE user_id = ? AND DATE(log_date) = ? AND is_completed = 1");
    $stmt->execute([$userId, $date]);
    return (int)$stmt->fetchColumn();
}

function getDailyProgress($pdo, $userId, $date) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM habit_types WHERE user_id = ?");
    $stmt->execute([$userId]);
    $total = (int)$stmt->fetchColumn();

    if ($total <= 0) return 0;

    $completed = getDailyHabitCount($pdo, $userId, $date);
    return round(($completed / $total) * 100);
}

function getJuzProgressPercent($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT current_juz FROM quran_progress WHERE user_id = ?");
    $stmt->execute([$userId]);
    $currentJuz = $stmt->fetchColumn();
    return $currentJuz ? round(($currentJuz / 30) * 100) : 0;
}

    // Konversi Halaman ke Juz (Estimasi Mushaf Madinah 604 hal)
    function pageToJuz($page) {
        if ($page <= 0) return 1;
        if ($page >= 604) return 30;
        return ceil($page / 20.13); 
    }