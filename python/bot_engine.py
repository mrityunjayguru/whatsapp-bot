"""
Bot reply logic for Track Route Pro's WhatsApp bot (trpgps.com).

This is a rule-based / keyword-matching engine on purpose: it's fast,
predictable, needs no API key or model, and is easy for you to extend
line-by-line. If you later want open-ended answers (not just these fixed
intents), swap the body of `generate_reply()` to call an LLM instead, and
keep the same function signature / return shape so nothing else has to
change on the Java side.

Matching strategy: check intents in priority order (most specific / most
urgent first), first match wins. Uses whole-word regex matching so "hi"
doesn't match inside "history", etc.
"""

import re
from typing import List, Optional

from schemas import HistoryItem, ReplyResponse
from faq_store import store as faq_store

# ---------------------------------------------------------------------------
# Company / product facts used in canned replies.
# Edit these as pricing / links change - it's the only place you need to touch.
# ---------------------------------------------------------------------------

COMPANY_NAME = "Track Route Pro"
SUPPORT_LINK = "https://trpgps.com/contact-us/"
DEMO_LINK = "https://trpgps.com/book-a-demo/"
PRODUCTS_LINK = "https://trpgps.com/products/"

PRODUCTS = {
    "sentinel": {
        "name": "Sentinel",
        "type": "wired GPS tracker",
        "price": "Rs. 1,999 (MRP Rs. 5,999)",
        "link": "https://trpgps.com/product/trackroutepro-sentinel/",
    },
    "magtrack": {
        "name": "SecureLink MagTrack",
        "type": "wireless (magnetic) GPS tracker",
        "price": "Rs. 3,199 (MRP Rs. 9,999)",
        "link": "https://trpgps.com/product/securelink-magtrack/",
    },
}


def _word_in(text: str, *words: str) -> bool:
    """True if any of `words` appears in `text` as a whole word (case-insensitive)."""
    for w in words:
        if re.search(rf"\b{re.escape(w)}\b", text, flags=re.IGNORECASE):
            return True
    return False


# ---------------------------------------------------------------------------
# Individual intent handlers. Each takes the raw message + optional name and
# returns (reply_text, should_handoff_to_human).
# ---------------------------------------------------------------------------

def _handle_greeting(message: str, name: Optional[str]) -> tuple[str, bool]:
    hello = f"Hi {name}! " if name else "Hi there! "
    reply = (
        f"{hello}Welcome to {COMPANY_NAME} 👋\n\n"
        "I can help you with:\n"
        "1. Product info & pricing (Sentinel / SecureLink MagTrack)\n"
        "2. Booking a demo\n"
        "3. Order / tracking support\n"
        "4. Talking to a human agent\n\n"
        "Just tell me what you need!"
    )
    return reply, False


def _handle_pricing(message: str, name: Optional[str]) -> tuple[str, bool]:
    wants_sentinel = _word_in(message, "sentinel")
    wants_magtrack = _word_in(message, "magtrack", "maglock", "securelink", "mag track")

    if wants_sentinel and not wants_magtrack:
        p = PRODUCTS["sentinel"]
        reply = (
            f"{p['name']} is our {p['type']}, currently at {p['price']}.\n"
            f"Details: {p['link']}"
        )
        return reply, False

    if wants_magtrack and not wants_sentinel:
        p = PRODUCTS["magtrack"]
        reply = (
            f"{p['name']} is our {p['type']}, currently at {p['price']}.\n"
            f"Details: {p['link']}"
        )
        return reply, False

    # generic pricing / product question, or both mentioned
    s, m = PRODUCTS["sentinel"], PRODUCTS["magtrack"]
    reply = (
        "Here are our current GPS trackers:\n\n"
        f"• {s['name']} ({s['type']}) — {s['price']}\n  {s['link']}\n\n"
        f"• {m['name']} ({m['type']}) — {m['price']}\n  {m['link']}\n\n"
        f"Full range: {PRODUCTS_LINK}"
    )
    return reply, False


def _handle_demo(message: str, name: Optional[str]) -> tuple[str, bool]:
    reply = (
        "Sure! You can book a free demo of our GPS tracking system here:\n"
        f"{DEMO_LINK}\n\n"
        "Or let me know a good time and a human from our team will reach out."
    )
    return reply, False


def _handle_order_status(message: str, name: Optional[str]) -> tuple[str, bool]:
    reply = (
        "I can help check that. Could you share your order ID or the phone "
        "number used while ordering? I'll pass this to our support team so "
        "they can pull up your order status."
    )
    # order lookups need real order data the bot doesn't have -> flag for a human
    return reply, True


def _handle_support(message: str, name: Optional[str]) -> tuple[str, bool]:
    reply = (
        "Sorry to hear you're facing an issue. Our support team is here to "
        f"help (24x7): {SUPPORT_LINK}\n\n"
        "Could you briefly describe the problem (e.g. device not connecting, "
        "app not showing location, etc.)? I'm connecting you with our team."
    )
    return reply, True


def _handle_human_handoff(message: str, name: Optional[str]) -> tuple[str, bool]:
    reply = (
        "Of course - connecting you with a member of our team now. "
        "They'll continue this chat shortly."
    )
    return reply, True


