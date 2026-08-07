"""
FAQ knowledge base: turns ingested text into searchable chunks and answers
queries by finding the most relevant chunk(s).

Retrieval approach: TF-IDF + cosine similarity (scikit-learn), not neural
embeddings. This is a deliberate choice, not a shortcut:
- No model download, no GPU, nothing that can silently break on a given
  Python/OS combo (you already hit a Python-version/transformers
  incompatibility before - this avoids that whole class of problem).
- It's fast and fully local - no per-query API cost or network call.
- For FAQ-style matching (a question that shares real keywords with the
  answer) it's very effective. This is the same baseline "big tech"
  support search used for years before embeddings became cheap.

If you outgrow it later (thousands of documents, or queries that are
worded very differently from the source text), the upgrade path is to
swap TfidfVectorizer for a sentence-transformers embedding model and swap
the cosine-similarity search for a vector index (e.g. FAISS or Chroma) -
`search()` is the only method that would need to change; everything that
calls it stays the same.

Storage: everything is kept in one JSON file on disk (chunks + metadata)
and rebuilt into an in-memory TF-IDF index on load. No database needed at
this scale (up to low thousands of chunks).
"""

import json
import re
import uuid
from dataclasses import dataclass, asdict, field
from datetime import datetime, timezone
from pathlib import Path
from typing import List, Optional

from sklearn.feature_extraction.text import TfidfVectorizer, ENGLISH_STOP_WORDS
from sklearn.metrics.pairwise import cosine_similarity

try:
    from nltk.stem.snowball import SnowballStemmer
    _stemmer = SnowballStemmer("english")
except ImportError:  # falls back to no stemming if nltk isn't installed
    _stemmer = None

_TOKEN_RE = re.compile(r"[a-zA-Z]{2,}")


def _stem_tokenizer(text: str) -> List[str]:
    """Lowercase, extract words, drop stopwords, then stem what's left
    (wiring/wire, connecting/connect, etc. all collapse to the same
    token). Stopwords are filtered BEFORE stemming - filtering after would
    miss most of them, since e.g. "having" stems to "have" and no longer
    matches the stopword list. This is what lets a query use a different
    word form than the source text and still match."""
    tokens = _TOKEN_RE.findall(text.lower())
    tokens = [t for t in tokens if t not in ENGLISH_STOP_WORDS]
    if _stemmer:
        tokens = [_stemmer.stem(t) for t in tokens]
    return tokens

DATA_FILE = Path(__file__).parent / "faq_data.json"

CHUNK_SIZE = 800       # characters per chunk (fallback chunker, non-Q&A docs)
CHUNK_OVERLAP = 150    # characters of overlap between consecutive chunks
MIN_SCORE = 0.15       # below this cosine similarity, treat as "no match"

# Matches lines like "Q1. What is TRPGPS?" or "Q12) How does X work?"
_Q_LINE_RE = re.compile(r"^Q\d+[.).\-:]\s*(.+)$", re.IGNORECASE)
# Heuristic for a short section-header line ("General", "Fleet Management",
# "School Bus") sitting between Q&A blocks: short, no ending punctuation.
_SECTION_LINE_RE = re.compile(r"^[A-Z][A-Za-z0-9 &/\-]{1,45}$")


def _try_qa_chunks(text: str) -> Optional[List[str]]:
    """If the document looks like a Q&A/FAQ list (has lines starting with
    "Q1.", "Q2.", etc.), split it into one chunk per question - each
    containing just that question, its answer, and its section heading if
    one precedes it (e.g. "Fleet Management"). Returns None if the text
    doesn't look like this format, so the caller can fall back to generic
    fixed-size chunking.

    This matters a lot for accuracy: a fixed-size window has no idea where
    one FAQ entry ends and the next begins, so it merges several unrelated
    Q&A pairs into a single chunk. That dilutes the real match (the
    specific question a customer asked competes against 4 other answers'
    worth of text in the same chunk) and creates false positives (any
    query sharing the document's own title/common words can match some
    chunk of it, even one that doesn't actually answer the question).
    Splitting one-chunk-per-question avoids both problems.
    """
    lines = [l.strip() for l in text.split("\n")]
    if not any(_Q_LINE_RE.match(l) for l in lines if l):
        return None

    chunks: List[str] = []
    current_section: Optional[str] = None
    current_lines: List[str] = []

    def flush():
        if not current_lines:
            return
        block = "\n".join(current_lines).strip()
        if block:
            chunks.append(f"{current_section}\n{block}" if current_section else block)

    for line in lines:
        if not line:
            continue
        if _Q_LINE_RE.match(line):
            flush()
            current_lines = [line]
        elif _SECTION_LINE_RE.match(line) and not line.endswith("."):
            # A short, header-like line (no ending punctuation) between
            # Q&A blocks - e.g. "Fleet Management" appearing right after
            # the previous section's last answer. Finalize whatever block
            # was in progress and start tracking the new section context.
            # Checked before the "continuation line" case below so this
            # doesn't just get glued onto the previous answer.
            flush()
            current_lines = []
            current_section = line
        elif current_lines:
            current_lines.append(line)
        # else: a line before any question has been seen at all (document
        # title, intro sentence) - not part of any Q&A block, skip it.
    flush()

    return chunks if chunks else None


