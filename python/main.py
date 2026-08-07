"""
TRP WhatsApp Bot Reply API
---------------------------
FastAPI microservice that generates an automated reply for an inbound
WhatsApp message. The existing Java (Spring Boot) app keeps doing exactly
what it does today - receive the Meta/Twilio webhook, save it to Postgres -
and additionally calls this API to get reply text, then sends that text
back to the customer using its existing WhatsAppService / TwilioService.

This service does NOT talk to WhatsApp, Twilio, or Postgres directly. It
only takes a message in and returns a reply out. That keeps it simple to
run, test, and hand off.

Run locally:
    uvicorn main:app --reload --port 8000

Interactive docs once running:
    http://localhost:8000/docs
"""

import logging
from pathlib import Path
from typing import List
from urllib.parse import quote

from fastapi import FastAPI, HTTPException, UploadFile, File, Form, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse

import extractors
from faq_store import store as faq_store
from bot_engine import generate_reply
from schemas import (
    ReplyRequest,
    ReplyResponse,
    FAQUrlRequest,
    FAQVideoRequest,
    FAQTextRequest,
    FAQSourceResponse,
    FAQSearchRequest,
    FAQSearchResponse,
    FAQMatch,
)

FAQ_FILES_DIR = Path(__file__).parent / "faq_files"
FAQ_FILES_DIR.mkdir(exist_ok=True)

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("trp-bot-api")

app = FastAPI(
    title="TRP WhatsApp Bot Reply API",
    description="Generates automated replies for Track Route Pro's WhatsApp bot.",
    version="1.0.0",
)

# Permissive CORS so a local test UI (opened as a file, or served from a
# different port) can call this API from the browser. This is fine for a
# local dev/test service; tighten allow_origins before exposing this
# publicly.
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/health")
def health_check():
    """Liveness check - the Java service (or a load balancer) can poll this."""
    return {"status": "ok", "service": "trp-bot-api"}


@app.post("/bot/reply", response_model=ReplyResponse)
def bot_reply(payload: ReplyRequest):
    """
    Given an inbound WhatsApp message, return the bot's reply text.

    Stateless: send the customer's phone number + message text (name and
    history are optional), get back the reply text to send over WhatsApp.
    """
    if not payload.message or not payload.message.strip():
        raise HTTPException(status_code=400, detail="message must not be empty")

    logger.info(
        "Incoming | conversation_id=%s phone=%s message=%r",
        payload.conversation_id, payload.phone_number, payload.message,
    )

    result = generate_reply(
        message=payload.message,
        profile_name=payload.profile_name,
        history=payload.history,
    )

    logger.info(
        "Reply | conversation_id=%s intent=%s handoff=%s -> %r",
        payload.conversation_id, result.intent, result.should_handoff_to_human, result.reply,
    )

    return result



# ---------------------------------------------------------------------------
# FAQ knowledge base - upload sources, then search them
# ---------------------------------------------------------------------------

@app.post("/faq/upload/document", response_model=FAQSourceResponse)
async def faq_upload_document(
    request: Request,
    file: UploadFile = File(...),
    send_as_link: bool = Form(True),
):
    """Upload a document (pdf, docx, pptx, xlsx, txt, md, csv) to index.

    The file itself is saved and served back at /faq/files/... - by
    default (send_as_link=True) the bot replies with a link to the whole
    file rather than a pasted snippet, since for things like a wiring
    diagram or a spec sheet, the file *is* the answer. Set
    send_as_link=false if this document is more like a manual FAQ (its
    extracted text should be pasted directly into the bot's reply).
    """
    content = await file.read()
    try:
        text = extractors.extract_from_document(file.filename, content)
        source = faq_store.add_source(
            name=file.filename,
            source_type="document",
            text=text,
            send_as_link=send_as_link,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))

    # Save the raw file and attach a URL to it now that we have the
    # generated source id. Sanitize the filename so it's a safe path
    # component (no slashes etc.) and keep the extension.
    safe_name = "".join(c for c in file.filename if c.isalnum() or c in "._-") or "file"
    stored_path = FAQ_FILES_DIR / f"{source.id}_{safe_name}"
    stored_path.write_bytes(content)

    file_url = str(request.base_url) + f"faq/files/{source.id}/{quote(safe_name)}"
    faq_store.set_source_url(source.id, file_url)

    logger.info("FAQ ingest | document=%s chunks=%d url=%s", file.filename, source.num_chunks, file_url)
    return source


