public function DemanderMdp($pseudo, $lang)
    /*
    *   Rôle : ce service web permet à un utilisateur de demander un nouveau mot de passe s'il l'a oublié.
    *   @param :    • pseudo : le pseudo de l'utilisateur
    *               • lang : le langage utilisé pour le flux de données ("xml" ou "json")
    *   Description du traitement :
    *               • Vérifier que les données transmises sont complètes
    *               • Vérifier que le pseudo de l'utilisateur existe
    *               • Générer un nouveau mot de passe
    *               • Enregistrer le nouveau mot de passe
    *               • Envoyer un courriel à l'utilisateur avec son nouveau mot de passe
{

}