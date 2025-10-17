<?php
// Projet TraceGPS - services web
// fichier :  api/services/RetirerUneAutorisation.php
// Dernière mise à jour : 17/10/2025 par VV

// Rôle :ce service web permet à un utilisateur authentifié d'envoyer sa position

// Le service web doit être appelé avec 9 paramètres obligatoires dont les noms sont volontairement non significatifs :
// pseudo : le pseudo de l'utilisateur
// mdp : le mot de passe de l'utilisateur hashé en sha1
// idTrace : l'id de la trace dont le point fera partie
// dateHeure : la date et l'heure au point de passage (format 'Y-m-d H:i:s')
// latitude : latitude du point de passage
// longitude : longitude du point de passage
// altitude : altitude du point de passage
// rythmeCardio : rythme cardiaque au point de passage (ou 0 si le rythme n'est pas mesurable)
// lang : le langage utilisé pour le flux de données ("xml" ou "json")



// Description du traitement :
// Vérifier que les données transmises sont complètes
// Vérifier l'authentification de l'utilisateur
// Vérifier l'existence du numéro de trace
// Vérifier que la trace appartient bien à l'utilisateur
// Vérifier que la trace n'est pas encore terminée
// Enregistrer le point dans la base de données
// Retourner l'id du point

// ces variables globales sont définies dans le fichier modele/parametres.php
global $ADR_MAIL_EMETTEUR, $ADR_SERVICE_WEB;

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = (empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$unIdTrace = (empty($this->request['idTrace'])) ? '' : $this->request['idTrace'];
$uneDateHeure = (empty($this->request['dateHeure'])) ? '' : $this->request['dateHeure'];
$uneLatitude = (empty($this->request['latitude'])) ? '' : $this->request['latitude'];
$uneLongitude = (empty($this->request['longitude'])) ? '' : $this->request['longitude'];
$uneAltitude = (empty($this->request['altitude'])) ? '' : $this->request['altitude'];
$unRythmeCardio = (empty($this->request['rythmeCardio'])) ? '' : $this->request['rythmeCardio'];
$lang = (empty($this->request['lang'])) ? '' : $this->request['lang'];

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET")
{
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
}
else
{
    // Test avec des paramètres incorrects ou incomplets
    if ($pseudo == "" || $mdpSha1 == "" || $unIdTrace == "" || $uneDateHeure == "" || $uneLatitude == "" || $uneLongitude == "" || $uneAltitude == "" || $unRythmeCardio == "")
    {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    }
     else
    {
        // Test de l'authentification de l'utilisateur demandeur
        $niveauConnexion = $dao->getNiveauConnexion($pseudo, $mdpSha1);
        
        if ($niveauConnexion == 0)
        {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        }
        else
        {
            $laTrace = $dao->getUneTrace($unIdTrace);
            if ( $laTrace == null)
            {
                $msg = "Erreur : le numéro de trace n'existe pas.";
                $code_reponse = 400;
            }
            else
            {   $idProprietaire = $laTrace -> getIdUtilisateur();
                if ($idProprietaire != $pseudo)
                {
                    $msg = "Erreur : le numéro de trace ne correspond pas à cet utilisateur.";
                    $code_reponse = 400;
                }
            }
        }
    }
}









unset($dao);   // ferme la connexion à MySQL

// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML($msg);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON($msg);
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
         <!--Service web DemanderUneAutorisation - BTS SIO - Lycée De La Salle - Rennes-->
         <data>
            <reponse>oxygen va recevoir un courriel avec votre demande.</reponse>
         </data>
     */

    // crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();

    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web DemanderUneAutorisation - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);

    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);

    // place l'élément 'reponse' juste après l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', htmlspecialchars($msg, ENT_XML1));
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
                "reponse": "oxygen va recevoir un courriel avec votre demande."
             }
         }
     */

    // construction de l'élément "data"
    $elt_data = ["reponse" => $msg];

    // construction de la racine
    $elt_racine = ["data" => $elt_data];

    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// ================================================================================================
