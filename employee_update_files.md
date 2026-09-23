# Employee Functionality - Live Server Update Guide

Here is the complete list of files that were modified or created to implement the employee login, data filtering, and role-based UI restrictions. You will need to upload these files to your live server to apply the changes.

## 1. Controllers
* **`app/Http/Controllers/EmployeeController.php`**
  *(Added logic to sync employee creation, updates, and deletions with the `users` table so they can log in.)*
* **`app/Http/Controllers/ConversationController.php`**
  *(Updated to filter conversations so employees only see their assigned ones, and fixed the assign logic.)*
* **`app/Http/Controllers/ContactController.php`**
  *(Updated to filter contacts so employees only see contacts related to their assigned conversations.)*
* **`app/Http/Controllers/TagController.php`**
  *(Updated to filter tagged contacts so employees only see their assigned contacts on the tag details page.)*

## 2. Models
* **`app/Models/Conversation.php`**
  *(Updated the `assignedUser` relationship to link to the `Employee` model instead of the default User model.)*

## 3. Routes
* **`routes/web.php`**
  *(Updated the dashboard route logic to accurately count and display stats based on the logged-in employee's assigned conversations/contacts.)*

## 4. Views (UI Restrictions & Updates)
* **`resources/views/layout/partials/sidebar.blade.php`**
  *(Hid the "WhatsApp Bot Settings" menu item for employees.)*
* **`resources/views/employees/index.blade.php`**
  *(Hid the "Add Employee" button and the Action column (Edit/Delete) for employees.)*
* **`resources/views/conversations/index.blade.php`**
  *(Updated the filters and list display to work seamlessly with the `Employee` relationship.)*
* **`resources/views/conversations/show.blade.php`**
  *(Hid the "Assign" button, "Edit Contact" button, and "Add Tag" button for employees. Also updated user display names.)*
* **`resources/views/contacts/show.blade.php`**
  *(Hid the "Edit Contact", "Add Tag", and "Remove Tag" buttons for employees.)*
* **`resources/views/tags/index.blade.php`**
  *(Hid the "Create Tag" button for employees.)*
* **`resources/views/tags/show.blade.php`**
  *(Hid the "Edit Tag" and "Delete Tag" buttons for employees.)*

---

> [!IMPORTANT]
> **Data Migration Note:** 
> Because we linked the existing `employees` to the Laravel `users` table for authentication, you will need to run the data sync script (or manually insert the employees into the `users` table with their hashed passwords) on your live server just once so existing employees can log in.
