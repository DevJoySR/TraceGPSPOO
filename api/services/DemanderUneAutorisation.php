<?php
// Projet TraceGPS - services web
// fichier :  api/services/DemandeUneAutorisation.php
// Dernière mise à jour : 16/10/2025 par VV

// Rôle : ce service web permet à un utilisateur de demander une autorisation à un autre utilisateur.

// Le service web doit être appelé avec 6 paramètres obligatoires dont les noms sont volontairement non significatifs :
// a : le pseudo de l'utilisateur qui demande l'autorisation
// b : le mot de passe hashé en sha1 de l'utilisateur qui demande l'autorisation
// c : le pseudo de l'utilisateur à qui on demande l'autorisation
// d : le texte d'un message accompagnant la demande
// e : le nom et le prénom du demandeur
// f : le langage utilisé pour le flux de données ("xml" ou "json")

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
$pseudoAutorise = ( empty($this->request['a'])) ? "" : $this->request['a'];
$mdpSha1 = ( empty($this->request['b'])) ? "" : $this->request['b'];
$pseudoAutorisant = ( empty($this->request['c'])) ?'': $this->request['c'];
$message = ( empty($this->request['d'])) ?'': $this->request['d'];
$unPrenomApres = ( empty($this->request['e'])) ?'': $this->request['e'];
$lang = ( empty($this->request['f'])) ?'': $this->request['f'];

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET")
{	$msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
}
else 
{
    // Test avec des paramètres incorrects ou incomplets
    if ( $pseudoAutorise == "" || $mdpSha1 == "" || $pseudoAutorisant == "" || $message =="" || $unPrenomApres == "" || $lang == "")
    {	$message = "Erreur : données incomplètes.";
        $code_reponse = 400;
    }
    else
    {

    }

}
unset($dao);   // ferme la connexion à MySQL
?>