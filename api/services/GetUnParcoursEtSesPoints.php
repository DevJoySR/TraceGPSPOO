<?php
    // Rôle : permet à un utilisateur d'obtenir le détail d'un de ses parcours ou d'un parcours d'un membre qui l'autorise
    // le service web doit avoir 4 paramètres 
    // pseudo : pseudo de l'utilisateur
    // mdp : mot de passe hashé en sha1
    // idTrace, l'id de la trace à consulter
    // lang : language utilisé pour le flux de données (xml ou json)
    
    // le service web retourne un flux de message xml contenant les traces.

    // 

// connexion du serveur web à la base MySQL
$dao = new DAO();
	
// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$idTrace = ( empty($this->request['idTrace'])) ?"": $this ->request["idTrace"];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];


$lesTraces = array();
$laTrace = null;
$msg = "";
$code_reponse = null;

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET")
{	$msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
}
else {
    // Les paramètres doivent être présents
    if ( $pseudo == "" || $mdpSha1 == "" )
    {	$msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    }
    else
    {	if ( $dao->getNiveauConnexion($pseudo, $mdpSha1) == 0 ) {
    		$msg = "Erreur : authentification incorrecte.";
    		$code_reponse = 401;
        }
    	else 
    	{	
            // on regarde si la trace existe, et si elle n'est pas nu
             if ( $dao->getUneTrace($idTrace ) == null) {
                $msg = "Erreur : parcours inexistant.";
                $code_reponse = 402;
            }
            else{
                // on récupère la trace depuis l'idtrace
                $laTrace = $dao->getUneTrace($idTrace);
                $proprietaire = $laTrace->getIdUtilisateur();
                $utilisateurAutorise = $dao->getUnUtilisateur($pseudo);
                $id = $utilisateurAutorise->getId();
                $autorise = $dao->autoriseAConsulter($proprietaire, $id);


                 if (!$autorise && $proprietaire != $id){
                    $laTrace = null;
                    $msg = "Erreur : Vous n'êtes pas autorisé par le propriétaire du parcours.";
                    $code_reponse = 403;
                 }
            }
    	}
    }
}
unset($dao);

// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML($msg, $laTrace);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON($msg, $laTrace);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;


// création du flux XML en sortie
function creerFluxXML($msg, $laTrace)
	
    /* Exemple de code XML
        <?xml version="1.0" encoding="UTF-8"?>
<!--Service web GetUnParcoursEtSesPoints - BTS SIO - Lycée De La Salle - Rennes-->
<data>
  <reponse>Données de la trace demandée.</reponse>
  <donnees>
    <trace>
    <id>2</id>
    <dateHeureDebut>2018-01-19 13:08:48</dateHeureDebut>
    <terminee>1</terminee>
    <dateHeureFin>2018-01-19 13:11:48</dateHeureFin>
    <idUtilisateur>2</idUtilisateur>
   </trace>
   <lesPoints>
    <point>
    <id>1</id>
    <latitude>48.2109</latitude>
    <longitude>-1.5535</longitude>
    <altitude>60</altitude>
    <dateHeure>2018-01-19 13:08:48</dateHeure>
    <rythmeCardio>81</rythmeCardio>
    </point>
 .....................................................................................................
    <point>
    <id>10</id>
    <latitude>48.2199</latitude>
    <longitude>-1.5445</longitude>
    <altitude>150</altitude>
    <dateHeure>2018-01-19 13:11:48</dateHeure>
    <rythmeCardio>90</rythmeCardio>
   </point>
  </lesPoints>
 </donnees>
</data>
     */

