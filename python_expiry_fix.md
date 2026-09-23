# Fix: Missing Expiry Date

The reason the date isn't showing up after you save is because the **Python backend doesn't know what to do with the new `expiry_date` field yet!** 

When Laravel sends the date to Python, the Python `WidgetConfig` model ignores it because it's an "unknown" field, so it doesn't get saved to the JSON file. 

To fix this, your Python developer needs to make these quick updates:

### 1. Update `schemas.py`
Add `expiry_date` to both the **Request** and **Response** models for the Widget config:

```python
class WidgetConfigModel(BaseModel):
    token: str
    site_name: str
    contact_email: str
    bot_name: str
    welcome_message: str
    primary_color: str
    button_position: str
    fallback_message: str
    human_handoff_message: Optional[str] = None
    is_active: bool
    # Add this line:
    expiry_date: Optional[str] = None

class WidgetConfigUpdateRequest(BaseModel):
    site_name: str
    contact_email: Optional[str] = None
    bot_name: str
    welcome_message: str
    primary_color: str
    button_position: str
    fallback_message: str
    human_handoff_message: Optional[str] = None
    is_active: bool
    # Add this line:
    expiry_date: Optional[str] = None
```

### 2. Update `widget_config.py`
Find your `WidgetConfig` class and make sure it has the `expiry_date` attribute and handles it during updates:

```python
class WidgetConfig:
    def __init__(self, token: str, site_name: str, contact_email: str = ""):
        # ... your existing fields ...
        self.is_active: bool = True
        
        # Add this line:
        self.expiry_date: Optional[str] = None

    def update(self, payload: 'WidgetConfigUpdateRequest'):
        # ... your existing updates ...
        self.is_active = payload.is_active
        
        # Add this line:
        self.expiry_date = payload.expiry_date
        
        self.save()
```

Once those 3 small lines are added to the Python code, the Python API will successfully store the date we send it, and the calendar field in Laravel will stay populated!
