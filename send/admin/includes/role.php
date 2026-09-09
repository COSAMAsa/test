<?php

function isAdmin(){
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function isAgent(){
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'agent';
}

function requireAdmin(){
    if (!isAdmin()) {
        header("Location: controle.php");
        exit;
    }
}

function requireAgent(){
    if (!isAgent() && !isAdmin()) {
        header("Location: ../index.php");
        exit;
    }
}
