<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Sécurité rôle
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['admin', 'exploitation', 'finance'])) {
    header("Location: dashboard.php");
    exit;
}

/* =========================================
   📅 FILTRES (identiques à ca.php)
========================================= */
$traversee_id = $_GET['traversee_id'] ?? '';
$date_debut   = $_GET['date_debut'] ?? '';
$date_fin     = $_GET['date_fin'] ?? '';

/* =========================================
   📋 LISTE DES TRAVERSEES
========================================= */
$sqlTrav = "SELECT id, date_depart FROM traversees";
$condTrav = [];
$paramsTrav = [];

if (!empty($date_debut)) {
    $condTrav[] = "DATE(date_depart) >= ?";
    $paramsTrav[] = $date_debut;
}

if (!empty($date_fin)) {
    $condTrav[] = "DATE(date_depart) <= ?";
    $paramsTrav[] = $date_fin;
}

if (!empty($condTrav)) {
    $sqlTrav .= " WHERE " . implode(" AND ", $condTrav);
}

$sqlTrav .= " ORDER BY date_depart DESC";

$stmtTrav = $pdo->prepare($sqlTrav);
$stmtTrav->execute($paramsTrav);
$traversees = $stmtTrav->fetchAll();

/* =========================================
   💰 FONCTION CALCUL CA
========================================= */
function calculerCA($pdo, $traversee_id, $statuts) {

    $placeholders = implode(',', array_fill(0, count($statuts), '?'));

    $sql = "
        SELECT
            COALESCE(SUM(b.prix), 0) AS total_billets,
            COALESCE(SUM(b.frais_service), 0) AS total_frais,
            COUNT(*) AS nb_billets
        FROM billets b
        WHERE b.traversee_id = ?
        AND b.statut IN ($placeholders)
    ";

    $params = array_merge([$traversee_id], $statuts);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $res = $stmt->fetch();

    $res['total_general'] = $res['total_billets'] + $res['total_frais'];

    return $res;
}

/* =========================================
   🧾 PREPARATION DES LIGNES A EXPORTER
========================================= */
$filename = 'ca_export_' . date('Y-m-d_His') . '.csv';
$rows = [];

if (!empty($traversee_id)) {

    /* ---------- EXPORT DETAIL D'UNE TRAVERSEE ---------- */

    $t_selected = null;
    foreach ($traversees as $t) {
        if ($t['id'] == $traversee_id) {
            $t_selected = $t;
            break;
        }
    }

    $ca_global   = calculerCA($pdo, $traversee_id, ['valide', 'embarque']);
    $ca_embarque = calculerCA($pdo, $traversee_id, ['embarque']);

    $sqlDetail = "
        SELECT
            b.*,
            p.type_place

        FROM billets b

        LEFT JOIN places p
        ON b.id_place = p.id_place

        WHERE b.traversee_id = ?
        AND b.statut IN ('valide', 'embarque')

        ORDER BY b.id DESC
    ";

    $stmtDetail = $pdo->prepare($sqlDetail);
    $stmtDetail->execute([$traversee_id]);
    $billets_detail = $stmtDetail->fetchAll();

    $filename = 'ca_traversee_' . $traversee_id . '_' . date('Y-m-d_His') . '.csv';

    // Ligne de titre / contexte
    $rows[] = ['Chiffre d\'affaires - Traversée du ' . ($t_selected ? date('d/m/Y H:i', strtotime($t_selected['date_depart'])) : '')];
    $rows[] = ['Exporté le ' . date('d/m/Y H:i')];
    $rows[] = [];

    // Résumé
    $rows[] = ['Résumé', 'Nb billets', 'Total billets (FCFA)', 'Frais service (FCFA)', 'CA total (FCFA)'];
    $rows[] = [
        'Valide + Embarqué',
        $ca_global['nb_billets'],
        $ca_global['total_billets'],
        $ca_global['total_frais'],
        $ca_global['total_general'],
    ];
    $rows[] = [
        'Embarqué uniquement',
        $ca_embarque['nb_billets'],
        $ca_embarque['total_billets'],
        $ca_embarque['total_frais'],
        $ca_embarque['total_general'],
    ];
    $rows[] = [];

    // Détail billets
    $rows[] = ['Nom', 'Prénom', 'Téléphone', 'Place', 'Prix (FCFA)', 'Frais (FCFA)', 'Total payé (FCFA)', 'Code billet', 'Statut'];

    foreach ($billets_detail as $b) {

        $total_paye = $b['prix'] + $b['frais_service'];

        $rows[] = [
            $b['nom'],
            $b['prenom'],
            $b['telephone'],
            $b['type_place'] ?? '',
            $b['prix'],
            $b['frais_service'],
            $total_paye,
            $b['code_qr'],
            $b['statut'] == 'embarque' ? 'Embarqué' : 'Valide',
        ];
    }

} else {

    /* ---------- EXPORT RECAP TOUTES TRAVERSEES ---------- */

    $ca_recap_total_global   = 0;
    $ca_recap_total_embarque = 0;
    $recap = [];

    foreach ($traversees as $t) {

        $g = calculerCA($pdo, $t['id'], ['valide', 'embarque']);
        $e = calculerCA($pdo, $t['id'], ['embarque']);

        $ca_recap_total_global   += $g['total_general'];
        $ca_recap_total_embarque += $e['total_general'];

        $recap[] = [
            'traversee'   => $t,
            'ca_global'   => $g,
            'ca_embarque' => $e,
        ];
    }

    $filename = 'ca_recap_' . date('Y-m-d_His') . '.csv';

    $rows[] = ['Chiffre d\'affaires - Récapitulatif de toutes les traversées'];

    if (!empty($date_debut) || !empty($date_fin)) {
        $rows[] = [
            'Période : '
            . (!empty($date_debut) ? date('d/m/Y', strtotime($date_debut)) : '...')
            . ' -> '
            . (!empty($date_fin) ? date('d/m/Y', strtotime($date_fin)) : '...')
        ];
    }

    $rows[] = ['Exporté le ' . date('d/m/Y H:i')];
    $rows[] = [];

    $rows[] = ['TOTAUX', 'CA valide + embarqué (FCFA)', 'CA embarqué (FCFA)'];
    $rows[] = ['Toutes traversées', $ca_recap_total_global, $ca_recap_total_embarque];
    $rows[] = [];

    $rows[] = [
        'Traversée',
        'Nb billets (valide+embarqué)',
        'CA valide + embarqué (FCFA)',
        'Nb billets embarqués',
        'CA embarqué (FCFA)',
    ];

    foreach ($recap as $r) {

        $rows[] = [
            date('d/m/Y H:i', strtotime($r['traversee']['date_depart'])),
            $r['ca_global']['nb_billets'],
            $r['ca_global']['total_general'],
            $r['ca_embarque']['nb_billets'],
            $r['ca_embarque']['total_general'],
        ];
    }
}

/* =========================================
   📤 GENERATION DU FICHIER CSV (compatible Excel)
========================================= */

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// BOM UTF-8 pour qu'Excel affiche correctement les accents
fwrite($output, "\xEF\xBB\xBF");

foreach ($rows as $row) {
    // Séparateur ';' : format par défaut d'Excel en locale française
    fputcsv($output, $row, ';');
}

fclose($output);
exit;
