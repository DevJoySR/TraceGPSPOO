# TraceGPS

## Présentation

TraceGPS est un projet pédagogique de développement collaboratif dont l’objectif est de modéliser, manipuler et visualiser des traces GPS via des applications web et mobiles, en mobilisant plusieurs technologies. Ce projet s’inscrit dans une démarche complète : analyse du besoin, développement (web/mobile/desktop), collaboration via GitHub, et bonnes pratiques professionnelles.

## Objectifs fonctionnels

- Importation, lecture et visualisation de fichiers de traces GPS (.gpx, .tcx, .pwx)
- Gestion d’utilisateurs et authentification
- Ajout de points d’intérêt et manipulation des parcours
- Edition, stockage et restitution des traces GPS
- Interopérabilité via API RESTful, XML et JSON
- Compatibilité web, mobile (Android) et poste de travail (Windows avec C#)

## Technologies

- PHP (modèle, DAO, services web)
- Java (modèle et appli Android)
- C# (API et application Windows)
- HTML5, CSS3, JavaScript, jQuery Mobile (web mobile)
- Git, GitHub pour la gestion collaborative
- BDD relationnelle (MySQL), diagrammes UML

## Structure du dépôt

- `/src` : codes source (PHP, Java, C#, JS, HTML)
- `/data` : exemples de traces GPS (gpx…)
- `/docs` : cahier des charges, specs, diagrammes
- `/tests` : scripts et jeux de test unitaires
- `.gitignore` : fichiers/dossiers à exclure du suivi
- `README.md` : ce document

## Organisation pédagogique & modules

Le projet se décompose en modules :
1. Analyse du besoin, cahier des charges et conception BDD
2. Développement du modèle (PHP), organisation dossier, diagramme classes
3. Debug et tests, création DAO, services web RESTful (en PHP)
4. Développement API Java (Android)
5. Développement application mobile Android (Java)
6. Développement web mobile utilisateurs (HTML, PHP, jQuery Mobile, Google Maps)
7. API et application Windows en C#
8. Simulateur de parcours en C#

## Workflow collaboratif

- Travail par branches dédiées à chaque fonctionnalité/bug
- Commits cohérents et documentés (« feat: ajout API, fix: bug DAO … »)
- Relectures, Pull Requests, résolutions de conflits sur GitHub
- Respect des conventions de nommage et du découpage modulaire

## Sauvegardes et sécurité

- **Obligation de faire des sauvegardes régulières** avant modifications majeures (copie locale ou cloud)
- En cas de merge complexe ou de refonte majeure, réaliser un zip du projet avant toute opération risquée
- Contrôler que tout nouvel apport soit documenté dans `/docs`

## Tests & validation

- Validation systématique du développement par tests unitaires et jeux d’essais dédiés
- Déploiement progressif (local -> préprod -> prod)
- Organisation de tests d’intégration et d’acceptation en équipe

## Bonnes pratiques

- Toujours travailler sur des copies / branches
- Documenter chaque nouveauté/fix (code + README + `/docs`)
- Synchroniser et tirer (pull) avant tout nouveau push sur la branche principale
- Vérifier la cohérence et la portabilité du code (respect des plateformes et formats de fichiers)
- Utiliser les outils de gestion de tickets pour le suivi des bugs et tâches

## Contribuer

1. **Forker** le dépôt, créer une branche pour chaque nouvelle tâche
2. **Développer** et documenter son travail
3. **Commit** clair et régulier, puis **push**
4. **Pull Request** pour validation par l’équipe

