<?php 
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "gilles_le_magnifique";
$connexion = mysqli_connect($servername, $username, $password, $dbname);
$a = $_POST["matiere"];
$requete = "INSERT INTO matieres(nom) value('$a')";
$result = mysqli_query($connexion, $requete);
header("location: enseignants.php");
?>