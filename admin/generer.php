<?php
session_start();
require_once '../db.php';

// Vérification d'accès admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Inclure le fichier de génération
require_once 'generate_shedule.php';
?>
