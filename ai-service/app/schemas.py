from pydantic import BaseModel, Field

class ChunkOut(BaseModel):
    text: str = Field(min_length=1)
    page_start: int = Field(ge=1)
    page_end: int = Field(ge=1)

class IngestResponse(BaseModel):
    page_count: int = Field(ge=0)
    chunks: list[ChunkOut]
