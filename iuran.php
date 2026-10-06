<?php
require_once __DIR__ . '/config/database.php';

// Action Toggle Status (Admin Only)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    require_admin();
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT status FROM iuran WHERE id = ?");
    $stmt->execute([$id]);
    $curr = $stmt->fetchColumn();
    $newStatus = ($curr === 'Lunas') ? 'Belum Bayar' : 'Lunas';

    $stmtU = $pdo->prepare("UPDATE iuran SET status = ? WHERE id = ?");
    $stmtU->execute([$newStatus, $id]);
    $_SESSION['toast_msg'] = "Status pembayaran berhasil diubah menjadi $newStatus!";
    header("Location: iuran.php");
    exit;
}

// Fetch List
$filter_periode = trim($_GET['periode'] ?? '');
$filter_status  = trim($_GET['status'] ?? '');

$query = "SELECT * FROM iuran WHERE 1=1";
$params = [];

if (!empty($filter_periode)) {
    $query .= " AND periode = ?";
    $params[] = $filter_periode;
}
if (!empty($filter_status)) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$iuran_list = $stmt->fetchAll();

// Total Summary
$total_lunas = $pdo->query("SELECT SUM(jumlah) FROM iuran WHERE status = 'Lunas'")->fetchColumn() ?: 0;
$total_pending = $pdo->query("SELECT SUM(jumlah) FROM iuran WHERE status = 'Belum Bayar'")->fetchColumn() ?: 0;

// Fetch Stored Procedure Breakdown sp_rekap_keuangan_periode
$stmtRekap = $pdo->prepare("CALL sp_rekap_keuangan_periode(?)");
$stmtRekap->execute([$filter_periode]);
$rekap_data = $stmtRekap->fetchAll();
$stmtRekap->closeCursor();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Title -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
    <div>
        <h1 class="text-xl font-extrabold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-wallet text-brand-gold"></i> <?= $isAdmin ? 'Modul Iuran & Pembayaran Warga' : 'Informasi Kas & Iuran RT' ?>
        </h1>
        <p class="text-xs text-slate-500 mt-1"><?= $isAdmin ? 'Pencatatan kas iuran bulanan, kebersihan, keamanan, dan sumbangan RT.' : 'Pantau riwayat pembayaran iuran dan rekapitulasi kas RT 01 / RW 05.' ?></p>
    </div>
    <?php if ($isAdmin): ?>
        <div class="flex gap-2">
            <a href="index.php#iuranFormSection" class="bg-brand-accent hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus-circle"></i> Catat Iuran Baru
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Stats Header -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs text-slate-500 font-semibold block mb-1">Total Kas Terkumpul (Lunas)</span>
            <h2 class="text-2xl font-extrabold text-emerald-600">Rp <?= number_format($total_lunas, 0, ',', '.') ?></h2>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl font-bold">
            <i class="fa-solid fa-vault"></i>
        </div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs text-slate-500 font-semibold block mb-1">Total Piutang Belum Bayar</span>
            <h2 class="text-2xl font-extrabold text-rose-600">Rp <?= number_format($total_pending, 0, ',', '.') ?></h2>
        </div>
        <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl font-bold">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>
</div>

