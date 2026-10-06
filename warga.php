<?php
require_once __DIR__ . '/config/database.php';

// Handle Add/Edit Warga (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_warga') {
    require_admin();
    $id           = $_POST['id'] ?? null;
    $nama         = trim($_POST['nama']);
    $blok_no      = trim($_POST['blok_no']);
    $nik          = trim($_POST['nik'] ?? '');
    $no_kk        = trim($_POST['no_kk'] ?? '');
    $no_hp        = trim($_POST['no_hp'] ?? '');
    $status_warga = $_POST['status_warga'] ?? 'Tetap';

    if ($id) {
        $stmt = $pdo->prepare("UPDATE warga SET nama=?, blok_no=?, nik=?, no_kk=?, no_hp=?, status_warga=? WHERE id=?");
        $stmt->execute([$nama, $blok_no, $nik, $no_kk, $no_hp, $status_warga, $id]);
        $_SESSION['toast_msg'] = "Data warga $nama berhasil diperbarui!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO warga (nama, blok_no, nik, no_kk, no_hp, status_warga) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $blok_no, $nik, $no_kk, $no_hp, $status_warga]);
        $_SESSION['toast_msg'] = "Data warga baru $nama berhasil ditambahkan!";
    }
    header("Location: warga.php");
    exit;
}

// Handle Delete Warga (Admin Only, Triggers before_warga_delete trigger in MySQL)
if (isset($_GET['action']) && $_GET['action'] === 'delete_warga' && isset($_GET['id'])) {
    require_admin();
    $delete_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM warga WHERE id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['toast_msg'] = "Data warga berhasil dihapus.";
        $_SESSION['toast_type'] = "rose";
    } catch (PDOException $e) {
        // Catch Database Trigger Signal Error (SQLSTATE 45000)
        $_SESSION['toast_msg'] = "⚠️ Trigger System: " . $e->getMessage();
        $_SESSION['toast_type'] = "rose";
    }
    header("Location: warga.php");
    exit;
}

// Check Edit
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmtE = $pdo->prepare("SELECT * FROM warga WHERE id = ?");
    $stmtE->execute([$edit_id]);
    $edit_data = $stmtE->fetch();
}

// Search filter
$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $stmtW = $pdo->prepare("SELECT * FROM warga WHERE nama LIKE ? OR blok_no LIKE ? OR nik LIKE ? ORDER BY id DESC");
    $stmtW->execute(["%$search%", "%$search%", "%$search%"]);
    $warga_list = $stmtW->fetchAll();
} else {
    $warga_list = $pdo->query("SELECT * FROM warga ORDER BY id DESC")->fetchAll();
}

