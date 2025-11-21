<?php

// controller/CategoryController.php
require_once __DIR__ . '/../models/InventoryModel.php';

class CategoryController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new InventoryModel($db);
    }

    // tampilkan daftar kategori
    public function index() {
        $categories = $this->model->getCategories();
        include __DIR__ . '/../view/categorylist.php';
    }

    // tampilkan form tambah kategori
    public function create() {
        // $category kosong untuk form
        $category = null;
        include __DIR__ . '/../view/addcategory.php';
    }

    // handle POST simpan kategori baru
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=listcategory');
            exit;
        }

        $name = trim($_POST['nama_kategori'] ?? '');
        $code = trim($_POST['kode_kategori'] ?? '');
        $desc = trim($_POST['deskripsi'] ?? '');

        $errors = [];
        if ($name === '') $errors[] = 'Nama kategori wajib diisi.';

        if (!empty($errors)) {
            // tampilkan form lagi dengan pesan error
            $err_msgs = $errors;
            $category = ['nama_kategori' => $name, 'kode_kategori' => $code, 'deskripsi' => $desc];
            include __DIR__ . '/../view/addcategory.php';
            return;
        }

        try {
            $newId = $this->model->insertCategory($name, $code, $desc);
            header('Location: index.php?action=listcategory&msg=created');
            exit;
        } catch (Exception $e) {
            $err_msgs = ['Gagal menyimpan: ' . $e->getMessage()];
            $category = ['nama_kategori' => $name, 'kode_kategori' => $code, 'deskripsi' => $desc];
            include __DIR__ . '/../view/addcategory.php';
            return;
        }
    }

    // tampilkan form edit kategori
    public function edit() {
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: index.php?action=listcategory');
            exit;
        }

        $category = $this->model->getCategoryById($id);
        if (!$category) {
            header('Location: index.php?action=listcategory&msg=notfound');
            exit;
        }

        include __DIR__ . '/../view/editcategory.php';
    }

    // handle POST update kategori
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=listcategory');
            exit;
        }

        $id   = intval($_POST['id'] ?? 0);
        $name = trim($_POST['nama_kategori'] ?? '');
        $code = trim($_POST['kode_kategori'] ?? '');
        // $desc = trim($_POST['deskripsi'] ?? '');

        $errors = [];
        if ($id <= 0) $errors[] = 'ID kategori tidak valid.';
        if ($name === '') $errors[] = 'Nama kategori wajib diisi.';

        if (!empty($errors)) {
            $err_msgs = $errors;
            $category = ['id_kategori' => $id, 'nama_kategori' => $name, 'kode_kategori' => $code, 'deskripsi' => $desc];
            include __DIR__ . '/../view/editcategory.php';
            return;
        }

        try {
            $ok = $this->model->updateCategory($id, $name, $code, $desc);
            header('Location: index.php?action=listcategory&msg=updated');
            exit;
        } catch (Exception $e) {
            $err_msgs = ['Gagal update: ' . $e->getMessage()];
            $category = ['id_kategori' => $id, 'nama_kategori' => $name, 'kode_kategori' => $code, 'deskripsi' => $desc];
            include __DIR__ . '/../view/editcategory.php';
            return;
        }
    }

    // hapus kategori
    public function delete() {
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: index.php?action=listcategory');
            exit;
        }

        try {
            $this->model->deleteCategory($id);
            header('Location: index.php?action=listcategory&msg=deleted');
            exit;
        } catch (Exception $e) {
            header('Location: index.php?action=listcategory&msg=error');
            exit;
        }
    }
}


?>