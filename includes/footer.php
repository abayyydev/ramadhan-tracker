</main>

        <footer class="py-6 px-6 mt-auto bg-white border-t border-slate-100 shrink-0">
            <div class="max-w-7xl mx-auto flex justify-center items-center">
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    by 
                    <a href="https://github.com/AbayyyDev" target="_blank" class="font-black text-emerald-600 tracking-tighter text-sm hover:text-emerald-700 transition-all">
                        AbayyyDev
                    </a> 
                    <span class="opacity-30">|</span> 
                    © 2026 Ramadhan Tracker
                </p>
            </div>
        </footer>

    </div>
</div>

<script>
    lucide.createIcons();

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('open');
        if (sidebar.classList.contains('open')) overlay.classList.add('show');
        else overlay.classList.remove('show');
    }

    function toggleProfileDropdown() {
        const dropdownMenu = document.getElementById('dropdown-menu');
        const notifMenu = document.getElementById('notif-menu');
        
        // Tutup notif jika sedang terbuka
        if (notifMenu && notifMenu.classList.contains('dropdown-visible')) {
            notifMenu.classList.remove('dropdown-visible');
            notifMenu.classList.add('dropdown-hidden');
        }

        if (dropdownMenu.classList.contains('dropdown-hidden')) {
            dropdownMenu.classList.remove('dropdown-hidden');
            dropdownMenu.classList.add('dropdown-visible');
        } else {
            dropdownMenu.classList.remove('dropdown-visible');
            dropdownMenu.classList.add('dropdown-hidden');
        }
    }

    function toggleNotifDropdown() {
        const notifMenu = document.getElementById('notif-menu');
        const dropdownMenu = document.getElementById('dropdown-menu');
        
        // Tutup profil jika sedang terbuka
        if (dropdownMenu && dropdownMenu.classList.contains('dropdown-visible')) {
            dropdownMenu.classList.remove('dropdown-visible');
            dropdownMenu.classList.add('dropdown-hidden');
        }

        if (notifMenu.classList.contains('dropdown-hidden')) {
            notifMenu.classList.remove('dropdown-hidden');
            notifMenu.classList.add('dropdown-visible');
        } else {
            notifMenu.classList.remove('dropdown-visible');
            notifMenu.classList.add('dropdown-hidden');
        }
    }

    // Tutup dropdown saat area luar diklik
    window.addEventListener('click', function(e) {
        const profileContainer = document.getElementById('profile-dropdown-container');
        const profileMenu = document.getElementById('dropdown-menu');
        
        const notifContainer = document.getElementById('notif-dropdown-container');
        const notifMenu = document.getElementById('notif-menu');
        
        if (profileContainer && !profileContainer.contains(e.target)) {
            if (profileMenu && profileMenu.classList.contains('dropdown-visible')) {
                profileMenu.classList.remove('dropdown-visible');
                profileMenu.classList.add('dropdown-hidden');
            }
        }

        if (notifContainer && !notifContainer.contains(e.target)) {
            if (notifMenu && notifMenu.classList.contains('dropdown-visible')) {
                notifMenu.classList.remove('dropdown-visible');
                notifMenu.classList.add('dropdown-hidden');
            }
        }
    });
</script>
</body>
</html>