# Configuration web

Ce plugin fonctionne nativement à l'adresse 
<http://url-moodle/local/redirection/index.php?target=[url-perenne]>

Pour l'utiliser normalement, il est indispensable de configurer également le serveur
web, avec une réécriture d'url.

## Configuration Apache 2.4

Il faut activer le module `mod_rewrite` et dans le fichier Virtualhost commandant Moodle, 
ajouter les lignes semblables à :

```
RewriteEngine  on  
RewriteCond %{REQUEST_URI} /fixe/
RewriteRule    "/moodleparis1/fixe/(.*)$"  "/moodleparis1/local/redirection/index.php?target=$1" [PT]

```

En adaptant l'adresse *moodleparis* à celle utilisée dans le Virtualhost.


