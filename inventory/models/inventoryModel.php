<?php
// Catatan: Tidak perlu require_once 'config/conn.php' di sini,
// karena class Database akan diinisialisasi dan objek koneksi
// akan dilewatkan dari file index.php

class InventoryModel {
    private $conn;

    // Constructor menerima objek koneksi PDO
    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    // --- METHOD UNTUK DASHBOARD ---

    // Mengambil total jumlah (SUM) transaksi masuk
    public function getCountMasuk(){
        // Asumsi: menggunakan kolom 'total_harga' dari 'transaksi_masuk'
        $query = "SELECT COUNT(id_transaksi_masuk) FROM transaksi_masuk"; 
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn(); 
    }

    // Mengambil total jumlah (SUM) transaksi keluar
    public function getCountKeluar(){
        // Asumsi: menggunakan kolom 'total_harga' dari 'transaksi_keluar'
        $query = "SELECT COUNT(id_transaksi_keluar) FROM transaksi_keluar";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }


    // Mengambil jumlah total barang
    public function getCountBarang(){
        $query = "SELECT COUNT(*) FROM barang";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }


    // Mengambil jumlah total supplier
    public function getCountSupplier(){
        $query = "SELECT COUNT(*) FROM supplier";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }


    // Mengambil jumlah total kategori
    public function getCountKategori(){
        $query = "SELECT COUNT(*) FROM kategori_barang";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }


    // Mengambil jumlah total gudang
    public function getCountGudang(){
        $query = "SELECT COUNT(*) FROM gudang";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // Mengambil daftar barang yang baru ditambahkan
    public function getNewestBarang($limit = 5){
        $query = "
            SELECT id_barang, nama_barang, stok
            FROM barang 
            ORDER BY id_barang DESC
            LIMIT :limit
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- METHOD UNTUK DASHBOARD ---

    public function getAllBarang()
    {
        $query = "SELECT 
        barang.*,
        kategori_barang.nama_kategori AS kategori,
        barang.id_supplier,
        supplier.nama_supplier AS supplier
    FROM barang
    LEFT JOIN kategori_barang ON barang.id_kategori = kategori_barang.id_kategori
    LEFT JOIN supplier ON barang.id_supplier = supplier.id_supplier;
    ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGudangs() {
        try {
            $sql = "SELECT id_gudang, nama_gudang, lokasi FROM gudang ORDER BY nama_gudang";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("InventoryModel::getGudangs error: " . $e->getMessage());
            return [];
        }
    }

    public function getSuppliers() {
        try {
            $sql = "SELECT id_supplier, nama_supplier, telepon, alamat FROM supplier ORDER BY nama_supplier";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("InventoryModel::getSuppliers error: " . $e->getMessage());
            return [];
        }
    }

     public function getCategories() {
        try {
            $sql = "SELECT id_kategori, nama_kategori FROM kategori_barang ORDER BY nama_kategori";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("InventoryModel::getCategories error: " . $e->getMessage());
            return [];
        }
    }

    public function insertProductWithInitialStock($nama, $stok, $id_kategori, $id_supplier, $id_gudang = null) {
        try {
            // mulai transaction
            $this->conn->beginTransaction();

            // 1) Insert ke tabel barang, gunakan RETURNING (Postgres)
            $sql = "INSERT INTO barang (nama_barang, stok, id_kategori, id_supplier)
                    VALUES (:nama, :stok, :id_kategori, :id_supplier)
                    RETURNING id_barang";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':nama' => $nama,
                ':stok' => $stok,
                ':id_kategori' => $id_kategori,
                ':id_supplier' => $id_supplier
            ]);

            // Ambil id_barang dari RETURNING
            $id_barang = $stmt->fetchColumn();
            if (!$id_barang) {
                // fallback: try lastInsertId (untuk keamanan, tapi di Postgres RETURNING harusnya bekerja)
                $id_barang = $this->conn->lastInsertId('barang_id_barang_seq') ?: null;
            }

            // 2) Jika ada stok awal > 0 dan gudang valid -> insert transaksi_masuk
            if ($stok > 0 && !empty($id_gudang) && intval($id_gudang) > 0) {
                $sql2 = "INSERT INTO transaksi_masuk (jumlah_masuk, id_barang, id_gudang)
                         VALUES (:qty, :id_barang, :id_gudang)";
                $stmt2 = $this->conn->prepare($sql2);
                $stmt2->execute([
                    ':qty' => $stok,
                    ':id_barang' => $id_barang,
                    ':id_gudang' => $id_gudang
                ]);
            }

            // commit jika semua sukses
            $this->conn->commit();
            return (int)$id_barang;
        } catch (PDOException $e) {
            // rollback kalau error dan teruskan exception agar controller bisa tangani
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("InventoryModel::insertProductWithInitialStock error: " . $e->getMessage());
            throw new Exception("Gagal menyimpan produk: " . $e->getMessage());
        }
    }

    public function updateProductImage($id_barang, $imagePath) {
        try {
            $sql = "UPDATE barang SET image_path = :img WHERE id_barang = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':img' => $imagePath,
                ':id'  => $id_barang
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("InventoryModel::updateProductImage error: " . $e->getMessage());
            return false;
        }
    }

   // ambil 1 kategori berdasarkan id
    public function getCategoryById($id) {
        try {
            $sql = "SELECT id_kategori, nama_kategori, kode_kategori, deskripsi 
                    FROM kategori_barang 
                    WHERE id_kategori = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getCategoryById error: " . $e->getMessage());
            return null;
        }
    }

    // insert kategori baru
    public function insertCategory($name, $code = null, $desc = null) {
        try {
            $sql = "INSERT INTO kategori_barang (nama_kategori, kode_kategori, deskripsi)
                    VALUES (:nama, :kode, :deskripsi)
                    RETURNING id_kategori";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':nama' => $name,
                ':kode' => $code,
                ':deskripsi' => $desc
            ]);

            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("insertCategory error: " . $e->getMessage());
            throw new Exception("Gagal insert kategori");
        }
    }

    // update kategori
    public function updateCategory($id, $name, $code = null, $desc = null) {
        try {
            $sql = "UPDATE kategori_barang 
                    SET nama_kategori = :nama,
                        kode_kategori = :kode,
                        deskripsi = :deskripsi
                    WHERE id_kategori = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':nama' => $name,
                ':kode' => $code,
                ':deskripsi' => $desc,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("updateCategory error: " . $e->getMessage());
            throw new Exception("Gagal update kategori");
        }
    }

    // delete kategori
    public function deleteCategory($id) {
        try {
            $sql = "DELETE FROM kategori_barang WHERE id_kategori = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("deleteCategory error: " . $e->getMessage());
            throw new Exception("Gagal delete kategori");
        }
    }



}
?>