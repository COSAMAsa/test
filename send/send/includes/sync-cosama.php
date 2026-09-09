<?php
// /var/www/includes/sync-cosama.php

// Charger le .env s'il n'a pas déjà été chargé ailleurs dans la requête
if (!getenv('COSAMA_SYNC_API_KEY')) {
    $lignes = @file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lignes) {
        foreach ($lignes as $ligne) {
            if (strpos(trim($ligne), '#') === 0) continue;
            $parts = array_map('trim', explode('=', $ligne, 2));
            if (count($parts) === 2) {
                putenv($parts[0] . '=' . $parts[1]);
                $_ENV[$parts[0]] = $parts[1];
            }
        }
    }
}

function envoyerVersCosama($billet) {
    $url = 'http://gocosama.cosama.sn:574/api/sync-billet.php';
    $apiKey = getenv('COSAMA_SYNC_API_KEY');

    $payload = json_encode(array(
        'id'                 => $billet['id'],
        'nom'                => $billet['nom'],
        'telephone'          => $billet['telephone'],
        'cni'                => $billet['cni'],
        'type_passager'      => $billet['type_passager'],
        'type_client'        => $billet['type_client'],
        'id_place'           => $billet['id_place'],
        'traversee_id'       => $billet['traversee_id'],
        'code_qr'            => $billet['code_qr'],
        'prix'               => $billet['prix'],
        'statut'             => $billet['statut'],
        'date_reservation'   => $billet['date_reservation'],
        'report_effectue'    => $billet['report_effectue'],
        'frais_service'      => $billet['frais_service'],
        'depart_client'      => $billet['depart_client'],
        'montant_rembourse'  => $billet['montant_rembourse'],
        'date_remboursement' => $billet['date_remboursement'],
    ));

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'X-Api-Key: ' . $apiKey,
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErreur = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Échec sync Cosama pour billet ID {$billet['id']}: HTTP $httpCode - $response - $curlErreur");
        return false;
    }

    return true;
}
