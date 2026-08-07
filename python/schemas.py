"""
Request/response models for the TRP WhatsApp Bot Reply API.

Kept in a separate file so the Java developer (or anyone else) can see the
exact contract at a glance, independent of the matching logic in bot_engine.py.
"""

from typing import List, Optional
from pydantic import BaseModel, Field


class HistoryItem(BaseModel):
    """One prior message, optionally sent for context. Not required."""
    direction: str = Field(..., description="'Inbound' (from customer) or 'Outbound' (from bot/agent)")
    text: str = Field(..., description="Message text")


class ReplyRequest(BaseModel):
    """What the Java service sends for every inbound WhatsApp message."""

    phone_number: str = Field(
        ..., description="Customer's WhatsApp number / wa_id, e.g. '9198XXXXXXXX'"
    )
    message: str = Field(
        ..., description="The inbound message text from the customer"
    )
    profile_name: Optional[str] = Field(
        None, description="Customer's WhatsApp profile name, if known"
    )
    conversation_id: Optional[str] = Field(
        None, description="Java-side conversation/message id, echoed back for log correlation only"
    )
    history: Optional[List[HistoryItem]] = Field(
        default=None,
        description="Optional recent messages for context, oldest first. Safe to omit.",
    )

    class Config:
        json_schema_extra = {
            "example": {
                "phone_number": "919876543210",
                "message": "What is the price of Sentinel?",
                "profile_name": "Rushil",
                "conversation_id": "1042",
            }
        }


class ReplyResponse(BaseModel):
    """What this API sends back. The Java service sends `reply` to WhatsApp."""

    reply: str = Field(..., description="Text to send back to the customer over WhatsApp")
    intent: str = Field(..., description="Detected intent, e.g. 'pricing', 'support', 'fallback'")
    should_handoff_to_human: bool = Field(
        False,
        description="If true, a human agent should take over this conversation (bot reply can still be sent first)",
    )

    class Config:
        json_schema_extra = {
            "example": {
                "reply": "Sentinel (wired GPS tracker) is available at a special price of Rs. 1,999 (MRP Rs. 5,999). More details: https://trpgps.com/product/trackroutepro-sentinel/",
                "intent": "pricing",
                "should_handoff_to_human": False,
            }
        }


# ---------------------------------------------------------------------------
# FAQ knowledge base
# ---------------------------------------------------------------------------

class FAQUrlRequest(BaseModel):
    url: str = Field(..., description="Website URL to fetch and index")
    name: Optional[str] = Field(None, description="Friendly name for this source; defaults to the URL")


class FAQVideoRequest(BaseModel):
    url: str = Field(..., description="YouTube video URL to transcribe and index")
    name: Optional[str] = Field(None, description="Friendly name for this source; defaults to the URL")


class FAQTextRequest(BaseModel):
    text: str = Field(..., description="Raw text to index, e.g. a pasted social media caption or manual FAQ entry")
    name: str = Field(..., description="Friendly name for this source, e.g. 'Instagram post - return policy'")
    source_url: Optional[str] = Field(
        None, description="Link back to the original post/page, e.g. the Instagram/Facebook/TikTok post URL. Included in the bot's reply when this source answers a question."
    )
    send_as_link: Optional[bool] = Field(
        None,
        description="If true, the bot replies with just source_url instead of the text. Defaults to true only when source_url is set, false otherwise.",
    )


class FAQSourceResponse(BaseModel):
    id: str
    name: str
    type: str
    added_at: str
    num_chunks: int
    source_url: Optional[str] = None
    send_as_link: bool = False


class FAQMatch(BaseModel):
    text: str = Field(..., description="The matching chunk of indexed text")
    source_name: str
    source_type: str
    source_id: str
    source_url: Optional[str] = Field(None, description="Link back to the original video/page/post/file, if one exists")
    send_as_link: bool = Field(False, description="If true, the bot's reply is just source_url rather than the text")
    score: float = Field(..., description="Cosine similarity score, 0-1 (higher = more relevant)")


class FAQSearchRequest(BaseModel):
    query: str = Field(..., description="The customer's question")
    top_k: int = Field(3, description="Max number of matching chunks to return")


class FAQSearchResponse(BaseModel):
    matches: List[FAQMatch]
    answer: Optional[str] = Field(
        None, description="Best-match answer text if a confident match was found, else null"
    )
