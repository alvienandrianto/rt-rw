<?php
require_once __DIR__ . '/config/database.php';

// Handle POST Request for Adding/Updating Iuran (Using Stored Procedure sp_catat_iuran)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_iuran') {
    $id        = $_POST['id'] ?? null;
    $nama      = trim($_POST['nama']);
    $jenis     = trim($_POST['jenis']);
    $periode   = trim($_POST['periode']);
    $jumlah    = (int) $_POST['jumlah'];
    $tanggal   = $_POST['tanggal'];
    $metode    = $_POST['metode'];
    $keterangan= trim($_POST['keterangan'] ?? '');
    $status    = $_POST['status'] ?? 'Lunas';

    if ($id) {
        $warga_id = null;
        $stmtW = $pdo->prepare("SELECT id FROM warga WHERE nama = ? LIMIT 1");
        $stmtW->execute([$nama]);
        $wRow = $stmtW->fetch();
        if ($wRow) $warga_id = $wRow['id'];

        $stmt = $pdo->prepare("UPDATE iuran SET warga_id=?, nama=?, jenis=?, periode=?, jumlah=?, metode=?, tanggal=?, keterangan=?, status=? WHERE id=?");
        $stmt->execute([$warga_id, $nama, $jenis, $periode, $jumlah, $metode, $tanggal, $keterangan, $status, $id]);
        $_SESSION['toast_msg'] = "Data iuran $nama berhasil diperbarui!";
    } else {
        $stmt = $pdo->prepare("CALL sp_catat_iuran(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $jenis, $periode, $jumlah, $metode, $tanggal, $keterangan, $status]);
        $_SESSION['toast_msg'] = "Berhasil mencatat iuran warga via Stored Procedure!";
    }
    header("Location: index.php");
    exit;
}

// Handle GET Actions (Delete, Verify Surat using Stored Procedure sp_verifikasi_surat)
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete_iuran' && isset($_GET['id'])) {
        $delete_id = (int)$_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM iuran WHERE id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['toast_msg'] = "Data iuran berhasil dihapus.";
        $_SESSION['toast_type'] = "rose";
        header("Location: index.php");
        exit;
    }

    if ($_GET['action'] === 'verify_surat' && isset($_GET['id'])) {
        $surat_id = (int)$_GET['id'];
        $stmt = $pdo->prepare("CALL sp_verifikasi_surat(?, 'Verified')");
        $stmt->execute([$surat_id]);
        $_SESSION['toast_msg'] = "Surat permohonan berhasil diverifikasi via Stored Procedure!";
        header("Location: index.php");
        exit;
    }
}

// Check Edit Mode for Form
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmtE = $pdo->prepare("SELECT * FROM iuran WHERE id = ?");
    $stmtE->execute([$edit_id]);
    $edit_data = $stmtE->fetch();
}

// Fetch Warga list for Select dropdown
$warga_list = $pdo->query("SELECT * FROM warga ORDER BY nama ASC")->fetchAll();

// Fetch Pending Surat Requests
$surat_pending = $pdo->query("SELECT * FROM surat WHERE status = 'Menunggu' ORDER BY id DESC")->fetchAll();
$surat_pending_count = count($surat_pending);

// Fetch Stats Summary via Stored Procedure sp_get_dashboard_stats
$stmtStats = $pdo->query("CALL sp_get_dashboard_stats()");
$dashboard_stats = $stmtStats->fetch();
$stmtStats->closeCursor();

$total_iuran_masuk    = $dashboard_stats['total_iuran_masuk'] ?? 0;
$surat_verified_count = $dashboard_stats['surat_verified_count'] ?? 0;
$belum_bayar_count    = $dashboard_stats['belum_bayar_count'] ?? 0;
$total_warga_count    = $dashboard_stats['total_warga_count'] ?? 0;

