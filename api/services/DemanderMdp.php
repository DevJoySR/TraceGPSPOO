<?php
// Projet TraceGPS - services web
// fichier : api/services/DemanderMdp.php
// Dernière mise à jour : 16/10/2025 par DevJoySR

// Rôle : ce service web permet à un utilisateur de demander un nouveau mot de passe s'il l'a oublié.
// Le service web doit recevoir 2 paramètres :
//          • pseudo : le pseudo de l'utilisateur
//          • lang : le langage utilisé pour le flux de données ("xml" ou "json")
// Description du traitement :
//          • Vérifier que les données transmises sont complètes
//          • Vérifier que le pseudo de l'utilisateur existe
//          • Générer un nouveau mot de passe
//          • Enregistrer le nouveau mot de passe
//          • Envoyer un courriel à l'utilisateur avec son nouveau mot de passe
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
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
        // Vérifier que le pseudo existe
        if (! $dao->existePseudoUtilisateur($pseudo)) {
            $msg = "Erreur : pseudo inexistant.";
            $code_reponse = 404;
        } else {
            // création d'un mot de passe aléatoire de 8 caractères + le mot de passe sera hashé
            $nouveauMdp = Outils::creerMdp();
            $hash = sha1($nouveauMdp);

            // Enregistrer le nouveau hash en base
            $ok = $dao->modifierMdpUtilisateur($pseudo, $hash);
            if (! $ok) {
                $msg = "Erreur : problème lors de l'enregistrement du mot de passe.";
                $code_reponse = 500;
            } else {
                // Envoyer le nouveau mot de passe en clair par email
                $ok = $dao->envoyerMdp($pseudo, $nouveauMdp);
                if (! $ok) {
                    $msg = "Enregistrement effectué ; l'envoi du courriel de confirmation a rencontré un problème.";
                    $code_reponse = 500;
                } else {
                    $msg = "Enregistrement effectué ; vous allez recevoir un courriel avec votre nouveau mot de passe.";
                    $code_reponse = 200;
                }
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
    /* Exemple de code JSON
         <?xml version="1.0" encoding="UTF-8"?>
         <!--Service web DemanderMdp - BTS SIO - Lycée De La Salle - Rennes-->
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
	$elt_commentaire = $doc->createComment('Service web Connecter - BTS SIO - Lycée De La Salle - Rennes');
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