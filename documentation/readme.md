# Informations générales

# Installation d'EvalSMSI

1. copier le répertoire EvalSMSI à un emplacement accessible par votre serveur Web.
2. affecter les droits à l'utilisateur sous lequel tourne le serveur Web. Le plus souvent pour apache: `chown -R www-data:www-data evalsmsi/`
3. création d'une base mysql appellée `evalsmsi`
4. intégration des tables de la base de données à partir du fichier `documentation/evalsmsi.sql`
5. mise à jour des données au début du fichier `config.php`:

```php
// Nom de la machine hébergeant le serveur MySQL
$servername = 'localhost';
// Nom de la base de données
$dbname = 'evalsmsi';
// Nom de l'utilisateur autorisé à se connecter sur la BDD
$login = 'web';
// Mot de passe de connexion
$passwd = 'changeme';
// Titre de l'application
$appli_titre = "Evaluation du SMSI";
$appli_titre_short = "EvalSMSI";
// Thème CSS
$cssTheme = 'green'; // glp, beige, blue, green
// Image accueil
$auhtPict = 'pict/accueil.png';
// Image rapport
$rapportPicts = array("pict/archoad.png", "pict/customer.png");
// Mode captcha
$captchaMode = 'num'; // 'txt' or 'num'
// Webauthn attestation mode
$attestationMode = 'direct'; // 'none' or 'indirect' or 'direct'
// Session length
$sessionDuration = 3600; // 60 minutes
```
6. enjoy !!!
