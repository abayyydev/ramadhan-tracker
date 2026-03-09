</main> <!-- Akhir dari KONTEN HALAMAN INJEKSI (main) -->

        <!-- Footer Profesional Sederhana -->
        <footer class="py-6 px-6 mt-auto bg-white border-t border-slate-100 shrink-0">
            <div class="max-w-7xl mx-auto flex justify-center items-center">
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    by 
                    <a href="https://github.com/AbayyyDev" target="_blank" class="font-black text-emerald-600 italic tracking-tighter text-sm hover:text-emerald-700 transition-all">
                        AbayyyDev
                    </a> 
                    <span class="opacity-30">|</span> 
                    © 2026 Ramadhan Tracker
                </p>
            </div>
        </footer>

    </div> <!-- Akhir dari main-content -->
</div> <!-- Akhir dari container flex utama -->

<script>
    // 1. Inisialisasi Lucide Icons
    lucide.createIcons();

    // 2. Fungsi Toggle Mobile Sidebar
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        sidebar.classList.toggle('open');
        
        if (sidebar.classList.contains('open')) {
            overlay.classList.add('show');
        } else {
            overlay.classList.remove('show');
        }
    }

    // 3. Fungsi Toggle Profile Dropdown
    function toggleProfileDropdown() {
        const dropdownMenu = document.getElementById('dropdown-menu');
        
        if (dropdownMenu.classList.contains('dropdown-hidden')) {
            dropdownMenu.classList.remove('dropdown-hidden');
            dropdownMenu.classList.add('dropdown-visible');
        } else {
            dropdownMenu.classList.remove('dropdown-visible');
            dropdownMenu.classList.add('dropdown-hidden');
        }
    }

    // 4. Tutup dropdown saat area luar diklik
    window.addEventListener('click', function(e) {
        const dropdownContainer = document.getElementById('profile-dropdown-container');
        const dropdownMenu = document.getElementById('dropdown-menu');
        
        // Jika klik BUKAN di area dropdown container, tutup menu
        if (dropdownContainer && !dropdownContainer.contains(e.target)) {
            if (dropdownMenu.classList.contains('dropdown-visible')) {
                dropdownMenu.classList.remove('dropdown-visible');
                dropdownMenu.classList.add('dropdown-hidden');
            }
        }
    });
</script>
</body>
</html>