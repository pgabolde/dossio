from dataclasses import dataclass
from io import BytesIO

from pypdf import PdfReader


@dataclass(frozen=True)
class Page:
    number: int
    text: str


def extract_pages(pdf_bytes: bytes) -> list[Page]:
    reader = PdfReader(BytesIO(pdf_bytes))
    pages: list[Page] = []

    for number, pdf_page in enumerate(reader.pages, start=1):
        text = pdf_page.extract_text() or ""
        pages.append(Page(number=number, text=text))

    return pages
