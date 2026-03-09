</div> <!-- Penutup div.max-w-7xl dari header.php -->
    </div> <!-- Penutup div.main-content dari header.php -->
</div> <!-- Penutup div.flex dari header.php -->

<!-- FOOTNAVBAR MOBILE (Hanya tampil di HP) -->
<nav class="lg:hidden fixed bottom-0 left-0 w-full bg-white border-t border-slate-100 z-50 px-2 py-2 flex justify-between items-center shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.05)] pb-[calc(env(safe-area-inset-bottom)+0.5rem)] overflow-x-auto no-scrollbar">
    
    <a href="index.php" class="flex flex-col items-center justify-center min-w-[65px] px-2 <?= getMobileMenuClass('index.php', $current_page_name) ?> group">
        <div class="p-1.5 rounded-xl transition-all <?= $current_page_name == 'index.php' ? 'bg-emerald-50 text-emerald-600' : 'group-hover:bg-slate-50' ?>">
            <i data-lucide="layout-dashboard" size="22" class="<?= $current_page_name == 'index.php' ? 'fill-emerald-100' : '' ?>"></i>
        </div>
        <span class="text-[9px] font-bold mt-1 tracking-tighter">Beranda</span>
    </a>

    <a href="input_nafsiyah.php" class="flex flex-col items-center justify-center min-w-[65px] px-2 <?= getMobileMenuClass('input_nafsiyah.php', $current_page_name) ?> group">
        <div class="p-1.5 rounded-xl transition-all <?= $current_page_name == 'input_nafsiyah.php' ? 'bg-emerald-50 text-emerald-600' : 'group-hover:bg-slate-50' ?>">
            <i data-lucide="check-square" size="22" class="<?= $current_page_name == 'input_nafsiyah.php' ? 'fill-emerald-100' : '' ?>"></i>
        </div>
        <span class="text-[9px] font-bold mt-1 tracking-tighter">Nafsiyah</span>
    </a>

    <a href="habits.php" class="flex flex-col items-center justify-center min-w-[65px] px-2 <?= getMobileMenuClass('habits.php', $current_page_name) ?> group">
        <div class="p-1.5 rounded-xl transition-all <?= $current_page_name == 'habits.php' ? 'bg-emerald-50 text-emerald-600' : 'group-hover:bg-slate-50' ?>">
            <i data-lucide="list-todo" size="22" class="<?= $current_page_name == 'habits.php' ? 'fill-emerald-100' : '' ?>"></i>
        </div>
        <span class="text-[9px] font-bold mt-1 tracking-tighter">Habit</span>
    </a>

    <a href="quran.php" class="flex flex-col items-center justify-center min-w-[65px] px-2 <?= getMobileMenuClass('quran.php', $current_page_name) ?> group">
        <div class="p-1.5 rounded-xl transition-all <?= $current_page_name == 'quran.php' ? 'bg-emerald-50 text-emerald-600' : 'group-hover:bg-slate-50' ?>">
            <i data-lucide="book-open" size="22" class="<?= $current_page_name == 'quran.php' ? 'fill-emerald-100' : '' ?>"></i>
        </div>
        <span class="text-[9px] font-bold mt-1 tracking-tighter">Qur'an</span>
    </a>

    <a href="leaderboard.php" class="flex flex-col items-center justify-center min-w-[65px] px-2 <?= getMobileMenuClass('leaderboard.php', $current_page_name) ?> group">
        <div class="p-1.5 rounded-xl transition-all <?= $current_page_name == 'leaderboard.php' ? 'bg-emerald-50 text-emerald-600' : 'group-hover:bg-slate-50' ?>">
            <i data-lucide="trophy" size="22" class="<?= $current_page_name == 'leaderboard.php' ? 'fill-emerald-100' : '' ?>"></i>
        </div>
        <span class="text-[9px] font-bold mt-1 tracking-tighter">Rank</span>
    </a>

    <?php if($_SESSION['role'] === 'admin'): ?>
    <a href="admin_users.php" class="flex flex-col items-center justify-center min-w-[65px] px-2 <?= getMobileMenuClass('admin_users.php', $current_page_name) ?> group">
        <div class="p-1.5 rounded-xl transition-all <?= $current_page_name == 'admin_users.php' ? 'bg-emerald-50 text-emerald-600' : 'group-hover:bg-slate-50' ?>">
            <i data-lucide="settings" size="22" class="<?= $current_page_name == 'admin_users.php' ? 'fill-emerald-100' : '' ?>"></i>
        </div>
        <span class="text-[9px] font-bold mt-1 tracking-tighter">Admin</span>
    </a>
    <?php endif; ?>

</nav>

<script>
    // Merender ulang ikon Lucide secara global
    lucide.createIcons();
</script>
</body>
</html>