@dataclass
class Chunk:
    id: str
    source_id: str
    source_name: str
    source_type: str  # "document" | "url" | "video" | "text"
    text: str
    source_url: Optional[str] = None
    send_as_link: bool = False


@dataclass
class Source:
    id: str
    name: str
    type: str
    added_at: str
    num_chunks: int
    source_url: Optional[str] = None
    send_as_link: bool = False


def _chunk_text(text: str, size: int = CHUNK_SIZE, overlap: int = CHUNK_OVERLAP) -> List[str]:
    """Split text into chunks for indexing. Tries Q&A-aware splitting
    first (one chunk per question - see _try_qa_chunks); if the text
    doesn't look like a Q&A list, falls back to overlapping fixed-size
    windows that break on sentence/paragraph boundaries where possible."""
    text = re.sub(r"\n{3,}", "\n\n", text).strip()
    if not text:
        return []

    qa_chunks = _try_qa_chunks(text)
    if qa_chunks:
        # Still cap any individual Q&A block's length in the rare case one
        # answer is unusually long - reuses the same fixed-size splitter.
        result = []
        for block in qa_chunks:
            if len(block) <= size:
                result.append(block)
            else:
                result.extend(_fixed_size_chunks(block, size, overlap))
        return result

    return _fixed_size_chunks(text, size, overlap)


def _fixed_size_chunks(text: str, size: int = CHUNK_SIZE, overlap: int = CHUNK_OVERLAP) -> List[str]:
    """Split text into overlapping chunks, breaking on sentence/paragraph
    boundaries where possible so chunks don't cut mid-sentence."""
    if len(text) <= size:
        return [text] if text else []

    chunks = []
    start = 0
    while start < len(text):
        end = start + size
        if end < len(text):
            # try to break at the last sentence/paragraph boundary in range
            window = text[start:end]
            boundary = max(window.rfind(". "), window.rfind("\n"))
            if boundary > size * 0.5:  # only use it if it's not too early
                end = start + boundary + 1
        chunk = text[start:end].strip()
        if chunk:
            chunks.append(chunk)
        start = end - overlap if end - overlap > start else end
    return chunks


