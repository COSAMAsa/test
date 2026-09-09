<?php
require_once '../config/database.php';

$raw_input = file_get_contents('php://input');

error_log("=== CALLBACK SUCCES RECU ===");
error_log("METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("GET: " . print_r($_GET, true));
error_log("POST: " . print_r($_POST, true));
error_log("RAW BODY: " . $raw_input);

$order_id = $_GET['orderId'] ?? $_POST['orderId'] ?? null;

if (!$order_id) {
    error_log("Callback reçu sans orderId");
    die("Données de callback manquantes");
}

header("Location: valider_paiement.php?orderId=" . urlencode($order_id));
exit;
?>