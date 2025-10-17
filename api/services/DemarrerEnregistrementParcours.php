<?php
// Projet TraceGPS - services web
// fichier : api/services/DemarrerEnregistrementParcours.php
// Dernière mise à jour : 17/10/2025 par DevJoySR

// Rôle : ce service web permet à un utilisateur de démarrer l'enregistrement d'un parcours.
// Le service web doit recevoir 3 paramètres :
//          • pseudo : le pseudo de l'utilisateur
//          • mdp : le mot de passe de l'utilisateur hashé en sha1
//          • lang : le langage utilisé pour le flux de données ("xml" ou "json")
// Description du traitement :
//          • Vérifier que les données transmises sont complètes
//          • Vérifier l'authentification de l'utilisateur
//          • Créer une nouvelle trace dans la base de données pour cet utilisateur (avec les champs 
//          terminee=0 et dateFin=null)
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdp = (empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$lang = (empty($this->request['lang'])) ? "" : $this->request['lang'];

// Langage par défaut "xml"
if ($lang != "json") $lang = "xml";

// On vérifie la méthode HTTP (préférer POST ici pour plus de sécurité)
if ($this->getMethodeRequete() != "POST" && $this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
} else {
    // Vérifier présence des paramètres
    if ($pseudo == "") {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {
        if ($dao->getNiveauConnexion($pseudo, $mdp) == 0) {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        } else {
            // création d'un nouveau parcours
            $unUtilisateur = $dao->getUnUtilisateur($pseudo);
            $uneTrace = new Trace(
                null,
                date("Y-m-d H:i:s"),
                null,
                0,
                $unUtilisateur->getId()
            );
            $ok = $dao->creerUneTrace($uneTrace);

            if ($ok) {
            $nouvelleTrace = $dao->getUneTrace($uneTrace->getId());
            if ($nouvelleTrace) {
                $msg = "Trace créée.";
                $code_reponse = 200;
            } else {
                $msg = "Impossible de créer la Trace";
                $code_reponse = 500;
            }
        } else {
            $msg = "Trace non créée.";
            $code_reponse = 500;
        }
        }   
    }
}       

// ferme la connexion à MySQL :
unset($dao);

// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML ($msg);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON ($msg);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;

// ================================================================================================

// création du flux XML en sortie
function creerFluxXML($msg)
{	
    /* Exemple de code XML
         <?xml version="1.0" encoding="UTF-8"?>
        <!--Service web DemarrerEnregistrementParcours - BTS SIO - Lycée De La Salle - Rennes-->
        <data>
            <reponse>............. (message retourné par le service web) ...............</reponse>
        </data>
     */
    
    // crée une instance de DOMdocument (DOM : Document Object Model)
	$doc = new DOMDocument();
	
	// specifie la version et le type d'encodage
	$doc->version = '1.0';
	$doc->encoding = 'UTF-8';
	
	// crée un commentaire et l'encode en UTF-8
	$elt_commentaire = $doc->createComment('Service web DemarrerEnregistrementParcours - BTS SIO - Lycée De La Salle - Rennes');
	// place ce commentaire à la racine du document XML
	$doc->appendChild($elt_commentaire);
	
	// crée l'élément 'data' à la racine du document XML
	$elt_data = $doc->createElement('data');
	$doc->appendChild($elt_data);
	
	// place l'élément 'reponse' juste après l'élément 'data'
	$elt_reponse = $doc->createElement('reponse', $msg);
	$elt_data->appendChild($elt_reponse);
	
	// Mise en forme finale
	$doc->formatOutput = true;
	
	// renvoie le contenu XML
	return $doc->saveXML();
}

// ================================================================================================

// création du flux JSON en sortie
function creerFluxJSON($msg)
{
    /* Exemple de code JSON 
        {
            "data": {
                "reponse": "............. (message retourné par le service web) ..............."
            }
        }
     */
    
    // 2 notations possibles pour créer des tableaux associatifs (la deuxième est en commentaire)
    
    // construction de l'élément "data"
    $elt_data = ["reponse" => $msg];
//     $elt_data = array("reponse" => $msg);
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
//     $elt_racine = array("data" => $elt_data);
    
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}

// ================================================================================================
?>