import os
import re

files_to_patch = [
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\widgets_edit.blade.php',
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\edit.blade.php'
]

for fpath in files_to_patch:
    if os.path.exists(fpath):
        with open(fpath, 'r', encoding='utf-8') as f:
            content = f.read()

        # Add id to the config form
        content = content.replace(
            '<form action="{{ route(\'widgets.config.update\', $token) }}" method="POST">',
            '<form action="{{ route(\'widgets.config.update\', $token) }}" method="POST" id="configForm">'
        )
        
        # Add id and remove required from welcome_message
        content = content.replace(
            '<textarea class="form-control" name="welcome_message" rows="2" required>',
            '<textarea class="form-control" name="welcome_message" id="welcome_message" rows="4">'
        )
        # Handle if it was already modified or didn't have required somehow
        content = content.replace(
            '<textarea class="form-control" name="welcome_message" rows="2">',
            '<textarea class="form-control" name="welcome_message" id="welcome_message" rows="4">'
        )

        # Add id and remove required from fallback_message
        content = content.replace(
            '<textarea class="form-control" name="fallback_message" rows="2" required>',
            '<textarea class="form-control" name="fallback_message" id="fallback_message" rows="4">'
        )
        content = content.replace(
            '<textarea class="form-control" name="fallback_message" rows="2">',
            '<textarea class="form-control" name="fallback_message" id="fallback_message" rows="4">'
        )

        # Update TinyMCE init to apply to the new IDs
        tinymce_init = """        tinymce.init({
            selector: '#answer',"""
        
        tinymce_new_init = """        tinymce.init({
            selector: '#answer, #welcome_message, #fallback_message',"""
        
        content = content.replace(tinymce_init, tinymce_new_init)
        
        # Update TinyMCE form validation
        validation_script = """        // Sync TinyMCE content to textarea before form submit and validate
        var addBtn = document.getElementById('addFaqBtn');
        if (addBtn) {
            addBtn.closest('form').addEventListener('submit', function(e) {
                tinymce.triggerSave();
                var val = document.getElementById('answer').value.trim();
                if (!val || val === '<p></p>' || val === '<p><br></p>') {
                    e.preventDefault();
                    alert('Please enter an answer.');
                    tinymce.get('answer').focus();
                }
            });
        }"""
        
        new_validation_script = """        // Sync TinyMCE content to textarea before form submit and validate
        var addBtn = document.getElementById('addFaqBtn');
        if (addBtn) {
            addBtn.closest('form').addEventListener('submit', function(e) {
                tinymce.triggerSave();
                var val = document.getElementById('answer').value.trim();
                if (!val || val === '<p></p>' || val === '<p><br></p>') {
                    e.preventDefault();
                    alert('Please enter an answer.');
                    tinymce.get('answer').focus();
                }
            });
        }

        var configForm = document.getElementById('configForm');
        if (configForm) {
            configForm.addEventListener('submit', function(e) {
                tinymce.triggerSave();
                var welcome = document.getElementById('welcome_message').value.trim();
                if (!welcome || welcome === '<p></p>' || welcome === '<p><br></p>') {
                    e.preventDefault();
                    alert('Please enter a welcome message.');
                    tinymce.get('welcome_message').focus();
                    return;
                }
                var fallback = document.getElementById('fallback_message').value.trim();
                if (!fallback || fallback === '<p></p>' || fallback === '<p><br></p>') {
                    e.preventDefault();
                    alert('Please enter a fallback message.');
                    tinymce.get('fallback_message').focus();
                    return;
                }
            });
        }"""
        
        content = content.replace(validation_script, new_validation_script)

        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f'Patched {fpath}')
