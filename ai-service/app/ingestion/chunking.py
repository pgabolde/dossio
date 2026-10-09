import re
from dataclasses import dataclass

from app.ingestion.extraction import Page

WHITESPACE = re.compile(r"\s+")
SENTENCE_END = re.compile(r"(?<=[.!?])\s+")

@dataclass(frozen=True)
class Chunk:
    text: str
    page_start: int
    page_end: int

def split_sentences(pages: list[Page]) -> list[tuple[str, int]]:
    sentences: list[tuple[str, int]] = []

    for page in pages:
        text = WHITESPACE.sub(" ", page.text).strip()
        if not text:
            continue

        for sentence in SENTENCE_END.split(text):
            sentences.append((sentence, page.number))

    return sentences

def to_chunk(sentences: list[tuple[str, int]]) -> Chunk:
    text = " ".join(sentence for sentence, _ in sentences)
    return Chunk(text=text, page_start=sentences[0][1], page_end=sentences[-1][1])

def chunk_pages(pages: list[Page], max_chars: int = 800, overlap_sentences: int = 1) -> list[Chunk]:
    chunks: list[Chunk] = []
    current: list[tuple[str, int]] = []

    for sentence in split_sentences(pages):
        candidate = current + [sentence]

        if current and len(to_chunk(candidate).text) > max_chars:
            chunks.append(to_chunk(current))

            overlap = current[-overlap_sentences:] if overlap_sentences > 0 else []
            current = overlap + [sentence]

            if len(to_chunk(current).text) > max_chars:
                current = [sentence]
        else:
            current = candidate

    if current:
        chunks.append(to_chunk(current))

    return chunks
