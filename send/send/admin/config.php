<?php
/**
 * ======================================================
 * CONFIGURATION SYNCHRONISATION BATOBI -> GOCOSAMA
 * Compatible PHP 5.5
 * ======================================================
 */

date_default_timezone_set('Africa/Dakar');

/*************************************************
 * CONNEXION BATOBI (Serveur distant)
 *************************************************/

try {

   $batobi = new PDO(
        "mysql:host=196.250.202.51;port=3307;dbname=billeterie;charset=utf8",
        "billet_user",
        "billet123"
    );

    $batobi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Erreur connexion BATOBI : " . $e->getMessage());

}


/*************************************************
 * CONNEXION GOCOSAMA (Serveur local)
 *************************************************/

try {

    $goco = new PDO(
        "mysql:host=127.0.0.1;dbname=cosamasn_bd;charset=utf8",
        "cosamasn",
        "COS#2008!Mer"
    );
    $goco->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Erreur connexion GoCOSAMA : " . $e->getMessage());

}
