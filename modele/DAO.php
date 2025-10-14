<?php
// Projet TraceGPS
// fichier : modele/DAO.php   (DAO : Data Access Object)
// Rôle : fournit des méthodes d'accès à la bdd tracegps (projet TraceGPS) au moyen de l'objet PDO
// modifié par dP le 12/8/2021

// liste des méthodes déjà développées (dans l'ordre d'apparition dans le fichier) :

// __construct() : le constructeur crée la connexion $cnx à la base de données
// __destruct() : le destructeur ferme la connexion $cnx à la base de données
// getNiveauConnexion($login, $mdp) : fournit le niveau (0, 1 ou 2) d'un utilisateur identifié par $login et $mdp
// existePseudoUtilisateur($pseudo) : fournit true si le pseudo $pseudo existe dans la table tracegps_utilisateurs, false sinon
// getUnUtilisateur($login) : fournit un objet Utilisateur à partir de $login (son pseudo ou son adresse mail)
// getTousLesUtilisateurs() : fournit la collection de tous les utilisateurs (de niveau 1)
// creerUnUtilisateur($unUtilisateur) : enregistre l'utilisateur $unUtilisateur dans la bdd
// modifierMdpUtilisateur($login, $nouveauMdp) : enregistre le nouveau mot de passe $nouveauMdp de l'utilisateur $login daprès l'avoir hashé en SHA1
// supprimerUnUtilisateur($login) : supprime l'utilisateur $login (son pseudo ou son adresse mail) dans la bdd, ainsi que ses traces et ses autorisations
// envoyerMdp($login, $nouveauMdp) : envoie un mail à l'utilisateur $login avec son nouveau mot de passe $nouveauMdp

// liste des méthodes restant à développer :

// existeAdrMailUtilisateur($adrmail) : fournit true si l'adresse mail $adrMail existe dans la table tracegps_utilisateurs, false sinon
// getLesUtilisateursAutorises($idUtilisateur) : fournit la collection  des utilisateurs (de niveau 1) autorisés à suivre l'utilisateur $idUtilisateur
// getLesUtilisateursAutorisant($idUtilisateur) : fournit la collection  des utilisateurs (de niveau 1) autorisant l'utilisateur $idUtilisateur à voir leurs parcours
// autoriseAConsulter($idAutorisant, $idAutorise) : vérifie que l'utilisateur $idAutorisant) autorise l'utilisateur $idAutorise à consulter ses traces
// creerUneAutorisation($idAutorisant, $idAutorise) : enregistre l'autorisation ($idAutorisant, $idAutorise) dans la bdd
// supprimerUneAutorisation($idAutorisant, $idAutorise) : supprime l'autorisation ($idAutorisant, $idAutorise) dans la bdd
// getLesPointsDeTrace($idTrace) : fournit la collection des points de la trace $idTrace
// getUneTrace($idTrace) : fournit un objet Trace à partir de identifiant $idTrace
// getToutesLesTraces() : fournit la collection de toutes les traces
// getLesTraces($idUtilisateur) : fournit la collection des traces de l'utilisateur $idUtilisateur
// getLesTracesAutorisees($idUtilisateur) : fournit la collection des traces que l'utilisateur $idUtilisateur a le droit de consulter
// creerUneTrace(Trace $uneTrace) : enregistre la trace $uneTrace dans la bdd
// terminerUneTrace($idTrace) : enregistre la fin de la trace d'identifiant $idTrace dans la bdd ainsi que la date de fin
// supprimerUneTrace($idTrace) : supprime la trace d'identifiant $idTrace dans la bdd, ainsi que tous ses points
// creerUnPointDeTrace(PointDeTrace $unPointDeTrace) : enregistre le point $unPointDeTrace dans la bdd


// certaines méthodes nécessitent les classes suivantes :
include_once ('Utilisateur.php');
include_once ('Trace.class.php');
include_once ('PointDeTrace.php');
include_once ('Point.php');
include_once ('Outils.php');

// inclusion des paramètres de l'application
include_once ('parametres.php');

// début de la classe DAO (Data Access Object)
class DAO
{
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Membres privés de la classe ---------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    private $cnx;				// la connexion à la base de données
    
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Constructeur et destructeur ---------------------------------------
    // ------------------------------------------------------------------------------------------------------
    public function __construct() {
        global $PARAM_HOTE, $PARAM_PORT, $PARAM_BDD, $PARAM_USER, $PARAM_PWD;
        try
        {	$this->cnx = new PDO ("mysql:host=" . $PARAM_HOTE . ";port=" . $PARAM_PORT . ";dbname=" . $PARAM_BDD,
            $PARAM_USER,
            $PARAM_PWD);
        return true;
        }
        catch (Exception $ex)
        {	echo ("Echec de la connexion a la base de donnees <br>");
        echo ("Erreur numero : " . $ex->getCode() . "<br />" . "Description : " . $ex->getMessage() . "<br>");
        echo ("PARAM_HOTE = " . $PARAM_HOTE);
        return false;
        }
    }
    
    public function __destruct() {
        // ferme la connexion à MySQL :
        unset($this->cnx);
    }
    
