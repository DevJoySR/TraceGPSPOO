<?php
// Projet TraceGPS - services web
// fichier : api/services/DemanderUneAutorisation.php
// Dernière mise à jour : 16/10/2025 par VV

// Rôle : ce service web permet à un utilisateur de demander une autorisation à un autre utilisateur.

// Le service web doit être appelé avec 6 paramètres obligatoires dont les noms sont volontairement non significatifs :
// pseudo : le pseudo de l'utilisateur qui demande l'autorisation
// mdp : le mot de passe hashé en sha1 de l'utilisateur qui demande l'autorisation
// pseudoDestinataire : le pseudo de l'utilisateur à qui on demande l'autorisation
// texteMessage : le texte d'un message accompagnant la demande
// nomPrenom : le nom et le prénom du demandeur
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
$pseudoAutorise = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = (empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoAutorisant = (empty($this->request['pseudoDestinataire'])) ? '' : $this->request['pseudoDestinataire'];
$texteMessage = (empty($this->request['texteMessage'])) ? '' : $this->request['texteMessage'];
$nomPrenom = (empty($this->request['nomPrenom'])) ? '' : $this->request['nomPrenom'];
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
    if ($pseudoAutorise == "" || $mdpSha1 == "" || $pseudoAutorisant == "" || $texteMessage == "" || $nomPrenom == "")
    {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    }
    else
    {
        // Test de l'authentification de l'utilisateur demandeur
        $niveauConnexion = $dao->getNiveauConnexion($pseudoAutorise, $mdpSha1);
        
        if ($niveauConnexion == 0)
        {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        }
        else
        {
            // Vérifier que le pseudo destinataire existe
            if (!$dao->existePseudoUtilisateur($pseudoAutorisant))
            {
                $msg = "Erreur : pseudo utilisateur inexistant.";
                $code_reponse = 400;
            }
            else
            {
                // Récupérer les utilisateurs
                $utilisateurAutorise = $dao->getUnUtilisateur($pseudoAutorise);
                $utilisateurAutorisant = $dao->getUnUtilisateur($pseudoAutorisant);

                $idAutorise = $utilisateurAutorise->getId();
                $idAutorisant = $utilisateurAutorisant->getId();
                $adrMailAutorisant = $utilisateurAutorisant->getAdrMail();

                // Vérifier que l'autorisation n'existe pas déjà
                if ($dao->autoriseAConsulter($idAutorisant, $idAutorise))
                {
                    $msg = "Erreur : autorisation déjà accordée.";
                    $code_reponse = 400;
                }
                else
                {
                    // Envoyer un mail de notification au destinataire
                    $sujetMail = "Demande d'autorisation TraceGPS de " . $nomPrenom;
                    $contenuMail = "Bonjour " . $pseudoAutorisant . ",\n\n";
                    $contenuMail .= $nomPrenom . " (" . $pseudoAutorise . ") souhaite que vous l'autorisiez à consulter vos parcours.\n\n";
                    $contenuMail .= "Message : " . $texteMessage . "\n\n";
                    $contenuMail .= "Cordialement,\n";
                    $contenuMail .= "L'administrateur du système TraceGPS";

                    $ok = Outils::envoyerMail($adrMailAutorisant, $sujetMail, $contenuMail, $ADR_MAIL_EMETTEUR);

                    if (!$ok)
                    {
                        $msg = "Erreur : l'envoi du courriel de demande d'autorisation a rencontré un problème.";
                        $code_reponse = 500;
                    }
                    else
                    {
                        $msg = $pseudoAutorisant . " va recevoir un courriel avec votre demande.";
                        $code_reponse = 200;
                    }
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
