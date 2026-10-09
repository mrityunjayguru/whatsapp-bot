with open(r"C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_engine.py", "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace("from widget_store import get_faq_store`nfrom company_store import get_company_faq_store", "from widget_store import get_faq_store\nfrom company_store import get_company_faq_store")

content = content.replace("    cfg = WidgetConfig.load(token)`n    store = get_company_faq_store(cfg.company_id)`n    match = store.get_by_source_id(source_id)`n    if match is None:`n        return None", "    cfg = WidgetConfig.load(token)\n    store = get_company_faq_store(cfg.company_id)\n    match = store.get_by_source_id(source_id)\n    if match is None:\n        return None")

with open(r"C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_engine.py", "w", encoding="utf-8") as f:
    f.write(content)
