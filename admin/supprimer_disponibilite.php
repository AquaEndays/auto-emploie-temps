<?php
require_once '../db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM disponibilites WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: voir_disponibilites.php");
exit;
