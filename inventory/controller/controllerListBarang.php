<?php

require_once 'models/inventoryModel.php';


class ListBarangController {

    private $inventoryModel;

    public function __construct($db)
    {
        $this->inventoryModel = new InventoryModel($db);
    }
    public function index()
    {
        $barangList = $this->inventoryModel->getAllBarang();
        include 'view/productlist.php';
    }
}
