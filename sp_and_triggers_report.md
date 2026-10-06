# 📜 Laporan Implementasi Stored Procedures & Triggers DB - Smart RT

> **Lokasi Project:** `c:\laragon\www\rt-rw`  
> **Database:** `rtrw_db` (MySQL Laragon)  
> **Tanggal Implementasi:** 4 Oktober 2026  

---

## 📌 Ringkasan Eksekutif

Telah berhasil dibuat dan diintegrasikan **3 Database Triggers** dan **4 Stored Procedures** ke dalam database MySQL `rtrw_db` serta dihubungkan langsung ke kode aplikasi PHP backend (`index.php`, `warga.php`, `surat.php`, `iuran.php`).

Implementasi ini meningkatkan:
1. **Integritas & Proteksi Data**: Mencegah penghapusan warga yang masih memiliki tunggakan iuran atau permohonan surat aktif.
2. **Otomatisasi**: Men-generate Nomor Surat resmi dan melakukan auto-sync `warga_id` secara transparan di layer database.
3. **Performa & Reusability**: Mengurangi query berulang dari PHP dengan mengapsulasi transaksi keuangan & rekapitulasi ke dalam Stored Procedure.

---

## ⚡ 1. Database Triggers (Trigger MySQL)

### A. `before_warga_delete` (Proteksi Hapus Warga)
- **Tabel:** `warga`
- **Kejadian:** `BEFORE DELETE`
- **Fungsi:** Sebelum data warga dihapus, trigger ini mengecek apakah warga tersebut memiliki iuran dengan status `'Belum Bayar'` atau surat permohonan dengan status `'Menunggu'`. Jika ditemukan, database akan melemparkan exception `SIGNAL SQLSTATE '45000'` yang kemudian ditangkap secara ramah oleh PHP notification toast.
- **SQL Trigger:**
```sql
CREATE TRIGGER `before_warga_delete`
BEFORE DELETE ON `warga`
FOR EACH ROW
BEGIN
    DECLARE tunggakan_count INT DEFAULT 0;
    DECLARE surat_pending_count INT DEFAULT 0;

    SELECT COUNT(*) INTO tunggakan_count FROM `iuran` WHERE `warga_id` = OLD.id AND `status` = 'Belum Bayar';
    SELECT COUNT(*) INTO surat_pending_count FROM `surat` WHERE (`nik` = OLD.nik OR `pemohon` = OLD.nama) AND `status` = 'Menunggu';

    IF tunggakan_count > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Gagal Hapus: Warga ini masih memiliki tunggakan iuran yang belum lunas!';
    ELSEIF surat_pending_count > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Gagal Hapus: Warga ini masih memiliki permohonan surat yang belum diverifikasi!';
    END IF;
END;
```

---

### B. `before_surat_insert` (Auto-Generate Nomor Surat)
- **Tabel:** `surat`
- **Kejadian:** `BEFORE INSERT`
- **Fungsi:** Men-generate nomor surat resmi (format: `SKT-001/RT-01/2026` / `SKU-002/RT-01/2026`) secara otomatis saat permohonan baru dibuat.
- **SQL Trigger:**
```sql
CREATE TRIGGER `before_surat_insert`
BEFORE INSERT ON `surat`
FOR EACH ROW
BEGIN
    DECLARE next_id INT DEFAULT 1;
    DECLARE code_prefix VARCHAR(10) DEFAULT 'SKT';
    
    IF NEW.no_surat IS NULL OR NEW.no_surat = '' THEN
        SELECT AUTO_INCREMENT INTO next_id FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'surat';
        IF NEW.jenis_surat LIKE '%Usaha%' THEN SET code_prefix = 'SKU';
        ELSEIF NEW.jenis_surat LIKE '%KTP%' THEN SET code_prefix = 'SKP';
        ELSEIF NEW.jenis_surat LIKE '%SKCK%' THEN SET code_prefix = 'SKC';
        END IF;

        SET NEW.no_surat = CONCAT(code_prefix, '-', LPAD(next_id, 3, '0'), '/RT-01/', YEAR(CURDATE()));
    END IF;
END;
```

---

### C. `before_iuran_insert` (Auto-Sync Warga ID)
- **Tabel:** `iuran`
- **Kejadian:** `BEFORE INSERT`
- **Fungsi:** Jika `warga_id` bernilai NULL saat pencatatan iuran, trigger akan otomatis mencari `id` dari nama warga yang sesuai di tabel `warga` dan mengisi kolom `warga_id`.

---

## ⚙️ 2. Stored Procedures (SP MySQL)

