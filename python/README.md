# TRP WhatsApp Bot Reply API

A small Python (FastAPI) service that generates the bot's reply text for
an inbound WhatsApp message. It does **not** talk to WhatsApp, Twilio, or
your Postgres DB — it only takes a message in and returns reply text out.
Your existing Java service keeps doing everything it already does
(receiving the webhook, saving `ContactEntity` / `ConversationEntity` /
`MessageEntity`) and just adds one HTTP call to this API to decide what
to reply, then sends that text using the `WhatsAppService` /
`TwilioService` you already have.

```
WhatsApp → Meta/Twilio webhook → WebhookController (Java, saves to DB)
                                        │
                                        ▼
                          POST /bot/reply  (this Python service)
                                        │
                                        ▼
                          WhatsAppService.sendMessage(to, reply)
```

---

## 1. Run it on your laptop

```bash
# from inside this folder
python3 -m venv venv
source venv/bin/activate        # Windows: venv\Scripts\activate
pip install -r requirements.txt

uvicorn main:app --reload --port 8000
```

You should see:
```
Uvicorn running on http://0.0.0.0:8000
```

Interactive docs (try it in the browser, no curl needed): `http://localhost:8000/docs`

## 2. Test it locally

Option A — the visual test UI (easiest): open `test_ui.html` in your
browser (just double-click it, no server needed for the UI itself). It
gives you a chat window to test `/bot/reply` and a panel to upload
documents/URLs/videos/text and search the FAQ knowledge base — as long
as `uvicorn` is running, it talks straight to `http://localhost:8000`
(editable at the top if you're testing against a different host, like
your VPS). See section 6 below for what it looks like.

Option B — the included script:
```bash
python test_bot.py
```

Option C — curl:
```bash
curl -X POST http://localhost:8000/bot/reply \
  -H "Content-Type: application/json" \
  -d '{"phone_number":"919876543210","message":"What is the price of Sentinel?"}'
```

Expected response:
```json
{
  "reply": "Sentinel is our wired GPS tracker, currently at Rs. 1,999 (MRP Rs. 5,999).\nDetails: https://trpgps.com/product/trackroutepro-sentinel/",
  "intent": "pricing",
  "should_handoff_to_human": false
}
```

## 3. Files

| File | Purpose |
|---|---|
| `main.py` | FastAPI app — the `/health` and `/bot/reply` routes |
| `bot_engine.py` | The actual reply logic (keyword/intent matching). Edit this to change what the bot says. |
| `schemas.py` | Request/response shapes (also what generates the `/docs` page) |
| `test_bot.py` | Standalone script that fires a handful of sample messages at the running server |
| `requirements.txt` | Python dependencies |

The bot currently handles: greetings, product/pricing questions (Sentinel,
SecureLink MagTrack), demo booking, order-status questions, support
issues, "talk to a human," thanks, and a fallback for anything else.
`should_handoff_to_human` is `true` for anything the bot can't resolve on
its own (support issues, order lookups, explicit human requests) — that's
a signal for your side to route the conversation to a human agent, not
just a bot reply.

---

## 4. API contract — for the Java developer

**Endpoint:** `POST /bot/reply`
**Content-Type:** `application/json`

### Request body

| Field | Type | Required | Notes |
|---|---|---|---|
| `phone_number` | string | yes | Customer's WhatsApp number / `wa_id` |
| `message` | string | yes | The inbound message text |
| `profile_name` | string | no | Customer's WhatsApp profile name, if known |
| `conversation_id` | string | no | Your conversation/message id, echoed into logs only, not used in logic |
| `history` | array | no | Optional, for future use — `[{"direction":"Inbound","text":"..."}]` |

```json
{
  "phone_number": "919876543210",
  "message": "What is the price of Sentinel?",
  "profile_name": "Rushil",
  "conversation_id": "1042"
}
```

### Response body

```json
{
  "reply": "Sentinel is our wired GPS tracker, currently at Rs. 1,999 (MRP Rs. 5,999).\nDetails: https://trpgps.com/product/trackroutepro-sentinel/",
  "intent": "pricing",
  "should_handoff_to_human": false
}
```

