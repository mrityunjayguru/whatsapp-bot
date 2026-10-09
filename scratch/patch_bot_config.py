import os
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

# 1. Update company_store.py
patch_file("company_store.py", [
    (
        "from faq_store import FAQStore",
        "from faq_store import FAQStore\nfrom bot_config import BotConfigStore"
    ),
    (
        "_company_faq_stores: Dict[str, FAQStore] = {}",
        "_company_faq_stores: Dict[str, FAQStore] = {}\n_company_bot_config_stores: Dict[str, BotConfigStore] = {}"
    ),
    (
        "    return _company_faq_stores[company_id]",
        "    return _company_faq_stores[company_id]\n\ndef get_company_bot_config_store(company_id: str) -> BotConfigStore:\n    if not company_id or not str(company_id).strip():\n        company_id = 'default'\n    company_id = str(company_id).strip()\n    \n    if company_id not in _company_bot_config_stores:\n        d = COMPANIES_DIR / company_id\n        d.mkdir(parents=True, exist_ok=True)\n        _company_bot_config_stores[company_id] = BotConfigStore(path=d / 'bot_config.json')\n    return _company_bot_config_stores[company_id]"
    )
])

# 2. Add /company/{company_id}/bot/config routes to main.py
main_bot_config_routes = """
from company_store import get_company_bot_config_store

@app.get("/company/{company_id}/bot/config", response_model=BotConfigModel, dependencies=[Depends(require_api_key)])
def company_get_bot_config(company_id: str):
    return get_company_bot_config_store(company_id).get_dict()

@app.put("/company/{company_id}/bot/config", response_model=BotConfigModel, dependencies=[Depends(require_api_key)])
def company_update_bot_config(company_id: str, payload: BotConfigModel):
    store = get_company_bot_config_store(company_id)
    store.update(payload.model_dump())
    return store.get_dict()

@app.post("/company/{company_id}/bot/config/reset", response_model=BotConfigModel, dependencies=[Depends(require_api_key)])
def company_reset_bot_config(company_id: str):
    store = get_company_bot_config_store(company_id)
    store.reset()
    return store.get_dict()
"""
patch_file("main.py", [
    (
        "# --- Company FAQ Routes ---",
        main_bot_config_routes + "\n\n# --- Company FAQ Routes ---"
    )
])

# 3. Update widget_routes.py to use company bot config
patch_file("widget_routes.py", [
    (
        "from company_store import get_company_faq_store",
        "from company_store import get_company_faq_store, get_company_bot_config_store"
    ),
    (
        "cfg_store=get_bot_config_store(token)",
        "cfg_store=get_company_bot_config_store(cfg.company_id)"
    )
])

# 4. Update whatsapp_routes.py to use company bot config
patch_file("whatsapp_routes.py", [
    (
        "from company_store import get_company_faq_store",
        "from company_store import get_company_faq_store, get_company_bot_config_store"
    ),
    (
        "cfg_store=get_bot_config_store(phone_number_id)",
        "cfg_store=get_company_bot_config_store(cfg.company_id)"
    )
])

print("Python bot config patch complete!")
