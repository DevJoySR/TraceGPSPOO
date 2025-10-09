<?php
// Projet TraceGPS
// fichier : modele/Trace.php
// Rôle : la classe Trace représente une trace ou un parcours
// Dernière mise à jour : 02/10/2025 par aS

include_once("PointDeTrace.php");

use modele\PointDeTrace;
use modele\Point;

class Trace
{
    #####################################
    ### Attributs privés de la classe ###
    #####################################

    private $id;                    // identifiant de la trace
    private $dateHeureDebut;        // date et heure de début
    private $dateHeureFin;          // date et heure de fin
    private $terminee;              // true si la trace est terminée, false sinon
    private $idUtilisateur;         // identifiant de l'utilisateur ayant créé la trace
    private $lesPointsDeTrace;      // la collection (array) des objets PointDeTrace formant la trace

    ####################
    ### Constructeur ###
    ####################

    public function __construct($unId, $uneDateHeureDebut, $uneDateHeureFin, $terminee, $unIdUtilisateur)
    {
        $this->id = $unId;
        $this->dateHeureDebut = $uneDateHeureDebut;
        $this->dateHeureFin = $uneDateHeureFin;
        $this->terminee = $terminee;
        $this->idUtilisateur = $unIdUtilisateur;

        $this->lesPointsDeTrace = array();
    }

    ##########################
    ### Getters et setters ###
    ##########################
    public function getId() {return $this->id;}
    public function setId($unId) {$this->id = $unId;}
    
    public function getDateHeureDebut() {return $this->dateHeureDebut;}
    public function setDateHeureDebut($uneDateHeureDebut) {$this->dateHeureDebut = $uneDateHeureDebut;}

    public function getDateHeureFin() {return $this->dateHeureFin;}
    public function setDateHeureFin($uneDateHeureFin) {$this->dateHeureFin= $uneDateHeureFin;}
    
    public function getTerminee() {return $this->terminee;}
    public function setTerminee($terminee) {$this->terminee = $terminee;}
    
    public function getIdUtilisateur() {return $this->idUtilisateur;}
    public function setIdUtilisateur($unIdUtilisateur) {$this->idUtilisateur = $unIdUtilisateur;}

    public function getLesPointsDeTrace() {return $this->lesPointsDeTrace;}
    public function setLesPointsDeTrace($lesPointsDeTrace) {$this->lesPointsDeTrace = $lesPointsDeTrace;}

    ############################
    ### Méthodes d'instances ###
    ############################
    // Fournit une chaine contenant toutes les données de l'objet
    public function toString() {
        $msg = "Id : " . $this->getId() . "<br>";
        $msg .= "Utilisateur : " . $this->getIdUtilisateur() . "<br>";
        if ($this->getDateHeureDebut() != null) {
        $msg .= "Heure de début : " . $this->getDateHeureDebut() . "<br>";
        }
        if ($this->getTerminee()) {
        $msg .= "Terminée : Oui <br>"; 
        }
        else {
        $msg .= "Terminée : Non <br>";
        }
        $msg .= "Nombre de points : " . $this->getNombrePoints() . "<br>";
        if ($this->getNombrePoints() > 0) { 
        if ($this->getDateHeureFin() != null) {
        $msg .= "Heure de fin : " . $this->getDateHeureFin() . "<br>";
        }
        $msg .= "Durée en secondes : " . $this->getDureeEnSecondes() . "<br>";
        $msg .= "Durée totale : " . $this->getDureeTotale() . "<br>";
        $msg .= "Distance totale en Km : " . $this->getDistanceTotale() . "<br>";
        $msg .= "Dénivelé en m : " . $this->getDenivele() . "<br>";
        $msg .= "Dénivelé positif en m : " . $this->getDenivelePositif() . "<br>";
        $msg .= "Dénivelé négatif en m : " . $this->getDeniveleNegatif() . "<br>";
        $msg .= "Vitesse moyenne en Km/h : " . $this->getVitesseMoyenne() . "<br>";
        $msg .= "Centre du parcours : " . "<br>";
        $msg .= " - Latitude : " . $this->getCentre()->getLatitude() . "<br>";
        $msg .= " - Longitude : " . $this->getCentre()->getLongitude() . "<br>";
        $msg .= " - Altitude : " . $this->getCentre()->getAltitude() . "<br>";
        }
        return $msg;
    }

    public function getNombrePoints(){
        $NombrePoints = sizeof($this->lesPointsDeTrace);
        return $NombrePoints;
    }

    public function getCentre(){
        if ($this->lesPointsDeTrace == null || sizeof($this->lesPointsDeTrace) == 0)
        {
            return null;
        }

        $centrePoints = new Point(0,0,0);
        $premierPoint = $this->lesPointsDeTrace[0];

        $latitudeMin = $premierPoint->getLatitude();
        $latitudeMax = $premierPoint->getLatitude();
        $longitudeMin = $premierPoint->getLatitude();
        $longitudeMax = $premierPoint->getLatitude();

        for ($i = 1 ; $i < sizeof($this->lesPointsDeTrace) ; $i++)
        {
            $lePoint = $this->lesPointsDeTrace[$i];
            if ($latitudeMin > $lePoint->getLatitude())
            {
                $latitudeMin = $lePoint->getLatitude();
            }
            if ($latitudeMax > $lePoint->getLatitude())
            {
                $latitudeMax = $lePoint->getLatitude();
            }
            if ($longitudeMin > $lePoint->getLongitude())
            {
                $longitudeMin = $lePoint->getLongitude();
            }
            if ($longitudeMax > $lePoint->getLongitude())
            {
                $longitudeMax = $lePoint->getLongitude();
            }

            $centrePoints->setLatitude(($latitudeMin + $latitudeMax) / 2);
            $centrePoints->setLongitude(($longitudeMin + $longitudeMax) / 2);

            return $centrePoints;
        }
    }