| Field | Type | Notes |
|---|---|---|
| `reply` | string | Send this text back to the customer over WhatsApp |
| `intent` | string | What the bot thinks the message was about (useful for logging/analytics) |
| `should_handoff_to_human` | boolean | If `true`, flag/route the conversation to a human agent |

### Errors
- `400` if `message` is empty/blank: `{"detail": "message must not be empty"}`
- `422` if the request body doesn't match the schema above (standard FastAPI validation error)

### Example Java call (RestTemplate)

Drop this into `WebhookController`, right after the existing code that
saves the inbound `MessageEntity` (around line 185 in the current file),
and before `return ResponseEntity.ok("EVENT_RECEIVED");`:

```java
// 1. Ask the Python bot service what to reply
String botApiUrl = "http://localhost:8000/bot/reply"; // swap for the deployed URL later

Map<String, Object> botRequest = new HashMap<>();
botRequest.put("phone_number", phoneNumberId);
botRequest.put("message", messageBody);
botRequest.put("profile_name", profileName);
botRequest.put("conversation_id", String.valueOf(conversationEntityData.getId()));

RestTemplate restTemplate = new RestTemplate();
Map<String, Object> botResponse = restTemplate.postForObject(botApiUrl, botRequest, Map.class);

String replyText = (String) botResponse.get("reply");
Boolean handoff = (Boolean) botResponse.get("should_handoff_to_human");

// 2. Send it back over WhatsApp using the service you already have
service.sendMessage(phoneNumberId, replyText);

// 3. (optional) if handoff == true, mark the conversation for a human agent
```

(`service` here is the existing `WhatsAppService` already autowired in
`WhatsAppController` — you'd autowire it into `WebhookController` too, or
call `WhatsAppService.sendMessage()` directly since it's already a
`@Service` bean.)

---

## 5. Letting the Java developer call it

**If you're both on the same office/local network:** run the server as
above, then share `http://<your-laptop-LAN-IP>:8000` instead of
`localhost`. Find your LAN IP with `ipconfig` (Windows) or `ifconfig` /
`ip addr` (Mac/Linux). Make sure your firewall allows inbound connections
on port 8000.

**If he's remote:** use a tunnel so he gets a temporary public HTTPS URL
without you deploying anything yet:

```bash
# install ngrok: https://ngrok.com/download
ngrok http 8000
```

It prints something like `https://abcd1234.ngrok-free.app` — send him
that URL plus this README (or just the "API contract" section above). He
calls `https://abcd1234.ngrok-free.app/bot/reply` exactly like
`http://localhost:8000/bot/reply` above. Note free ngrok URLs change
every time you restart it, so re-share the URL if you restart.

**For the real deployment:** once he's happy with it, deploy this same
FastAPI app on the VPS your Java app and Postgres already run on
(`147.93.19.155`), on an internal port (e.g. 8000), and point the Java
`RestTemplate` call at `http://localhost:8000/bot/reply` if it's the same
box, or the VPS's private IP if not — no need to expose it to the public
internet at all, since only your Java app needs to reach it. A simple way
to keep it running is a `systemd` service:

```ini
# /etc/systemd/system/trp-bot-api.service
[Unit]
Description=TRP WhatsApp Bot Reply API
After=network.target

[Service]
WorkingDirectory=/opt/trp-bot-api
ExecStart=/opt/trp-bot-api/venv/bin/uvicorn main:app --host 0.0.0.0 --port 8000
Restart=always
User=www-data

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now trp-bot-api
```

---

## 6. FAQ knowledge base (upload docs/URLs/videos, bot answers from them)

On top of the fixed intents above, the bot can now answer from content you
upload - documents, website pages, YouTube transcripts, or pasted text.
When a message doesn't match any rule in `bot_engine.py`, it's checked
against this knowledge base before falling back to the generic message.

**How it works:** every source you upload gets split into overlapping
~800-character chunks and indexed with TF-IDF (keyword-weighted search,
not a neural embedding model — see the docstring at the top of
`faq_store.py` for why that's a deliberate choice, and how to upgrade to
embeddings later if you outgrow it). A query is matched against all
chunks by cosine similarity; the best match is returned if it clears a
relevance threshold, otherwise the bot says it doesn't know. Everything
is stored locally: chunk/source metadata in `faq_data.json`, and
uploaded documents' original files in `faq_files/` — add both to
`.gitignore` alongside `venv/`, same reasoning as before.