class FAQStore:
    def __init__(self, path: Path = DATA_FILE):
        self.path = path
        self.sources: dict[str, Source] = {}
        self.chunks: List[Chunk] = []
        self._vectorizer: Optional[TfidfVectorizer] = None
        self._matrix = None
        self._load()
        self._rebuild_index()

    # -- persistence ---------------------------------------------------

    def _load(self):
        if not self.path.exists():
            return
        data = json.loads(self.path.read_text(encoding="utf-8"))
        self.sources = {s["id"]: Source(**s) for s in data.get("sources", [])}
        self.chunks = [Chunk(**c) for c in data.get("chunks", [])]

    def _save(self):
        data = {
            "sources": [asdict(s) for s in self.sources.values()],
            "chunks": [asdict(c) for c in self.chunks],
        }
        self.path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")

    # -- indexing --------------------------------------------------------

    def _rebuild_index(self):
        if not self.chunks:
            self._vectorizer = None
            self._matrix = None
            return
        self._vectorizer = TfidfVectorizer(
            tokenizer=_stem_tokenizer,
            token_pattern=None,
            ngram_range=(1, 2),
            max_features=20000,
        )
        # Index the source name alongside each chunk's text so a
        # document/URL/video's own name - "wiring-diagram.pdf", "Delivery
        # & Shipping Policy" - contributes to matching even if the body
        # text doesn't repeat those words. Only a single repetition: for a
        # multi-chunk document (e.g. a 30-question FAQ file), every chunk
        # shares the same source name, so over-weighting it would make
        # every chunk look similarly relevant to any query that mentions
        # the document/company name, drowning out what actually
        # distinguishes one chunk's content from another's. The chunk's
        # stored `text` (what actually gets shown/returned) is untouched -
        # this only affects what the search index sees.
        index_texts = [f"{c.source_name} {c.text}" for c in self.chunks]
        self._matrix = self._vectorizer.fit_transform(index_texts)

    # -- ingestion ---------------------------------------------------------

    def add_source(
        self,
        name: str,
        source_type: str,
        text: str,
        source_url: Optional[str] = None,
        send_as_link: Optional[bool] = None,
    ) -> Source:
        if not text or not text.strip():
            raise ValueError("Extracted text is empty - nothing to index")

        if send_as_link is None:
            # Documents and videos are things people want as a whole file/
            # video, not a pasted snippet - default to linking. Pasted text
            # only defaults to linking if a source_url was actually given
            # (the social-media-post case); plain manual FAQ text has
            # nothing to link to, so it answers with the text itself.
            # send_as_link = source_type in ("document", "video") or (
            #     source_type == "text" and bool(source_url)
            # )
            send_as_link = False

        source_id = str(uuid.uuid4())[:8]
        pieces = _chunk_text(text)
        if not pieces:
            raise ValueError("Extracted text produced no usable chunks")

        for piece in pieces:
            self.chunks.append(
                Chunk(
                    id=str(uuid.uuid4())[:8],
                    source_id=source_id,
                    source_name=name,
                    source_type=source_type,
                    text=piece,
                    source_url=source_url,
                    send_as_link=send_as_link,
                )
            )

        source = Source(
            id=source_id,
            name=name,
            type=source_type,
            added_at=datetime.now(timezone.utc).isoformat(),
            num_chunks=len(pieces),
            source_url=source_url,
            send_as_link=send_as_link,
        )
        self.sources[source_id] = source

        self._rebuild_index()
        self._save()
        return source

    def set_source_url(self, source_id: str, url: str) -> None:
        """Attach a URL after the fact - used for documents, where the
        file has to be saved to disk (and its id known) before a URL to
        serve it can be built. Metadata-only change, no re-indexing needed."""
        if source_id in self.sources:
            self.sources[source_id].source_url = url
        for chunk in self.chunks:
            if chunk.source_id == source_id:
                chunk.source_url = url
        self._save()

    def delete_source(self, source_id: str) -> bool:
        if source_id not in self.sources:
            return False
        del self.sources[source_id]
        self.chunks = [c for c in self.chunks if c.source_id != source_id]
        self._rebuild_index()
        self._save()
        return True

    def list_sources(self) -> List[Source]:
        return list(self.sources.values())

    # -- search ---------------------------------------------------------

    def search(self, query: str, top_k: int = 3) -> List[dict]:
        """Return up to top_k chunks most relevant to the query, each with
        a similarity score in [0, 1]. Empty list if the store is empty or
        nothing clears MIN_SCORE.

        Scores are penalized by vocabulary coverage: TF-IDF silently drops
        query words that never appear anywhere in the corpus (out-of-
        vocabulary), rather than treating their absence as evidence the
        question isn't covered. Without this, a query like "Who is the
        CEO of TRPGPS?" - where "CEO" appears nowhere in the knowledge
        base - would still score decently against any chunk that merely
        mentions "TRPGPS", because the unanswerable half of the question
        is invisible to the model rather than counting against the match.
        Multiplying by (known content words / total content words) makes
        a mostly-unrecognized question score low even if the one word it
        does share with a chunk is a strong match on its own.
        """
        if self._vectorizer is None or self._matrix is None:
            return []

        query_tokens = set(_stem_tokenizer(query))
        if not query_tokens:
            return []
        vocab = self._vectorizer.vocabulary_
        known = sum(1 for t in query_tokens if t in vocab)
        coverage = known / len(query_tokens)

        query_vec = self._vectorizer.transform([query])
        scores = cosine_similarity(query_vec, self._matrix)[0] * coverage

        ranked = sorted(range(len(scores)), key=lambda i: scores[i], reverse=True)
        results = []
        for i in ranked[:top_k]:
            if scores[i] < MIN_SCORE:
                break
            chunk = self.chunks[i]
            results.append(
                {
                    "text": chunk.text,
                    "source_name": chunk.source_name,
                    "source_type": chunk.source_type,
                    "source_id": chunk.source_id,
                    "source_url": chunk.source_url,
                    "send_as_link": chunk.send_as_link,
                    "score": round(float(scores[i]), 4),
                }
            )
        return results


# Module-level singleton so main.py and bot_engine.py share one index
# without re-reading the file on every request.
store = FAQStore()
