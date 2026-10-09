with open(r"C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py", "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace("return cfg = _load_or_404(token).__dict__", "return _load_or_404(token).__dict__")
content = content.replace("cfg = cfg = _load_or_404(token)", "cfg = _load_or_404(token)")
content = content.replace("forget_company_faq_store(cfg.company_id)", "forget_faq_store(token)")

with open(r"C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py", "w", encoding="utf-8") as f:
    f.write(content)
