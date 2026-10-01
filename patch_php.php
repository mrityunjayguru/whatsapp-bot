<?php
$file = 'c:/xampp/htdocs/chatbot/resources/views/conversations/show.blade.php';
$content = file_get_contents($file);

$search1 = "                                        return preg_replace_callback(\$pattern, function (\$m) {\n" .
           "                                            \$mdUrl = isset(\$m[2]) && \$m[2] !== '' ? \$m[2] : null;\n" .
           "                                            \$bareUrl = isset(\$m[3]) && \$m[3] !== '' ? \$m[3] : null;\n" .
           "                                            \$href = \$mdUrl ?? \$bareUrl;\n" .
           "                                            \$label = \$mdUrl !== null && isset(\$m[1]) && \$m[1] !== '' ? \$m[1] : \$href;\n" .
           "                                            return '<a href=\"' . \$href . '\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color: inherit; text-decoration: underline;\">' . \$label . '</a>';\n" .
           "                                        }, \$text);";

$replace1 = "                                        \$replaced = preg_replace_callback(\$pattern, function (\$m) {\n" .
            "                                            \$mdUrl = isset(\$m[2]) && \$m[2] !== '' ? \$m[2] : null;\n" .
            "                                            \$bareUrl = isset(\$m[3]) && \$m[3] !== '' ? \$m[3] : null;\n" .
            "                                            \$href = \$mdUrl ?? \$bareUrl;\n" .
            "                                            \$label = \$mdUrl !== null && isset(\$m[1]) && \$m[1] !== '' ? \$m[1] : \$href;\n" .
            "                                            return '<a href=\"' . \$href . '\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color: inherit; text-decoration: underline;\">' . \$label . '</a>';\n" .
            "                                        }, \$text);\n" .
            "                                        return nl2br(\$replaced);";

$content = str_replace($search1, $replace1, $content);

$search2 = "                                    return preg_replace_callback(\$pattern, function (\$m) {\n" .
           "                                        \$mdUrl = isset(\$m[2]) && \$m[2] !== '' ? \$m[2] : null;\n" .
           "                                        \$bareUrl = isset(\$m[3]) && \$m[3] !== '' ? \$m[3] : null;\n" .
           "                                        \$href = \$mdUrl ?? \$bareUrl;\n" .
           "                                        \$label = \$mdUrl !== null && isset(\$m[1]) && \$m[1] !== '' ? \$m[1] : \$href;\n" .
           "                                        return '<a href=\"' . \$href . '\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color: inherit; text-decoration: underline;\">' . \$label . '</a>';\n" .
           "                                    }, \$escaped);";

$replace2 = "                                    \$replaced = preg_replace_callback(\$pattern, function (\$m) {\n" .
            "                                        \$mdUrl = isset(\$m[2]) && \$m[2] !== '' ? \$m[2] : null;\n" .
            "                                        \$bareUrl = isset(\$m[3]) && \$m[3] !== '' ? \$m[3] : null;\n" .
            "                                        \$href = \$mdUrl ?? \$bareUrl;\n" .
            "                                        \$label = \$mdUrl !== null && isset(\$m[1]) && \$m[1] !== '' ? \$m[1] : \$href;\n" .
            "                                        return '<a href=\"' . \$href . '\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color: inherit; text-decoration: underline;\">' . \$label . '</a>';\n" .
            "                                    }, \$escaped);\n" .
            "                                    return nl2br(\$replaced);";

$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Done";
