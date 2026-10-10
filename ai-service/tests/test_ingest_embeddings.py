from pathlib import Path

from fastapi.testclient import TestClient

from app.main import app

FIXTURE = Path(__file__).parent / "fixtures" / "facture_acme.pdf"


def test_ingest_returns_one_vector_per_chunk():
    client = TestClient(app)

    with FIXTURE.open("rb") as pdf:
        response = client.post("/ingest", files={"file": pdf})

    data = response.json()
    assert response.status_code == 200
    assert data["embedding_model"] == "fake-embedder"
    assert all(len(chunk["embedding"]) == 1024 for chunk in data["chunks"])
