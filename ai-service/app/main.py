from fastapi import FastAPI, HTTPException, UploadFile
from pypdf.errors import PdfReadError

from app.ingestion.chunking import chunk_pages
from app.ingestion.extraction import extract_pages
from app.schemas import ChunkOut, IngestResponse

app = FastAPI()

@app.get("/health")
def health():
    return {"status": "ok"}

@app.post("/ingest")
def ingest(file: UploadFile) -> IngestResponse:
    content = file.file.read()

    try:
        pages = extract_pages(content)
    except PdfReadError as exc:
        raise HTTPException(status_code=422, detail="Invalid or unreadable PDF") from exc

    chunks = chunk_pages(pages)

    return IngestResponse(
        page_count=len(pages),
        chunks=[
            ChunkOut(text=chunk.text, page_start=chunk.page_start, page_end=chunk.page_end)
            for chunk in chunks
        ],
    )