    // ------------------------------------------------------------------------------------------------------
    // -------------------------------------- Méthodes d'instances ------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    // fournit le niveau (0, 1 ou 2) d'un utilisateur identifié par $pseudo et $mdpSha1
    // cette fonction renvoie un entier :
    //     0 : authentification incorrecte
    //     1 : authentification correcte d'un utilisateur (pratiquant ou personne autorisée)
    //     2 : authentification correcte d'un administrateur
    // modifié par dP le 11/1/2018
    public function getNiveauConnexion($pseudo, $mdpSha1) {
        // préparation de la requête de recherche
        $txt_req = "Select niveau from tracegps_utilisateurs";
        $txt_req .= " where pseudo = :pseudo";
        $txt_req .= " and mdpSha1 = :mdpSha1";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        $req->bindValue("mdpSha1", $mdpSha1, PDO::PARAM_STR);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        // traitement de la réponse
        $reponse = 0;
        if ($uneLigne) {
        	$reponse = $uneLigne->niveau;
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        // fourniture de la réponse
        return $reponse;
    }
    
    
    // fournit true si le pseudo $pseudo existe dans la table tracegps_utilisateurs, false sinon
    // modifié par dP le 27/12/2017
    public function existePseudoUtilisateur($pseudo) {
        // préparation de la requête de recherche
        $txt_req = "Select count(*) from tracegps_utilisateurs where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        // exécution de la requête
        $req->execute();
        $nbReponses = $req->fetchColumn(0);
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        // fourniture de la réponse
        if ($nbReponses == 0) {
            return false;
        }
        else {
            return true;
        }
    }
    
    
    // fournit un objet Utilisateur à partir de son pseudo $pseudo
    // fournit la valeur null si le pseudo n'existe pas
    // modifié par dP le 9/1/2018
    public function getUnUtilisateur($pseudo) {
        // préparation de la requête de recherche
        $txt_req = "Select id, pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation, nbTraces, dateDerniereTrace";
        $txt_req .= " from tracegps_vue_utilisateurs";
        $txt_req .= " where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        // traitement de la réponse
        if ( ! $uneLigne) {
            return null;
        }
        else {
            // création d'un objet Utilisateur
            $unId = mb_convert_encoding($uneLigne->id, 'UTF-8', 'ISO-8859-1');
            $unPseudo = mb_convert_encoding($uneLigne->pseudo, 'UTF-8', 'ISO-8859-1');
            $unMdpSha1 = mb_convert_encoding($uneLigne->mdpSha1, 'UTF-8', 'ISO-8859-1');
            $uneAdrMail = mb_convert_encoding($uneLigne->adrMail, 'UTF-8', 'ISO-8859-1');
            $unNumTel = mb_convert_encoding($uneLigne->numTel, 'UTF-8', 'ISO-8859-1');
            $unNiveau = mb_convert_encoding($uneLigne->niveau, 'UTF-8', 'ISO-8859-1');
            $uneDateCreation = mb_convert_encoding($uneLigne->dateCreation, 'UTF-8', 'ISO-8859-1');
            $unNbTraces = mb_convert_encoding($uneLigne->nbTraces, 'UTF-8', 'ISO-8859-1');
            $uneDateDerniereTrace = isset($uneLigne->dateDerniereTrace)? mb_convert_encoding($uneLigne->dateDerniereTrace, 'UTF-8', 'ISO-8859-1'): "";
            
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            return $unUtilisateur;
        }
    }
    
    
    // fournit la collection  de tous les utilisateurs (de niveau 1)
    // le résultat est fourni sous forme d'une collection d'objets Utilisateur
    // modifié par dP le 27/12/2017
    public function getTousLesUtilisateurs() {
        // préparation de la requête de recherche
        $txt_req = "Select id, pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation, nbTraces, dateDerniereTrace";
        $txt_req .= " from tracegps_vue_utilisateurs";
        $txt_req .= " where niveau = 1";
        $txt_req .= " order by pseudo";
        
        $req = $this->cnx->prepare($txt_req);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        // construction d'une collection d'objets Utilisateur
        $lesUtilisateurs = array();
        // tant qu'une ligne est trouvée :
        while ($uneLigne) {
            // création d'un objet Utilisateur
            $unId = mb_convert_encoding($uneLigne->id, 'UTF-8', 'ISO-8859-1');
            $unPseudo = mb_convert_encoding($uneLigne->pseudo, 'UTF-8', 'ISO-8859-1');
            $unMdpSha1 = mb_convert_encoding($uneLigne->mdpSha1, 'UTF-8', 'ISO-8859-1');
            $uneAdrMail = mb_convert_encoding($uneLigne->adrMail, 'UTF-8', 'ISO-8859-1');
            $unNumTel = mb_convert_encoding($uneLigne->numTel, 'UTF-8', 'ISO-8859-1');
            $unNiveau = mb_convert_encoding($uneLigne->niveau, 'UTF-8', 'ISO-8859-1');
            $uneDateCreation = mb_convert_encoding($uneLigne->dateCreation, 'UTF-8', 'ISO-8859-1');
            $unNbTraces = mb_convert_encoding($uneLigne->nbTraces, 'UTF-8', 'ISO-8859-1');
            $uneDateDerniereTrace = isset($uneLigne->dateDerniereTrace)? mb_convert_encoding($uneLigne->dateDerniereTrace, 'UTF-8', 'ISO-8859-1'): "";
            
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            // ajout de l'utilisateur à la collection
            $lesUtilisateurs[] = $unUtilisateur;
            // extrait la ligne suivante
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        // fourniture de la collection
        return $lesUtilisateurs;
    }

    
    // enregistre l'utilisateur $unUtilisateur dans la bdd
    // fournit true si l'enregistrement s'est bien effectué, false sinon
    // met à jour l'objet $unUtilisateur avec l'id (auto_increment) attribué par le SGBD
    // modifié par dP le 9/1/2018
    public function creerUnUtilisateur($unUtilisateur) {
        // on teste si l'utilisateur existe déjà
        if ($this->existePseudoUtilisateur($unUtilisateur->getPseudo())) return false;
        
        // préparation de la requête
        $txt_req1 = "insert into tracegps_utilisateurs (pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation)";
        $txt_req1 .= " values (:pseudo, :mdpSha1, :adrMail, :numTel, :niveau, :dateCreation)";
        $req1 = $this->cnx->prepare($txt_req1);
        // liaison de la requête et de ses paramètres
        $req1->bindValue("pseudo", mb_convert_encoding($unUtilisateur->getPseudo(), 'UTF-8', 'ISO-8859-1'), PDO::PARAM_STR);
        $req1->bindValue("mdpSha1", mb_convert_encoding(sha1($unUtilisateur->getMdpsha1()), 'UTF-8', 'ISO-8859-1'), PDO::PARAM_STR);
        $req1->bindValue("adrMail", mb_convert_encoding($unUtilisateur->getAdrmail(), 'UTF-8', 'ISO-8859-1'), PDO::PARAM_STR);
        $req1->bindValue("numTel", mb_convert_encoding($unUtilisateur->getNumTel(), 'UTF-8', 'ISO-8859-1'), PDO::PARAM_STR);
        $req1->bindValue("niveau", mb_convert_encoding($unUtilisateur->getNiveau(), 'UTF-8', 'ISO-8859-1'), PDO::PARAM_INT);
        $req1->bindValue("dateCreation", mb_convert_encoding($unUtilisateur->getDateCreation(), 'UTF-8', 'ISO-8859-1'), PDO::PARAM_STR);
        // exécution de la requête
        $ok = $req1->execute();
        // sortir en cas d'échec
        if ( ! $ok) { return false; }
        
        // recherche de l'identifiant (auto_increment) qui a été attribué à la trace
        $unId = $this->cnx->lastInsertId();
        $unUtilisateur->setId($unId);
        return true;
    }
    
    
    // enregistre le nouveau mot de passe $nouveauMdp de l'utilisateur $pseudo daprès l'avoir hashé en SHA1
    // fournit true si la modification s'est bien effectuée, false sinon
    // modifié par dP le 9/1/2018
    public function modifierMdpUtilisateur($pseudo, $nouveauMdp) {
        // préparation de la requête
        $txt_req = "update tracegps_utilisateurs set mdpSha1 = :nouveauMdp";
        $txt_req .= " where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("nouveauMdp", sha1($nouveauMdp), PDO::PARAM_STR);
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        // exécution de la requête
        $ok = $req->execute();
        return $ok;
    }
    
    
    // supprime l'utilisateur $pseudo dans la bdd, ainsi que ses traces et ses autorisations
    // fournit true si l'effacement s'est bien effectué, false sinon
    // modifié par dP le 9/1/2018
    public function supprimerUnUtilisateur($pseudo) {
        $unUtilisateur = $this->getUnUtilisateur($pseudo);
        if ($unUtilisateur == null) {
            return false;
        }
        else {
            $idUtilisateur = $unUtilisateur->getId();
            
            // suppression des traces de l'utilisateur (et des points correspondants)
            $lesTraces = $this->getLesTraces($idUtilisateur);
            if($lesTraces != null)
			{
				foreach ($lesTraces as $uneTrace) {
					$this->supprimerUneTrace($uneTrace->getId());
				}
            }
            // préparation de la requête de suppression des autorisations
            $txt_req1 = "delete from tracegps_autorisations" ;
            $txt_req1 .= " where idAutorisant = :idUtilisateur or idAutorise = :idUtilisateur";
            $req1 = $this->cnx->prepare($txt_req1);
            // liaison de la requête et de ses paramètres
            $req1->bindValue("idUtilisateur", mb_convert_encoding($idUtilisateur, 'UTF-8', 'ISO-8859-1'), PDO::PARAM_INT);
            // exécution de la requête
            $ok = $req1->execute();
            
            // préparation de la requête de suppression de l'utilisateur
            $txt_req2 = "delete from tracegps_utilisateurs" ;
            $txt_req2 .= " where pseudo = :pseudo";
            $req2 = $this->cnx->prepare($txt_req2);
            // liaison de la requête et de ses paramètres
            $req2->bindValue("pseudo", mb_convert_encoding($pseudo, 'UTF-8', 'ISO-8859-1'), PDO::PARAM_STR);
            // exécution de la requête
            $ok = $req2->execute();
            return $ok;
        }
    }
    
    
    // envoie un mail à l'utilisateur $pseudo avec son nouveau mot de passe $nouveauMdp
    // retourne true si envoi correct, false en cas de problème d'envoi
    // modifié par dP le 9/1/2018
    public function envoyerMdp($pseudo, $nouveauMdp) {
        global $ADR_MAIL_EMETTEUR;
        // si le pseudo n'est pas dans la table tracegps_utilisateurs :
        if ( $this->existePseudoUtilisateur($pseudo) == false ) return false;
        
        // recherche de l'adresse mail
        $adrMail = $this->getUnUtilisateur($pseudo)->getAdrMail();
        
        // envoie un mail à l'utilisateur avec son nouveau mot de passe
        $sujet = "Modification de votre mot de passe d'accès au service TraceGPS";
        $message = "Cher(chère) " . $pseudo . "\n\n";
        $message .= "Votre mot de passe d'accès au service service TraceGPS a été modifié.\n\n";
        $message .= "Votre nouveau mot de passe est : " . $nouveauMdp ;
        $ok = Outils::envoyerMail ($adrMail, $sujet, $message, $ADR_MAIL_EMETTEUR);
        return $ok;
    }
    
    
    // Le code restant à développer va être réparti entre les membres de l'équipe de développement.
    // Afin de limiter les conflits avec GitHub, il est décidé d'attribuer une zone de ce fichier à chaque développeur.
    // Développeur 1 : lignes 350 à 616
    // Développeur 2 : lignes 617 à 883
    // Développeur 3 : lignes 884 à 1150
    
    // Quelques conseils pour le travail collaboratif :
    // avant d'attaquer un cycle de développement (début de séance, nouvelle méthode, ...), faites un Pull pour récupérer 
    // la dernière version du fichier.
    // Après avoir testé et validé une méthode, faites un commit et un push pour transmettre cette version aux autres développeurs.
    
    
    
    
    
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 1 (Adrien Sudja) : lignes 350 à 549
    // --------------------------------------------------------------------------------------
    public function existeAdrMailUtilisateur($adrMail)
    /*
    *   Indique si $adrMail existe dans la table tracegps_utilisateurs
    *
    *   @param : string $adrMail
    *   @returns : bool True si l'adresse existe ou au contraire, false
    */
    {
        // préparation de la requête de recherche
        $txt_req = "select * from tracegps_utilisateurs where adrMail = :adrMail";
        $req = $this->cnx->prepare($txt_req);

        // liaison de la requête et de ses paramètres
        $req->bindValue(":adrMail", $adrMail, PDO::PARAM_STR);

        // exécution de la requête
        $req->execute();

        // extrait la ligne suivante
        $resultat = $req->fetch(PDO::FETCH_OBJ);

        if ($resultat) {
            return true;
        } 
        else {
            return false;
        }
    }

    public function getLesUtilisateursAutorisant($idUtilisateur)
    /*
    *   Fournit la collection des utilisateurs (de niveau 1) autorisant l'utilisateur $idUtilisateur à voir leurs parcours
    *   
    *   @param : string $idUtilisateur
    *   @returns : la collection des utilisateurs qui ont donné l'autorisation à $idUtilisateur, soit collection d'objets Utilisateur
    */
    {
        // préparation de la requête de recherche
        $txt_req = "SELECT tracegps_vue_utilisateurs.id, tracegps_vue_utilisateurs.pseudo, tracegps_vue_utilisateurs.mdpSha1, tracegps_vue_utilisateurs.adrMail, ";
        $txt_req .= "tracegps_vue_utilisateurs.numTel, tracegps_vue_utilisateurs.niveau, tracegps_vue_utilisateurs.dateCreation, tracegps_vue_utilisateurs.dateDerniereTrace, ";
        $txt_req .= "(SELECT COUNT(*) FROM tracegps_traces WHERE tracegps_traces.idUtilisateur = tracegps_vue_utilisateurs.id) AS nbTraces, ";
        $txt_req .= "tracegps_autorisations.idAutorisant ";
        $txt_req .= "FROM tracegps_vue_utilisateurs ";
        $txt_req .= "INNER JOIN tracegps_autorisations ON tracegps_vue_utilisateurs.id = tracegps_autorisations.idAutorisant ";
        $txt_req .= "WHERE tracegps_vue_utilisateurs.niveau = 1 AND tracegps_autorisations.idAutorise = :idUtilisateur ";
        $txt_req .= "ORDER BY tracegps_vue_utilisateurs.pseudo";

        $req = $this->cnx->prepare($txt_req);

        // liaison de la requête et de ses paramètres
        $req->bindValue(":idUtilisateur", $idUtilisateur, PDO::PARAM_INT);
        
        // exécution de la requête
        $req->execute();

        // construction d'une collection d'objets Utilisateur
        $lesUtilisateurs = array();

        // tant qu'une ligne est trouvée :
        while ($uneLigne = $req->fetch(PDO::FETCH_OBJ)) {
            // création d'un objet Utilisateur
            $unId = mb_convert_encoding($uneLigne->id, 'UTF-8', 'ISO-8859-1');
            $unPseudo = mb_convert_encoding($uneLigne->pseudo, 'UTF-8', 'ISO-8859-1');
            $unMdpSha1 = mb_convert_encoding($uneLigne->mdpSha1, 'UTF-8', 'ISO-8859-1');
            $uneAdrMail = mb_convert_encoding($uneLigne->adrMail, 'UTF-8', 'ISO-8859-1');
            $unNumTel = mb_convert_encoding($uneLigne->numTel, 'UTF-8', 'ISO-8859-1');
            $unNiveau = mb_convert_encoding($uneLigne->niveau, 'UTF-8', 'ISO-8859-1');
            $uneDateCreation = mb_convert_encoding($uneLigne->dateCreation, 'UTF-8', 'ISO-8859-1');
            $unNbTraces = mb_convert_encoding($uneLigne->nbTraces, 'UTF-8', 'ISO-8859-1');
            $uneDateDerniereTrace = mb_convert_encoding($uneLigne->dateDerniereTrace, 'UTF-8', 'ISO-8859-1');

            $unUtilisateur = new Utilisateur(
                $unId,
                $unPseudo,
                $unMdpSha1,
                $uneAdrMail,
                $unNumTel,
                $unNiveau,
                $uneDateCreation,
                $unNbTraces,
                $uneDateDerniereTrace
            );

            // ajout de l'utilisateur à la collection
            $lesUtilisateurs[] = $unUtilisateur;
        }

        $req->closeCursor();

    return $lesUtilisateurs;
}

    public function getLesUtilisateursAutorises($idUtilisateur)
    /*
    *   Fournit la collection des utilisateurs (de niveau 1) autorisés à voir les parcours de l'utilisateur $idUtilisateur
    *   
    *   @param : string $idUtilisateur
    *   @returns : la collection des utilisateurs qui ont donné l'autorisation à $idUtilisateur, soit collection d'objets Utilisateur
    */
    {
        // préparation de la requête de recherche
        $txt_req = "SELECT tracegps_vue_utilisateurs.id, tracegps_vue_utilisateurs.pseudo, tracegps_vue_utilisateurs.mdpSha1, tracegps_vue_utilisateurs.adrMail, ";
        $txt_req .= "tracegps_vue_utilisateurs.numTel, tracegps_vue_utilisateurs.niveau, tracegps_vue_utilisateurs.dateCreation, tracegps_vue_utilisateurs.dateDerniereTrace, ";
        $txt_req .= "(SELECT COUNT(*) FROM tracegps_traces WHERE tracegps_traces.idUtilisateur = tracegps_vue_utilisateurs.id) AS nbTraces, ";
        $txt_req .= "tracegps_autorisations.idAutorise ";
        $txt_req .= "FROM tracegps_vue_utilisateurs ";
        $txt_req .= "INNER JOIN tracegps_autorisations ON tracegps_vue_utilisateurs.id = tracegps_autorisations.idAutorise ";
        $txt_req .= "WHERE tracegps_vue_utilisateurs.niveau = 1 AND tracegps_autorisations.idAutorisant = :idUtilisateur ";
        $txt_req .= "ORDER BY tracegps_vue_utilisateurs.pseudo";

        $req = $this->cnx->prepare($txt_req);

        // liaison de la requête et de ses paramètres
        $req->bindValue(":idUtilisateur", $idUtilisateur, PDO::PARAM_INT);
        
        // exécution de la requête
        $req->execute();

        // construction d'une collection d'objets Utilisateur
        $lesUtilisateurs = array();

        // tant qu'une ligne est trouvée :
        while ($uneLigne = $req->fetch(PDO::FETCH_OBJ)) {
            // création d'un objet Utilisateur
            $unId = mb_convert_encoding($uneLigne->id, 'UTF-8', 'ISO-8859-1');
            $unPseudo = mb_convert_encoding($uneLigne->pseudo, 'UTF-8', 'ISO-8859-1');
            $unMdpSha1 = mb_convert_encoding($uneLigne->mdpSha1, 'UTF-8', 'ISO-8859-1');
            $uneAdrMail = mb_convert_encoding($uneLigne->adrMail, 'UTF-8', 'ISO-8859-1');
            $unNumTel = mb_convert_encoding($uneLigne->numTel, 'UTF-8', 'ISO-8859-1');
            $unNiveau = mb_convert_encoding($uneLigne->niveau, 'UTF-8', 'ISO-8859-1');
            $uneDateCreation = mb_convert_encoding($uneLigne->dateCreation, 'UTF-8', 'ISO-8859-1');
            $unNbTraces = mb_convert_encoding($uneLigne->nbTraces, 'UTF-8', 'ISO-8859-1');
            $uneDateDerniereTrace = mb_convert_encoding($uneLigne->dateDerniereTrace, 'UTF-8', 'ISO-8859-1');

            $unUtilisateur = new Utilisateur(
                $unId,
                $unPseudo,
                $unMdpSha1,
                $uneAdrMail,
                $unNumTel,
                $unNiveau,
                $uneDateCreation,
                $unNbTraces,
                $uneDateDerniereTrace
            );

            // ajout de l'utilisateur à la collection
            $lesUtilisateurs[] = $unUtilisateur;
        }

        $req->closeCursor();

    return $lesUtilisateurs;
}

    public function autoriseAConsulter($idAutorisant, $idAutorise)
    /*
    *   Indique si l'utilisateur $idAutorisant autorise l'utilisateur $idAutorise à consulter ses traces
    *   
    *   @param : $idAutorisant : l'id de l'utilisateur qui autorise
    *            $idAutorise : l'id de l'utilisateur qui est autorisé
    *   @returns : true si l'autorisation est donnée false sinon
    */
    {
        // préparation de la requête de recherche
        $txt_req = "SELECT COUNT(*) AS nb FROM tracegps_autorisations";
        $txt_req .= " WHERE idAutorisant = :idAutorisant AND idAutorise = :idAutorise";
    
        $req = $this->cnx->prepare($txt_req);

        // liaison de la requête et de ses paramètres
        $req->bindValue(":idAutorisant", $idAutorisant, PDO::PARAM_INT);
        $req->bindValue(":idAutorise", $idAutorise, PDO::PARAM_INT);

        // exécution de la requête
        $req->execute();

        // extrait la ligne suivante
        $resultat = $req->fetch(PDO::FETCH_OBJ);

    if ($resultat && $resultat->nb > 0) {
        return true;
    } 
    else {
        return false;
    }
}

    public function creerUneAutorisation($idAutorisant, $idAutorise)
    /*
    *   Enregistre l'autorisation ($idAutorisant, $idAutorise) dans la table tracegps_autorisations
    *   
    *   Appel de la méthode autoriseAConsulter() afin de déterminer si l'autorisation a déjà été emise
    *
    *   @param : $idAutorisant : l'id de l'utilisateur qui autorise
    *            $idAutorise : l'id de l'utilisateur qui est autorisé
    *   @returns : true  si l'enregistrement s'est bien passé false sinon
    */
    {
        // // préparation de la requête de recherche
        // $txt_req = "SELECT COUNT(*) AS nb FROM tracegps_autorisations";
        // $txt_req .= " WHERE idAutorisant = :idAutorisant AND idAutorise = :idAutorise";
        // $req = $this->cnx->prepare($txt_req);

        // // liaison de la requête et de ses paramètres
        // $req->bindValue(":idAutorisant", $idAutorisant, PDO::PARAM_INT);
        // $req->bindValue(":idAutorise", $idAutorise, PDO::PARAM_INT);

        // // exécution de la requête
        // $req->execute();

        // // extrait la ligne suivante
        // $resultat = $req->fetch(PDO::FETCH_OBJ);

        // if ($resultat && $resultat->nb > 0) {
        //     return false;
        // }
        
        $dao = new DAO();
        $ok=$dao->autoriseAConsulter($idAutorisant, $idAutorise);

        if($ok)
        { 
            return false;
        }
        $txt_req2 = "INSERT INTO tracegps_autorisations (idAutorisant, idAutorise)";
        $txt_req2 .= " VALUES(:idAutorisant, :idAutorise)";
    
        $req2 = $this->cnx->prepare($txt_req2);

        // liaison de la requête et de ses paramètres
        $req2->bindValue(":idAutorisant", $idAutorisant, PDO::PARAM_INT);
        $req2->bindValue(":idAutorise", $idAutorise, PDO::PARAM_INT);

        // exécution de la requête
        $req2->execute();

        // extrait la ligne suivante
        $resultat2 = $req2->fetch(PDO::FETCH_OBJ);

        if ($resultat2) {
        return true;
    } 
    else {
        return false;
    }
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 2 (Arthur Théard) : lignes 550 à 749
    // --------------------------------------------------------------------------------------




    public function creerUneTrace($uneTrace) {

        /*         creerUneTrace($uneTrace)
         @Rôle : enregistre la trace $uneTrace dans la table tracegps_traces et met à jour l'objet $uneTrace
         avec l'identifiant (auto_increment) attribué par le SGBD
         Paramètres à fournir :
         $uneTrace : la trace à enregistrer
         @Valeur de retour : un booléen
        true si l'enregistrement s'est bien passé
        false sinon
        Particularités :
        -      Si la date de fin est nulle (cas d'une trace non terminée), le champ dateFin prendra une valeur
                nulle (PDO::PARAM_NULL) ; sinon il prendra une valeur chaine (PDO::PARAM_STR).
        - On n'enregistre pas les points de la trace, même si l'objet $uneTrace en contient.


        @return : true or false
            */


        $txt_req = "INSERT INTO tracegps_traces (dateDebut, dateFin, terminee, idUtilisateur)";
        $txt_req .= " values (:dateDebut, :dateFin, :terminee, :IdUtilisateur)";

        $req = $this->cnx->prepare($txt_req);
       

        //$req->bindValue(":id", mb_convert_encoding($UneTrace->getid , 'UTF-8', 'ISO-8859-1'), PDO::PARAM_INT);
        $req->bindValue(":dateDebut",$uneTrace->getDateHeureDebut(), PDO::PARAM_STR);
        // On regarde si dateFin n'est pas null (elle l'est si la trace n'est pas terminée)
        // on le remplacera donc par null
        if ($uneTrace->getDateHeureFin() === null) {
            $req->bindValue(":dateFin", null, PDO::PARAM_NULL);
        } else {
            $req->bindValue(":dateFin",$uneTrace->getDateHeureFin(), PDO::PARAM_STR);
        }
        $req->bindValue(":terminee",$uneTrace->getTerminee(), PDO::PARAM_STR);
        $req->bindValue(":IdUtilisateur",$uneTrace->getIdUtilisateur() , PDO::PARAM_INT);

        // exécution de la requête
        $ok = $req->execute();
        // sortir en cas d'échec
        if ( ! $ok) { return false; }
        
        // recherche de l'identifiant (auto_increment) qui a été attribué à la trace
        $unId = $this->cnx->lastInsertId();
        $uneTrace->setId($unId);
        return true;
}



    public function supprimerUneTrace($idTrace){
        /*
        @Rôle : supprime la trace d'identifiant $idTrace dans la table tracegps_traces, ainsi que tous ses points
        dans la table tracegps_points
        Paramètres à fournir :
        $idTrace : l'identifiant de la trace à supprimer
        @Valeur de retour : un booléen
        true si la suppression s'est bien passée
        false sinon
        */

        // préparation de la requête pour la table tracegps_points
            $txt_req = "DELETE FROM tracegps_points" ;
            $txt_req .= " WHERE idTrace = :idTrace";
            $req = $this->cnx->prepare($txt_req);
            // liaison de la requête et de ses paramètres
            $req->bindValue("idTrace",$idTrace, PDO::PARAM_INT);
            // exécution de la requête
            $ok = $req->execute();


         // préparation de la requête pour la table tracegps_traces
            $txt_req1 = "DELETE FROM tracegps_traces" ;
            $txt_req1 .= " WHERE id = :idTrace";
            $req1 = $this->cnx->prepare($txt_req1);
            $req1->bindValue("idTrace",$idTrace, PDO::PARAM_INT);
            $ok1 = $req1->execute();

            

            
        if ( ! $ok && ! $ok1) { return false; }
    
        return true;
    }

    public function terminerUneTrace(int $idTrace): bool
{
    // 1) Chercher la date du dernier point
    $sqlMax = "SELECT MAX(dateHeure)
               FROM tracegps_points
               WHERE idTrace = :idTrace";

    $stmtMax = $this->cnx->prepare($sqlMax);
    $stmtMax->bindValue(':idTrace', $idTrace, PDO::PARAM_INT);
    if (!$stmtMax->execute()) {
        return false;
    }
    $lastDate = $stmtMax->fetchColumn(); // string 'YYYY-mm-dd HH:ii:ss' ou false/null
    $stmtMax->closeCursor();

    // 2) Choisir la dateFin (dernier point ou date système)
    $dateFin = $lastDate ?: date('Y-m-d H:i:s');

    // 3) Mettre à jour la trace
    $sqlUpd = "UPDATE tracegps_traces
               SET terminee = 1,
                   dateFin  = :dateFin
               WHERE id = :idTrace";

    $stmtUpd = $this->cnx->prepare($sqlUpd);
    $stmtUpd->bindValue(':dateFin',  $dateFin,  PDO::PARAM_STR);
    $stmtUpd->bindValue(':idTrace',  $idTrace,  PDO::PARAM_INT);

    if (!$stmtUpd->execute()) {
        return false;
    }
    return $stmtUpd->rowCount() > 0;
}




        public function getLesTraces($idUtilisateur)

        /*
        @Rôle : >Fournit la collection des traces d'un utilisateurs
        Paramètres à fournir :
        $idUtilisateur : l'identifiant de l'utilisateur
        @Valeur de retour : une collection d'objet Trace
        */
        {
            $txt_req = "SELECT id, terminee, dateDebut, dateFin" ;
            $txt_req .= "FROM tracegps_traces" ;
            $txt_req .= " WHERE idUtilisateur = :idUtilisateur";

            $req = $this->cnx->prepare($txt_req);
            // liaison de la requête et de ses paramètres
            $req->bindValue("idUtilisateur",$idUtilisateur, PDO::PARAM_INT);
            // exécution de la requête
            $req->execute();
            $lesTraces = array();
            
            // Parcours des résultats et création des objets PointDeTrace
    while ($uneLigne = $req->fetch(PDO::FETCH_OBJ)) {
        
        // Création d'un objet PointDeTrace
            $unPoint = new Trace(
            $uneLigne->id,
            $uneLigne->dateDebut,
            $uneLigne->dateFin,
            $uneLigne->$idUtilisateur,
            $uneLigne->terminee,

        );
        
        // Ajout de l'objet à la collection
        $lesTraces[] = $unPoint;
        
    }
    return $lesTraces;

        }
    
    


    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 3 (Valentin Verdier) : lignes 750 à 949
    // --------------------------------------------------------------------------------------
   
    //méthode pour supprimer une autorisation
    public function supprimerUneAutorisation($idAutorisant, $idAutorise):bool
        /*
        *   Enregistre l'autorisation ($idAutorisant, $idAutorise) dans la table tracegps_autorisations
        *   
        *   Appel de la méthode autoriseAConsulter() afin de déterminer si l'autorisation a déjà été emise
        *
        *   @param : $idAutorisant : l'id de l'utilisateur qui autorise
        *            $idAutorise : l'id de l'utilisateur qui est autorisé
        *   @returns : true  si la suppression s'est bien passé false sinon
        */
    {
        $dao = new DAO();
        $ok=$dao->autoriseAConsulter($idAutorisant, $idAutorise);

        if($ok)
        { 
            return true;
        }
        $txt_req2 = "DELETE FROM tracegps_autorisations (idAutorisant, idAutorise)";
        $txt_req2 .= " VALUES(:idAutorisant, :idAutorise)";
    
        $req2 = $this->cnx->prepare($txt_req2);

        // liaison de la requête et de ses paramètres
        $req2->bindValue(":idAutorisant", $idAutorisant, PDO::PARAM_INT);
        $req2->bindValue(":idAutorise", $idAutorise, PDO::PARAM_INT);

        // exécution de la requête
        $req2->execute();

        // extrait la ligne suivante
        $resultat2 = $req2->fetch(PDO::FETCH_OBJ);

        if ($resultat2) 
        {
        return true;
        } 
        else 
        {
        return false;
        }
    }
    
   
        
public function getLesPointsDeTrace($idTrace) 
        /*
        * rôle : fournit la collection des points de la trace $idTrace
        * @param : $idTrace : identifiant de la trace
        * @return : collection d'objets PointDeTrace
        *           la collection des points de la trace $idTrace
        */
{
    // Préparation de la collection à retourner
    $lesPoints = array();
    
    // Préparation de la requête SQL
    $txt_req = "SELECT * FROM tracegps_points";
    $txt_req .= " WHERE idTrace = :idTrace";
    $txt_req .= " ORDER BY id";
    
    // Préparation et exécution de la requête
    $req = $this->cnx->prepare($txt_req);
    $req->bindValue("idTrace", $idTrace, PDO::PARAM_INT);
    $req->execute();
    
    // Parcours des résultats et création des objets PointDeTrace
    while ($uneLigne = $req->fetch(PDO::FETCH_OBJ)) {
        
        // Création d'un objet PointDeTrace
        $unPoint = new PointDeTrace(
            $uneLigne->idTrace,
            $uneLigne->id,
            $uneLigne->latitude,
            $uneLigne->longitude,
            $uneLigne->altitude,
            $uneLigne->dateHeure,
            $uneLigne->rythmeCardio,
            $uneLigne->tempsCumule ?? 0,
            $uneLigne->distanceCumulee ?? 0,
            $uneLigne->vitesse ?? 0,
        );
        
        // Ajout de l'objet à la collection
        $lesPoints[] = $unPoint;
    }
    
    // Libération des ressources
    $req->closeCursor();
    
    // Retour de la collection
    return $lesPoints;
}





public function creerUnPointDeTrace($unPointDeTrace)
        /*
        * rôle : fournit la collection des points de la trace $idTrace
        * @param : $idTrace : identifiant de la trace
        * @return : collection d'objets PointDeTrace
        *           la collection des points de la trace $idTrace
        * @speciality : Si le point est le premier d'une trace ($id = 1), il faut modifier la date de début de la trace en lui
        *               affectant la date du point
        */
{
    try {
        
        // Insertion du point de trace
        $txt_req = "INSERT INTO tracegps_points 
                    (idTrace, id, latitude, longitude, altitude, dateHeure, 
                     rythmeCardio)
                    VALUES (:idTrace, :id, :latitude, :longitude, :altitude, :dateHeure,
                            :rythmeCardio)";
        
        $req = $this->cnx->prepare($txt_req);
        
        $req->bindValue(':idTrace', $unPointDeTrace->getIdTrace(), PDO::PARAM_INT);
        $req->bindValue(':id', $unPointDeTrace->getId(), PDO::PARAM_INT);
        $req->bindValue(':latitude', $unPointDeTrace->getLatitude(), PDO::PARAM_STR);
        $req->bindValue(':longitude', $unPointDeTrace->getLongitude(), PDO::PARAM_STR);
        $req->bindValue(':altitude', $unPointDeTrace->getAltitude(), PDO::PARAM_STR);
        $req->bindValue(':dateHeure', $unPointDeTrace->getDateHeure(), PDO::PARAM_STR);
        $req->bindValue(':rythmeCardio', $unPointDeTrace->getRythmeCardio(), PDO::PARAM_INT);
        
        
        $ok = $req->execute();
        
        // Si c'est le premier point de la trace (id = 1)
        if ($ok && $unPointDeTrace->getId() == 1) {
            // Mise à jour de la date de début de la trace
            $txt_req2 = "UPDATE tracegps_traces 
                        SET dateDebut = :dateDebut 
                        WHERE id = :idTrace";
            
            $req2 = $this->cnx->prepare($txt_req2);
            $req2->bindValue(':dateDebut', $unPointDeTrace->getDateHeure(), PDO::PARAM_STR);
            $req2->bindValue(':idTrace', $unPointDeTrace->getIdTrace(), PDO::PARAM_INT);
            $ok = $req2->execute();
        }
        
        return $ok;
    }
    catch (PDOException $e) {
        echo "Erreur lors de la création du point de trace : " . $e->getMessage();
        return false;
    }
}
   
public function getUneTrace($idTrace)
        /*
        * rôle :  fournit un objet Trace à partir de son identifiant $idTrace
        * @param : $idTrace : l'identifiant de la trace
        * @return : un objet de la classe Trace si $idTrace existe 
        *           l'objet null si $idTrace n'existe pas
        * @speciality :  utiliser la méthode getLesPointsDeTrace($idTrace) pour obtenir les points de la trace et
        *                les ajouter à l'objet Trace qui sera retourné
        */
    {
    // Requête pour récupérer les données de base de la trace
    $txt_req = "SELECT id, dateDebut, dateFin, terminee, idUtilisateur ";
    $txt_req .= "FROM tracegps_traces WHERE id = :idTrace";
    
    $req = $this->cnx->prepare($txt_req);
    $req->bindValue("idTrace", $idTrace, PDO::PARAM_INT);
    $req->execute();
    
    $ligne = $req->fetch(PDO::FETCH_OBJ);
    
    // Si la trace n'existe pas, retourner null
    if (!$ligne) {
        return null;
    }
    
    // Créer l'objet Trace avec les données de base
    $uneTrace = new Trace(
        $ligne->id,
        $ligne->dateDebut,
        $ligne->dateFin,
        $ligne->terminee,
        $ligne->idUtilisateur
    );
    
    // Récupérer et ajouter les points de la trace
    $lesPoints = $this->getLesPointsDeTrace($idTrace);
    foreach ($lesPoints as $unPoint) {
        $uneTrace->ajouterPoint($unPoint);
    }
    
    return $uneTrace;
}

        
    
    
    
    
    
    
    
    

} // fin de la classe DAO


// ATTENTION : on ne met pas de balise de fin de script pour ne pas prendre le risque
// d'enregistrer d'espaces après la balise de fin de script !!!!!!!!!!!!