### Ingestion endpoints

| Endpoint | What it takes | Notes |
|---|---|---|
| `POST /faq/upload/document` | multipart file upload + optional `send_as_link` form field (default true) | pdf, docx, pptx, xlsx, txt, md, csv |
| `GET /faq/files/{id}/{filename}` | — | serves the original uploaded file back (used in the bot's link replies) |
| `POST /faq/upload/url` | `{"url": "...", "name": "optional"}` | fetches a web page's visible text |
| `POST /faq/upload/video` | `{"url": "...", "name": "optional"}` | YouTube only, needs captions (auto-captions are fine) |
| `POST /faq/upload/text` | `{"name": "...", "text": "..."}` | paste raw text directly |
| `GET /faq/sources` | — | list everything indexed |
| `DELETE /faq/sources/{id}` | — | remove a source and its chunks |
| `POST /faq/search` | `{"query": "...", "top_k": 3}` | search directly, without going through `/bot/reply` |

Example — upload a document:
```bash
curl -X POST http://localhost:8000/faq/upload/document -F "file=@warranty-policy.pdf"
```

Example — upload a website page:
```bash
curl -X POST http://localhost:8000/faq/upload/url \
  -H "Content-Type: application/json" \
  -d '{"url":"https://trpgps.com/delivery-shipping-policy/","name":"Shipping Policy"}'
```

Example — search directly:
```bash
curl -X POST http://localhost:8000/faq/search \
  -H "Content-Type: application/json" \
  -d '{"query":"what is your warranty policy"}'
```
Response:
```json
{
  "matches": [{"text": "...", "source_name": "warranty-policy.pdf", "source_type": "document", "source_id": "9dbb4e6c", "score": 0.35}],
  "answer": "..."
}
```

Once something is uploaded, `/bot/reply` automatically uses it — try
asking the bot something only covered in your uploaded content and check
the response's `intent` field says `"faq"`.

**Linking back to the original video/post:** website and video uploads
automatically store the URL you gave them, and the bot appends it to its
reply when that source answers a question — e.g. a customer asking about
something covered in a YouTube demo gets the video link included in the
bot's reply. For pasted text (the social-media case, since there's no
URL to fetch automatically), pass an optional `source_url` alongside the
caption text and it'll be appended the same way:
```bash
curl -X POST http://localhost:8000/faq/upload/text \
  -H "Content-Type: application/json" \
  -d '{"name":"Instagram - unboxing","text":"Unboxing and setup walkthrough for Sentinel...","source_url":"https://instagram.com/p/example123"}'
```
If you don't pass `source_url`, nothing is appended — plain FAQ text (a
manual policy entry, say) doesn't need a link tacked onto it.

**Video and social-media matches reply with just the link, not the raw
content.** If the best match is a video transcript or a pasted social
caption that has a `source_url`, the bot doesn't dump that text into the
chat — it replies with just the link (`"Here you go: <url>"`), since a
customer asking "do you have an installation video?" wants the video,
not a transcript pasted into WhatsApp. Document and website matches
still return the actual matched text (that content usually *is* the
answer), with the link appended after it if there is one.

**Documents reply with a link to the whole file by default, not an
extracted snippet.** For something like a wiring diagram or spec sheet,
the file itself is the answer a customer wants — not a pasted-in text
excerpt. So when you upload a document, the original file is saved and
served back at `/faq/files/{id}/{filename}`, and by default the bot
replies with just that link (`"Here you go: <url>"`) instead of the
matched text. If a document is more like a manual FAQ where the actual
extracted text should be the answer, pass `send_as_link=false` on upload
(a form field on `/faq/upload/document`, a checkbox in `test_ui.html`) —
then it behaves like a normal FAQ match: matched text as the reply, with
the file link appended after it. Video and social-link text sources keep
defaulting to link-only, same as before; this same override is also
available on `/faq/upload/text` if you want a pasted-caption source to
answer with its full text instead of just its link.

