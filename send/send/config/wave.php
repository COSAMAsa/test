<?php
/**
 * Configuration Wave — Billetterie
 *
 * ⚠️ Ce fichier doit être placé dans le dossier /config (au même niveau
 * que database.php), donc HORS de /public, pour ne jamais être
 * accessible directement via une URL du navigateur.
 *
 * ⚠️ Ne jamais commiter ce fichier dans Git. Ajoutez-le à votre .gitignore :
 *     config/wave.php
 */

return [
    // Clé API Wave (Bearer token pour les appels sortants vers api.wave.com)
    'api_key' => 'wave_sn_prod_ixa73VfEk8EVyU0Umm0B9-W_o4sgjdjwNeSntrNgel8AyOnIW1jgiFtycfjdQtGopvTEq9KNcP1Kqa60v77xu_o41Tk4TlUWcg',

    // Secret de signature (wave_sn_AKS_...) — utilisé pour DEUX choses :
    // 1) signer chaque requête sortante (Wave-Signature) car la signature
    //    a été activée sur cette clé API
    // 2) vérifier la signature des webhooks entrants dans webhook_wave.php
    // C'est le MÊME secret pour les deux usages.
    'signing_secret' => 'wave_sn_AKS_1yhmwp359qae34zjndxjh1n67vc3nret9frmjrmp8fk62fghvk80',

    // Alias conservé pour compatibilité avec webhook_wave.php
    'webhook_secret' => 'wave_sn_AKS_1yhmwp359qae34zjndxjh1n67vc3nret9frmjrmp8fk62fghvk80',
];
