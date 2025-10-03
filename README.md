# 📦 SolidMakerBundle

Un bundle Symfony permettant de générer automatiquement des **services**, **interfaces SOLID** et **collections** à partir de vos entités Doctrine.  

Ce bundle repose sur `symfony/maker-bundle` et ajoute de nouveaux makers personnalisés pour accélérer la mise en place d’architectures **SOLID** dans vos projets.  

---

## 🚀 Installation

### 1. Ajouter le bundle à votre projet Symfony

Depuis Packagist (lorsque publié) ou via un dépôt local/GitHub :  

```bash
composer require maxime/solidmaker-bundle:dev-main
````

⚠️ Si vous développez en local avec un dépôt path, ajoutez dans votre composer.json du projet :
```yaml
"repositories": [
    {
        "type": "path",
        "url": "../SolidMakerBundle",
        "options": {
            "symlink": true
        }
    }
]
````
Puis installez :
```bash
composer require maxime/solidmaker-bundle:dev-main
````

### 2. Activer le bundle
Vérifiez que le bundle est bien chargé dans config/bundles.php :
```php
return [
    // ...
    Maxime\SolidMakerBundle\SolidMakerBundle::class => ['all' => true],
];
````

### 3. Vérification

Listez vos commandes Symfony :
```bash
php bin/console list make
````
Vous devriez voir apparaître :
```bash
make:solid-service
make:solid-interface
make:solid-collection
````

# 🛠️ Utilisation 

🔹 Génération d’un Service SOLID
```bash
php bin/console make:solid-service <entity>
````
➡️ Génère un service dédié pour l’entité \<entity> :

- Classe de service préconfigurée
- Respect des principes SOLID
- Base prête à l’emploi pour vos règles métiers
- Exploite DoctrineHelper pour détecter les métadonnées de l’entité
- Utilise la réflexion PHP pour adapter automatiquement le service à la structure de l’entité ciblée

⚠️⚠️⚠️ Le service généré utilise `DoctrineHelper::getDoctrineColumns()` pour récupérer dynamiquement les colonnes Doctrine de l'entité associée, ce qui simplifie la gestion des propriétés dans le service.
Par conséquent la génération de App\Utils\DoctrineHelper est nécessaire et prise en charge par la commande 
php bin/console make:solid-service <entity>



🔹 Génération d’une Interface SOLID
```bash
php bin/console make:solid-interface <entity>
````

➡️ Génère une interface associée au service de l’entité \<entity> :

- Définit les contrats de votre service
- Encourage l’inversion de dépendances et les bonnes pratiques

🔹 Génération d’une Collection
```bash
php bin/console make:solid-collection <entity>
````

➡️ Génère une collection typée pour l’entité <entity> :

- Encapsulation de la logique de collection dans une structure claire et orientée SOLID
- Génère automatiquement une collection fortement typée adaptée à la structure de l’entité
- Itérable, typée et extensible
- Utile pour manipuler des groupes d’entités avec des règles métiers
- Classe \<entity>Collection prête à l’emploi
- Utilise DoctrineHelper pour vérifier que l’entité existe et récupérer ses métadonnées


📜 Licence

MIT © 2025 – Maxime Coustes