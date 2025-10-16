<?php

// Projet TraceGPS - services web
// fichier :  api/services/DemandeUneAutorisation.php
// Dernière mise à jour : 16/10/2025 par VV

// Rôle : ce service web permet à un utilisateur de demander une autorisation à un autre utilisateur.

// Le service web doit être appelé avec 6 paramètres obligatoires dont les noms sont volontairement non significatifs :
// pseudo : le pseudo de l'utilisateur qui demande l'autorisation
// mdp : le mot de passe hashé en sha1 de l'utilisateur qui demande l'autorisation
// pseudoDestinataire : le pseudo de l'utilisateur à qui on demande l'autorisation
// d : le texte d'un message accompagnant la demande
// e : le nom et le prénom du demandeur
// lang : le langage utilisé pour le flux de données ("xml" ou "json")

// Description du traitement :
// Vérifier que les données transmises sont complètes
// Vérifier l'authentification de l'utilisateur demandeur
// Vérifier que le pseudo de l'utilisateur destinataire existe
// Envoyer un courriel à l'utilisateur destinataire

// ces variables globales sont définies dans le fichier modele/parametres.php
global $ADR_MAIL_EMETTEUR, $ADR_SERVICE_WEB;

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudoAutorise = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoAutorisant = ( empty($this->request['pseudoDestinataire'])) ?'': $this->request['pseudoDestinataire'];
$texteMessage = ( empty($this->request['texteMessage'])) ?'': $this->request['texteMessage'];
$nomPrenom = ( empty($this->request['nomPrenom'])) ?'': $this->request['nomPrenom'];
$lang = ( empty($this->request['lang'])) ?'': $this->request['lang'];

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET")
{	$msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
}
else 
{
    // Test avec des paramètres incorrects ou incomplets
    if ( $pseudoAutorise == "" || $mdpSha1 == "" || $pseudoAutorisant == "" || $texteMessage =="" || $nomPrenom == "")
    {	$msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    }
     else
    {	
        $pseudoAutorise = $dao -> getLesUtilisateursAutorises($pseudoAutorise);
        if ( $pseudoAutorise == null)
        {
            $msg = "Erreur d'authentification incorrecte.";
            $code_reponse = 401;
        }
        else 
        {
            if ( $pseudoAutorise -> getCode() != $mdpSha1 )
            {
                $msg = "Erreur d'authentification incorrecte.";
                $code_reponse = 401;
            }
        }
    }

}
unset($dao);   // ferme la connexion à MySQL

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
         <!--Service web Connecter - BTS SIO - Lycée De La Salle - Rennes-->
         <data>
            <reponse>Erreur : données incomplètes.</reponse>
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
             "data":{
                "reponse": "authentification incorrecte."
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