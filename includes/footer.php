        </div> <!-- End of Dynamic Content Container -->
    </main>

    <!-- MOBILE NATIVE BOTTOM NAVIGATION BAR (Only visible on small screens < md) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200/90 shadow-2xl px-2 py-1.5 flex items-center justify-around pb-safe">
        <a href="index.php" class="flex flex-col items-center justify-center w-full py-1 text-center transition <?= ($current_page == 'index.php') ? 'text-brand-accent font-bold' : 'text-slate-500 hover:text-slate-800' ?>">
            <i class="fa-solid fa-house-chimney text-lg mb-0.5"></i>
            <span class="text-[10px] leading-tight block">Home</span>
        </a>
        <a href="warga.php" class="flex flex-col items-center justify-center w-full py-1 text-center transition <?= ($current_page == 'warga.php') ? 'text-brand-accent font-bold' : 'text-slate-500 hover:text-slate-800' ?>">
            <i class="fa-solid fa-users text-lg mb-0.5"></i>
            <span class="text-[10px] leading-tight block">Warga</span>
        </a>
        <a href="iuran.php" class="flex flex-col items-center justify-center w-full py-1 text-center transition <?= ($current_page == 'iuran.php') ? 'text-brand-accent font-bold' : 'text-slate-500 hover:text-slate-800' ?>">
            <i class="fa-solid fa-wallet text-lg mb-0.5"></i>
            <span class="text-[10px] leading-tight block">Iuran</span>
        </a>
        <a href="surat.php" class="flex flex-col items-center justify-center w-full py-1 text-center transition <?= ($current_page == 'surat.php') ? 'text-brand-accent font-bold' : 'text-slate-500 hover:text-slate-800' ?>">
            <i class="fa-solid fa-file-lines text-lg mb-0.5"></i>
            <span class="text-[10px] leading-tight block">Surat</span>
        </a>
        <button onclick="toggleMobileDrawer()" class="flex flex-col items-center justify-center w-full py-1 text-center text-slate-500 hover:text-slate-800">
            <i class="fa-solid fa-bars-staggered text-lg mb-0.5"></i>
            <span class="text-[10px] leading-tight block">Menu</span>
        </button>
    </nav>

    <!-- Custom Modal Popup for Preview Surat / Detail -->
    <div id="modalSurat" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-5 sm:p-6 space-y-4 transform transition-all">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="font-bold text-slate-800 text-sm sm:text-base" id="modalTitle">Detail Dokumen Surat</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="space-y-3 text-xs text-slate-600" id="modalBody">
                <!-- Content set dynamically -->
            </div>
            <div class="pt-2 flex justify-end">
                <button onclick="closeModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 rounded-lg text-xs font-semibold text-slate-700">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-16 sm:bottom-5 right-3 sm:right-5 bg-slate-900 text-white px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3 transform translate-y-20 opacity-0 transition duration-300 z-50 text-xs">
        <div id="toastIcon" class="text-emerald-400 text-base"><i class="fa-solid fa-circle-check"></i></div>
        <span id="toastMsg">Pesan notifikasi</span>
    </div>

    <script>
        function toggleMobileDrawer() {
            const drawer = document.getElementById('mobileDrawer');
            if (drawer) {
                drawer.classList.toggle('-translate-x-full');
            }
        }

        function closeModal() {
            document.getElementById('modalSurat').classList.add('hidden');
        }

        function showToast(msg, type = "emerald") {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toastMsg');
            const toastIcon = document.getElementById('toastIcon');

            toastMsg.innerText = msg;
            if(type === "rose") {
                toastIcon.className = "text-rose-400 text-base";
                toastIcon.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
            } else {
                toastIcon.className = "text-emerald-400 text-base";
                toastIcon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
            }

            toast.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 3500);
        }

        <?php if (isset($_SESSION['toast_msg'])): ?>
            showToast("<?= htmlspecialchars($_SESSION['toast_msg']) ?>", "<?= htmlspecialchars($_SESSION['toast_type'] ?? 'emerald') ?>");
            <?php 
                unset($_SESSION['toast_msg']); 
                unset($_SESSION['toast_type']);
            ?>
        <?php endif; ?>
    </script>
</body>
</html>
