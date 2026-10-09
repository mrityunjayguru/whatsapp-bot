import os
import re
from pathlib import Path

bot_dir = Path("C:/Users/HP/Downloads/whatsapp-bot-api/whatsapp-bot-api")

def patch_file(filename, replacements):
    path = bot_dir / filename
    if not path.exists():
        print(f"Not found: {filename}")
        return
    text = path.read_text(encoding="utf-8")
    for search, replace in replacements:
        text = text.replace(search, replace)
    path.write_text(text, encoding="utf-8")
    print(f"Patched {filename}")

# 1. Update schemas.py
patch_file("schemas.py", [
    (
        "class WidgetConfigModel(BaseModel):",
        "class WidgetConfigModel(BaseModel):\n    company_id: Optional[str] = \"\""
    ),
    (
        "class WidgetConfigUpdateRequest(BaseModel):",
        "class WidgetConfigUpdateRequest(BaseModel):\n    company_id: Optional[str] = None"
    ),
    (
        "class WhatsAppNumberConfigModel(BaseModel):",
        "class WhatsAppNumberConfigModel(BaseModel):\n    company_id: Optional[str] = \"\""
    ),
    (
        "class WhatsAppNumberConfigUpdateRequest(BaseModel):",
        "class WhatsAppNumberConfigUpdateRequest(BaseModel):\n    company_id: Optional[str] = None"
    )
])

# 2. Update widget_config.py
patch_file("widget_config.py", [
    (
        "    site_name: str",
        "    company_id: str = \"\"\n    site_name: str"
    )
])

# 3. Update whatsapp_config.py
patch_file("whatsapp_config.py", [
    (
        "    display_number: str = \"\"",
        "    company_id: str = \"\"\n    display_number: str = \"\""
    )
])

# 4. Create company_store.py
company_store_py = """
from typing import Dict
from pathlib import Path
from faq_store import FAQStore

COMPANIES_DIR = Path(__file__).parent / "companies"
COMPANIES_DIR.mkdir(exist_ok=True)

_company_faq_stores: Dict[str, FAQStore] = {}

def get_company_faq_store(company_id: str) -> FAQStore:
    if not company_id or not str(company_id).strip():
        company_id = "default"
    company_id = str(company_id).strip()
    
    if company_id not in _company_faq_stores:
        d = COMPANIES_DIR / company_id
        d.mkdir(parents=True, exist_ok=True)
        _company_faq_stores[company_id] = FAQStore(path=d / "faq_data.json")
    return _company_faq_stores[company_id]
"""
(bot_dir / "company_store.py").write_text(company_store_py, encoding="utf-8")
print("Created company_store.py")

# 5. Update main.py to handle /company/{id}/faq/...
main_py_additions = """
# --- Company FAQ Routes ---
from company_store import get_company_faq_store

@app.post("/company/{company_id}/faq/upload/text", response_model=FAQSourceResponse, dependencies=[Depends(require_api_key)])
def company_faq_upload_text(company_id: str, payload: FAQTextRequest):
    store = get_company_faq_store(company_id)
    try:
        send_as_link = payload.send_as_link if payload.send_as_link is not None else bool(payload.source_url)
        source = store.add_source(
            name=payload.name,
            source_type="text",
            text=payload.text,
            source_url=payload.source_url,
            send_as_link=send_as_link,
            keywords=payload.keywords,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))
    return source

@app.post("/company/{company_id}/faq/upload/document", response_model=FAQSourceResponse, dependencies=[Depends(require_api_key)])
async def company_faq_upload_document(
    company_id: str,
    request: Request,
    file: UploadFile = File(...),
    send_as_link: bool = Form(True),
):
    store = get_company_faq_store(company_id)
    content = await file.read()
    try:
        text = extractors.extract_from_document(file.filename, content)
        source = store.add_source(name=file.filename, source_type="document", text=text, send_as_link=send_as_link)
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))
    return source

@app.post("/company/{company_id}/faq/files/attach", dependencies=[Depends(require_api_key)])
async def company_faq_attach_file(company_id: str, request: Request, file: UploadFile = File(...)):
    import uuid
    content = await file.read()
    if not content:
        raise HTTPException(status_code=400, detail="Empty file")
    
    faq_files_dir = Path(__file__).parent / "companies" / str(company_id) / "faq_files"
    faq_files_dir.mkdir(parents=True, exist_ok=True)
    file_id = str(uuid.uuid4())[:8]
    safe_name = "".join(c for c in file.filename if c.isalnum() or c in "._-") or "file"
    (faq_files_dir / f"{file_id}_{safe_name}").write_bytes(content)
    
    file_url = f"{_public_base_url(request)}/company/{company_id}/faq/files/{file_id}/{quote(safe_name)}"
    return {"id": file_id, "url": file_url}

@app.delete("/company/{company_id}/faq/sources/{source_id}", dependencies=[Depends(require_api_key)])
def company_faq_delete_source(company_id: str, source_id: str):
    deleted = get_company_faq_store(company_id).delete_source(source_id)
    if not deleted:
        raise HTTPException(status_code=404, detail=f"No source with id {source_id}")
    return {"deleted": source_id}
"""
patch_file("main.py", [
    (
        "if __name__ == \"__main__\":",
        main_py_additions + "\n\nif __name__ == \"__main__\":"
    )
])

# 6. Update widget_routes.py to use company FAQ store
patch_file("widget_routes.py", [
    (
        "from widget_store import forget as forget_stores, get_bot_config_store, get_faq_store",
        "from widget_store import forget as forget_stores, get_bot_config_store\nfrom company_store import get_company_faq_store"
    ),
    (
        "store=get_faq_store(token)",
        "store=get_company_faq_store(cfg.company_id)"
    )
])

# 7. Update whatsapp_routes.py to use company FAQ store
patch_file("whatsapp_routes.py", [
    (
        "from whatsapp_store import forget as forget_stores, get_bot_config_store, get_faq_store",
        "from whatsapp_store import forget as forget_stores, get_bot_config_store\nfrom company_store import get_company_faq_store"
    ),
    (
        "store=get_faq_store(phone_number_id)",
        "store=get_company_faq_store(cfg.company_id)"
    )
])

print("Python patch complete!")