    public function getDenivele()
    {
        if ($this->lesPointsDeTrace == null || sizeof($this->lesPointsDeTrace) == 0)
        {
            return 0;
        } 

        $premierPoint = $this->lesPointsDeTrace[0];
        $altitudeMin = $premierPoint->getAltitude();
        $altitudeMax = $premierPoint->getAltitude();

        for ($i = 1 ; $i < sizeof($this->lesPointsDeTrace) ; $i++)
        {
            $lePoint = $this->lesPointsDeTrace[$i];
            if ($altitudeMin > $lePoint->getAltitude())
            {
                $altitudeMin = $lePoint->getAltitude();
            }
            if ($altitudeMax < $lePoint->getAltitude())
            {
                $altitudeMax = $lePoint->getAltitude();
            }

            $Denivele = $altitudeMax - $altitudeMin;

            return $Denivele;
        }
    }

    public function getDureeEnSecondes()
    {
        if (count($this->lesPointsDeTrace) == 0)
            return 0;

        $premierPoint = $this->lesPointsDeTrace[0];
        $dernierPoint = $this->lesPointsDeTrace[count($this->lesPointsDeTrace) - 1];

        $dateDebut = new DateTime($premierPoint->getDateHeure());
        $dateFin = new DateTime($dernierPoint->getDateHeure());

        return $dateFin->getTimestamp() - $dateDebut->getTimestamp();
    }

    public function getDureeTotale()
    {
        $secondes = $this->getDureeEnSecondes();

        $heures = $secondes /3600;
        $minutes = ($secondes % 3600) / 60;
        $secondesRestantes = $secondes / 60;

        return sprintf("%02d",$heures) . ":" . sprintf("%02d",$minutes) . ":" . sprintf("%02d",$secondesRestantes);
    }

    public function getDistanceTotale()
    {
        if ($this->lesPointsDeTrace == null || sizeof($this->lesPointsDeTrace) == 0)
            return 0;

        $dernierPoint = $this->lesPointsDeTrace[count($this->lesPointsDeTrace) - 1];
        $distanceTotale = $dernierPoint->getDistanceCumulee();
        return $distanceTotale;
    }

    public function getDenivelePositif()
    {
        $cumulDenivelePositif = 0;
        for ($i = 1 ; $i < sizeof($this->lesPointsDeTrace) ; $i++)
        {
            $pointActuel = $this->lesPointsDeTrace[$i - 1];
            $pointSuivant = $this->lesPointsDeTrace[$i];

            $altitude1 = $pointActuel->getAltitude();
            $altitude2 = $pointSuivant->getAltitude();

            if ($altitude2 > $altitude1)
                $cumulDenivelePositif += $altitude2 - $altitude1;
        }
        return $cumulDenivelePositif;
    }

        public function getDeniveleNegatif()
    {
        $cumulDeniveleNegatif = 0;
        for ($i = 1 ; $i < sizeof($this->lesPointsDeTrace) ; $i++)
        {
            $pointActuel = $this->lesPointsDeTrace[$i - 1];
            $pointSuivant = $this->lesPointsDeTrace[$i];

            $altitude1 = $pointActuel->getAltitude();
            $altitude2 = $pointSuivant->getAltitude();

            if ($altitude2 < $altitude1)
                $cumulDeniveleNegatif += $altitude1 - $altitude2;
        }
        return $cumulDeniveleNegatif;
    }

    public function getVitesseMoyenne()
    {
        if ($this->lesPointsDeTrace == null || sizeof($this->lesPointsDeTrace) == 0)
        {
            return 0;
        }

        $distance = $this->getDistanceTotale();
        $dureeEnSecondes = $this->getDureeEnSecondes();
        $vitesseMoyenne = 0;

        $vitesseMoyenne = ($distance * 3600) / $dureeEnSecondes;

        return $vitesseMoyenne;
    }

    public function ajouterPoint($nouveauPoint)
    {
        if ($this->lesPointsDeTrace == null || sizeof($this->lesPointsDeTrace) == 0)
        {
            $this->lesPointsDeTrace[] = $nouveauPoint;
        }
        else
        {
            $dernierPoint = $this->lesPointsDeTrace[count($this->lesPointsDeTrace) - 1];
            $distance = Point::getDistance($nouveauPoint, $dernierPoint);
            
            $dateDebut = new DateTime($dernierPoint->getDateHeure());
            $dateFin = new DateTime($nouveauPoint->getDateHeure());

            $tempsSec = $dateFin->getTimestamp() - $dateDebut->getTimestamp();
            
            $nouveauPoint->setDistanceCumulee($dernierPoint->getDistanceCumulee() + $distance);

            $nouveauPoint->setTempsCumule($dernierPoint->getTempsCumule() + $tempsSec);

            if ($tempsSec > 0)
            {
                $nouveauPoint->setVitesse(($distance / $tempsSec) * 3600);
            }
            else
            {
                $nouveauPoint->setVitesse(0);
            }
            $this->lesPointsDeTrace[] = $nouveauPoint;
        }
    }

    public function viderListePoints()
    {
        $this->lesPointsDeTrace = array();
    }
}// fin de la classe Trace