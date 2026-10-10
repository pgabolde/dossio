import os
from typing import Protocol

import httpx


class EmbeddingError(Exception):
    """L'appel au modèle d'embeddings a échoué ou a renvoyé un résultat invalide."""


class Embedder(Protocol):
    model: str

    def embed(self, texts: list[str]) -> list[list[float]]: ...


class OllamaEmbedder:
    def __init__(
        self,
        client: httpx.Client,
        model: str,
        dimensions: int,
        batch_size: int = 32,
    ) -> None:
        self.model = model
        self._client = client
        self._dimensions = dimensions
        self._batch_size = batch_size

    def embed(self, texts: list[str]) -> list[list[float]]:
        vectors: list[list[float]] = []
        for start in range(0, len(texts), self._batch_size):
            batch = texts[start : start + self._batch_size]
            vectors.extend(self._embed_batch(batch))
        return vectors

    def _embed_batch(self, batch: list[str]) -> list[list[float]]:
        try:
            response = self._client.post(
                "/api/embed", json={"model": self.model, "input": batch}
            )
            response.raise_for_status()
        except httpx.HTTPError as exc:
            raise EmbeddingError(f"Appel à Ollama impossible : {exc}") from exc

        embeddings: list[list[float]] = response.json()["embeddings"]

        if len(embeddings) != len(batch):
            raise EmbeddingError(
                f"{len(batch)} textes envoyés, {len(embeddings)} vecteurs reçus"
            )
        for vector in embeddings:
            if len(vector) != self._dimensions:
                raise EmbeddingError(
                    f"Vecteur de {len(vector)} dimensions, {self._dimensions} attendues"
                )

        return embeddings


def build_embedder() -> OllamaEmbedder:
    return OllamaEmbedder(
        client=httpx.Client(base_url=os.environ["OLLAMA_BASE_URL"], timeout=60.0),
        model=os.environ["EMBEDDING_MODEL"],
        dimensions=int(os.environ["EMBEDDING_DIMENSIONS"]),
    )
