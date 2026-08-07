"""
Text extraction for the FAQ knowledge base.

Each function here takes a source (file bytes, URL, etc.) and returns plain
text. faq_store.py chunks and indexes whatever text comes out of these.

Honest limitations (read before wiring up "any social media URL"):
- Documents: PDF, DOCX, PPTX, XLSX, CSV, TXT/MD are supported directly.
- Websites: works for normal server-rendered pages (blogs, docs, most
  company sites). Pages that render their content with JavaScript after
  load (many single-page apps) won't extract well with a plain HTTP fetch.
- Video: only YouTube is supported, and only if the video has captions
  (auto-generated captions work fine). Other video platforms and videos
  with no captions aren't supported here - transcribing arbitrary video
  requires speech-to-text (e.g. Whisper), which is a separate, heavier
  piece you'd bolt on later if you need it.
- Social media (Instagram/X/Facebook/TikTok posts): these are mostly
  JavaScript-rendered and/or login-walled, so a plain scraper won't
  reliably get the caption/text. The practical approach real teams use:
  paste the caption/post text in via /faq/upload/text instead of trying
  to scrape it.
"""

import io
import re
from typing import Optional

import requests
from bs4 import BeautifulSoup


# ---------------------------------------------------------------------------
# Documents
# ---------------------------------------------------------------------------

def extract_from_document(filename: str, content: bytes) -> str:
    """Dispatch by file extension. Raises ValueError for unsupported types."""
    ext = filename.lower().rsplit(".", 1)[-1] if "." in filename else ""

    if ext == "pdf":
        return _extract_pdf(content)
    if ext == "docx":
        return _extract_docx(content)
    if ext == "pptx":
        return _extract_pptx(content)
    if ext == "xlsx":
        return _extract_xlsx(content)
    if ext in ("txt", "md", "csv"):
        return content.decode("utf-8", errors="ignore")

    # last resort: try to decode as text rather than flatly rejecting it
    try:
        return content.decode("utf-8")
    except UnicodeDecodeError:
        raise ValueError(
            f"Unsupported file type '.{ext}'. Supported: pdf, docx, pptx, xlsx, txt, md, csv"
        )


def _extract_pdf(content: bytes) -> str:
    from pypdf import PdfReader

    reader = PdfReader(io.BytesIO(content))
    return "\n\n".join(page.extract_text() or "" for page in reader.pages)


def _extract_docx(content: bytes) -> str:
    import docx

    doc = docx.Document(io.BytesIO(content))
    parts = [p.text for p in doc.paragraphs if p.text.strip()]
    for table in doc.tables:
        for row in table.rows:
            parts.append(" | ".join(cell.text for cell in row.cells))
    return "\n".join(parts)


def _extract_pptx(content: bytes) -> str:
    from pptx import Presentation

    prs = Presentation(io.BytesIO(content))
    parts = []
    for slide in prs.slides:
        for shape in slide.shapes:
            if hasattr(shape, "text") and shape.text.strip():
                parts.append(shape.text)
    return "\n".join(parts)


def _extract_xlsx(content: bytes) -> str:
    import openpyxl

    wb = openpyxl.load_workbook(io.BytesIO(content), data_only=True)
    parts = []
    for sheet in wb.worksheets:
        for row in sheet.iter_rows(values_only=True):
            cells = [str(c) for c in row if c is not None]
            if cells:
                parts.append(" | ".join(cells))
    return "\n".join(parts)


# ---------------------------------------------------------------------------
# Websites
# ---------------------------------------------------------------------------

def extract_from_url(url: str, timeout: int = 15) -> str:
    """Fetch a web page and return its visible text. See module docstring
    for what this can and can't handle.

    Uses browser-like headers because some sites (WordPress security
    plugins, Cloudflare, etc.) block requests from obvious bot user-agents
    even for public pages. This isn't a guarantee - sites with stronger
    bot protection or JS-rendered content may still fail; if so, the error
    message will say so and you can paste the content via /faq/upload/text
    instead.
    """
    headers = {
        "User-Agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
        ),
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Language": "en-US,en;q=0.9",
    }
    resp = requests.get(url, headers=headers, timeout=timeout)
    resp.raise_for_status()

    soup = BeautifulSoup(resp.text, "lxml")
    for tag in soup(["script", "style", "noscript", "nav", "footer", "header"]):
        tag.decompose()

    text = soup.get_text(separator="\n")
    lines = [line.strip() for line in text.splitlines()]
    lines = [line for line in lines if line]
    return "\n".join(lines)


# ---------------------------------------------------------------------------
# YouTube video transcripts
# ---------------------------------------------------------------------------

_YOUTUBE_ID_PATTERNS = [
    r"(?:v=|\/)([0-9A-Za-z_-]{11}).*",
    r"youtu\.be\/([0-9A-Za-z_-]{11})",
]


def _extract_youtube_id(url: str) -> Optional[str]:
    for pattern in _YOUTUBE_ID_PATTERNS:
        match = re.search(pattern, url)
        if match:
            return match.group(1)
    return None


def extract_from_video(url: str, languages=("en",)) -> str:
    """Fetch a YouTube video's transcript/captions as plain text.

    Raises ValueError with a clear message if the URL isn't YouTube, or if
    the video has no captions available.
    """
    video_id = _extract_youtube_id(url)
    if not video_id:
        raise ValueError(
            "Only YouTube URLs are supported for video transcripts right now. "
            "For other platforms, paste the transcript/caption text via /faq/upload/text."
        )

    from youtube_transcript_api import YouTubeTranscriptApi
    from youtube_transcript_api._errors import TranscriptsDisabled, NoTranscriptFound

    try:
        api = YouTubeTranscriptApi()
        fetched = api.fetch(video_id, languages=list(languages))
    except (TranscriptsDisabled, NoTranscriptFound) as exc:
        raise ValueError(f"No captions available for this video: {exc}")

    return "\n".join(snippet.text for snippet in fetched)
