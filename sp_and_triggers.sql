USE `rtrw_db`;

-- Drop existing triggers if any
DROP TRIGGER IF EXISTS `before_warga_delete`;
DROP TRIGGER IF EXISTS `before_surat_insert`;
DROP TRIGGER IF EXISTS `before_iuran_insert`;

-- Drop existing procedures if any
DROP PROCEDURE IF EXISTS `sp_catat_iuran`;
DROP PROCEDURE IF EXISTS `sp_verifikasi_surat`;
DROP PROCEDURE IF EXISTS `sp_get_dashboard_stats`;
DROP PROCEDURE IF EXISTS `sp_rekap_keuangan_periode`;

DELIMITER //

-- ========================================================
-- 1. TRIGGER: Mencegah hapus warga jika ada tunggakan/surat pending
-- ========================================================
CREATE TRIGGER `before_warga_delete`
BEFORE DELETE ON `warga`
FOR EACH ROW
BEGIN
    DECLARE tunggakan_count INT DEFAULT 0;
    DECLARE surat_pending_count INT DEFAULT 0;

    -- Cek tunggakan iuran
    SELECT COUNT(*) INTO tunggakan_count 
    FROM `iuran` 
    WHERE `warga_id` = OLD.id AND `status` = 'Belum Bayar';

    -- Cek surat pending
    SELECT COUNT(*) INTO surat_pending_count 
    FROM `surat` 
    WHERE (`nik` = OLD.nik OR `pemohon` = OLD.nama) AND `status` = 'Menunggu';

    IF tunggakan_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Gagal Hapus: Warga ini masih memiliki tunggakan iuran yang belum lunas!';
    ELSEIF surat_pending_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Gagal Hapus: Warga ini masih memiliki permohonan surat yang belum diverifikasi!';
    END IF;
END //

-- ========================================================
-- 2. TRIGGER: Auto-sync warga_id sebelum insert iuran
-- ========================================================
CREATE TRIGGER `before_iuran_insert`
BEFORE INSERT ON `iuran`
FOR EACH ROW
BEGIN
    DECLARE found_warga_id INT DEFAULT NULL;

    IF NEW.warga_id IS NULL AND NEW.nama IS NOT NULL THEN
        SELECT id INTO found_warga_id FROM `warga` WHERE `nama` = NEW.nama LIMIT 1;
        IF found_warga_id IS NOT NULL THEN
            SET NEW.warga_id = found_warga_id;
        END IF;
    END IF;
END //

-- ========================================================
-- 3. TRIGGER: Auto-generate Nomor Surat resmi jika belum diisi
-- ========================================================
CREATE TRIGGER `before_surat_insert`
BEFORE INSERT ON `surat`
FOR EACH ROW
BEGIN
    DECLARE next_id INT DEFAULT 1;
    DECLARE code_prefix VARCHAR(10) DEFAULT 'SKT';
    
    IF NEW.no_surat IS NULL OR NEW.no_surat = '' THEN
        SELECT AUTO_INCREMENT INTO next_id 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'surat';

        IF NEW.jenis_surat LIKE '%Usaha%' THEN
            SET code_prefix = 'SKU';
        ELSEIF NEW.jenis_surat LIKE '%KTP%' THEN
            SET code_prefix = 'SKP';
        ELSEIF NEW.jenis_surat LIKE '%SKCK%' THEN
            SET code_prefix = 'SKC';
        END IF;

        SET NEW.no_surat = CONCAT(code_prefix, '-', LPAD(next_id, 3, '0'), '/RT-01/', YEAR(CURDATE()));
    END IF;
END //

-- ========================================================
-- 4. STORED PROCEDURE: Pencatatan Iuran Terintegrasi
-- ========================================================
CREATE PROCEDURE `sp_catat_iuran` (
    IN p_nama VARCHAR(100),
    IN p_jenis VARCHAR(50),
    IN p_periode VARCHAR(20),
    IN p_jumlah INT,
    IN p_metode VARCHAR(20),
    IN p_tanggal DATE,
    IN p_keterangan TEXT,
    IN p_status VARCHAR(20)
)
BEGIN
    DECLARE target_warga_id INT DEFAULT NULL;

    -- Cari warga_id berdasarkan nama
    SELECT id INTO target_warga_id FROM `warga` WHERE `nama` = p_nama LIMIT 1;

    INSERT INTO `iuran` (`warga_id`, `nama`, `jenis`, `periode`, `jumlah`, `metode`, `tanggal`, `keterangan`, `status`)
    VALUES (target_warga_id, p_nama, p_jenis, p_periode, p_jumlah, p_metode, p_tanggal, p_keterangan, p_status);
END //

-- ========================================================
-- 5. STORED PROCEDURE: Verifikasi Surat
-- ========================================================
CREATE PROCEDURE `sp_verifikasi_surat` (
    IN p_surat_id INT,
    IN p_status_baru VARCHAR(20)
)
BEGIN
    UPDATE `surat` 
    SET `status` = p_status_baru 
    WHERE `id` = p_surat_id;
END //

-- ========================================================
-- 6. STORED PROCEDURE: Ambil Ringkasan Statistik Dashboard
-- ========================================================
CREATE PROCEDURE `sp_get_dashboard_stats` ()
BEGIN
    SELECT 
        (SELECT COALESCE(SUM(jumlah), 0) FROM iuran WHERE status = 'Lunas') AS total_iuran_masuk,
        (SELECT COUNT(*) FROM surat WHERE status = 'Verified') AS surat_verified_count,
        (SELECT COUNT(*) FROM iuran WHERE status = 'Belum Bayar') AS belum_bayar_count,
        (SELECT COUNT(*) FROM warga) AS total_warga_count;
END //

-- ========================================================
-- 7. STORED PROCEDURE: Rekapitulasi Kas Keuangan Per Periode
-- ========================================================
CREATE PROCEDURE `sp_rekap_keuangan_periode` (
    IN p_periode VARCHAR(20)
)
BEGIN
    SELECT 
        jenis,
        COUNT(*) AS total_transaksi,
        SUM(CASE WHEN status = 'Lunas' THEN jumlah ELSE 0 END) AS total_lunas,
        SUM(CASE WHEN status = 'Belum Bayar' THEN jumlah ELSE 0 END) AS total_piutang
    FROM iuran
    WHERE (p_periode IS NULL OR p_periode = '' OR periode = p_periode)
    GROUP BY jenis;
END //

DELIMITER ;
