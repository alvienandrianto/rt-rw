<?php
require_once __DIR__ . '/config/database.php';

// Handle Add Activity (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_kegiatan') {
    require_admin();
    $nama_kegiatan = trim($_POST['nama_kegiatan']);
    $tanggal       = $_POST['tanggal'];
    $waktu         = trim($_POST['waktu'] ?: '08:00 WIB');
    $lokasi        = trim($_POST['lokasi']);
    $keterangan    = trim($_POST['keterangan'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO kegiatan (nama_kegiatan, tanggal, waktu, lokasi, keterangan, status) VALUES (?, ?, ?, ?, ?, 'Mendatang')");
    $stmt->execute([$nama_kegiatan, $tanggal, $waktu, $lokasi, $keterangan]);
    $_SESSION['toast_msg'] = "Kegiatan $nama_kegiatan berhasil ditambahkan ke agenda!";
    header("Location: kegiatan.php");
    exit;
}

// Action Complete / Delete (Admin Only)
if (isset($_GET['action']) && isset($_GET['id'])) {
    require_admin();
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'complete') {
        $stmt = $pdo->prepare("UPDATE kegiatan SET status = 'Selesai' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['toast_msg'] = "Status kegiatan diperbarui menjadi Selesai!";
    } elseif ($_GET['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM kegiatan WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['toast_msg'] = "Kegiatan telah dihapus.";
        $_SESSION['toast_type'] = "rose";
    }
    header("Location: kegiatan.php");
    exit;
}

$kegiatan_list = $pdo->query("SELECT * FROM kegiatan ORDER BY tanggal ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Title -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
    <div>
        <h1 class="text-xl font-extrabold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-calendar-days text-brand-accent"></i> Agenda & Kegiatan RT 01
        </h1>
        <p class="text-xs text-slate-500 mt-1">Jadwal kerja bakti, rapat bulanan, sosialisasi, dan kegiatan kebersamaan warga.</p>
    </div>
    <?php if ($isAdmin): ?>
        <button onclick="document.getElementById('modalKegiatan').classList.remove('hidden')" class="bg-brand-accent hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition flex items-center gap-1.5">
            <i class="fa-solid fa-calendar-plus"></i> Tambah Agenda Kegiatan
        </button>
    <?php endif; ?>
</div>

<!-- Kegiatan Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($kegiatan_list as $k): ?>
        <?php 
            $isDone = ($k['status'] === 'Selesai');
            $badgeBg = $isDone ? "bg-emerald-100 text-emerald-700 border-emerald-200" : "bg-blue-100 text-blue-700 border-blue-200";
        ?>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4 hover:shadow-md transition flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex flex-col items-center justify-center font-bold shadow-md">
                            <span class="text-xs leading-none uppercase"><?= date('M', strtotime($k['tanggal'])) ?></span>
                            <span class="text-lg leading-none mt-0.5"><?= date('d', strtotime($k['tanggal'])) ?></span>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-slate-800"><?= htmlspecialchars($k['nama_kegiatan']) ?></h3>
                            <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                <i class="fa-regular fa-clock text-slate-400"></i> <?= htmlspecialchars($k['waktu']) ?>
                            </p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $badgeBg ?>">
                        <?= htmlspecialchars($k['status']) ?>
                    </span>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl text-xs space-y-1 border border-slate-100">
                    <div class="flex items-center gap-2 text-slate-600">
                        <i class="fa-solid fa-location-dot text-brand-accent"></i>
                        <span class="font-semibold"><?= htmlspecialchars($k['lokasi']) ?></span>
                    </div>
                    <?php if (!empty($k['keterangan'])): ?>
                        <p class="text-slate-500 pt-1 border-t border-slate-200/50 mt-1"><?= htmlspecialchars($k['keterangan']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <?php if (!$isDone): ?>
                        <a href="kegiatan.php?action=complete&id=<?= $k['id'] ?>" class="flex-1 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-xs font-semibold text-center transition">
                            <i class="fa-solid fa-check mr-1"></i> Tandai Selesai
                        </a>
                    <?php else: ?>
                        <span class="flex-1 text-slate-400 text-xs italic text-center">Kegiatan Telah Selesai</span>
                    <?php endif; ?>
                    <a href="kegiatan.php?action=delete&id=<?= $k['id'] ?>" onclick="return confirm('Hapus kegiatan ini?')" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded transition" title="Hapus">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                    <span><i class="fa-solid fa-user-group text-slate-300 mr-1"></i> Terbuka untuk seluruh warga RT 01</span>
                    <span class="font-semibold <?= $isDone ? 'text-emerald-600' : 'text-blue-600' ?>"><?= $isDone ? 'Selesai' : 'Diagendakan' ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal Add Kegiatan -->
<div id="modalKegiatan" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="font-bold text-slate-800 text-base">Tambah Agenda Kegiatan</h3>
            <button onclick="document.getElementById('modalKegiatan').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form action="kegiatan.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="add_kegiatan">
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Nama Kegiatan *</label>
                <input type="text" name="nama_kegiatan" required placeholder="Contoh: Kerja Bakti Massal" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Tanggal *</label>
                    <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Waktu *</label>
                    <input type="text" name="waktu" placeholder="07:30 WIB" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
                </div>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Lokasi *</label>
                <input type="text" name="lokasi" required placeholder="Balai Warga / Lapangan RT" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Deskripsi / Keterangan</label>
                <textarea name="keterangan" rows="2" placeholder="Rincian perlengkapan atau instruksi..." class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent"></textarea>
            </div>
            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalKegiatan').classList.add('hidden')" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-brand-accent text-white rounded-lg font-semibold hover:bg-blue-700">Simpan Agenda</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
