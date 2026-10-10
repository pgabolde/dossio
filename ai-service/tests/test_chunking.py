from pathlib import Path

from fastapi.testclient import TestClient

from app.ingestion.chunking import chunk_pages
from app.ingestion.extraction import Page, extract_pages
from app.main import app

FIXTURE = Path(__file__).parent / "fixtures" / "facture_acme.pdf"


def test_extracts_every_page() -> None:
    pages = extract_pages(FIXTURE.read_bytes())
    assert len(pages) == 3


def test_sentence_split_across_pages_ends_up_in_one_chunk() -> None:
    chunks = chunk_pages(extract_pages(FIXTURE.read_bytes()))
    matching = [c for c in chunks if "s'élève" in c.text and "30 avril 2026" in c.text]

    assert len(matching) >= 1
    assert matching[0].page_start == 1
    assert matching[0].page_end == 2


def test_overlap_repeats_last_sentence() -> None:
    pages = [
        Page(1, "Facture de mars. Client ACME."),
        Page(2, "Montant 1200 €. Échéance 30 avril."),
    ]
    chunks = chunk_pages(pages, max_chars=40)

    assert [c.text for c in chunks] == [
        "Facture de mars. Client ACME.",
        "Client ACME. Montant 1200 €.",
        "Montant 1200 €. Échéance 30 avril.",
    ]


def test_ingest_rejects_non_pdf() -> None:
    client = TestClient(app)
    response = client.post(
        "/ingest", files={"file": ("x.pdf", b"pas un pdf", "application/pdf")}
    )

    assert response.status_code == 422
