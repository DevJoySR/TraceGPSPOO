<?php
    /* Projet TraceGPS

    Rôle : ce service web permet à un utilisateur d'obtenir la liste de ses parcours d'un utilisateur qui l'autorise
    
    Paramètre à fournir : 
    pseudo : pseudo de l'utilisateur
    mdp : mot de passe de l'utilisateur 
    pseudoConsulté : le pseudo  de l'utilisateur dont on veut consulter la trace
    lang : le langage utilisé pour le flux de donnée (xml ou json)

    Message retourné : Aucune trace pour l'utilisateur oxygen
                       2 trace(s) pour l'utilisateur callisto.
    */

    // connexion du serveur web à la base MySQL
$dao = new DAO();
	
// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoConsulte = ( empty($this->request['pseudoConsulte'])) ? "" : $this->request['pseudoConsulte'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

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
        $lesTraces = null;
        $code_reponse = 400;
    }
    else
    {	if ( $dao->getNiveauConnexion($pseudo, $mdpSha1) == 0 ) {
    		$msg = "Erreur : authentification incorrecte.";
            $lesTraces = null;
    		$code_reponse = 401;
        }
    	else 
    	{	
            // on regarde si la trace existe, et si elle n'est pas nu
             if ( $dao->existePseudoUtilisateur($pseudoConsulte) == false) {
                $msg = "Erreur : pseudo consulté inexistant.";
                $lesTraces = null;
                $code_reponse = 402;
            }
                
            else{
                // on récupère l'id de l'utilisateur possiblement autorisé depuis le dao
                $utilisateurAutorise = $dao->getUnUtilisateur($pseudo);
                $id = $utilisateurAutorise->getId();

                // on récupère l'id de l'utilisateur de qui on va consulté la trace
                $utilisateurConsulte = $dao->getUnUtilisateur($pseudoConsulte);
                $idConsulte = $utilisateurConsulte->getId();
               
                // puis on regarde si l'utilisateur consulté à autoriser le visionnage
                $autorise = $dao->autoriseAConsulter($idConsulte, $id);


                // et on indente les traces de l'utilisateur de qui ont consulte les traces
                $lesTraces = $dao->getLesTraces($idConsulte);

                 if ($id !=$idConsulte && !$autorise){
                    $lesTraces = null;
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
    $donnees = creerFluxXML($msg, $lesTraces); 
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON($msg, $lesTraces);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;

function creerFluxXML($msg, $lesTraces)
    /*
    <?xml version="1.0" encoding="UTF-8"?>
<!--Service web GetLesParcoursDunUtilisateur - BTS SIO - Lycée De La Salle - Rennes-->
<data>
    <reponse>2 trace(s) pour l'utilisateur callisto</reponse>
    <donnees>
        <lesTraces>
            <trace>
                <id>2</id>
                <dateHeureDebut>2018-01-19 13:08:48</dateHeureDebut>
                <terminee>1</terminee>
                <dateHeureFin>2018-01-19 13:11:48</dateHeureFin>
                <distance>1.2</distance>
                <idUtilisateur>2</idUtilisateur>
            </trace>
            <trace>
                <id>1</id>
                <dateHeureDebut>2018-01-19 13:08:48</dateHeureDebut>
                <terminee>0</terminee>
                <distance>0.5</distance>
                <idUtilisateur>2</idUtilisateur>
            </trace>
        </lesTraces>
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
	$elt_commentaire = $doc->createComment('Service web GetLesParcoursDunUtilisateur - BTS SIO - Lycée De La Salle - Rennes');
	// place ce commentaire à la racine du document XML
	$doc->appendChild($elt_commentaire);
	
	// crée l'élément 'data' à la racine du document XML
	$elt_data = $doc->createElement('data');
	$doc->appendChild($elt_data);
	
	// place l'élément 'reponse' dans l'élément 'data'
	$elt_reponse = $doc->createElement('reponse', $msg);
	$elt_data->appendChild($elt_reponse);

    // traitement des utilisateurs
	if ($lesTraces != null) {
        foreach ($lesTraces as $uneTrace) {
	    // place l'élément 'donnees' dans l'élément 'data'
	    $elt_donnees = $doc->createElement('donnees');
	    $elt_data->appendChild($elt_donnees);	    
        
        // Création de l'élément 'trace'
        $elt_trace = $doc->createElement('trace');
        $elt_donnees->appendChild($elt_trace);
        
        // Ajout des données de la trace
        $elt_id = $doc->createElement('id', $uneTrace->getId());
        $elt_trace->appendChild($elt_id);
        
        $elt_dateHeureDebut = $doc->createElement('dateHeureDebut', $uneTrace->getDateHeureDebut());
        $elt_trace->appendChild($elt_dateHeureDebut);
        
        $elt_terminee = $doc->createElement('terminee', $uneTrace->getTerminee());
        $elt_trace->appendChild($elt_terminee);
        
        // Récupère d'abord la valeur
    $dateHeureFin = $uneTrace->getDateHeureFin();

    // Vérifie qu'elle n'est pas NULL avant de créer l'élément
    if ($dateHeureFin !== null && $dateHeureFin !== "") {
    $elt_dateHeureFin = $doc->createElement('dateHeureFin', $dateHeureFin);
    $elt_trace->appendChild($elt_dateHeureFin);
    }
        
        $elt_idUtilisateur = $doc->createElement('idUtilisateur', $uneTrace->getIdUtilisateur());
        $elt_trace->appendChild($elt_idUtilisateur);
        
        // Création de l'élément 'lesPoints'
        $elt_lesPoints = $doc->createElement('lesPoints');
        $elt_donnees->appendChild($elt_lesPoints);
        

        }
    }
 
		
	// Mise en forme finale
	$doc->formatOutput = true;
	
	// renvoie le contenu XML
	return $doc->saveXML();

    
}


function creerFluxJSON($msg, $lesTraces)

/*
{
    "data": {
        "reponse": "2 trace(s) pour l'utilisateur callisto",
        "donnees": {
            "lesTraces": [
                {
                    "id": "2",
                    "dateHeureDebut": "2018-01-19 13:08:48",
                    "terminee": "1",
                    "dateHeureFin": "2018-01-19 13:11:48",
                    "distance": "1.2",
                    "idUtilisateur": "2"
                },
                {
                    "id": "1",
                    "dateHeureDebut": "2018-01-19 13:08:48",
                    "terminee": "0",
                    "distance": "0.5",
                    "idUtilisateur": "2"
                }
            ]
        }
    }
}

*/

{

if ($lesTraces == null) {
        // Pas de données à renvoyer
        $elt_data = ["reponse" => $msg];
    }
    else {
        $tableauTraces = [];
        
        foreach ($lesTraces as $uneTrace) {
            // Construction d'UN objet trace
            $objetTrace = array(
                "id" => $uneTrace->getId(),
                "dateHeureDebut" => $uneTrace->getDateHeureDebut(),
                "terminee" => $uneTrace->getTerminee(),
                "idUtilisateur" => $uneTrace->getIdUtilisateur()
            );

            // Ajout conditionnel de dateHeureFin
            $dateHeureFin = $uneTrace->getDateHeureFin();
            if ($dateHeureFin !== null && $dateHeureFin !== "") {
                $objetTrace["dateHeureFin"] = $dateHeureFin;
            }
            
            // Ajout conditionnel de distance
            $distance = $uneTrace->getDistanceTotale();
            if ($distance !== null && $distance !== "") {
                $objetTrace["distance"] = $distance;
            }

            $tableauTraces[] = $objetTrace;
        }

        $elt_donnees = array(
            "lesTraces" => $tableauTraces
        );

        // Construction de l'élément "data"
        $elt_data = ["reponse" => $msg, "donnees" => $elt_donnees];
    }

    // Construction de la racine
    $elt_racine = ["data" => $elt_data];

    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}
?>
