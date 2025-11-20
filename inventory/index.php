<?php
require_once 'config/conn.php';
require_once 'controller/controllerDashboard.php';
require_once 'controller/controllerListBarang.php';
require_once 'controller/controllerAddProduct.php';
require_once 'controller/controllerInventory.php';
require_once 'controller/controllerCategory.php';

// Inisialisasi database
$database = new Database();
$db = $database->getConnection();

// Tangkap action URL
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

switch ($action) {

    case 'dashboard':
        $controller = new DashboardController($db);
        $controller->index();
        break;
    case 'listbarang':
        $controller = new ListBarangController($db);
        $controller->index();
        break;
    case 'addproduct':
        $controller = new AddProductController($db);
        $controller->index();
        break;
    case 'store':
        $controller = new AddProductController($db);
        $controller->store();
        break;
    case 'listcategory':
        $controller = new CategoryController($db);
        $controller->index();
    break;

    default:
        echo "404 - Page Not Found";
        break;
}
