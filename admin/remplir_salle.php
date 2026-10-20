<?php 
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "gilles_le_magnifique";
$connexion = mysqli_connect($servername, $username, $password, $dbname);
if (!$connexion){
    die("Echec de la connexion à la base de données" .mysqli_connect_error());
}
$salles = [];
$requete = "SELECT * from salles where est_indisponible = 1";
$result = mysqli_query($connexion, $requete);
if ($result&&mysqli_num_rows($result)>0){
    while ($ligne = mysqli_fetch_assoc($result)){
        $salles[] = $ligne;
    }

}
else{

}
mysqli_close($connexion);
?>
