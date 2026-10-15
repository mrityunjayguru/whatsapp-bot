import os

bot_engine_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\bot_engine.py'
with open(bot_engine_path, 'r', encoding='utf-8') as f:
    be_content = f.read()

# 1. Update support in bot_engine.py
old_support = """    (
        "support",
        lambda t, cfg: _word_in(
            t, "issue", "problem", "not working", "broken", "complaint",
            "error", "not connecting", "no signal", "battery", "faulty",
        ),
        _handle_support,
    ),"""

new_support = """    (
        "support",
        lambda t, cfg: _word_in(
            t, "support", "help", "helpdesk", "issue", "problem", "not working",
            "broken", "complaint", "error", "not connecting", "no signal",
            "battery", "faulty", "assistance", "ticket", "trouble",
        ),
        _handle_support,
    ),"""

if old_support in be_content:
    be_content = be_content.replace(old_support, new_support)
    with open(bot_engine_path, 'w', encoding='utf-8') as f:
        f.write(be_content)
    print("bot_engine.py support intent patched successfully!")
else:
    print("old_support not found in bot_engine.py")

# Also check demo matcher in bot_engine.py
old_demo = """    (
        "demo",
        lambda t, cfg: _word_in(t, "demo", "trial"),
        _handle_demo,
    ),"""

new_demo = """    (
        "demo",
        lambda t, cfg: _word_in(t, "demo", "trial", "book demo", "book a demo", "schedule demo", "request demo", "need demo"),
        _handle_demo,
    ),"""

if old_demo in be_content:
    be_content = be_content.replace(old_demo, new_demo)
    with open(bot_engine_path, 'w', encoding='utf-8') as f:
        f.write(be_content)
    print("bot_engine.py demo intent patched successfully!")
else:
    print("old_demo not found in bot_engine.py")