### A. `sp_catat_iuran`
- **Parameter:** `(p_nama, p_jenis, p_periode, p_jumlah, p_metode, p_tanggal, p_keterangan, p_status)`
- **Kegunaan:** Mencatat transaksi iuran baru secara aman di mana pencarian `warga_id` dan `INSERT` dilakukan langsung oleh Stored Procedure di MySQL.
- **Penggunaan di PHP:** `CALL sp_catat_iuran(?, ?, ?, ?, ?, ?, ?, ?)`

---

### B. `sp_verifikasi_surat`
- **Parameter:** `(p_surat_id, p_status_baru)`
- **Kegunaan:** Menggantikan query update manual untuk menyetujui (`'Verified'`) atau menolak (`'Ditolak'`) permohonan surat warga.
- **Penggunaan di PHP:** `CALL sp_verifikasi_surat(?, 'Verified')`

---

### C. `sp_get_dashboard_stats`
- **Parameter:** *(Tanpa parameter)*
- **Kegunaan:** Mengembalikan 4 statistik utama (Total Iuran Masuk, Total Surat Verified, Total Warga Belum Bayar, Total Warga Terdaftar) dalam satu kali eksekusi prosedural.
- **Penggunaan di PHP:** `$stmt = $pdo->query("CALL sp_get_dashboard_stats()");`

---

### D. `sp_rekap_keuangan_periode`
- **Parameter:** `(p_periode)`
- **Kegunaan:** Menghitung breakdown total penerimaan kas lunas & piutang tertunggak yang dikelompokkan per jenis iuran (`Iuran Bulanan`, `Kebersihan`, `Keamanan`, `PHBN`).
- **Penggunaan di PHP:** `$stmt = $pdo->prepare("CALL sp_rekap_keuangan_periode(?)");`

---

## 📁 3. Perubahan Pada File Kode PHP

| File PHP | Deskripsi Perubahan Integration |
|---|---|
| 📄 [`config/database.php`](file:///c:/laragon/www/rt-rw/config/database.php) | Ditambahkan konfigurasi PDO `EMULATE_PREPARES => false` & `ERRMODE_EXCEPTION` untuk penanganan Stored Procedure & Trigger signal error. |
| 📊 [`index.php`](file:///c:/laragon/www/rt-rw/index.php) | Memanggil `sp_catat_iuran` untuk simpan iuran, `sp_verifikasi_surat` untuk verifikasi surat, dan `sp_get_dashboard_stats` untuk statistik ringkasan. |
| 👥 [`warga.php`](file:///c:/laragon/www/rt-rw/warga.php) | Menambahkan `try-catch PDOException` saat aksi hapus warga agar pesan error dari Trigger `before_warga_delete` tampil sebagai alert notifikasi warna merah secara ramah. |
| ✉️ [`surat.php`](file:///c:/laragon/www/rt-rw/surat.php) | Menggunakan `sp_verifikasi_surat` dan mengosongkan `no_surat` saat insert permohonan baru agar memicu Trigger `before_surat_insert`. |
| 💰 [`iuran.php`](file:///c:/laragon/www/rt-rw/iuran.php) | Memanggil `sp_rekap_keuangan_periode` untuk menampilkan kartu breakdown penerimaan kas per jenis iuran. |
| 🗄️ [`sp_and_triggers.sql`](file:///c:/laragon/www/rt-rw/sp_and_triggers.sql) | Script SQL lengkap penyusun seluruh Trigger & Stored Procedure. |

---

## 🧪 4. Cara Pengujian & Uji Coba

1. **Uji Coba Trigger Hapus Warga (`before_warga_delete`):**
   * Buka menu **Data Warga** ([`warga.php`](http://localhost/rt-rw/warga.php)).
   * Coba hapus warga yang masih memiliki tunggakan (misal: *Dewi Lestari*).
   * **Hasil:** Sistem akan menolak dan menampilkan toast merah: `⚠️ Trigger System: Gagal Hapus: Warga ini masih memiliki tunggakan iuran yang belum lunas!`.

2. **Uji Coba Stored Procedure Iuran (`sp_catat_iuran`):**
   * Buka **Dashboard** ([`index.php`](http://localhost/rt-rw/index.php)).
   * Isi Form Pencatatan Iuran dan klik **Simpan Data**.
   * **Hasil:** Data langsung masuk melalui Stored Procedure dan angka statistik otomatis terupdate via `sp_get_dashboard_stats()`.

3. **Uji Coba Auto No. Surat (`before_surat_insert`):**
   * Buka menu **Verifikasi Surat** ([`surat.php`](http://localhost/rt-rw/surat.php)).
   * Buat permohonan surat baru (misal: *Surat Keterangan Usaha*).
   * **Hasil:** Nomor surat otomatis di-generate seperti `SKU-004/RT-01/2026`.