@app.get("/faq/files/{source_id}/{filename}")
def faq_get_file(source_id: str, filename: str):
    """Serve an uploaded document's original file so the bot can link to
    the whole thing instead of pasting an extracted snippet."""
    safe_name = "".join(c for c in filename if c.isalnum() or c in "._-") or "file"
    path = FAQ_FILES_DIR / f"{source_id}_{safe_name}"
    if not path.exists():
        raise HTTPException(status_code=404, detail="File not found")
    return FileResponse(path, filename=filename)


@app.post("/faq/upload/url", response_model=FAQSourceResponse)
def faq_upload_url(payload: FAQUrlRequest):
    """Fetch a website URL and index its visible text."""
    try:
        text = extractors.extract_from_url(payload.url)
        source = faq_store.add_source(
            name=payload.name or payload.url,
            source_type="url",
            text=text,
            source_url=payload.url,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))
    except Exception as exc:  # network/HTTP errors from requests
        raise HTTPException(status_code=422, detail=f"Could not fetch URL: {exc}")
    logger.info("FAQ ingest | url=%s chunks=%d", payload.url, source.num_chunks)
    return source


@app.post("/faq/upload/video", response_model=FAQSourceResponse)
def faq_upload_video(payload: FAQVideoRequest):
    """Fetch a YouTube video's transcript and index it. Other video
    platforms aren't supported - see extractors.py docstring for why."""
    try:
        text = extractors.extract_from_video(payload.url)
        source = faq_store.add_source(
            name=payload.name or payload.url,
            source_type="video",
            text=text,
            source_url=payload.url,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))
    logger.info("FAQ ingest | video=%s chunks=%d", payload.url, source.num_chunks)
    return source


@app.post("/faq/upload/text", response_model=FAQSourceResponse)
def faq_upload_text(payload: FAQTextRequest):
    """Index raw text directly - the fallback for social media captions/
    posts, or any manually-written FAQ content."""
    try:
        source = faq_store.add_source(
            name=payload.name,
            source_type="text",
            text=payload.text,
            source_url=payload.source_url,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))
    logger.info("FAQ ingest | text=%s chunks=%d", payload.name, source.num_chunks)
    return source


@app.get("/faq/sources", response_model=List[FAQSourceResponse])
def faq_list_sources():
    """List everything currently indexed."""
    return faq_store.list_sources()


@app.delete("/faq/sources/{source_id}")
def faq_delete_source(source_id: str):
    """Remove a source (and all its chunks) from the index."""
    deleted = faq_store.delete_source(source_id)
    if not deleted:
        raise HTTPException(status_code=404, detail=f"No source with id {source_id}")
    return {"deleted": source_id}


@app.post("/faq/search", response_model=FAQSearchResponse)
def faq_search(payload: FAQSearchRequest):
    """Search the FAQ index for the best-matching content for a query.

    Returns the top matching chunks plus a convenience `answer` field set
    to the single best match's text when there's a confident match (or
    null if nothing cleared the relevance threshold).
    """
    results = faq_store.search(payload.query, top_k=payload.top_k)
    matches = [FAQMatch(**r) for r in results]
    answer = matches[0].text if matches else None
    return FAQSearchResponse(matches=matches, answer=answer)


if __name__ == "__main__":
    # Lets you also run this with `python main.py` instead of the uvicorn CLI.
    import uvicorn

    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True)
