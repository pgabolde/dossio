# Dossio

> 🚧 **En construction** — projet développé en *build in public*.

CRM multi-organisations qui centralise tous les documents d'un client (factures, devis, contrats en PDF, DOCX, XLSX) et permet de leur poser des questions en langage naturel, avec des **réponses sourcées**.

> *« Quel est le montant du dernier devis envoyé à ACME, et quelles sont ses conditions de paiement ? »*
> → réponse + extraits des documents d'origine (fichier, page).

## Le problème

Les informations d'un client sont dispersées dans des dizaines de documents hétérogènes. Retrouver une échéance, un montant ou une clause, c'est ouvrir les fichiers un par un. Dossio indexe ces documents et répond en citant ses sources, sans jamais mélanger les données de deux organisations.

## Stack

| Brique | Techno |
|---|---|
| Application | Laravel · Inertia · Vue 3 · TypeScript · Tailwind · shadcn-vue |
| Service IA | Python · FastAPI · Pydantic |
| Base | PostgreSQL + pgvector |
| Traitements longs | Queue Laravel (driver `database`) |
| Tests & qualité | Pest · Larastan · Pint · pytest · Ruff |
| Environnement | Docker Compose |

## Architecture

```text
   Navigateur (Vue 3 + TS via Inertia)
                 │
                 ▼
             Laravel ───── HTTP interne ─────▶ Python / FastAPI
     (auth, organisations,                     /ingest /embed /answer
      documents, queue,                               │
      recherche vectorielle)                          ▼
                 │                          Fournisseurs LLM / embeddings
                 ▼ SQL
       PostgreSQL + pgvector
       (seul Laravel y accède)
```

Principe clé : **Python est stateless et n'a aucun accès à la base.** Laravel est le seul propriétaire des données, ce qui garantit l'isolation entre organisations par construction. Voir [ADR-001](docs/decisions/0001-ecriture-des-embeddings.md).

## Décisions d'architecture

Les choix techniques sont documentés sous forme d'ADR dans [`docs/decisions/`](docs/decisions/).

## Statut

- [ ] Environnement Docker
- [ ] Socle Laravel + authentification
- [ ] Multi-tenant (organisations, isolation)
- [ ] Clients et upload de documents
- [ ] Extraction et découpage des documents (Python)
- [ ] Embeddings + pgvector
- [ ] Question / réponse sourcée (RAG)
- [ ] Suivi des coûts IA

## Licence

[MIT](LICENSE)
