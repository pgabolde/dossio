# ADR-001 — Écriture des embeddings en base

## Statut

Accepté — 2026-10-05

## Contexte

Dossio repose sur deux services : une application **Laravel** (cœur métier, authentification, multi-tenant) et un service **Python / FastAPI** (extraction de documents, découpage en chunks, calcul des embeddings, génération des réponses).

Les chunks et leurs embeddings doivent être stockés dans PostgreSQL (pgvector), à côté des données métier. Il faut décider **qui écrit ces données en base**.

Contraintes :

- L'application est multi-organisations : un chunk d'une organisation ne doit **jamais** être visible par une autre.
- Le schéma est géré par les migrations Laravel.
- Le service Python doit rester simple à tester et à remplacer.

## Options envisagées

**A. Python écrit directement en base.** Python reçoit le fichier, calcule les embeddings et insère les chunks lui-même.

**B. Python renvoie, Laravel écrit.** Python reçoit le fichier, calcule et renvoie les chunks et leurs embeddings en JSON. Laravel les insère dans une transaction, avec l'`organization_id` du document.

## Décision

**Option B.** Laravel est le seul service à lire et écrire en base. Python est stateless : il reçoit, calcule, renvoie, et n'a aucun identifiant de connexion à la base.

## Conséquences

### Avantages

- **Un seul propriétaire du schéma.** Les migrations, les modèles et les contraintes vivent à un seul endroit. Pas de risque de dérive entre un ORM PHP et un ORM Python.
- **Isolation multi-tenant garantie par construction.** C'est Laravel qui pose l'`organization_id`, avec les mêmes scopes et policies que le reste de l'application. Python ne peut pas écrire au mauvais endroit puisqu'il n'a pas accès à la base.
- **Surface d'attaque réduite.** Une compromission du service Python ne donne pas accès aux données des clients.
- **Python trivial à tester.** Un fichier en entrée, du JSON en sortie, aucune base à monter dans les tests.
- **Cohérence transactionnelle.** L'insertion des chunks et le passage du document en `ready` se font dans la même transaction. Si quelque chose échoue, rien n'est à moitié écrit, et le job est relancé par la queue.

### Inconvénients

- **Volume transféré.** Les embeddings transitent en JSON. Ordre de grandeur avec 1536 dimensions : environ 11 caractères par nombre, soit environ 17 Ko par chunk ; un document de 300 chunks pèse environ 5 Mo de JSON.
- **Mémoire côté PHP.** Le worker de queue décode ce JSON en mémoire. À surveiller : `memory_limit` du worker et taille maximale des documents. Piste si ça devient un problème : envoyer les chunks par lots.
- **Contrat entre services.** Le format JSON de `/ingest` devient une interface à versionner. Tout changement doit être fait des deux côtés en même temps (contrat validé par Pydantic côté Python, testé côté Laravel).
- **Timeouts HTTP.** L'ingestion d'un gros document peut être longue ; le client HTTP de Laravel et le job doivent avoir des timeouts adaptés.
