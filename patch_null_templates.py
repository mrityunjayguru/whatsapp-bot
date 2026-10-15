bot_config_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\bot_config.py'
with open(bot_config_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. In _load: filter out None or empty values from raw templates
old_load = '''        self._config = BotConfig(
            company_name=raw.get("company_name", self._config.company_name),
            support_link=raw.get("support_link", self._config.support_link),
            demo_link=raw.get("demo_link", self._config.demo_link),
            products_link=raw.get("products_link", self._config.products_link),
            products=products,
            templates={**DEFAULT_TEMPLATES, **raw.get("templates", {})},
        )'''

new_load = '''        clean_raw_templates = {k: v for k, v in raw.get("templates", {}).items() if v}
        self._config = BotConfig(
            company_name=raw.get("company_name", self._config.company_name),
            support_link=raw.get("support_link", self._config.support_link),
            demo_link=raw.get("demo_link", self._config.demo_link),
            products_link=raw.get("products_link", self._config.products_link),
            products=products,
            templates={**DEFAULT_TEMPLATES, **clean_raw_templates},
        )'''

if old_load in content:
    content = content.replace(old_load, new_load)
    print("_load patched in bot_config.py")
else:
    print("old_load not found")

# 2. In update: filter out None or empty values from data.get("templates", {})
old_update = '''            self._config = BotConfig(
                company_name=data.get("company_name", self._config.company_name),
                support_link=data.get("support_link", self._config.support_link),
                demo_link=data.get("demo_link", self._config.demo_link),
                products_link=data.get("products_link", self._config.products_link),
                products=products,
                templates={**self._config.templates, **data.get("templates", {})},
            )'''

new_update = '''            clean_incoming_templates = {k: v for k, v in data.get("templates", {}).items() if v}
            self._config = BotConfig(
                company_name=data.get("company_name", self._config.company_name),
                support_link=data.get("support_link", self._config.support_link),
                demo_link=data.get("demo_link", self._config.demo_link),
                products_link=data.get("products_link", self._config.products_link),
                products=products,
                templates={**DEFAULT_TEMPLATES, **self._config.templates, **clean_incoming_templates},
            )'''

if old_update in content:
    content = content.replace(old_update, new_update)
    print("update patched in bot_config.py")
else:
    print("old_update not found")

with open(bot_config_path, 'w', encoding='utf-8') as f:
    f.write(content)
print("Finished patching bot_config.py")
