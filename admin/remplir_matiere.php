<?php 
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "gilles_le_magnifique";
$connexion = mysqli_connect($servername, $username, $password, $dbname);
if (!$connexion){
    die("Echec de la connexion à la base de données" .mysqli_connect_error());
}
$matieres = [];
$requete = "SELECT * from matieres";
$result = mysqli_query($connexion, $requete);
if ($result&&mysqli_num_rows($result)>0){
    while ($ligne = mysqli_fetch_assoc($result)){
        $matieres[] = $ligne;
    }

}
else{

}
mysqli_close($connexion);
?>