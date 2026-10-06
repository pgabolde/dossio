# ADR-005 — Isolation multi-tenant

## Statut

Accepté — 2026-10-06 (bloc B3)

## Contexte

Dossio est un CRM multi-organisations. Chaque organisation y dépose les documents de ses clients : factures, devis, contrats. Une fuite de données entre organisations serait la pire faille possible du produit.

Le risque n'est pas limité aux écrans :

- les **URL forgées** (IDOR) : `/clients/43` au lieu de `/clients/42` ;
- les **formulaires modifiés** : un `organization_id` glissé dans un champ caché ;
- les **traitements hors requête web** : jobs de queue (ingestion), commandes Artisan, seeders, où aucun utilisateur n'est connecté ;
- la **recherche vectorielle** (B8) : un chunk d'une autre organisation envoyé au LLM serait une fuite, même si personne ne le voit à l'écran.

Il faut donc une isolation **sûre par défaut**, qui ne repose pas sur le fait de « penser à filtrer » à chaque requête.

## Options envisagées

### 1. Une base (ou un schéma Postgres) par organisation

- ✅ Isolation physique, la plus forte possible.
- ❌ Migrations à jouer N fois, connexions dynamiques, index pgvector par schéma, sauvegardes multipliées.
- ❌ Disproportionné pour le volume visé.

### 2. Base partagée, filtrage manuel dans les contrôleurs

- ✅ Simple à comprendre.
- ❌ Un seul oubli suffit à créer une fuite. Ne couvre ni les jobs ni les commandes.

### 3. Postgres Row Level Security (RLS)

- ✅ Isolation garantie par la base elle-même, quel que soit le code.
- ❌ Variable de session à positionner sur chaque connexion, interaction délicate avec le pooling, debug et migrations plus complexes.
- À reconsidérer si le projet grandit (voir Évolutions).

### 4. Base partagée, colonne `organization_id`, global scope Eloquent, policies et contraintes en base ← retenue

- ✅ Sûr par défaut : toute requête Eloquent est filtrée sans y penser.
- ✅ Défense en profondeur sur trois couches (modèle, autorisation, base).
- ✅ Simple à tester.

## Décision

**Base partagée avec colonne `organization_id`, filtrée par un global scope, complétée par des policies et des contraintes en base.**

### Modèle de données

- `organizations` ; pivot `organization_user` avec un `role` (`owner` | `member`, défaut `member`) ; contrainte unique `(organization_id, user_id)` et index sur `user_id`.
- `users.current_organization_id` (nullable, FK `nullOnDelete`, indexée) : l'organisation dans laquelle l'utilisateur travaille.
- Chaque table métier porte `organization_id` en `NOT NULL`, avec clé étrangère et **index explicite** (Postgres n'indexe pas les FK automatiquement).

### Mécanique

1. **Contexte d'organisation** : la classe `App\Support\CurrentOrganization` porte l'organisation courante. Elle est enregistrée en **`scoped`** dans le container : remise à zéro entre chaque requête et chaque job, ce qui évite qu'un worker de queue réutilise l'organisation du job précédent. Lue alors qu'elle est vide, elle lève `NoCurrentOrganization` : un crash bruyant plutôt qu'une fuite silencieuse.
2. **Middleware `SetCurrentOrganization`** : sur les routes protégées, il vérifie que l'utilisateur est **toujours membre** de son organisation courante (sinon 403), puis remplit le contexte. Il est placé dans la liste de priorités **avant `SubstituteBindings`**, pour que le route model binding s'exécute avec le contexte déjà rempli.
3. **Trait `BelongsToOrganization`**, utilisé par chaque modèle métier :
    - global scope : `WHERE <table>.organization_id = <organisation du contexte>`, évalué à chaque requête ;
    - événement `creating` : remplit `organization_id` depuis le contexte s'il n'est pas déjà fourni par une relation ;
    - relation `organization()`.
4. **`organization_id` n'est jamais dans `$fillable`** : il ne peut pas venir d'une requête HTTP.
5. **Policies** : règles internes à une organisation (seul un `owner` supprime), et revérification de l'appartenance par comparaison de colonnes, au cas où un scope serait désactivé.
6. **Accès inter-organisations : 404, pas 403.** Une 403 confirmerait que la ressource existe et permettrait d'énumérer les identifiants.

Hors requête web (jobs, commandes, seeders), c'est au code appelant de remplir le contexte, ou de créer via la relation (`$organization->clients()->create(...)`).

## Conséquences

### Avantages

- Une requête Eloquent oubliée reste filtrée : l'oubli n'est plus une faille.
- Isolation vérifiée par des tests (`tests/Feature/OrganizationIsolationTest.php`) : visibilité limitée à son organisation, exception sans contexte, `organization_id` forgé ignoré, 404 sur une URL forgée, 403 après retrait de l'organisation, suppression réservée aux owners.
- Pas de dépendance supplémentaire, ni d'infrastructure en plus.

### Inconvénients et points de vigilance

- **Le SQL brut contourne le scope.** `DB::select`, `DB::table` et la future recherche pgvector (B8) doivent filtrer `organization_id` explicitement, avec un test dédié.
- **`withoutGlobalScopes()` désactive la protection** : à réserver à des cas explicites, et à repérer en review.
- **Dépendance à l'ordre des middlewares** : retirer la priorité avant `SubstituteBindings` casse toutes les routes avec binding (erreur 500, pas de fuite).
- **Les jobs doivent recevoir l'organisation** (via le modèle traité) et remplir le contexte avant toute requête.
- **Les suppressions en cascade sont faites par Postgres**, pas par Laravel : les fichiers stockés ne sont pas supprimés (à traiter en B5).
- Un utilisateur retiré de toutes ses organisations reçoit une 403 sans porte de sortie : un écran de choix ou de création d'organisation est à prévoir.

## Évolutions possibles

- Ajouter du RLS Postgres en seconde barrière, si le nombre d'organisations ou la sensibilité des données le justifie.
- Remplacer les rôles en chaîne de caractères par une enum PHP.
