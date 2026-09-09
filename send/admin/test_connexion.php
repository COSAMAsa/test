<?php
// test_connexion.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test connexion BATOBI...\n";
try {
   $batobi = new PDO(
        "mysql:host=196.250.202.51;port=3307;dbname=billeterie;charset=utf8",
        "billet_user",
        "billet123"
    );
    echo "âœ… BATOBI OK\n";
} catch (PDOException $e) {
    echo "âŒ BATOBI Ã‰CHEC : " . $e->getMessage() . "\n";
}

echo "Test connexion GoCOSAMA...\n";
try {
    $goco = new PDO(
        "mysql:host=127.0.0.1;dbname=cosamasn_bd;charset=utf8",
        "cosamasn",
        "COS#2008!Mer"
    );
    echo "âœ… GoCOSAMA OK\n";
} catch (PDOException $e) {
    echo "âŒ GoCOSAMA Ã‰CHEC : " . $e->getMessage() . "\n";
}