// Fetch Iuran Table Data with optional Search
$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $stmtI = $pdo->prepare("SELECT * FROM iuran WHERE nama LIKE ? OR jenis LIKE ? OR periode LIKE ? ORDER BY id DESC");
    $stmtI->execute(["%$search%", "%$search%", "%$search%"]);
    $iuran_data = $stmtI->fetchAll();
} else {
    $iuran_data = $pdo->query("SELECT * FROM iuran ORDER BY id DESC")->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<div class="bg-gradient-to-r from-blue-700 via-brand-accent to-blue-500 rounded-2xl p-5 sm:p-6 text-white shadow-lg relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div class="z-10 max-w-xl">
        <span class="bg-blue-800/60 text-blue-100 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider mb-2 inline-block">Smart RT Mobile Native</span>
        <h1 class="text-xl sm:text-2xl font-bold mb-2">Pencatatan Iuran & Verifikasi Surat</h1>
        <p class="text-blue-100 text-xs sm:text-sm leading-relaxed mb-4 opacity-90">
            Kelola pencatatan iuran warga dan verifikasi surat permohonan dengan mudah, transparan, dan cepat dalam satu dasbor terpadu Smart RT.
        </p>
        <div class="flex flex-wrap gap-2 w-full sm:w-auto">
            <a href="#iuranFormSection" class="flex-1 sm:flex-initial text-center bg-white text-brand-blue hover:bg-blue-50 font-semibold px-4 py-2.5 sm:py-2 rounded-xl text-xs shadow transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-plus-circle"></i> Pencatatan Iuran
            </a>
            <a href="#verifikasiSuratSection" class="flex-1 sm:flex-initial text-center bg-blue-800/50 hover:bg-blue-800/70 border border-white/20 font-semibold px-4 py-2.5 sm:py-2 rounded-xl text-xs transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-check-double"></i> Verifikasi Surat (<?= $surat_pending_count ?>)
            </a>
        </div>
    </div>
    <!-- Banner Illustration Overlay -->
    <div class="hidden md:block z-10 opacity-90">
        <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-5xl text-white shadow-2xl transform rotate-3 hover:rotate-0 transition duration-300">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
    </div>
    <div class="absolute -bottom-10 -right-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>
</div>

<!-- GRID CONTAINER FOR FORM, TABLE & RIGHT SIDEBAR -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">

    <!-- LEFT & CENTER COLUMNS (2 Columns width on lg) -->
    <div class="lg:col-span-2 space-y-4 sm:space-y-6">

        <!-- FORM PENCATATAN IURAN -->
        <div id="iuranFormSection" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-4 sm:px-5 py-3.5 sm:py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-xs sm:text-sm flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-brand-accent"></i> 
                    <?= $edit_data ? 'Edit Data Iuran Warga' : 'Form Pencatatan Iuran' ?>
                </h3>
                <span class="text-[10px] sm:text-xs text-slate-400">* Wajib diisi</span>
            </div>
            <form action="index.php" method="POST" class="p-4 sm:p-5 space-y-3.5 sm:space-y-4">
                <input type="hidden" name="action" value="save_iuran">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <!-- Pilih Warga -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Pilih Warga *</label>
                        <select name="nama" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 sm:py-2 text-xs text-slate-700 focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none transition">
                            <option value="">-- Pilih / Cari nama warga --</option>
                            <?php foreach ($warga_list as $w): ?>
                                <option value="<?= htmlspecialchars($w['nama']) ?>" <?= ($edit_data && $edit_data['nama'] === $w['nama']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($w['nama']) ?> (<?= htmlspecialchars($w['blok_no']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Jenis Iuran -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Iuran *</label>
                        <select name="jenis" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 sm:py-2 text-xs text-slate-700 focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none transition">
                            <option value="">Pilih jenis iuran</option>
                            <?php 
                            $jenis_options = ["Iuran Bulanan", "Iuran Kebersihan", "Iuran Keamanan", "Sumbangan PHBN"];
                            foreach ($jenis_options as $opt):
                            ?>
                                <option value="<?= $opt ?>" <?= ($edit_data && $edit_data['jenis'] === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Periode -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Periode *</label>
                        <select name="periode" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 sm:py-2 text-xs text-slate-700 focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none transition">
                            <?php 
                            $periode_options = ["Sep 2025", "Okt 2025", "Nov 2025", "Des 2025", "Jan 2026"];
                            foreach ($periode_options as $popt):
                            ?>
                                <option value="<?= $popt ?>" <?= ($edit_data && $edit_data['periode'] === $popt) ? 'selected' : '' ?>><?= $popt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Jumlah -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Jumlah (Rp) *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 sm:top-2 text-xs text-slate-400 font-medium">Rp</span>
                            <input type="number" name="jumlah" required value="<?= $edit_data ? $edit_data['jumlah'] : '50000' ?>" placeholder="50.000" class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3 py-2.5 sm:py-2 text-xs text-slate-700 focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none transition">
                        </div>
                    </div>
                    <!-- Tanggal Pembayaran -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Tanggal Pembayaran *</label>
                        <input type="date" name="tanggal" value="<?= $edit_data ? $edit_data['tanggal'] : date('Y-m-d') ?>" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 sm:py-2 text-xs text-slate-700 focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none transition">
                    </div>
                    <!-- Metode Pembayaran -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Metode Pembayaran *</label>
                        <select name="metode" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 sm:py-2 text-xs text-slate-700 focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none transition">
                            <option value="Transfer" <?= ($edit_data && $edit_data['metode'] === 'Transfer') ? 'selected' : '' ?>>Transfer Bank / QRIS</option>
                            <option value="Tunai" <?= ($edit_data && $edit_data['metode'] === 'Tunai') ? 'selected' : '' ?>>Tunai / Cash</option>
                        </select>
                    </div>
                </div>

                <!-- Status & Keterangan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Status Pembayaran</label>
                        <select name="status" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 sm:py-2 text-xs text-slate-700 focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none transition">
                            <option value="Lunas" <?= ($edit_data && $edit_data['status'] === 'Lunas') ? 'selected' : '' ?>>Lunas</option>
                            <option value="Belum Bayar" <?= ($edit_data && $edit_data['status'] === 'Belum Bayar') ? 'selected' : '' ?>>Belum Bayar</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Keterangan Opsional</label>
                        <input type="text" name="keterangan" value="<?= htmlspecialchars($edit_data['keterangan'] ?? '') ?>" placeholder="Keterangan..." class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 sm:py-2 text-xs text-slate-700 focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none transition">
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <?php if ($edit_data): ?>
                        <a href="index.php" class="px-4 py-2.5 sm:py-2 rounded-xl text-xs font-medium border border-slate-300 text-slate-600 hover:bg-slate-100 transition">
                            Batal Edit
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 sm:py-2 rounded-xl text-xs font-semibold bg-brand-accent hover:bg-blue-700 text-white shadow-md hover:shadow transition flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-paper-plane"></i> <?= $edit_data ? 'Update Data' : 'Simpan Data' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- RIWAYAT PENCATATAN IURAN TABLE -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-xs sm:text-sm flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-brand-blue"></i> Riwayat Pencatatan Iuran
                </h3>
                <form method="GET" action="index.php" class="flex items-center gap-2 w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-56">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama / periode..." class="w-full bg-white border border-slate-300 rounded-xl pl-8 pr-3 py-2 sm:py-1.5 text-xs focus:ring-2 focus:ring-brand-accent focus:outline-none">
                        <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-3 sm:top-2.5 text-slate-400 text-xs"></i>
                    </div>
                    <button type="submit" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-3 py-2 sm:py-1.5 rounded-xl text-xs font-medium">Cari</button>
                    <?php if (!empty($search)): ?>
                        <a href="index.php" class="text-xs text-rose-600 hover:underline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-slate-100/70 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                            <th class="p-3 pl-4 sm:pl-5">No</th>
                            <th class="p-3">Nama Warga</th>
                            <th class="p-3">Jenis Iuran</th>
                            <th class="p-3">Periode</th>
                            <th class="p-3">Jumlah</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 pr-4 sm:pr-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <?php if (empty($iuran_data)): ?>
                            <tr>
                                <td colspan="7" class="p-6 text-center text-slate-400 italic">
                                    Belum ada data pencatatan iuran.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($iuran_data as $idx => $row): ?>
                                <?php 
                                    $isLunas = ($row['status'] === 'Lunas');
                                    $badgeClass = $isLunas 
                                        ? "bg-emerald-100 text-emerald-700 border-emerald-200" 
                                        : "bg-rose-100 text-rose-700 border-rose-200";
                                ?>
                                <tr class="hover:bg-slate-50 transition border-b border-slate-100">
                                    <td class="p-3 pl-4 sm:pl-5 text-slate-500 font-medium"><?= $idx + 1 ?></td>
                                    <td class="p-3 font-semibold text-slate-800"><?= htmlspecialchars($row['nama']) ?></td>
                                    <td class="p-3 text-slate-600"><?= htmlspecialchars($row['jenis']) ?></td>
                                    <td class="p-3 text-slate-600"><?= htmlspecialchars($row['periode']) ?></td>
                                    <td class="p-3 font-medium text-slate-700">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $badgeClass ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3 pr-4 sm:pr-5 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a href="index.php?edit_id=<?= $row['id'] ?>#iuranFormSection" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a href="index.php?action=delete_iuran&id=<?= $row['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded transition" title="Hapus">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-4 sm:px-5 py-3 border-t border-slate-100 text-xs text-slate-500">
                <span>Menampilkan <strong>1-<?= count($iuran_data) ?></strong> dari <strong><?= count($iuran_data) ?></strong> data</span>
            </div>
        </div>

    </div>

    <!-- RIGHT COLUMN (1 Column width on lg) -->
    <div class="space-y-4 sm:space-y-6">

        <!-- VERIFIKASI SURAT PANEL -->
        <div id="verifikasiSuratSection" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-4 sm:px-5 py-3.5 sm:py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-xs sm:text-sm flex items-center gap-2">
                    <i class="fa-solid fa-file-circle-check text-blue-600"></i> Verifikasi Surat
                </h3>
                <span class="bg-amber-100 text-amber-700 text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $surat_pending_count ?> Menunggu</span>
            </div>

            <div class="p-3.5 sm:p-4 space-y-3 max-h-[420px] overflow-y-auto">
                <?php if (empty($surat_pending)): ?>
                    <div class="p-4 bg-slate-50 text-center rounded-xl border border-dashed text-xs text-slate-400">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-lg mb-1 block"></i>
                        Tidak ada permohonan surat yang menunggu verifikasi.
                    </div>
                <?php else: ?>
                    <?php foreach ($surat_pending as $surat): ?>
                        <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl hover:shadow-sm transition space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-xs text-slate-800"><?= htmlspecialchars($surat['jenis_surat']) ?></h4>
                                        <p class="text-[11px] text-slate-500"><?= htmlspecialchars($surat['pemohon']) ?></p>
                                    </div>
                                </div>
                                <span class="bg-amber-100 text-amber-700 text-[10px] font-semibold px-2 py-0.5 rounded">Menunggu</span>
                            </div>
                            <div class="text-[10px] text-slate-400 flex items-center justify-between pt-1 border-t border-slate-200/50">
                                <span>No: <?= htmlspecialchars($surat['no_surat']) ?></span>
                                <span><?= date('d M Y', strtotime($surat['tanggal_pengajuan'])) ?></span>
                            </div>
                            <div class="flex gap-2 pt-1">
                                <button onclick="previewSurat('<?= htmlspecialchars($surat['jenis_surat']) ?>', '<?= htmlspecialchars($surat['pemohon']) ?>', '<?= htmlspecialchars($surat['no_surat']) ?>', '<?= htmlspecialchars($surat['keperluan']) ?>')" class="flex-1 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-md text-[11px] font-medium transition flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-eye"></i> Lihat
                                </button>
                                <a href="index.php?action=verify_surat&id=<?= $surat['id'] ?>" class="flex-1 py-1.5 bg-brand-accent hover:bg-blue-700 text-white rounded-md text-[11px] font-medium transition flex items-center justify-center gap-1 shadow-xs">
                                    <i class="fa-solid fa-check"></i> Verifikasi
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="p-3 border-t border-slate-100 text-center">
                <a href="surat.php" class="text-xs font-semibold text-brand-accent hover:underline flex items-center justify-center gap-1">
                    Lihat Semua Permohonan Surat <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>

        <!-- RINGKASAN HARI INI STATS CARDS -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 sm:p-5 space-y-3 sm:space-y-4">
            <h3 class="font-bold text-slate-800 text-xs sm:text-sm flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-brand-gold"></i> Ringkasan System
            </h3>

            <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                <!-- Stat 1 -->
                <div class="p-3 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200/60 flex flex-col justify-between">
                    <span class="text-[10px] sm:text-[11px] font-medium text-amber-700">Total Kas Iuran</span>
                    <div class="mt-1.5 sm:mt-2">
                        <span class="text-sm sm:text-base font-extrabold text-slate-800">Rp <?= number_format($total_iuran_masuk, 0, ',', '.') ?></span>
                    </div>
                </div>

                <!-- Stat 2 -->
                <div class="p-3 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200/60 flex flex-col justify-between">
                    <span class="text-[10px] sm:text-[11px] font-medium text-blue-700">Surat Verified</span>
                    <div class="mt-1.5 sm:mt-2">
                        <span class="text-sm sm:text-base font-extrabold text-slate-800"><?= $surat_verified_count ?> surat</span>
                    </div>
                </div>

                <!-- Stat 3 -->
                <div class="p-3 rounded-xl bg-gradient-to-br from-rose-50 to-red-50 border border-rose-200/60 flex flex-col justify-between">
                    <span class="text-[10px] sm:text-[11px] font-medium text-rose-700">Belum Bayar</span>
                    <div class="mt-1.5 sm:mt-2">
                        <span class="text-sm sm:text-base font-extrabold text-slate-800"><?= $belum_bayar_count ?> warga</span>
                    </div>
                </div>

                <!-- Stat 4 -->
                <div class="p-3 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200/60 flex flex-col justify-between">
                    <span class="text-[10px] sm:text-[11px] font-medium text-emerald-700">Total Warga</span>
                    <div class="mt-1.5 sm:mt-2">
                        <span class="text-sm sm:text-base font-extrabold text-slate-800"><?= $total_warga_count ?> KK</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
function previewSurat(jenisSurat, pemohon, noSurat, keperluan) {
    document.getElementById('modalTitle').innerText = jenisSurat;
    document.getElementById('modalBody').innerHTML = `
        <div class="p-3 bg-slate-50 rounded-lg space-y-2 border border-slate-200">
            <p><strong>Nomor Surat:</strong> ${noSurat}</p>
            <p><strong>Pemohon:</strong> ${pemohon}</p>
            <p><strong>Keperluan:</strong> ${keperluan}</p>
            <p><strong>Status NIK:</strong> Terverifikasi di Database RT</p>
        </div>
        <p class="text-slate-500 text-[11px] leading-relaxed">Dokumen ini dibuat secara otomatis oleh sistem Smart RT dan siap diotorisasi oleh Ketua RT.</p>
    `;
    document.getElementById('modalSurat').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
