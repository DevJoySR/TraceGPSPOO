<?php
// Projet TraceGPS - services web
// fichier :  api/services/EnvoyerPosition.php
// Dernière mise à jour : 17/10/2025 par VV

// Rôle : ce service web permet à un utilisateur authentifié d'envoyer sa position

// Le service web doit être appelé avec 9 paramètres obligatoires :
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

// Initialisation des variables de réponse
$msg = "";
$code_reponse = 200;
$idPoint = null;

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
            if ($laTrace == null)
            {
                $msg = "Erreur : le numéro de trace n'existe pas.";
                $code_reponse = 400;
            }
            else
            {   
                // Récupérer l'utilisateur authentifié
                $utilisateur = $dao->getUnUtilisateur($pseudo);
                $idUtilisateur = $utilisateur->getId();

                // Récupérer le propriétaire de la trace
                $idProprietaire = $laTrace->getIdUtilisateur();

                // Comparer les ID numériques
                if ($idProprietaire != $idUtilisateur)
                {
                    $msg = "Erreur : le numéro de trace ne correspond pas à cet utilisateur.";
                    $code_reponse = 400;
                }
                else
                {
                    if ($laTrace->getTerminee() == true || $laTrace->getTerminee() == 1)
                    {
                        $msg = "Erreur : cette trace est déjà terminée.";
                        $code_reponse = 400;
                    }
                    else
                    {
                        // Calculer le numéro du point
                        $lesPoints = $dao->getLesPointsDeTrace($unIdTrace);
                        $numPoint = count($lesPoints) + 1;

                        // Créer le nouveau point de trace
                        $nouveauPoint = new PointDeTrace(
                            $unIdTrace,
                            $numPoint,
                            $uneLatitude,
                            $uneLongitude,
                            $uneAltitude,
                            $uneDateHeure,
                            $unRythmeCardio,
                            0,
                            0,
                            0
                        );

                        // Enregistrer le point dans la base de données
                        $ok = $dao->creerUnPointDeTrace($nouveauPoint);

                        if ($ok == false)
                        {
                            $msg = "Erreur : problème lors de l'enregistrement du point.";
                            $code_reponse = 500;
                        }
                        else
                        {
                            // Créer l'ID du point (format: idTrace-numPoint)
                            $idPoint = $unIdTrace . "-" . $numPoint;
                            $msg = "Point créé.";
                            $code_reponse = 200;
                        }
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
    $donnees = creerFluxXML($msg, $idPoint);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON($msg, $idPoint);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;

// ================================================================================================

// création du flux XML en sortie
function creerFluxXML($msg, $idPoint = null)
{
    $doc = new DOMDocument();
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    $elt_commentaire = $doc->createComment('Service web EnvoyerPosition - BTS SIO - Lycée De La Salle - Rennes');
    $doc->appendChild($elt_commentaire);

    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);

    $elt_reponse = $doc->createElement('reponse', htmlspecialchars($msg, ENT_XML1));
    $elt_data->appendChild($elt_reponse);

    // Ajouter l'ID du point si disponible
    if ($idPoint !== null) {
        $elt_idPoint = $doc->createElement('idPoint', htmlspecialchars($idPoint, ENT_XML1));
        $elt_data->appendChild($elt_idPoint);
    }

    $doc->formatOutput = true;

    return $doc->saveXML();
}

// ================================================================================================

// création du flux JSON en sortie
function creerFluxJSON($msg, $idPoint = null)
{
    $elt_data = ["reponse" => $msg];
    
    // Ajouter l'ID du point si disponible
    if ($idPoint !== null) {
        $elt_data["idPoint"] = $idPoint;
    }
    
    $elt_racine = ["data" => $elt_data];

    return json_encode($elt_racine, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// ================================================================================================
?>
