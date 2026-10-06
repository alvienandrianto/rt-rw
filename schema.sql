-- Database: rtrw_db
CREATE DATABASE IF NOT EXISTS `rtrw_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rtrw_db`;

-- Table: warga
CREATE TABLE IF NOT EXISTS `warga` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL UNIQUE,
    `password` VARCHAR(255) DEFAULT NULL,
    `level` VARCHAR(20) NOT NULL DEFAULT 'user',
    `blok_no` VARCHAR(50) NOT NULL,
    `nik` VARCHAR(20) DEFAULT NULL,
    `no_kk` VARCHAR(20) DEFAULT NULL,
    `no_hp` VARCHAR(20) DEFAULT NULL,
    `status_warga` ENUM('Tetap', 'Kontrak') DEFAULT 'Tetap',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: iuran
CREATE TABLE IF NOT EXISTS `iuran` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `warga_id` INT DEFAULT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `jenis` VARCHAR(50) NOT NULL,
    `periode` VARCHAR(20) NOT NULL,
    `jumlah` INT NOT NULL,
    `metode` ENUM('Transfer', 'Tunai') DEFAULT 'Transfer',
    `tanggal` DATE NOT NULL,
    `keterangan` TEXT DEFAULT NULL,
    `status` ENUM('Lunas', 'Belum Bayar') DEFAULT 'Lunas',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`warga_id`) REFERENCES `warga`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: surat
CREATE TABLE IF NOT EXISTS `surat` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_surat` VARCHAR(50) NOT NULL,
    `jenis_surat` VARCHAR(100) NOT NULL,
    `pemohon` VARCHAR(100) NOT NULL,
    `nik` VARCHAR(20) DEFAULT NULL,
    `keperluan` TEXT DEFAULT NULL,
    `tanggal_pengajuan` DATE NOT NULL,
    `status` ENUM('Menunggu', 'Verified', 'Ditolak') DEFAULT 'Menunggu',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: kegiatan
CREATE TABLE IF NOT EXISTS `kegiatan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kegiatan` VARCHAR(150) NOT NULL,
    `tanggal` DATE NOT NULL,
    `waktu` VARCHAR(20) DEFAULT '08:00 WIB',
    `lokasi` VARCHAR(150) NOT NULL,
    `keterangan` TEXT DEFAULT NULL,
    `status` ENUM('Mendatang', 'Selesai', 'Batal') DEFAULT 'Mendatang',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: inventaris
CREATE TABLE IF NOT EXISTS `inventaris` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_barang` VARCHAR(150) NOT NULL,
    `jumlah` INT NOT NULL DEFAULT 1,
    `kondisi` ENUM('Baik', 'Rusak Ringan', 'Rusak Berat') DEFAULT 'Baik',
    `lokasi_simpan` VARCHAR(100) DEFAULT 'Pos Ronda RT 01',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: pengaduan
CREATE TABLE IF NOT EXISTS `pengaduan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_pelapor` VARCHAR(100) NOT NULL,
    `judul` VARCHAR(150) NOT NULL,
    `isi` TEXT NOT NULL,
    `tanggal` DATE NOT NULL,
    `status` ENUM('Pending', 'Proses', 'Selesai') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data: warga (Default Admin: admin@gmail.com / admin123)
INSERT INTO `warga` (`id`, `nama`, `email`, `password`, `level`, `blok_no`, `nik`, `no_kk`, `no_hp`, `status_warga`) VALUES
(1, 'Administrator RT', 'admin@gmail.com', '$2y$10$w811xKsqaYj0k3w2OQW0qugZ5vRj6l6e926k6P6HekR4jN2b67P9G', 'admin', 'Pos RT 01', '3271010000000001', '3271010000000000', '081200000000', 'Tetap'),
(2, 'Budi Santoso', 'budi@gmail.com', '$2y$10$0k8J9.F/V7o9R.3K9l7sOexWsq11Z1s2H/h6e9b8n7m6c5d4e3f2a', 'user', 'Blok A1 No. 4', '3271011508820001', '3271011508820000', '081234567890', 'Tetap'),
(3, 'Siti Aminah', NULL, NULL, 'user', 'Blok B2 No. 12', '3271015204850002', '3271015204850000', '081298765432', 'Tetap'),
(4, 'Andi Pratama', NULL, NULL, 'user', 'Blok C3 No. 8', '3271011011900003', '3271011011900000', '085712345678', 'Kontrak'),
(5, 'Dewi Lestari', NULL, NULL, 'user', 'Blok A2 No. 1', '3271016503920004', '3271016503920000', '087812345678', 'Tetap'),
(6, 'Rudi Hermawan', NULL, NULL, 'user', 'Blok D1 No. 15', '3271012207880005', '3271012207880000', '081311223344', 'Tetap')
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

