<?php
ob_start();

require_once '../config/database.php';
require_once '../lib/fpdf/fpdf.php';
require_once '../lib/phpqrcode/qrlib.php';

date_default_timezone_set('Africa/Dakar');
$id = $_GET['id'];

// 🔎 Récupération billet
$stmt = $pdo->prepare("
SELECT 
    b.*, 
    p.type_place,
    p.numero_place,
    t.depart,
    t.destination,
    t.date_depart
FROM billets b
JOIN places p ON b.id_place = p.id_place
JOIN traversees t ON b.traversee_id = t.id
WHERE b.id = ?
");
$stmt->execute([$id]);
$billet = $stmt->fetch();

if(!$billet){
    die("Billet introuvable");
}

// 🔳 QR CODE
$qrcodes_dir = __DIR__ . '/qrcodes';
if (!file_exists($qrcodes_dir)) mkdir($qrcodes_dir, 0755, true);
$qrFile = $qrcodes_dir . "/billet_" . $billet['id'] . ".png";



if (!file_exists($qrFile)) {
    QRcode::png($billet['code_qr'], $qrFile, QR_ECLEVEL_L, 5);
}

// 📅 Date formatée
$date = date('d/m/Y H:i', strtotime($billet['date_depart']));

// ================= PDF =================
$pdf = new FPDF();
$pdf->AddPage();

// Fond billet
$pdf->Image('../assets/billet_bg.jpg', 0, 0, 210, 297);

// ================= HEADER =================
$pdf->SetFont('Arial','B',16);
$pdf->SetTextColor(0,0,80);
$pdf->SetXY(0, 15);
$pdf->Cell(0,10,"-",0,1,'C');

$pdf->SetFont('Arial','B',14);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(0,8,"Code billet : ".$billet['code_qr'],0,1,'C');

$pdf->Ln(5);

// ================= QR CODE =================
$pdf->Image($qrFile, 80, 35, 50);

// ================= BOX TRAJET =================
$pdf->SetY(95);
$pdf->SetDrawColor(180,180,180);
$pdf->SetFillColor(245,245,245);

$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(220,235,255);
$pdf->Cell(0,8,"TRAJET",1,1,'C',true);

$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,utf8_decode($billet['depart']." -> ".$billet['destination']),1,1,'C');

$pdf->SetFont('Arial','',10);
$pdf->Cell(0,8,"Date depart : ".$date,1,1,'C');

$pdf->Ln(4);

// ================= PASSAGER =================
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(220,235,255);
$pdf->Cell(0,8,"PASSAGER",1,1,'C',true);

$pdf->SetFont('Arial','',11);
$nomComplet = trim(($billet['prenom'] ?? '') . ' ' . ($billet['nom'] ?? ''));

$pdf->Cell(
    0,
    7,
    utf8_decode("Nom : ".$nomComplet),
    1,
    1,
    'C'
);
$pdf->Cell(0,7,"CNI : ".$billet['cni'],1,1,'C');

$pdf->Ln(4);

// ================= PLACE =================
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,"DETAILS BILLET",1,1,'C',true);

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,7,"Place : ".$billet['type_place']." | Numero : ".$billet['numero_place'], 1,1,'C');
$pdf->Cell(0,7,"Type : ".$billet['type_passager']." / ".$billet['type_client']." / ".$billet['depart_client'],1,1,'C');

$pdf->Ln(5);

// ================= PRIX =================
$frais_service = !empty($billet['frais_service']) ? (int)$billet['frais_service'] : 500;
$prix_billet = (int)$billet['prix'];
$total = $prix_billet + $frais_service;

$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(220,235,255);
$pdf->Cell(0,8,"PAIEMENT",1,1,'C',true);

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,7,"Prix billet : ".number_format($prix_billet,0,',',' ')." FCFA",1,1,'C');
$pdf->Cell(0,7,"Frais service : ".number_format($frais_service,0,',',' ')." FCFA",1,1,'C');

$pdf->SetFont('Arial','B',14);
$pdf->SetFillColor(200,230,255);
$pdf->Cell(0,10,"TOTAL PAYE : ".number_format($total,0,',',' ')." FCFA",1,1,'C',true);

// ================= FOOTER =================
// ================= FOOTER =================
$pdf->Ln(5);

$pdf->SetFont('Arial','I',10);
$pdf->SetTextColor(100,100,100);

// Message principal
$pdf->Cell(0,8,"Bon voyage et merci pour votre confiance",0,1,'C');

$pdf->Ln(2);

// Texte informations voyageurs
$pdf->SetFont('Arial','',9);
$pdf->SetTextColor(80,80,80);

$pdf->MultiCell(
    0,
    5,
    utf8_decode(
        "Merci de vous munir de votre piece d'identification à presenter pendant le voyage. ".
        "Pour un enfant, se munir de son extrait de naissance. ".
        "Pour un bebe, une autorisation parentale est requise en plus de son extrait de naissance."
    ),
    0,
    'C'
);
// 🚫 ZONE PUB (VIDE volontairement)
// (ne rien mettre ici pour laisser l’espace publicité)

ob_end_clean();
$pdf->Output("I","billet_".$billet['id'].".pdf");
