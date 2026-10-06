<?php
require_once __DIR__ . '/config/database.php';

// Handle Action via Stored Procedure sp_verifikasi_surat (Admin Only)
if (isset($_GET['action']) && isset($_GET['id'])) {
    require_admin();
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'verify') {
        $stmt = $pdo->prepare("CALL sp_verifikasi_surat(?, 'Verified')");
        $stmt->execute([$id]);
        $_SESSION['toast_msg'] = "Surat berhasil diverifikasi via Stored Procedure!";
    } elseif ($_GET['action'] === 'reject') {
        $stmt = $pdo->prepare("CALL sp_verifikasi_surat(?, 'Ditolak')");
        $stmt->execute([$id]);
        $_SESSION['toast_msg'] = "Surat telah ditolak via Stored Procedure.";
        $_SESSION['toast_type'] = "rose";
    } elseif ($_GET['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM surat WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['toast_msg'] = "Permohonan surat berhasil dihapus.";
        $_SESSION['toast_type'] = "rose";
    }
    header("Location: surat.php");
    exit;
}

// Handle Form Submission New Surat (Leverages before_surat_insert Trigger for auto no_surat)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_surat') {
    $jenis_surat = trim($_POST['jenis_surat']);
    $pemohon     = trim($_POST['pemohon']);
    $nik         = trim($_POST['nik'] ?? '');
    $keperluan   = trim($_POST['keperluan']);
    $tanggal     = $_POST['tanggal_pengajuan'] ?: date('Y-m-d');

    // We leave no_surat NULL/empty so DB Trigger before_surat_insert auto-generates official number!
    $stmt = $pdo->prepare("INSERT INTO surat (no_surat, jenis_surat, pemohon, nik, keperluan, tanggal_pengajuan, status) VALUES (NULL, ?, ?, ?, ?, ?, 'Menunggu')");
    $stmt->execute([$jenis_surat, $pemohon, $nik, $keperluan, $tanggal]);

    $_SESSION['toast_msg'] = "Permohonan surat baru berhasil dibuat (Nomor Surat di-generate otomatis via Trigger DB)!";
    header("Location: surat.php");
    exit;
}