def _handle_thanks(message: str, name: Optional[str]) -> tuple[str, bool]:
    reply = "You're welcome! Happy to help. Let us know if you need anything else 🙂"
    return reply, False


def _handle_fallback(message: str, name: Optional[str]) -> tuple[str, bool]:
    reply = (
        "Thanks for your message! I can help with product info & pricing, "
        "booking a demo, or order support. You can also reach our team "
        f"directly here: {SUPPORT_LINK}"
    )
    return reply, False


# ---------------------------------------------------------------------------
# Ordered intent table: (intent_name, keyword-matcher, handler)
# Checked top to bottom - put more specific / more urgent intents first.
# ---------------------------------------------------------------------------

_INTENTS = [
    (
        "human_handoff",
        lambda t: _word_in(t, "agent", "human", "representative", "executive", "manager", "talk to someone"),
        _handle_human_handoff,
    ),
    (
        "support",
        lambda t: _word_in(
            t, "issue", "problem", "not working", "broken", "complaint",
            "error", "not connecting", "no signal", "battery", "faulty",
        ),
        _handle_support,
    ),
    (
        "order_status",
        lambda t: _word_in(t, "order", "tracking id", "shipment", "delivery", "delivered", "dispatch", "courier"),
        _handle_order_status,
    ),
    (
        "demo",
        lambda t: _word_in(t, "demo", "trial"),
        _handle_demo,
    ),
    (
        "pricing",
        lambda t: _word_in(
            t, "price", "pricing", "cost", "sentinel", "magtrack", "securelink", "buy",
            "purchase", "product", "products", "how much", "rate", "discount",
        ),
        _handle_pricing,
    ),
    (
        "thanks",
        lambda t: _word_in(t, "thanks", "thank you", "thankyou", "thnx"),
        _handle_thanks,
    ),
    (
        "greeting",
        lambda t: _word_in(t, "hi", "hello", "hey", "hii", "namaste", "good morning", "good evening"),
        _handle_greeting,
    ),
]


def generate_reply(
    message: str,
    profile_name: Optional[str] = None,
    history: Optional[List[HistoryItem]] = None,
) -> ReplyResponse:
    """
    Main entry point. Returns a ReplyResponse for the given inbound message.

    `history` is accepted for future use (e.g. multi-turn flows) but the
    current rule set only looks at the latest message.
    """
    text = message.strip()

    for intent_name, matches, handler in _INTENTS:
        if matches(text):
            reply_text, handoff = handler(text, profile_name)
            return ReplyResponse(reply=reply_text, intent=intent_name, should_handoff_to_human=handoff)

    # No rule matched - check the uploaded FAQ knowledge base before
    # giving up with the generic fallback message. faq_store.search()
    # already filters by its own MIN_SCORE, so any non-empty result here
    # is already a legitimate match - no need for a second, stricter gate
    # on top of it (that used to reject real matches for short, common-word
    # questions like "What is TRPGPS?").
    # faq_results = faq_store.search(text, top_k=1)
    # if faq_results:
    #     match = faq_results[0]
    #     if match.get("send_as_link") and match.get("source_url"):
    #         # Documents, videos, and linked social posts are things the
    #         # customer wants as a whole file/page, not a pasted snippet -
    #         # send the link itself rather than dumping extracted text.
    #         reply = f"Here you go: {match['source_url']}"
    #     else:
    #         reply = match["text"]
    #         if match.get("source_url"):
    #             reply = f"{reply}\n\n{match['source_url']}"
    #     return ReplyResponse(reply=reply, intent="faq", should_handoff_to_human=False)

    # reply_text, handoff = _handle_fallback(text, profile_name)
    # return ReplyResponse(reply=reply_text, intent="fallback", should_handoff_to_human=handoff)

# No rule matched - check the uploaded FAQ knowledge base
    faq_results = faq_store.search(text, top_k=1)
    if faq_results:
        match = faq_results[0]
        raw_text = match["text"]

        lines = [line.strip() for line in raw_text.split("\n") if line.strip()]
        answer_lines = []

        for line in lines:
            # 1. Skip question lines starting with "Q1.", "Q2)", etc.
            if re.match(r"^Q\d+[.).\-:]", line, re.IGNORECASE):
                continue

            # 2. Skip category / section headers (short headers without punctuation)
            # This catches headers like "General", "Fleet Management", "Car GPS", etc.
            if re.match(r"^[A-Z][A-Za-z0-9 &/\-]{1,45}$", line) and not line.lower().startswith("answer"):
                # If there are already valid answer lines captured, treat this as content; 
                # otherwise skip it as a section header.
                if not answer_lines:
                    continue

            # 3. Strip "Answer:" or "Answer -" prefix if present
            cleaned_line = re.sub(r"^Answer\s*[:\-]\s*", "", line, flags=re.IGNORECASE)
            
            if cleaned_line:
                answer_lines.append(cleaned_line)

        # Fall back to raw_text if filtering resulted in an empty string
        reply = "\n".join(answer_lines).strip() if answer_lines else raw_text

        return ReplyResponse(reply=reply, intent="faq", should_handoff_to_human=False)