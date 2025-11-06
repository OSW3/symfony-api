# Version

La propriété `version` permet de définir comment l'API expose et gère ses versions. Si elle est laissée à `null`, une version sera automatiquement affectée (v1, v2, …). Cette section décrit les sous‑clés disponibles, leur type, leur comportement et des exemples d'usage.


## Structure attendue (YAML)
```yaml
version:
  number: 1
  prefix: v
  location: path
  directive: Accept
  pattern: application/vnd.{vendor}.{version}+json
```


## Sous‑propriétés

- number  

    Numéro de version ou identifiant de version (utile pour la compatibilité et la génération d'URL/en‑têtes).
  - Type : int (ex : 1, 2, 42)
  - Requis : non
  - Valeur par défaut : null (auto-assignée si absent)
  - Exemple :
    ```yaml
    number: 1
    ```

- prefix  

    Préfixe utilisé pour construire la représentation textuelle de la version (souvent "v").
  - Type : string
  - Requis : non
  - Valeur par défaut : "v"
  - Exemple :
    ```yaml
    prefix: v
    ```

- location  

    Où et comment la version est exposée aux clients.
  - Type : string (valeurs acceptées : "path", "header", "param", "subdomain")
  - Requis : non
  - Valeur par défaut : "path"
  - Comportement :
    - path -> /api/v1/ressource
    - header -> version via en‑tête HTTP (voir `header_format`)
    - param -> ?version=1
    - subdomain -> v1.api.example.com
  - Exemple :
    ```yaml
    location: path
    ```

- header_format  

    Format du champ MIME utilisé lorsque `location` = "header". Les placeholders `{vendor}` et `{version}` sont remplacés dynamiquement.
  - Type : string
  - Requis : non
  - Valeur par défaut : "application/vnd.{vendor}.{version}+json"
  - Remarques :
    - `{vendor}` : nom de l'API/organisation (ex : myapp)
    - `{version}` : combinaison `prefix`+`number` (ex : v1)
    - Exemple de rendu : `application/vnd.myapp.v1+json`
  - Exemple :
    ```yaml
    header_format: application/vnd.{vendor}.{version}+json
    ```
    <!-- Vendor-specific (`vnd`) | `application/vnd.myapp.v1+json`   | Format privé, propre à ton API ou ton entreprise. -->
    <!-- Personal (`prs`)        | `application/prs.johndoe.v1+json` | Format personnel, non officiel. -->
    <!-- Experimental (`x`)      | `application/x.myapp.v1+json`     | Format expérimental, non normalisé. -->

- deprecated  

    Indique si cette version est dépréciée. Peut déclencher des en‑têtes d'avertissement ou des logs côté client/serveur.
  - Type : bool
  - Requis : non
  - Valeur par défaut : false
  - Exemple :
    ```yaml
    deprecated: true
    ```


## Notes et bonnes pratiques

- Si `number` est omis, la bibliothèque générera une valeur (v1, v2, …) selon l'ordre des providers/config.
- Préférer `location: path` pour une compatibilité maximale et une visibilité claire de la version dans les URLs.
- Utiliser `header_format` pour des versions moins visibles ou pour gérer plusieurs versions simultanément sans casser les URLs publiques.
- Déclarer `deprecated: true` quelques versions avant de retirer une API afin d'informer les consommateurs.