// Fetch Surat
$surat_list = $pdo->query("SELECT * FROM surat ORDER BY id DESC")->fetchAll();
$warga_list = $pdo->query("SELECT * FROM warga ORDER BY nama ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Title -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
    <div>
        <h1 class="text-xl font-extrabold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-file-lines text-brand-blue"></i> Layanan & Verifikasi Surat RT
        </h1>
        <p class="text-xs text-slate-500 mt-1">Verifikasi pengajuan surat domisili, SKU, dan pengantar KTP (Auto No. Surat via Trigger DB).</p>
    </div>
    <button onclick="document.getElementById('modalNewSurat').classList.remove('hidden')" class="bg-brand-accent hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition flex items-center gap-1.5">
        <i class="fa-solid fa-plus-circle"></i> Buat Permohonan Surat
    </button>
</div>

<!-- Surat Grid List -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($surat_list as $s): ?>
        <?php 
            $statusClass = "bg-amber-100 text-amber-700 border-amber-200";
            if ($s['status'] === 'Verified') $statusClass = "bg-emerald-100 text-emerald-700 border-emerald-200";
            if ($s['status'] === 'Ditolak') $statusClass = "bg-rose-100 text-rose-700 border-rose-200";
        ?>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4 hover:shadow-md transition">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-brand-blue flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-file-signature"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-800"><?= htmlspecialchars($s['jenis_surat']) ?></h3>
                        <p class="text-xs text-slate-500 font-medium"><?= htmlspecialchars($s['pemohon']) ?></p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $statusClass ?>">
                    <?= htmlspecialchars($s['status']) ?>
                </span>
            </div>

            <div class="p-3 bg-slate-50 rounded-lg text-xs space-y-1.5 border border-slate-100">
                <div class="flex justify-between text-slate-500">
                    <span>No. Surat:</span>
                    <strong class="text-slate-700 font-mono"><?= htmlspecialchars($s['no_surat']) ?></strong>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>Tgl Pengajuan:</span>
                    <strong class="text-slate-700"><?= date('d M Y', strtotime($s['tanggal_pengajuan'])) ?></strong>
                </div>
                <div class="pt-1 border-t text-slate-600">
                    <span class="text-slate-400 block text-[10px]">Keperluan:</span>
                    <p class="italic text-[11px]"><?= htmlspecialchars($s['keperluan'] ?: '-') ?></p>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100">
                <?php if ($isAdmin): ?>
                    <?php if ($s['status'] === 'Menunggu'): ?>
                        <a href="surat.php?action=verify&id=<?= $s['id'] ?>" class="flex-1 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-xs font-semibold text-center transition shadow-xs">
                            <i class="fa-solid fa-check mr-1"></i> Verifikasi
                        </a>
                        <a href="surat.php?action=reject&id=<?= $s['id'] ?>" onclick="return confirm('Tolak permohonan surat ini?')" class="py-1.5 px-3 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-md text-xs font-semibold text-center transition">
                            Tolak
                        </a>
                    <?php else: ?>
                        <span class="flex-1 py-1.5 text-slate-400 text-xs font-medium italic flex items-center justify-center gap-1">
                            <i class="fa-solid fa-circle-info"></i> Status: <?= $s['status'] ?>
                        </span>
                    <?php endif; ?>
                    <a href="surat.php?action=delete&id=<?= $s['id'] ?>" onclick="return confirm('Hapus surat ini?')" class="p-1.5 text-slate-400 hover:text-rose-600 rounded transition" title="Hapus">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>
                <?php else: ?>
                    <div class="w-full flex items-center justify-between text-xs py-1">
                        <span class="text-slate-500 font-medium">
                            <i class="fa-solid fa-shield text-slate-400 mr-1"></i> Verifikator: Admin RT
                        </span>
                        <?php if ($s['pemohon'] === $user['nama']): ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">Surat Saya</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal Add Surat -->
<div id="modalNewSurat" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="font-bold text-slate-800 text-base">Buat Permohonan Surat Baru</h3>
            <button onclick="document.getElementById('modalNewSurat').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form action="surat.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="add_surat">
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Jenis Surat *</label>
                <select name="jenis_surat" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
                    <option value="Surat Keterangan Domisili">Surat Keterangan Domisili</option>
                    <option value="Surat Keterangan Usaha">Surat Keterangan Usaha</option>
                    <option value="Surat Pengantar KTP">Surat Pengantar KTP</option>
                    <option value="Surat Pengantar SKCK">Surat Pengantar SKCK</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Nama Pemohon *</label>
                <?php if ($isAdmin): ?>
                    <select name="pemohon" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
                        <?php foreach ($warga_list as $w): ?>
                            <option value="<?= htmlspecialchars($w['nama']) ?>"><?= htmlspecialchars($w['nama']) ?> (<?= htmlspecialchars($w['blok_no']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" name="pemohon" readonly value="<?= htmlspecialchars($user['nama']) ?>" class="w-full bg-slate-100 border border-slate-300 rounded-lg p-2 font-semibold text-slate-700 cursor-not-allowed">
                    <input type="hidden" name="nik" value="<?= htmlspecialchars($user['nik'] ?? '') ?>">
                <?php endif; ?>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Keperluan *</label>
                <textarea name="keperluan" required rows="2" placeholder="Jelaskan keperluan pengajuan surat..." class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent"></textarea>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Tanggal Pengajuan</label>
                <input type="date" name="tanggal_pengajuan" value="<?= date('Y-m-d') ?>" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-brand-accent">
            </div>
            <div class="pt-3 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalNewSurat').classList.add('hidden')" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-brand-accent text-white rounded-lg font-semibold hover:bg-blue-700">Simpan Surat</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
