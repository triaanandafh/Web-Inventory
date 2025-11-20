<?php
require_once 'models/InventoryModel.php';
require_once 'config/conn.php';

class InventoryController
{
    private $model;

    public function __construct(PDO $db)
    {
        // model disiapkan di sini
        $this->model = new InventoryModel($db);
    }

    public function store()
    {
    $nama = $_POST['nama'];
    $stok = $_POST['stok'];
    $id_cat = $_POST['id_kategori'];
    $id_sup = $_POST['id_supplier'];

    $id_barang = $this->model->insertProductWithInitialStock(
        $nama,
        $stok,
        $id_cat,
        $id_sup
    );

    if (!empty($_FILES['gambar']['name'])) {
        $filename = time() . '_' . basename($_FILES['gambar']['name']);
        move_uploaded_file($_FILES['gambar']['tmp_name'], "uploads/" . $filename);
        $this->model->updateProductImage($id_barang, "uploads/" . $filename);
    }

    header("Location: index.php?mod=inventory&act=list");
    exit;
    }

    public function index() {
        $categories = $this->model->getCategories();
        include __DIR__ . '/../view/categorylist.php';
    }

     public function listCategory() {
        $categories = $this->model->getCategories();
        include __DIR__ . '/../view/categorylist.php';
    }
}
?>