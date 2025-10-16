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
if ($this->getMethodeRequete() != "POST") {
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
?>