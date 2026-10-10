import json

import httpx
import pytest

from app.embeddings import EmbeddingError, OllamaEmbedder

DIMENSIONS = 4


def make_embedder(handler, batch_size: int = 32) -> OllamaEmbedder:
    client = httpx.Client(
        base_url="http://ollama.test", transport=httpx.MockTransport(handler)
    )
    return OllamaEmbedder(
        client=client, model="bge-m3", dimensions=DIMENSIONS, batch_size=batch_size
    )


def test_texts_are_sent_in_batches():
    received_batches = []

    def handler(request: httpx.Request) -> httpx.Response:
        texts = json.loads(request.content)["input"]
        received_batches.append(texts)
        return httpx.Response(
            200, json={"embeddings": [[0.1] * DIMENSIONS for _ in texts]}
        )

    vectors = make_embedder(handler, batch_size=2).embed(["a", "b", "c"])

    assert received_batches == [["a", "b"], ["c"]]
    assert len(vectors) == 3


def test_wrong_dimension_is_rejected():
    def handler(request: httpx.Request) -> httpx.Response:
        return httpx.Response(200, json={"embeddings": [[0.1] * 3]})

    with pytest.raises(EmbeddingError):
        make_embedder(handler).embed(["a"])


def test_ollama_error_is_wrapped():
    def handler(request: httpx.Request) -> httpx.Response:
        return httpx.Response(500)

    with pytest.raises(EmbeddingError):
        make_embedder(handler).embed(["a"])
