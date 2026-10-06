<?php
require_once __DIR__ . '/config/database.php';

// Handle Add Complaint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_pengaduan') {
    $nama_pelapor = trim($_POST['nama_pelapor']);
    $judul        = trim($_POST['judul']);
    $isi          = trim($_POST['isi']);
    $tanggal      = date('Y-m-d');

    $stmt = $pdo->prepare("INSERT INTO pengaduan (nama_pelapor, judul, isi, tanggal, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->execute([$nama_pelapor, $judul, $isi, $tanggal]);
    $_SESSION['toast_msg'] = "Pengaduan warga berhasil ditambahkan!";
    header("Location: pengaduan.php");
    exit;
}

// Handle Update Status / Delete (Admin Only)
if (isset($_GET['action']) && isset($_GET['id'])) {
    require_admin();
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'status' && isset($_GET['val'])) {
        $val = $_GET['val'];
        $stmt = $pdo->prepare("UPDATE pengaduan SET status = ? WHERE id = ?");
        $stmt->execute([$val, $id]);
        $_SESSION['toast_msg'] = "Status pengaduan diubah menjadi $val";
    } elseif ($_GET['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM pengaduan WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['toast_msg'] = "Pengaduan berhasil dihapus.";
        $_SESSION['toast_type'] = "rose";
    }
    header("Location: pengaduan.php");
    exit;
}

$pengaduan_list = $pdo->query("SELECT * FROM pengaduan ORDER BY id DESC")->fetchAll();
$warga_list     = $pdo->query("SELECT * FROM warga ORDER BY nama ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Title -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
    <div>
        <h1 class="text-xl font-extrabold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-bullhorn text-brand-blue"></i> Pengaduan & Laporan Warga
        </h1>
        <p class="text-xs text-slate-500 mt-1">Layanan penerimaan aspirasi, fasilitas mati, keluhan lingkungan, dan tindak lanjut pengurus.</p>
    </div>
    <button onclick="document.getElementById('modalPengaduan').classList.remove('hidden')" class="bg-brand-accent hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition flex items-center gap-1.5">
        <i class="fa-solid fa-plus-circle"></i> <?= $isAdmin ? 'Buat Laporan / Pengaduan' : 'Buat Laporan Baru' ?>
    </button>
</div>

<!-- List Cards -->
<div class="space-y-4">
    <?php foreach ($pengaduan_list as $p): ?>
        <?php 
            $statusBg = "bg-amber-100 text-amber-700 border-amber-200";
            if ($p['status'] === 'Proses') $statusBg = "bg-blue-100 text-blue-700 border-blue-200";
            if ($p['status'] === 'Selesai') $statusBg = "bg-emerald-100 text-emerald-700 border-emerald-200";
            $isOwner = ($p['nama_pelapor'] === $user['nama']);
        ?>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-comment-dots"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-800">
                            <?= htmlspecialchars($p['judul']) ?>
                            <?php if (!$isAdmin && $isOwner): ?>
                                <span class="ml-1.5 px-1.5 py-0.5 rounded text-[9px] bg-blue-100 text-blue-700 font-bold">Laporan Saya</span>
                            <?php endif; ?>
                        </h3>
                        <p class="text-xs text-slate-500">Pelapor: <strong><?= htmlspecialchars($p['nama_pelapor']) ?></strong> • <?= date('d M Y', strtotime($p['tanggal'])) ?></p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $statusBg ?>">
                    <?= htmlspecialchars($p['status']) ?>
                </span>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl text-xs text-slate-700 leading-relaxed border border-slate-100">
                <?= nl2br(htmlspecialchars($p['isi'])) ?>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <?php if ($isAdmin): ?>
                    <div class="flex items-center gap-1 text-xs">
                        <span class="text-slate-400 mr-1">Ubah Status:</span>
                        <a href="pengaduan.php?action=status&id=<?= $p['id'] ?>&val=Pending" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200">Pending</a>
                        <a href="pengaduan.php?action=status&id=<?= $p['id'] ?>&val=Proses" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200">Proses</a>
                        <a href="pengaduan.php?action=status&id=<?= $p['id'] ?>&val=Selesai" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200">Selesai</a>
                    </div>
                    <a href="pengaduan.php?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Hapus laporan ini?')" class="p-1 text-rose-500 hover:bg-rose-50 rounded transition" title="Hapus">
                        <i class="fa-solid fa-trash-can text-xs"></i>
                    </a>
                <?php else: ?>
                    <div class="w-full flex items-center justify-between text-[11px] text-slate-400">
                        <span><i class="fa-solid fa-shield text-slate-300 mr-1"></i> Ditangani oleh Admin RT</span>
                        <span class="font-semibold <?= $p['status'] === 'Selesai' ? 'text-emerald-600' : ($p['status'] === 'Proses' ? 'text-blue-600' : 'text-amber-600') ?>">
                            <?= htmlspecialchars($p['status']) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal Add Pengaduan -->
<div id="modalPengaduan" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="font-bold text-slate-800 text-base">Buat Laporan / Pengaduan</h3>
            <button onclick="document.getElementById('modalPengaduan').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form action="pengaduan.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="add_pengaduan">
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Nama Pelapor *</label>
                <?php if ($isAdmin): ?>
                    <select name="nama_pelapor" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
                        <?php foreach ($warga_list as $w): ?>
                            <option value="<?= htmlspecialchars($w['nama']) ?>"><?= htmlspecialchars($w['nama']) ?> (<?= htmlspecialchars($w['blok_no']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" name="nama_pelapor" readonly value="<?= htmlspecialchars($user['nama']) ?>" class="w-full bg-slate-100 border border-slate-300 rounded-lg p-2 font-semibold text-slate-700 cursor-not-allowed">
                <?php endif; ?>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Judul Pengaduan / Keluhan *</label>
                <input type="text" name="judul" required placeholder="Contoh: Lampu Penerangan Jalan Matikan" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Rincian Pengaduan *</label>
                <textarea name="isi" required rows="3" placeholder="Jelaskan detail pengaduan..." class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent"></textarea>
            </div>
            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalPengaduan').classList.add('hidden')" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-brand-accent text-white rounded-lg font-semibold hover:bg-blue-700">Kirim Laporan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
