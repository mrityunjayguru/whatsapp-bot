path = r"C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py"
with open(path, "r", encoding="utf-8") as f:
    text = f.read()

old_code = """        var linkRegex = /\\[([^\\[\\]]+)\\]\\((https?:\\/\\/[^\\s()]+)\\)|(https?:\\/\\/[^\\s]+)/g;
        var htmlText = text;
        if (who !== "bot") {
            // User messages: escape HTML to prevent XSS, then linkify
            htmlText = htmlText.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            htmlText = htmlText.replace(linkRegex, function(match, mdLabel, mdUrl, bareUrl) {
                var href = mdUrl || bareUrl;
                var label = mdLabel || bareUrl;
                return '<a href="' + href + '" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">' + label + '</a>';
            });
        } else {
            // Bot messages: already contain HTML from rich text editor.
            // Only linkify plain-text bot messages (no existing <a> tags).
            if (htmlText.indexOf('<a ') === -1 && htmlText.indexOf('<A ') === -1) {
                htmlText = htmlText.replace(linkRegex, function(match, mdLabel, mdUrl, bareUrl) {
                    var href = mdUrl || bareUrl;
                    var label = mdLabel || bareUrl;
                    return '<a href="' + href + '" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">' + label + '</a>';
                });
            }
        }"""

new_code = """        var mdRegex = /\\[([^\\[\\]]+)\\]\\((https?:\\/\\/[^\\s()]+)\\)/g;
        var bareRegex = /(https?:\\/\\/[^\\s<]+)/g;
        var htmlText = text;
        
        if (who !== "bot") {
            // User messages: escape HTML to prevent XSS, then linkify both
            htmlText = htmlText.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            htmlText = htmlText.replace(mdRegex, function(match, mdLabel, mdUrl) {
                return '<a href="' + mdUrl + '" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">' + mdLabel + '</a>';
            });
            htmlText = htmlText.replace(bareRegex, function(match, bareUrl) {
                return '<a href="' + bareUrl + '" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">' + bareUrl + '</a>';
            });
        } else {
            // Bot messages: already contain HTML from rich text editor.
            // ALWAYS linkify explicit markdown links (e.g. Attachments)
            htmlText = htmlText.replace(mdRegex, function(match, mdLabel, mdUrl) {
                return '<a href="' + mdUrl + '" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">' + mdLabel + '</a>';
            });
            // Only linkify plain bare URLs if no <a> tags already exist in the WHOLE message.
            if (htmlText.indexOf('<a ') === -1 && htmlText.indexOf('<A ') === -1) {
                htmlText = htmlText.replace(bareRegex, function(match, bareUrl) {
                    return '<a href="' + bareUrl + '" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">' + bareUrl + '</a>';
                });
            }
        }"""

text = text.replace(old_code, new_code)

with open(path, "w", encoding="utf-8") as f:
    f.write(text)
