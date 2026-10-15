import os

widget_engine_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_engine.py'
with open(widget_engine_path, 'r', encoding='utf-8') as f:
    we_content = f.read()

# 1. Update imports
old_import = "from bot_engine import _format_faq_answer, _dedupe_by_source, _FAQ_AMBIGUITY_RATIO, _render"
new_import = "from bot_engine import _format_faq_answer, _dedupe_by_source, _FAQ_AMBIGUITY_RATIO, _render, _word_in"

if old_import in we_content:
    we_content = we_content.replace(old_import, new_import)
    print("Imports updated in widget_engine.py")
else:
    print("old_import not found or already updated")

# 2. Add intent keyword definitions and matching
old_human_block = '''# Phrases that mean "I want to talk to a person," checked BEFORE FAQ
# search - a visitor's clear intent to reach a human should always win
# over a coincidental keyword match in the knowledge base (e.g. an FAQ
# that happens to contain the word "support"). Deliberately simple
# substring matching, not a full intent classifier - good enough for
# catching the phrasings people actually type, and easy to extend.
_HUMAN_REQUEST_PHRASES = (
    "human support", "human agent", "talk to a human", "talk to human",
    "speak to a human", "speak to human", "real person", "live agent",
    "live support", "talk to someone", "speak to someone",
    "talk to an agent", "speak to an agent", "customer support",
    "representative", "human please", "connect me to",
)


def _wants_human(text: str) -> bool:
    lowered = text.lower()
    return any(phrase in lowered for phrase in _HUMAN_REQUEST_PHRASES)'''

new_intent_block = '''# Phrases and keyword sets for canned replies / intents checked BEFORE FAQ search.
_HUMAN_REQUEST_WORDS = (
    "agent", "human", "representative", "executive", "manager",
    "talk to someone", "talk to human", "live agent", "real person",
    "speak to a human", "speak to human", "live support", "human please",
    "talk to an agent", "speak to an agent", "connect me to",
)

_SUPPORT_WORDS = (
    "support", "help", "helpdesk", "issue", "problem", "not working",
    "broken", "complaint", "error", "not connecting", "no signal",
    "battery", "faulty", "assistance", "ticket", "trouble",
)

_DEMO_WORDS = (
    "demo", "trial", "book demo", "book a demo", "schedule demo",
    "request demo", "need demo",
)

_ORDER_WORDS = (
    "order", "tracking id", "shipment", "delivery", "delivered",
    "dispatch", "courier", "order status", "track order",
)

_THANKS_WORDS = (
    "thanks", "thank you", "thankyou", "thnx", "thx",
)

_GREETING_WORDS = (
    "hi", "hello", "hey", "hii", "namaste", "namaskar",
    "good morning", "good evening", "good afternoon",
)

def _match_canned_intent(text: str) -> Optional[tuple[str, bool]]:
    \"\"\"Checks if `text` matches one of the canned replies templates.
    Returns (intent_key, should_escalate) or None.
    \"\"\"
    if _word_in(text, *_HUMAN_REQUEST_WORDS):
        return ("human_handoff", True)
    if _word_in(text, *_SUPPORT_WORDS):
        return ("support", True)
    if _word_in(text, *_DEMO_WORDS):
        return ("demo", False)
    if _word_in(text, *_ORDER_WORDS):
        return ("order_status", True)
    if _word_in(text, *_THANKS_WORDS):
        return ("thanks", False)
    if _word_in(text, *_GREETING_WORDS):
        return ("greeting", False)
    return None'''

if old_human_block in we_content:
    we_content = we_content.replace(old_human_block, new_intent_block)
    print("Intent definitions added to widget_engine.py")
else:
    print("old_human_block not found in widget_engine.py")

# 3. Update generate_widget_reply check
old_check = '''    if not text:
        return {"reply": "", "escalated": False, "options": None, "intent": None}

    if _wants_human(text):
        return {
            "reply": _company_template(cfg.company_id, "human_handoff"),
            "escalated": True,
            "options": None,
            "intent": "human_handoff",
        }'''

new_check = '''    if not text:
        return {"reply": "", "escalated": False, "options": None, "intent": None}

    canned_match = _match_canned_intent(text)
    if canned_match:
        intent_key, should_escalate = canned_match
        return {
            "reply": _company_template(cfg.company_id, intent_key),
            "escalated": should_escalate,
            "options": None,
            "intent": intent_key,
        }'''

if old_check in we_content:
    we_content = we_content.replace(old_check, new_check)
    print("generate_widget_reply updated in widget_engine.py")
else:
    print("old_check not found in widget_engine.py")

with open(widget_engine_path, 'w', encoding='utf-8') as f:
    f.write(we_content)
print("Finished patching widget_engine.py")
