# Admin Settings API Documentation

## Endpoints Overview

| Method   | Endpoint                    | Auth  | Description                                      |
|----------|-----------------------------|-------|--------------------------------------------------|
| GET/PUT  | /admin/settings/{category}  | Admin | Retrieve or update settings for a given category |

## Endpoint Details

### GET /admin/settings/{category}
Retrieves the settings for the specified category.

**Available Settings & Data Types:**

- **General**:
  - site_name: string  
    - Validation: required, maximum length 255  
    - Default: "My Website"
  - site_url: string (URL)  
    - Validation: required, must be a valid URL  
    - Default: "http://localhost"
  - contact_email: string (email)  
    - Validation: required, valid email format  
    - Default: "admin@example.com"

- **Security**:
  - password_min_length: integer  
    - Validation: required, minimum value 6  
    - Default: 8
  - enable_2fa: boolean  
    - Validation: required  
    - Default: false
  - session_timeout: integer (minutes)  
    - Validation: required, minimum value 10  
    - Default: 30

- **Notifications**:
  - notify_on_comment: boolean  
    - Validation: required  
    - Default: true
  - smtp_server: string  
    - Validation: required  
    - Default: null
  - smtp_port: integer  
    - Validation: required, valid port number  
    - Default: 587

**Permission Requirements:**
- Only administrators may access this endpoint.

### PUT /admin/settings/{category}
Updates the settings for the specified category.

**Payload Requirements:**
- Must include valid values for the settings as detailed above.
- All fields are validated based on their respective rules.

**Permission Requirements:**
- Only administrators are allowed to perform updates.

## Setting Categories & Their Purposes

| Category      | Purpose                                                           |
|---------------|-------------------------------------------------------------------|
| general       | Contains basic site configuration and informational settings.     |
| security      | Manages settings related to authentication, password policies, and session management. |
| notifications | Configures email and other notification-related settings.         |

*Note: Replace `{category}` with one of the available categories ("general", "security", "notifications") when making requests.*