-- Seed Data: iuran
INSERT INTO `iuran` (`id`, `warga_id`, `nama`, `jenis`, `periode`, `jumlah`, `metode`, `tanggal`, `keterangan`, `status`) VALUES
(1, 1, 'Budi Santoso', 'Iuran Bulanan', 'Sep 2025', 50000, 'Transfer', '2025-09-15', 'Pembayaran via QRIS', 'Lunas'),
(2, 2, 'Siti Aminah', 'Iuran Kebersihan', 'Sep 2025', 25000, 'Tunai', '2025-09-14', 'Titip ke Bendahara', 'Lunas'),
(3, 3, 'Andi Pratama', 'Iuran Keamanan', 'Sep 2025', 25000, 'Transfer', '2025-09-13', 'Transfer BCA', 'Lunas'),
(4, 4, 'Dewi Lestari', 'Iuran Bulanan', 'Sep 2025', 50000, 'Tunai', '2025-09-12', 'Belum dikonfirmasi', 'Belum Bayar'),
(5, 5, 'Rudi Hermawan', 'Iuran Kebersihan', 'Sep 2025', 25000, 'Transfer', '2025-09-11', 'Transfer Mandiri', 'Lunas')
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

-- Seed Data: surat
INSERT INTO `surat` (`id`, `no_surat`, `jenis_surat`, `pemohon`, `nik`, `keperluan`, `tanggal_pengajuan`, `status`) VALUES
(1, 'SKT-001/RT-01/2025', 'Surat Keterangan Domisili', 'Budi Santoso', '3271011508820001', 'Persyaratan perpanjangan Paspor', '2025-09-28', 'Menunggu'),
(2, 'SKU-002/RT-01/2025', 'Surat Keterangan Usaha', 'Siti Aminah', '3271015204850002', 'Pengajuan Kredit Usaha Rakyat (KUR)', '2025-09-27', 'Menunggu'),
(3, 'SKP-003/RT-01/2025', 'Surat Pengantar KTP', 'Andi Pratama', '3271011011900003', 'Pengurusan E-KTP Hilang', '2025-09-26', 'Menunggu')
ON DUPLICATE KEY UPDATE `no_surat` = VALUES(`no_surat`);

-- Seed Data: kegiatan
INSERT INTO `kegiatan` (`id`, `nama_kegiatan`, `tanggal`, `waktu`, `lokasi`, `keterangan`, `status`) VALUES
(1, 'Kerja Bakti Bersih Lingkungan', '2025-10-05', '07:00 WIB', 'Fasum RT 01', 'Kerja bakti membersihkan selokan dan taman', 'Mendatang'),
(2, 'Rapat Bulanan Pengurus RT', '2025-10-12', '19:30 WIB', 'Balai Warga RT 01', 'Pembahasan laporan keuangan dan persiapan HUT RT', 'Mendatang')
ON DUPLICATE KEY UPDATE `nama_kegiatan` = VALUES(`nama_kegiatan`);

-- Seed Data: inventaris
INSERT INTO `inventaris` (`id`, `nama_barang`, `jumlah`, `kondisi`, `lokasi_simpan`) VALUES
(1, 'Tenda Lipat 3x3m', 4, 'Baik', 'Gudang Balai Warga'),
(2, 'Kursi Plastik Futura', 50, 'Baik', 'Gudang Balai Warga'),
(3, 'Sound System Portable', 1, 'Baik', 'Rumah Ketua RT'),
(4, 'Mesin Potong Rumput', 2, 'Rusak Ringan', 'Gudang Pos Ronda')
ON DUPLICATE KEY UPDATE `nama_barang` = VALUES(`nama_barang`);

-- Seed Data: pengaduan
INSERT INTO `pengaduan` (`id`, `nama_pelapor`, `judul`, `isi`, `tanggal`, `status`) VALUES
(1, 'Dewi Lestari', 'Lampu Jalan Blok A Matikan/Mati', 'Lampu penerangan jalan di depan rumah No. 3 mati sejak kemarin malam.', '2025-09-28', 'Pending'),
(2, 'Budi Santoso', 'Sampah Belum Diangkut', 'Pengangkutan sampah rutin hari Sabtu belum masuk area Blok A.', '2025-09-27', 'Proses')
ON DUPLICATE KEY UPDATE `judul` = VALUES(`judul`);
