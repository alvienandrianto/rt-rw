<?php
require_once __DIR__ . '/config/database.php';

// Handle Add Asset (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_inventaris') {
    require_admin();
    $nama_barang   = trim($_POST['nama_barang']);
    $jumlah        = (int)$_POST['jumlah'];
    $kondisi       = $_POST['kondisi'] ?? 'Baik';
    $lokasi_simpan = trim($_POST['lokasi_simpan'] ?? 'Gudang RT 01');

    $stmt = $pdo->prepare("INSERT INTO inventaris (nama_barang, jumlah, kondisi, lokasi_simpan) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nama_barang, $jumlah, $kondisi, $lokasi_simpan]);
    $_SESSION['toast_msg'] = "Barang inventaris $nama_barang berhasil dicatat!";
    header("Location: inventaris.php");
    exit;
}

// Handle Delete (Admin Only)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    require_admin();
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM inventaris WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['toast_msg'] = "Barang inventaris berhasil dihapus.";
    $_SESSION['toast_type'] = "rose";
    header("Location: inventaris.php");
    exit;
}

$inventaris_list = $pdo->query("SELECT * FROM inventaris ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Title -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
    <div>
        <h1 class="text-xl font-extrabold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-boxes-stacked text-brand-accent"></i> Inventaris & Aset Barang RT
        </h1>
        <p class="text-xs text-slate-500 mt-1">Pencatatan fasilitas tenda, kursi, sound system, dan barang inventaris bersama.</p>
    </div>
    <?php if ($isAdmin): ?>
        <button onclick="document.getElementById('modalInventaris').classList.remove('hidden')" class="bg-brand-accent hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition flex items-center gap-1.5">
            <i class="fa-solid fa-plus-circle"></i> Tambah Barang Inventaris
        </button>
    <?php endif; ?>
</div>

<!-- Table Card -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-warehouse text-brand-blue"></i> Daftar Barang Inventaris Terdaftar
        </h3>
        <?php if (!$isAdmin): ?>
            <span class="text-[11px] text-slate-400 flex items-center gap-1">
                <i class="fa-solid fa-eye text-slate-300"></i> Mode: Hanya Lihat
            </span>
        <?php endif; ?>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-100/70 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                    <th class="p-3 pl-5">No</th>
                    <th class="p-3">Nama Barang</th>
                    <th class="p-3">Jumlah Unit</th>
                    <th class="p-3">Kondisi</th>
                    <th class="p-3">Lokasi Penyimpanan</th>
                    <?php if ($isAdmin): ?>
                        <th class="p-3 pr-5 text-center">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                <?php foreach ($inventaris_list as $idx => $inv): ?>
                    <?php 
                        $badgeBg = "bg-emerald-100 text-emerald-700";
                        if ($inv['kondisi'] === 'Rusak Ringan') $badgeBg = "bg-amber-100 text-amber-700";
                        if ($inv['kondisi'] === 'Rusak Berat') $badgeBg = "bg-rose-100 text-rose-700";
                    ?>
                    <tr class="hover:bg-slate-50 transition border-b border-slate-100">
                        <td class="p-3 pl-5 text-slate-500 font-medium"><?= $idx + 1 ?></td>
                        <td class="p-3 font-semibold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-box text-brand-accent"></i>
                            <?= htmlspecialchars($inv['nama_barang']) ?>
                        </td>
                        <td class="p-3 font-bold text-slate-700"><?= $inv['jumlah'] ?> unit</td>
                        <td class="p-3">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $badgeBg ?>">
                                <?= htmlspecialchars($inv['kondisi']) ?>
                            </span>
                        </td>
                        <td class="p-3 text-slate-600"><?= htmlspecialchars($inv['lokasi_simpan']) ?></td>
                        <?php if ($isAdmin): ?>
                            <td class="p-3 pr-5 text-center">
                                <a href="inventaris.php?action=delete&id=<?= $inv['id'] ?>" onclick="return confirm('Hapus barang ini?')" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded transition" title="Hapus">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Modal Add (Admin Only) -->
<div id="modalInventaris" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="font-bold text-slate-800 text-base">Tambah Barang Inventaris</h3>
            <button onclick="document.getElementById('modalInventaris').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form action="inventaris.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="add_inventaris">
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Nama Barang *</label>
                <input type="text" name="nama_barang" required placeholder="Contoh: Tenda Lipat 3x3m" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Jumlah Unit *</label>
                    <input type="number" name="jumlah" required value="1" min="1" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Kondisi</label>
                    <select name="kondisi" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
                        <option value="Baik">Baik</option>
                        <option value="Rusak Ringan">Rusak Ringan</option>
                        <option value="Rusak Berat">Rusak Berat</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Lokasi Penyimpanan</label>
                <input type="text" name="lokasi_simpan" value="Gudang Balai Warga" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
            </div>
            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalInventaris').classList.add('hidden')" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-brand-accent text-white rounded-lg font-semibold hover:bg-blue-700">Simpan Barang</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