<!-- Rekap Per Jenis Breakdown (Powered by Stored Procedure) -->
<div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm space-y-3">
    <h3 class="font-bold text-slate-800 text-xs flex items-center gap-2 border-b pb-2">
        <i class="fa-solid fa-chart-line text-brand-blue"></i> Breakdown Penerimaan per Jenis Iuran (Stored Procedure: sp_rekap_keuangan_periode)
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
        <?php foreach ($rekap_data as $rk): ?>
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs">
                <span class="font-semibold text-slate-700 block"><?= htmlspecialchars($rk['jenis']) ?></span>
                <span class="text-slate-400 block text-[10px]"><?= $rk['total_transaksi'] ?> Transaksi</span>
                <div class="mt-1.5 flex justify-between font-bold">
                    <span class="text-emerald-600">Rp <?= number_format($rk['total_lunas'], 0, ',', '.') ?></span>
                    <?php if ($rk['total_piutang'] > 0): ?>
                        <span class="text-rose-500 text-[10px]">(Pending: <?= number_format($rk['total_piutang'], 0, ',', '.') ?>)</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Table Card -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden space-y-4">
    <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-receipt text-brand-blue"></i> Data Riwayat Transaksi Iuran
        </h3>
        <form method="GET" action="iuran.php" class="flex flex-wrap items-center gap-2">
            <select name="periode" class="bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs">
                <option value="">-- Semua Periode --</option>
                <option value="Sep 2025" <?= $filter_periode === 'Sep 2025' ? 'selected' : '' ?>>Sep 2025</option>
                <option value="Okt 2025" <?= $filter_periode === 'Okt 2025' ? 'selected' : '' ?>>Okt 2025</option>
                <option value="Nov 2025" <?= $filter_periode === 'Nov 2025' ? 'selected' : '' ?>>Nov 2025</option>
                <option value="Des 2025" <?= $filter_periode === 'Des 2025' ? 'selected' : '' ?>>Des 2025</option>
            </select>
            <select name="status" class="bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs">
                <option value="">-- Semua Status --</option>
                <option value="Lunas" <?= $filter_status === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                <option value="Belum Bayar" <?= $filter_status === 'Belum Bayar' ? 'selected' : '' ?>>Belum Bayar</option>
            </select>
            <button type="submit" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-3 py-1.5 rounded-lg text-xs font-medium">Filter</button>
            <?php if (!empty($filter_periode) || !empty($filter_status)): ?>
                <a href="iuran.php" class="text-xs text-rose-600 hover:underline">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-100/70 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                    <th class="p-3 pl-5">No</th>
                    <th class="p-3">Nama Warga</th>
                    <th class="p-3">Jenis Iuran</th>
                    <th class="p-3">Periode</th>
                    <th class="p-3">Jumlah</th>
                    <th class="p-3">Metode</th>
                    <th class="p-3">Tanggal Bayar</th>
                    <th class="p-3">Status</th>
                    <?php if ($isAdmin): ?>
                        <th class="p-3 pr-5 text-center">Ubah Status</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                <?php foreach ($iuran_list as $idx => $row): ?>
                    <?php 
                        $isLunas = ($row['status'] === 'Lunas');
                        $badgeClass = $isLunas 
                            ? "bg-emerald-100 text-emerald-700 border-emerald-200" 
                            : "bg-rose-100 text-rose-700 border-rose-200";
                    ?>
                    <tr class="hover:bg-slate-50 transition border-b border-slate-100">
                        <td class="p-3 pl-5 text-slate-500 font-medium"><?= $idx + 1 ?></td>
                        <td class="p-3 font-semibold text-slate-800">
                            <?= htmlspecialchars($row['nama']) ?>
                            <?php if ($row['nama'] === $user['nama']): ?>
                                <span class="ml-1.5 px-1.5 py-0.5 rounded text-[9px] bg-blue-100 text-blue-700 font-bold">Saya</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-slate-600"><?= htmlspecialchars($row['jenis']) ?></td>
                        <td class="p-3 text-slate-600"><?= htmlspecialchars($row['periode']) ?></td>
                        <td class="p-3 font-medium text-slate-700">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                        <td class="p-3 text-slate-500">
                            <span class="bg-slate-100 px-2 py-0.5 rounded border border-slate-200 text-[10px]"><?= htmlspecialchars($row['metode']) ?></span>
                        </td>
                        <td class="p-3 text-slate-500"><?= htmlspecialchars($row['tanggal']) ?></td>
                        <td class="p-3">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $badgeClass ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <?php if ($isAdmin): ?>
                            <td class="p-3 pr-5 text-center">
                                <a href="iuran.php?action=toggle_status&id=<?= $row['id'] ?>" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md text-[11px] font-medium border border-slate-300 transition inline-block">
                                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Switch Status
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