**Matching uses stemming, and the file/page name counts too.** A query
for "wire diagram" now matches content indexed under "wiring-diagram.pdf"
— related word forms (wiring/wire, connecting/connect, etc.) are treated
as the same term, and each source's name is factored into what it
matches on, not just its body text. This matters most for PDFs that are
mostly a schematic/image with little extractable text — the filename
picking up the slack is often the difference between a match and a miss.
If a PDF is entirely a scanned image with zero extractable text, no
amount of matching logic fixes that — the ingestion endpoint will
outright reject it (`400: Extracted text is empty`), which is your
signal that OCR would be needed for that particular file.

**Q&A-formatted documents get split one chunk per question.** If a
document has lines like `Q1. What is X?` followed by `Answer: ...`
(the common FAQ layout — including section headers like "Fleet
Management" in between question blocks), each question becomes its own
chunk instead of getting merged with 4-5 neighboring answers into one
fixed-size block. This matters a lot: a query about one specific question
used to compete against everything else crammed into the same chunk,
which both diluted real matches and let the bot occasionally return a
correct-looking but wrong answer copied from an unrelated question
elsewhere in the same block, just because they happened to land in the
same 800-character window. Non-Q&A documents (a plain policy paragraph,
a wiring diagram) still use the original fixed-size chunking.

**The bot won't confidently answer questions the knowledge base doesn't
actually cover**, even when the question shares a word with your
content. A query like "Who is the CEO?" sharing only your company's name
with a chunk isn't enough — search now checks what fraction of the
question's meaningful words appear anywhere in your uploaded content at
all, and downweights matches where most of the question is unrecognized
vocabulary. Without this, TF-IDF silently ignores words it's never seen
rather than treating their absence as a sign the question isn't
answered, so a two-word question could score fine on the strength of
just one shared word while the other (unanswerable) half of the question
was invisible to the scoring. This also fixed a related inconsistency:
`/bot/reply` used to apply a stricter, second threshold on top of the
one `/faq/search` uses, so a match visible in `test_ui.html`'s search
panel could still silently fail to make it into an actual bot reply.
Both now use exactly one threshold, so what you see in the search panel
is what the bot will actually use.

### Honest limits — read before promising these to anyone

- **Documents** (pdf/docx/pptx/xlsx/txt/md/csv): fully supported.
- **Websites**: works for normal server-rendered pages. Sites with bot
  protection (Cloudflare, WordPress security plugins — this can include
  your own site) may return 403 and block the fetch entirely; pages that
  build their content with JavaScript after load won't extract cleanly
  either. If a URL fails, paste the page's text via `/faq/upload/text`
  instead.
- **Video**: YouTube only, and only if the video has captions
  (auto-generated captions are fine, but a video with captions disabled
  can't be transcribed here). Other platforms aren't supported — this
  would need real speech-to-text (e.g. Whisper), which is a much heavier
  addition than this endpoint.
- **Social media** (Instagram/X/Facebook/TikTok posts): there's no
  dedicated endpoint for these on purpose. These platforms are mostly
  JavaScript-rendered and/or login-walled, so a plain scraper won't
  reliably get the content — and building one to bypass that is the kind
  of thing that gets accounts/IPs blocked. The practical approach (what
  real teams actually do for this): copy the caption/post text yourself
  and send it to `/faq/upload/text`.

If your Java developer needs to build an admin screen for uploading FAQ
content, the endpoints above are the whole contract — `/faq/upload/*` to
add, `/faq/sources` to list, `/faq/sources/{id}` (DELETE) to remove.

## 7. Extending the bot

All the reply content lives in `bot_engine.py`:
- `PRODUCTS` dict at the top — edit prices/links there when they change.
- `_INTENTS` list — add a new `(intent_name, matcher, handler)` tuple to
  handle a new topic, or add keywords to an existing matcher.
- If you later want open-ended (non-scripted) answers instead of fixed
  replies, replace the body of `generate_reply()` with a call to an LLM
  API — keep the same function signature so `main.py` doesn't need to
  change.
