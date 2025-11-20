<?php
// controller/controllerAddProduct.php

require_once __DIR__ . '/../models/inventoryModel.php'; // sesuaikan path

class AddProductController {
    private $model;

    public function __construct($db) {
        $this->model = new InventoryModel($db);
    }

    // menampilkan form tambah produk (GET)
    public function index() {
        // ambil data pendukung untuk dropdown
        $categories = $this->model->getCategories();
        $suppliers  = $this->model->getSuppliers();
        $gudangs    = $this->model->getGudangs();

        // include view form (path relatif terhadap index.php atau gunakan __DIR__ kalau include dari controller)
        include __DIR__ . '/../view/addproduct.php';
    }

    // menangani submit form (POST)
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=addproduct');
            exit;
        }

        // ambil & sanitasi sederhana
        $nama = trim($_POST['nama_barang'] ?? '');
        $stok = intval($_POST['stok'] ?? 0);
        $id_kat = intval($_POST['id_kategori'] ?? 0);
        $id_sup = intval($_POST['id_supplier'] ?? 0);
        $id_gudang = intval($_POST['id_gudang'] ?? 0);

        $errors = [];

        // validasi dasar
        if ($nama === '') $errors[] = 'Nama barang wajib diisi.';
        if ($id_kat <= 0) $errors[] = 'Pilih kategori.';
        if ($id_sup <= 0) $errors[] = 'Pilih supplier.';
        if ($stok < 0) $errors[] = 'Stok tidak boleh negatif.';

        // contoh validasi file gambar (opsional)
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg','image/png','image/gif'];
            if (!in_array($_FILES['image']['type'], $allowed)) {
                $errors[] = 'Format gambar tidak diperbolehkan.';
            }
            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran gambar maksimal 2MB.';
            }
        }

        if (!empty($errors)) {
            // tampilkan form lagi beserta pesan error
            $categories = $this->model->getCategories();
            $suppliers  = $this->model->getSuppliers();
            $gudangs    = $this->model->getGudangs();
            include __DIR__ . '/../view/addproduct.php';
            return;
        }

        // operasi simpan: gunakan model -- model menangani prepared statements & transaction
        try {
            // contoh method di model: insertProductWithInitialStock(...) -> harus kamu implementasikan
            $id_barang = $this->model->insertProductWithInitialStock($nama, $stok, $id_kat, $id_sup, $id_gudang);

            // jika ada upload gambar, simpan file lalu update record (atau simpan path di model sebelumnya)
            if (!empty($_FILES['image']['name'])) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'prod_' . $id_barang . '.' . $ext;
                $target = __DIR__ . '/../public/uploads/' . $filename;
                move_uploaded_file($_FILES['image']['tmp_name'], $target);
                // update field gambar lewat model
                $this->model->updateProductImage($id_barang, 'uploads/' . $filename);
            }

            // redirect ke list barang
            header('Location: index.php?action=listbarang&msg=created');
            exit;

        } catch (Exception $e) {
            // rollback handled di model jika perlu
            $errors[] = 'Gagal menyimpan data: ' . $e->getMessage();
            $categories = $this->model->getCategories();
            $suppliers  = $this->model->getSuppliers();
            $gudangs    = $this->model->getGudangs();
            include __DIR__ . '/../view/addproduct.php';
            return;
        }
    }
}
