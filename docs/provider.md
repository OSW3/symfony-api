# API Provider

Spécifie un provider API et les options disponibles pour le configurer. Chaque propriété listée ci‑dessous décrit son rôle, le type attendu, si elle est requise, ses valeurs par défaut et des exemples d'usage.

## Structure générale
```yaml
api:
  <provider_name>:
    version: ...
    routes: ...
    search: ...
    debug: ...
    tracing: ...
    pagination: ...
    url: ...
    response: ...
    rate_limit: ...
    serialization: ...
    documentation: ...
    collections: ...
```

## Propriétés

### version
Version de l'API cible (utile pour construire les routes, l'en-tête Accept, ou la compatibilité).  
-> [version](version.md)

### routes
Déclaration des routes/points d'accès exposés par le provider. Permet de mapper des opérations logiques (list, get, create...) vers des chemins HTTP.  
-> [routes](routes.md)

### search
Activation et configuration des capacités de recherche / filtrage.  
-> [search](search.md)

### debug
Active les logs ou le mode debug pour ce provider (journaux plus verbeux, dumps).  
-> [debug](debug.md)

### tracing
Configuration pour le traçage distribué (ex : OpenTelemetry, Jaeger).  
-> [tracing](tracing.md)

### pagination
Comportement de pagination par défaut pour les collections.  
-> [pagination](pagination.md)

### url
URL de base et options de connexion vers l'API distante.  
-> [url](url.md)

### response
Règles de traitement des réponses (enveloppe, codes de succès, désérialisation).  
-> [response](response.md)

### rate_limit
Configuration des limites de requêtes/gestion des quotas.  
-> [rate-limit](rate-limit.md)

### serialization
Options de sérialisation/désérialisation (groupes, formats, versioning).  
-> [serialization](serialization.md)

### documentation
Métadonnées pour générer la documentation (OpenAPI/Swagger) pour ce provider.  
-> [documentation](documentation.md)

### collections
Définition des collections/ressources exposées et leurs règles (mappage vers routes, pagination personnalisée, permissions).  
-> [collections](collections.md)

