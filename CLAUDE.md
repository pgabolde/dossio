# Dossio — Feuille de route du projet

> Ce fichier est la référence du projet. Il est lu au début de chaque session de travail.
> Il contient le contexte, les décisions actées, les règles pédagogiques et le parcours en blocs.
> Il est mis à jour à la fin de chaque bloc (statut, décisions, journal).

---

## 1. Contexte

Je suis développeur PHP senior (Symfony), freelance. Ce projet public sert à prouver ce que mon CV ne prouve pas encore :

- **Laravel** (écosystème, idiomes, conventions) ;
- **Python** (FastAPI, Pydantic, pytest) ;
- **IA générative intégrée proprement** dans une application métier (RAG, structured outputs, tools, plus tard agents).

**Positionnement visé** : développeur backend/fullstack PHP senior, capable de construire et d'intégrer une brique IA en Python dans une application métier.

**Le produit** : un CRM multi-organisations où l'on agrège tous les documents d'un client (PDF, DOCX, XLSX : factures, devis, contrats…), et où l'on pose des questions en langage naturel avec des réponses **sourcées**.

**Test final** : un recruteur ou un client qui regarde le GitHub pendant 5 minutes doit comprendre que je sais construire une vraie application métier moderne et y intégrer l'IA proprement.

---

## 2. Décisions actées

| Sujet | Décision | Pourquoi |
|---|---|---|
| Cœur applicatif | **Laravel** | Mon Symfony se voit déjà dans mon CV ; le GitHub doit prouver Laravel |
| Front | **Inertia + Vue 3 + TypeScript + Tailwind + shadcn-vue** (starter kit officiel) | Vue/TS sans API séparée ni auth SPA ; rendu pro rapide |
| Service IA | **Python + FastAPI** | Extraction de documents hétérogènes, chunking, embeddings : terrain natif de Python |
| Base | **PostgreSQL + pgvector** | Données métier et vecteurs dans la même base, pas de techno en plus |
| Multi-tenant | Base partagée, colonne `organization_id`, scope global + policies | Simple, défendable, testable |
| Accès base | **Seul Laravel lit et écrit en base. Python n'a aucun accès à la base.** | Un seul propriétaire du schéma ; isolation des données garantie par construction (ADR-001) |
| Communication | HTTP interne Laravel → Python | Python est stateless : il reçoit, calcule, renvoie |
| Traitements longs | Queue Laravel, driver `database` | Pas de Redis tant que ce n'est pas nécessaire |
| Tests | Pest (Laravel), pytest (Python) | Les appels LLM sont mockés : les tests coûtent 0 € |
| Qualité | Larastan, Pint, Ruff | Standards de chaque écosystème |
| Python deps | `uv` | Outil moderne et rapide de gestion de projet Python |
| Coût IA | Table `ai_usages` (modèle, tokens, coût estimé) dès le premier appel LLM | Le coût est mesuré, pas supposé |
| Démo | Semaine 1 : README + vidéo + `docker compose up`. Semaine 2 : démo en ligne avec garde-fous | La vidéo est vue par 90 % des visiteurs ; la démo publique exige des protections |

**Pas décidé, à trancher le moment venu (avec ADR)** : fournisseur d'embeddings, taille des chunks, modèle LLM pour chaque usage.

