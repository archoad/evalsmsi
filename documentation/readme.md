# Informations générales

La génération des rapports se base sur LaTeX.

La bibliothèque jpgraph utilise php-gd.

Voir le fichier package_list.txt pour une liste des paquets utilisés sur une station Debian
permettant de faire fonctionnner EvalSMSI.

# Installation d'EvalSMSI

1. copier le répertoire EvalSMSI à un emplacement accessible par votre serveur Web.
2. affecter les droits à l'utilisateur sous lequel tourne le serveur Web. Le plus souvent pour apache: `chown -R www-data:www-data evalsmsi/`
3. création d'une base mysql appellée `evalsmsi`
4. intégration des tables de la base de données à partir du fichier `evalsmsi.sql`
5. mise à jour des données au début du fichier `functions.php`:

```php
// --------------------
// Définition des variables de base
// Nom de la machine hébergeant le serveur MySQL
$servername='localhost';
// Nom de la base de données
$dbname='evalsmsi';
// Nom de l'utilisateur autorisé à se connecter sur la BDD
$login='<db_login>';
// Mot de passe de connexion
$passwd='<db_password>';
// Titre de l'application
$appli_titre = ("Evaluation du Système de Management de la Sécurité de l'Information");
$appli_titre_short = ("EvalSMSI");
// --------------------
```
6. enjoy !!!

# Utilisation d'EvalSMSI

Avant de commencer une évaluation il faut:

- saisir ou adapter les questions de l'audit;
- fixer une pondération pour chaque question;

Une fois cette étape validée, il faut créer un établissement et un utilisateur dont le rôle est RSSI. Une fois ces deux actions réalisées, l'établissement pourra réaliser son autoévaluation en répondant au questionnaire.

Une fois ces étapes réalisées, l'auditeur peut apprécier les réponses fournies et générer le rapport final.
