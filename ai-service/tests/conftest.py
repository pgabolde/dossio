import pytest

from app.main import app, get_embedder


class FakeEmbedder:
    model = "fake-embedder"

    def embed(self, texts: list[str]) -> list[list[float]]:
        return [[0.1] * 1024 for _ in texts]


@pytest.fixture(autouse=True)
def fake_embedder():
    app.dependency_overrides[get_embedder] = FakeEmbedder
    yield
    app.dependency_overrides.clear()
