<?php
// Projet TraceGPS - services web
// fichier : api/services/GetLesUtilisateursQueJautorise.php
// Dernière mise à jour : 16/10/2025 par DevJoySR

// Rôle : ce service web permet à un utilisateur d'obtenir la liste des utilisateurs qu'il autorise à consulter ses parcours.
// Le service web doit recevoir 2 paramètres :
//          • pseudo : le pseudo de l'utilisateur
//          • mdp : le mot de passe de l'utilisateur hashé en sha1
//          • lang : le langage utilisé pour le flux de données ("xml" ou "json")
// Description du traitement :
//          • Vérifier que les données transmises sont complètes
//          • Vérifier l'authentification de l'utilisateur
//          • Fournir la liste des utilisateurs qu'il autorise à consulter ses parcours
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// initialisation du nombre de réponses
$nbReponses = 0;
$lesUtilisateurs = array();

// On vérifie la méthode HTTP (préférer POST ici pour plus de sécurité)
if ($this->getMethodeRequete() != "POST" && $this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
    } else {
    // Les paramètres doivent être présents
    if ( $pseudo == "" || $mdpSha1 == "" )
    {	$msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    }
?>