**Exclu volontairement** : microservices supplémentaires, Kubernetes, Kafka, Elasticsearch, LangChain par défaut, fine-tuning, agents autonomes qui agissent (envoi d'emails, etc.).

---

## 3. Architecture

```text
            Navigateur (Vue 3 + TS via Inertia)
                          |
                          v
        +-----------------------------------+
        |              Laravel              |
        |  auth · organisations · clients   |
        |  documents · queue · recherche    |
        |  vectorielle (SQL) · ai_usages    |
        +--------+-----------------+--------+
                 |                 |
                 | SQL             | HTTP interne
                 v                 v
     PostgreSQL + pgvector    Python / FastAPI
     (seul Laravel y accède)  /ingest  /embed  /answer
                                   |
                                   v
                          Fournisseurs LLM / embeddings
```

### Flux d'ingestion (job de queue)

```text
Upload → Document (statut pending) → Job
Job → POST /ingest (fichier) → Python : extraction → chunks → embeddings
Python → JSON [{texte, page, embedding}, …] → Laravel insère en transaction
Statut : pending → processing → ready | failed (retry automatique de la queue)
```

### Flux de question (RAG)

```text
Question → POST /embed → vecteur de la question
Laravel → recherche pgvector filtrée par organization_id (+ client) → top N chunks
Laravel → POST /answer (question + chunks) → réponse + sources
Laravel → enregistre l'usage (tokens, coût) → affiche réponse + sources
```

### Structure du repository

```text
/                 → application Laravel (racine)
/ai-service/      → service Python FastAPI
/docs/decisions/  → ADR
/docs/linkedin/   → brouillons des posts
docker-compose.yml
CLAUDE.md         → ce fichier
README.md
```

---

## 4. Règles pédagogiques (consignes pour Claude)

**Claude est mon formateur et mon mentor technique, pas un générateur de code.** L'objectif est d'apprendre, pas de finir vite.

### Déroulé de chaque bloc

1. **Objectif** : ce qu'on construit et pourquoi.
2. **Concepts** : explication courte des notions nécessaires, avec exemples.
3. **Avant de coder** : 2 ou 3 questions pour vérifier que j'ai compris. J'y réponds.
4. **Exercice** : je code moi-même.
5. **Review** : je montre mon code ; Claude pointe les problèmes et donne des **indices**, pas la solution.
6. **Correction** : je corrige moi-même. Solution complète seulement si je bloque vraiment ou si je la demande, et toujours expliquée.
7. **Validation** : critères de réussite vérifiés (tests qui passent, comportement attendu).
8. **À retenir** : résumé des concepts, ajouté au journal.
9. **Entretien** : 2 ou 3 phrases pour expliquer le choix à un recruteur.
10. **LinkedIn** : idée de post du jour.

### Règle de délégation

- **Le code, c'est moi.** La première occurrence d'un pattern, je la code (premier modèle, première policy, premier test, premier endpoint…).
- **Une fois compris, Claude peut prendre les répétitions** (CRUD similaires, tests du même type, boilerplate), en me disant ce qu'il a fait.
- **La rédaction, c'est Claude.** ADR, README, `.gitignore`, `.env.example`, fichiers de config, brouillons LinkedIn : Claude explique d'abord les bonnes pratiques et les points clés, puis rédige. Je relis et j'ajuste dans l'IDE.
- **Les bugs, je les résous d'abord moi-même.** Claude m'aide à diagnostiquer avec des questions avant de donner la réponse.
- Le **bloc Python (bloc 6)** se fait sans délégation de code : c'est le cœur de l'apprentissage.

### Posture

- Si je pars dans une mauvaise direction : expliquer pourquoi c'est un problème et me demander comment l'améliorer, plutôt que corriger directement.
- Si plusieurs solutions sont raisonnables : donner la recommandation, la justifier, me laisser trancher.
- Challenger toute techno ajoutée parce qu'elle est à la mode.
- Ton direct et décontracté, pas de blabla.
- Pour chaque techno introduite, savoir répondre : à quoi ça sert, pourquoi ici, quelles alternatives, quels inconvénients, comment ça marche en production, quels problèmes à l'échelle.

---

## 5. Parcours en blocs

Budget : 3 h par jour. Les durées sont des estimations.

### Semaine 1 — objectif vendredi : une tranche verticale qui marche

« J'uploade un document sur un client, je pose une question, j'obtiens une réponse sourcée. »

| Jour | Blocs | Durée |
|---|---|---|
| Lundi | B0 (0,5 h) + B1 (2,5 h) | 3 h |
| Mardi | B2 (0,5 h) + B3 (1,5 h) + B4 (0,5 h) + B5 (0,5 h) | 3 h |
| Mercredi | B6 (3 h) | 3 h |
| Jeudi | B7 (1,5 h) + B8 (1,5 h) | 3 h |
| Vendredi | B9 (1,5 h) + B10 (1,5 h) | 3 h |
| **Total** | | **15 h** |

---

#### B0 — Cadrage et repository · 🟨 En cours

- **Objectif** : un repo propre dès le premier commit.
- **Tu apprends** : structure d'un repo multi-services, hygiène des secrets.
- **Étapes** : créer le repo public, `.gitignore`, `.env.example`, licence, README minimal (problème, stack, statut « en construction »), dossier `docs/decisions/`.
- **ADR** : ADR-001 — Qui écrit les embeddings en base (rédigé par Claude, relu par moi).
- **Validation** : aucun secret versionné, ADR-001 relu.
- **LinkedIn** : annonce du projet *build in public* + schéma d'archi.

#### B1 — Environnement Docker · ⬜ À faire

- **Objectif** : tout le système démarre avec `docker compose up`.
- **Tu apprends** : orchestration multi-services, healthchecks, réseau Docker entre services.
- **Services** : `app` (PHP-FPM, `pdo_pgsql`, `intl`), `nginx`, `queue` (même image qu'`app`, `php artisan queue:work`), `db` (`pgvector/pgvector`), `ai-service` (FastAPI, endpoint `/health`), front via Vite.
- **Validation** : healthchecks verts sur `db` et `ai-service` ; appel à `/health` réussi **depuis le conteneur `app`**.
- **Délégation** : aucune, c'est mon point fort.

#### B2 — Socle Laravel · ⬜ À faire

- **Objectif** : Laravel + starter kit Vue/TS + auth, connecté à Postgres.
- **Tu apprends** : structure d'un projet Laravel, Inertia, différences avec Symfony (conventions, façades, Artisan).
- **Avant de coder** : comment Inertia remplace une API REST classique ? Qu'est-ce qu'on perd et qu'on gagne ?
- **Attention** : `laravel new` refuse un dossier non vide. Installer dans un dossier temporaire, déplacer à la racine (fichiers cachés compris), fusionner les `.gitignore`, garder notre README.
- **Validation** : inscription/connexion fonctionnelles, tests du starter kit verts.

#### B3 — Multi-tenant · ⬜ À faire

- **Objectif** : organisations, appartenance des utilisateurs, isolation totale des données.
- **Tu apprends** : Eloquent (relations, global scopes), policies, middleware, comparaison avec les Voters Symfony.
- **Avant de coder** : qu'est-ce qu'une faille IDOR ? Où l'isolation doit-elle être garantie : contrôleur, modèle, base ?
- **Validation** : **tests d'isolation** (un utilisateur de l'org A ne voit, ne modifie et ne télécharge jamais rien de l'org B, même en forgeant l'URL).
- **Entretien** : c'est l'un des arguments sécurité les plus forts du projet.

#### B4 — Clients · ⬜ À faire

- **Objectif** : CRUD clients (liste, recherche, pagination, fiche).
- **Tu apprends** : Form Requests, resources, pagination Laravel, composants shadcn-vue.
- **Délégation** : je code la création et la liste ; Claude peut faire édition et suppression sur le même modèle.
- **Validation** : tests fonctionnels CRUD, isolation respectée.

#### B5 — Upload de documents · ⬜ À faire

- **Objectif** : déposer PDF, DOCX, XLSX sur une fiche client.
- **Tu apprends** : Storage Laravel, validation MIME et taille, jobs et queues, statuts de traitement.
- **Avant de coder** : pourquoi ne pas se fier à l'extension du fichier ? Pourquoi traiter en job plutôt que dans la requête ?
- **Validation** : upload refusé hors types autorisés ou trop lourd ; document créé en `pending` ; job dispatché.

#### B6 — Python : extraction et chunking · ⬜ À faire · 🚫 pas de délégation

- **Objectif** : endpoint `/ingest` qui reçoit un fichier et renvoie ses chunks avec leur page.
- **Tu apprends** : bases de Python moderne (typing, modules, environnements avec `uv`), FastAPI, Pydantic, pytest, librairies d'extraction de documents.
- **Avant de coder** : qu'est-ce qu'un bon chunk ? Que se passe-t-il si on coupe au milieu d'une phrase ? Pourquoi un chevauchement entre chunks ?
- **Validation** : tests pytest sur un PDF, un DOCX et un XLSX d'exemple ; contrat JSON validé par Pydantic.
- **Entretien** : pourquoi l'extraction est en Python et pas en PHP.

#### B7 — Embeddings et pgvector · ⬜ À faire

- **Objectif** : Python calcule les embeddings ; Laravel les stocke et les indexe.
- **Tu apprends** : embeddings en pratique, dimensions, type `vector` et index pgvector, migration Laravel avec extension Postgres.
- **ADR** : ADR-002 — Fournisseur d'embeddings ; ADR-003 — Stratégie de chunking.
- **Validation** : document de bout en bout passé en `ready`, chunks en base avec vecteur et `organization_id`.

#### B8 — RAG : question et réponse sourcée · ⬜ À faire

- **Objectif** : poser une question sur un client et obtenir une réponse avec ses sources.
- **Tu apprends** : recherche par similarité, construction d'un prompt avec contexte, citations, abstraction du client LLM (vrai client + mock pour les tests).
- **Avant de coder** : que doit faire le LLM si les chunks ne contiennent pas la réponse ? Comment l'y forcer ?
- **Validation** : la recherche ne remonte jamais de chunks d'une autre organisation (test) ; le LLM répond « je ne sais pas » quand l'info est absente ; tests sans appel réseau.

#### B9 — Chat sur la fiche client + suivi des coûts · ⬜ À faire

- **Objectif** : interface de question sur la fiche client ; table `ai_usages`.
- **Tu apprends** : états de chargement et d'erreur en Vue, suivi des tokens et du coût estimé.
- **Validation** : chaque appel LLM enregistre modèle, tokens entrée/sortie, coût estimé.

#### B10 — Vitrine · ⬜ À faire

- **Objectif** : un GitHub qui se comprend en 5 minutes.
- **Étapes** : README complet (problème, archi, stack, installation, tests, choix techniques, limites, roadmap), schéma, vidéo/GIF de 1 à 2 minutes, données de démo fictives (seeders).
- **Validation** : un `git clone` + `docker compose up` sur une machine vierge fonctionne en suivant le README.
- **LinkedIn** : post bilan de la semaine avec la vidéo.

---

### Semaine 2 et suivantes

#### B11 — CI · ⬜ À faire
GitHub Actions : Pint, Larastan, Pest, Ruff, pytest. Badge dans le README.

#### B12 — Démo en ligne · ⬜ À faire
VPS + Docker Compose, organisation de démo pré-remplie et remise à zéro chaque nuit, quota de questions par compte, **plafond de dépense chez le fournisseur LLM**, HTTPS.

#### B13 — Extraction structurée des factures · ⬜ À faire
Structured outputs : extraire numéro, montant, échéance, statut depuis les documents ; validation ; stockage en tables métier. Permet « Combien doit ACME ? » en SQL, de façon déterministe.

#### B14 — Routage SQL vs RAG · ⬜ À faire
Analyse de l'intention, tools (`get_unpaid_invoices`, `search_documents`…), réponse combinant données structurées et documents. Le LLM n'est jamais la source de vérité sur les chiffres.

#### B15 — Sécurité IA · ⬜ À faire
Prompt injection via le contenu des documents, rate limiting, données envoyées au LLM, logs sans données personnelles inutiles.

#### B16 — Évaluation du RAG · ⬜ À faire
Jeu de questions de référence, mesure de la qualité des réponses et des sources, détection des régressions.

#### B17 — Agent read-only · ⬜ À faire
« Quels clients relancer cette semaine et pourquoi ? » Tools en lecture seule, garde-fous, validation humaine pour toute action.

#### B18 — Mini-comparatif Laravel vs Symfony · ⬜ À faire
Article dans `docs/` : Eloquent vs Doctrine, policies vs voters, queues vs Messenger, DX. Support d'entretien et post LinkedIn.

---

## 6. ADR prévus

| ADR | Sujet | Statut |
|---|---|---|
| ADR-001 | Qui écrit les embeddings en base | ✅ Rédigé (B0) |
| ADR-002 | Fournisseur d'embeddings | B7 |
| ADR-003 | Stratégie de chunking | B7 |
| ADR-004 | Laravel au cœur, Python pour l'IA | À rédiger |
| ADR-005 | Isolation multi-tenant | B3 |
| ADR-006 | SQL vs RAG pour les questions métier | B14 |
| ADR-007 | Contrôle des coûts IA | B9 |
| ADR-008 | Pas de LangChain (pour l'instant) | B8 |

Format : **Statut**, **Contexte**, **Options envisagées**, **Décision**, **Conséquences** (avantages et inconvénients). Claude explique les enjeux, puis rédige ; je relis.

---

## 7. Glossaire

- **ADR** (*Architecture Decision Record*) : page courte qui trace une décision d'archi, son contexte, et ce qu'elle coûte.
- **Chunk** : morceau d'un document (quelques centaines de mots), unité recherchée puis envoyée au LLM.
- **Embedding** : vecteur de nombres représentant le sens d'un texte ; deux textes au sens proche ont des vecteurs proches.
- **pgvector** : extension PostgreSQL pour stocker des vecteurs et chercher les plus proches.
- **RAG** (*Retrieval-Augmented Generation*) : on récupère les chunks pertinents, puis le LLM répond à partir d'eux, avec sources.
- **Structured output** : forcer le LLM à répondre dans un format JSON défini et validé.
- **Tool calling** : le LLM demande à appeler une fonction de l'application (ex. `get_unpaid_invoices`) au lieu d'inventer une réponse.
- **Stateless** : un service qui ne garde aucun état entre deux appels.
- **IDOR** : faille où l'on accède à une ressource d'un autre en changeant un identifiant dans l'URL.

---

## 8. Journal

> Une entrée par session : ce qui a été fait, ce que j'ai appris, les blocages, la suite.

### 2026-10-05 — Cadrage
- Cadrage validé : Laravel au cœur, Python pour l'ingestion et le RAG, Python sans accès base.
- Notions vues : chunk, embedding, RAG.
- Suite : B0 (repo + ADR-001) puis B1 (Docker).

### 2026-10-05 — B0 (repo)
- Fichiers rédigés par Claude : `.gitignore` (racine + `ai-service/`), `.env.example`, `LICENSE` (MIT), `README.md`, ADR-001.
- Règle ajustée : la rédaction (docs, ADR, configs) est déléguée à Claude ; le code reste à moi.
- À retenir :
  - `laravel new` refuse un dossier non vide → installer à côté puis déplacer (B2).
  - Un secret poussé sur un repo public est compromis en quelques secondes : **révoquer d'abord**, nettoyer l'historique ensuite.
  - ADR-001 : Laravel seul écrit en base. Coût principal : ~17 Ko de JSON par chunk (1536 dimensions), ~5 Mo pour 300 chunks → surveiller le `memory_limit` du worker.
- Suite : créer le repo GitHub, premier commit, puis B1 (Docker).