$total_warga = count($warga_list);
$warga_tetap = $pdo->query("SELECT COUNT(*) FROM warga WHERE status_warga = 'Tetap'")->fetchColumn() ?: 0;
$warga_kontrak = $pdo->query("SELECT COUNT(*) FROM warga WHERE status_warga = 'Kontrak'")->fetchColumn() ?: 0;

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Title -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
    <div>
        <h1 class="text-xl font-extrabold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-users text-brand-blue"></i> <?= $isAdmin ? 'Data Warga RT 01' : 'Direktori Warga RT 01' ?>
        </h1>
        <p class="text-xs text-slate-500 mt-1">
            <?= $isAdmin ? 'Kelola data seluruh warga terdaftar (Proteksi Hapus via DB Trigger Active).' : 'Daftar tetangga dan warga terdaftar di lingkungan RT 01 / RW 05.' ?>
        </p>
    </div>
    <?php if ($isAdmin): ?>
        <div class="flex gap-2">
            <a href="#formWarga" class="bg-brand-accent hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-user-plus"></i> Tambah Warga Baru
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl font-bold">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <span class="text-xs text-slate-500 font-medium">Total Warga Terdaftar</span>
            <h3 class="text-xl font-extrabold text-slate-800"><?= $total_warga ?> KK</h3>
        </div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl font-bold">
            <i class="fa-solid fa-house"></i>
        </div>
        <div>
            <span class="text-xs text-slate-500 font-medium">Warga Tetap</span>
            <h3 class="text-xl font-extrabold text-slate-800"><?= $warga_tetap ?> KK</h3>
        </div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl font-bold">
            <i class="fa-solid fa-building-user"></i>
        </div>
        <div>
            <span class="text-xs text-slate-500 font-medium">Warga Kontrak</span>
            <h3 class="text-xl font-extrabold text-slate-800"><?= $warga_kontrak ?> KK</h3>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <?php if ($isAdmin): ?>
    <!-- Form Section (1 col, Admin Only) -->
    <div id="formWarga" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
        <h3 class="font-bold text-slate-800 text-sm border-b pb-3 flex items-center gap-2">
            <i class="fa-solid fa-user-pen text-brand-accent"></i> 
            <?= $edit_data ? 'Edit Data Warga' : 'Tambah Data Warga Baru' ?>
        </h3>
        <form action="warga.php" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="save_warga">
            <?php if ($edit_data): ?>
                <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
            <?php endif; ?>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Lengkap *</label>
                <input type="text" name="nama" required value="<?= htmlspecialchars($edit_data['nama'] ?? '') ?>" placeholder="Contoh: Budi Santoso" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Alamat / Blok & No *</label>
                <input type="text" name="blok_no" required value="<?= htmlspecialchars($edit_data['blok_no'] ?? '') ?>" placeholder="Contoh: Blok A1 No. 4" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">NIK (Nomor Induk Kependudukan)</label>
                <input type="text" name="nik" value="<?= htmlspecialchars($edit_data['nik'] ?? '') ?>" placeholder="327101..." class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">No KK (Kartu Keluarga)</label>
                <input type="text" name="no_kk" value="<?= htmlspecialchars($edit_data['no_kk'] ?? '') ?>" placeholder="327101..." class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">No HP / WhatsApp</label>
                <input type="text" name="no_hp" value="<?= htmlspecialchars($edit_data['no_hp'] ?? '') ?>" placeholder="08123456789" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Status Tempat Tinggal</label>
                <select name="status_warga" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-brand-accent focus:bg-white focus:outline-none">
                    <option value="Tetap" <?= ($edit_data && $edit_data['status_warga'] === 'Tetap') ? 'selected' : '' ?>>Warga Tetap</option>
                    <option value="Kontrak" <?= ($edit_data && $edit_data['status_warga'] === 'Kontrak') ? 'selected' : '' ?>>Warga Kontrak / Sewa</option>
                </select>
            </div>

            <div class="pt-2 flex gap-2">
                <?php if ($edit_data): ?>
                    <a href="warga.php" class="flex-1 text-center py-2 bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold hover:bg-slate-300">Batal</a>
                <?php endif; ?>
                <button type="submit" class="flex-1 py-2 bg-brand-accent text-white rounded-lg text-xs font-semibold hover:bg-blue-700 shadow-sm">
                    <i class="fa-solid fa-save mr-1"></i> <?= $edit_data ? 'Simpan Perubahan' : 'Simpan Data' ?>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Table Section (Takes 2 cols if Admin, or 3 cols full width if Citizen) -->
    <div class="<?= $isAdmin ? 'lg:col-span-2' : 'lg:col-span-3' ?> bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <i class="fa-solid fa-list-check text-brand-blue"></i> <?= $isAdmin ? 'Daftar Seluruh Warga' : 'Daftar Warga & Tetangga' ?>
            </h3>
            <form method="GET" action="warga.php" class="flex items-center gap-2">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama / alamat..." class="bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-brand-accent focus:outline-none">
                <button type="submit" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-3 py-1.5 rounded-lg text-xs font-medium">Cari</button>
                <?php if (!empty($search)): ?>
                    <a href="warga.php" class="text-xs text-rose-600 hover:underline">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100/70 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                        <th class="p-3 pl-5">No</th>
                        <th class="p-3">Nama Warga</th>
                        <th class="p-3">Blok & Rumah</th>
                        <th class="p-3">NIK</th>
                        <th class="p-3">No HP</th>
                        <th class="p-3">Status</th>
                        <?php if ($isAdmin): ?>
                            <th class="p-3 pr-5 text-center">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php if (empty($warga_list)): ?>
                        <tr>
                            <td colspan="<?= $isAdmin ? 7 : 6 ?>" class="p-6 text-center text-slate-400 italic">Data warga tidak ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($warga_list as $idx => $w): ?>
                            <tr class="hover:bg-slate-50 transition border-b border-slate-100">
                                <td class="p-3 pl-5 text-slate-500 font-medium"><?= $idx + 1 ?></td>
                                <td class="p-3 font-semibold text-slate-800">
                                    <?= htmlspecialchars($w['nama']) ?>
                                    <?php if ($w['id'] == $user['id']): ?>
                                        <span class="ml-1.5 px-1.5 py-0.5 rounded text-[9px] bg-blue-100 text-blue-700 font-bold">Saya</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-slate-600 font-medium"><?= htmlspecialchars($w['blok_no']) ?></td>
                                <td class="p-3 text-slate-500">
                                    <?php 
                                        if (!$isAdmin && !empty($w['nik']) && $w['id'] != $user['id']) {
                                            echo substr($w['nik'], 0, 6) . '******' . substr($w['nik'], -4);
                                        } else {
                                            echo htmlspecialchars($w['nik'] ?: '-');
                                        }
                                    ?>
                                </td>
                                <td class="p-3 text-slate-500"><?= htmlspecialchars($w['no_hp'] ?: '-') ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $w['status_warga'] === 'Tetap' ? 'bg-emerald-100 text-emerald-700' : 'bg-purple-100 text-purple-700' ?>">
                                        <?= htmlspecialchars($w['status_warga']) ?>
                                    </span>
                                </td>
                                <?php if ($isAdmin): ?>
                                <td class="p-3 pr-5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="warga.php?edit_id=<?= $w['id'] ?>#formWarga" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="warga.php?action=delete_warga&id=<?= $w['id'] ?>" onclick="return confirm('Hapus data warga ini? Trigger DB akan memproteksi jika warga masih ada tunggakan.')" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded transition" title="Hapus">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
