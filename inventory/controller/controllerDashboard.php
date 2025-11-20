<?php

require_once 'models/inventoryModel.php';

class DashboardController {

    private $inventoryModel;

    public function __construct($db)
    {
        $this->inventoryModel = new inventoryModel($db);
    }

    public function index()
    {
        // Ambil data dari model
        $sum_masuk   = $this->inventoryModel->getCountMasuk();
        $sum_keluar  = $this->inventoryModel->getCountKeluar();
        $count_barang    = $this->inventoryModel->getCountBarang();
        $count_supplier  = $this->inventoryModel->getCountSupplier();
        $count_kategori  = $this->inventoryModel->getCountKategori();
        $count_gudang    = $this->inventoryModel->getCountGudang();
        $newest_barang   = $this->inventoryModel->getNewestBarang(5);

        // Kirim data ke view
        include 'view/dashboard.php';
    }
}