{
    
    // crée une instance de DOMdocument (DOM : Document Object Model)
	$doc = new DOMDocument();
	
	// specifie la version et le type d'encodage
	$doc->version = '1.0';
	$doc->encoding = 'UTF-8';
	
	// crée un commentaire et l'encode en UTF-8
	$elt_commentaire = $doc->createComment('Service web GetUnParcoursEtSesPoints - BTS SIO - Lycée De La Salle - Rennes');
	// place ce commentaire à la racine du document XML
	$doc->appendChild($elt_commentaire);
	
	// crée l'élément 'data' à la racine du document XML
	$elt_data = $doc->createElement('data');
	$doc->appendChild($elt_data);
	
	// place l'élément 'reponse' dans l'élément 'data'
	$elt_reponse = $doc->createElement('reponse', $msg);
	$elt_data->appendChild($elt_reponse);

    // traitement des utilisateurs
	if ($laTrace != null) {
	    // place l'élément 'donnees' dans l'élément 'data'
	    $elt_donnees = $doc->createElement('donnees');
	    $elt_data->appendChild($elt_donnees);	    
        
        // Création de l'élément 'trace'
        $elt_trace = $doc->createElement('trace');
        $elt_donnees->appendChild($elt_trace);
        
        // Ajout des données de la trace
        $elt_id = $doc->createElement('id', $laTrace->getId());
        $elt_trace->appendChild($elt_id);
        
        $elt_dateHeureDebut = $doc->createElement('dateHeureDebut', $laTrace->getDateHeureDebut());
        $elt_trace->appendChild($elt_dateHeureDebut);
        
        $elt_terminee = $doc->createElement('terminee', $laTrace->getTerminee());
        $elt_trace->appendChild($elt_terminee);
        
        $elt_dateHeureFin = $doc->createElement('dateHeureFin', $laTrace->getDateHeureFin());
        $elt_trace->appendChild($elt_dateHeureFin);
        
        $elt_idUtilisateur = $doc->createElement('idUtilisateur', $laTrace->getIdUtilisateur());
        $elt_trace->appendChild($elt_idUtilisateur);
        
        // Création de l'élément 'lesPoints'
        $elt_lesPoints = $doc->createElement('lesPoints');
        $elt_donnees->appendChild($elt_lesPoints);
        
        // Parcours de tous les points de la trace
        foreach ($laTrace->getLesPointsDeTrace() as $unPoint) {
            // Crée un élément vide 'point'
            $elt_point = $doc->createElement('point');
            // Place l'élément 'point' dans l'élément 'lesPoints'
            $elt_lesPoints->appendChild($elt_point);
            
            // Crée les éléments enfants de l'élément 'point'
            $elt_idPoint = $doc->createElement('id', $unPoint->getId());
            $elt_point->appendChild($elt_idPoint);
            
            $elt_latitude = $doc->createElement('latitude', $unPoint->getLatitude());
            $elt_point->appendChild($elt_latitude);
            
            $elt_longitude = $doc->createElement('longitude', $unPoint->getLongitude());
            $elt_point->appendChild($elt_longitude);
            
            $elt_altitude = $doc->createElement('altitude', $unPoint->getAltitude());
            $elt_point->appendChild($elt_altitude);
            
            $elt_dateHeure = $doc->createElement('dateHeure', $unPoint->getDateHeure());
            $elt_point->appendChild($elt_dateHeure);
            
            $elt_rythmeCardio = $doc->createElement('rythmeCardio', $unPoint->getRythmeCardio());
            $elt_point->appendChild($elt_rythmeCardio);
        }
    }
 
		
	// Mise en forme finale
	$doc->formatOutput = true;
	
	// renvoie le contenu XML
	return $doc->saveXML();

    
}

function creerFluxJSON($msg, $laTrace)
/*
{
  "data": {
    "reponse": "Données de la trace demandée.",
    "donnees": {
      "trace": {
        "id": "2",
        "dateHeureDebut": "2018-01-19 13:08:48",
        "terminee": "1",
        "dateHeureFin": "2018-01-19 13:11:48",
        "idUtilisateur": "2"
      },
      "lesPoints": [
        {
          "id": "1",
          "latitude": "48.2109",
          "longitude": "-1.5535",
          "altitude": "60",
          "dateHeure": "2018-01-19 13:08:48",
          "rythmeCardio": "81"
        },
        .........................
        {
          "id": "10",
          "latitude": "48.2199",
          "longitude": "-1.5445",
          "altitude": "150",
          "dateHeure": "2018-01-19 13:11:48",
          "rythmeCardio": "90"
        }
      ]
    }
  }
}
*/

{
   
   if ($laTrace == null) {
        // Pas de données à renvoyer
        $elt_data = ["reponse" => $msg];
    }
    else {
        // Construction de l'objet trace
        $objetTrace = array(
            "id" => $laTrace->getId(),
            "dateHeureDebut" => $laTrace->getDateHeureDebut(),
            "terminee" => $laTrace->getTerminee(),
            "dateHeureFin" => $laTrace->getDateHeureFin(),
            "idUtilisateur" => $laTrace->getIdUtilisateur()
        );

        // Construction du tableau des points
        $lesObjetsPoints = array();
        foreach ($laTrace->getLesPointsDeTrace() as $unPoint)
        {
            $unObjetPoint = array(
                "id" => $unPoint->getId(),
                "latitude" => $unPoint->getLatitude(),
                "longitude" => $unPoint->getLongitude(),
                "altitude" => $unPoint->getAltitude(),
                "dateHeure" => $unPoint->getDateHeure(),
                "rythmeCardio" => $unPoint->getRythmeCardio()
            );
            $lesObjetsPoints[] = $unObjetPoint;
        }

        // Construction de l'élément "donnees"
        $elt_donnees = array(
            "trace" => $objetTrace,
            "lesPoints" => $lesObjetsPoints
        );

        // Construction de l'élément "data"
        $elt_data = ["reponse" => $msg, "donnees" => $elt_donnees];
    }

    // Construction de la racine
    $elt_racine = ["data" => $elt_data];

    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}




?>