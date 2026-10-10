from functools import lru_cache
from typing import Annotated

from fastapi import Depends, FastAPI, HTTPException, UploadFile
from pypdf.errors import PdfReadError

from app.embeddings import Embedder, EmbeddingError, build_embedder
from app.ingestion.chunking import chunk_pages
from app.ingestion.extraction import extract_pages
from app.schemas import ChunkOut, IngestResponse

app = FastAPI()


@lru_cache
def get_embedder() -> Embedder:
    return build_embedder()


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/ingest")
def ingest(
    file: UploadFile,
    embedder: Annotated[Embedder, Depends(get_embedder)],
) -> IngestResponse:
    content = file.file.read()

    try:
        pages = extract_pages(content)
    except PdfReadError as exc:
        raise HTTPException(
            status_code=422, detail="Invalid or unreadable PDF"
        ) from exc

    chunks = chunk_pages(pages)

    if not chunks:
        raise HTTPException(
            status_code=422, detail="No extractable text (scanned PDF?)"
        )

    try:
        vectors = embedder.embed([chunk.text for chunk in chunks])
    except EmbeddingError as exc:
        raise HTTPException(
            status_code=503, detail="Embedding service unavailable"
        ) from exc

    return IngestResponse(
        page_count=len(pages),
        embedding_model=embedder.model,
        chunks=[
            ChunkOut(
                text=chunk.text,
                page_start=chunk.page_start,
                page_end=chunk.page_end,
                embedding=vector,
            )
            for chunk, vector in zip(chunks, vectors, strict=True)
        ],
    )
