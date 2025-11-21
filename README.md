# User Info Collector - WordPress Plugin

A secure, professional WordPress plugin to collect user information via a front-end form with complete admin management capabilities.

## Features

✅ **Secure Form Submission**
- CSRF protection using WordPress nonces
- Input sanitization and validation
- XSS protection with proper output escaping

✅ **User-Friendly Form**
- Clean, responsive design
- Field validation with user-friendly error messages
- Form data persistence on validation errors
- Success messages after submission

✅ **Admin Management**
- View all submissions in a table
- Delete submissions
- Settings page for email notifications
- Custom admin menu with dashicons

✅ **Email Notifications**
- Automatic email notifications to admin
- Customizable recipient email address
- Enable/disable notifications via settings
- Detailed submission information in emails

✅ **N8N Webhook Integration**
- Send form data to N8N automation platform in real-time
- Connect to 400+ apps (Google Sheets, Slack, CRM, etc.)
- Secure webhook delivery with optional API authentication
- Built-in test function to verify connectivity
- Automatic retry logic and error logging

✅ **Modern WordPress Standards**
- Compatible with WordPress 6.x and PHP 7.4+
- Uses Custom Post Type for data storage
- Follows WordPress coding standards
- Internationalization ready (i18n)
- Object-oriented architecture

## Installation

### Method 1: Manual Installation

1. **Download or clone this repository**

2. **Copy the plugin folder** to your WordPress plugins directory:
   ```
   wp-content/plugins/user-info-collector/
   ```

3. **Verify the file structure** looks like this:
   ```
   wp-content/plugins/user-info-collector/
   ├── user-info-collector.php
   ├── README.md
   ├── includes/
   │   ├── class-uic-activator.php
   │   ├── class-uic-cpt.php
   │   ├── class-uic-shortcode.php
   │   ├── class-uic-admin.php
   │   └── class-uic-email.php
   └── assets/
       └── css/
           └── uic-styles.css
   ```

4. **Activate the plugin**
   - Go to WordPress Admin → Plugins
   - Find "User Info Collector"
   - Click "Activate"

### Method 2: Upload via WordPress Admin

1. **Zip the plugin folder**
   - Compress the `user-info-collector` folder into `user-info-collector.zip`

2. **Upload via WordPress**
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Choose the zip file
   - Click "Install Now"
   - Click "Activate Plugin"

## Usage

### Display the Form

Add the shortcode to any page or post:

```
[user_info_form]
```

**Example:**
1. Go to Pages → Add New (or edit an existing page)
2. Add the shortcode `[user_info_form]` in the content editor
3. Publish or update the page
4. View the page on the front-end to see the form

### Form Fields

The form includes the following fields:

- **Full Name** (required) - Single-line text input
- **Telephone** (required) - Phone number with basic validation
- **Email** (required) - Email with validation
- **Description** (optional) - Multi-line textarea

### View Submissions

1. Go to **WordPress Admin → User Info**
2. Click **All Submissions** to see a table of all form submissions
3. Each submission shows:
   - ID
   - Full Name
   - Telephone
   - Email
   - Description (truncated)
   - Date submitted
   - Actions (Delete)

### Configure Email Notifications

1. Go to **WordPress Admin → User Info → Settings**
2. Enable/disable email notifications using the checkbox
3. Set a custom notification email (optional - defaults to admin email)
4. Click "Save Settings"

### Email Notification Format

When enabled, you'll receive emails like this:

```
Subject: [Your Site Name] New User Info Form Submission

A new user info form submission has been received:

Full Name: John Doe
Telephone: +1 (555) 123-4567
Email: john@example.com
Description:
I would like more information about your services.

Submitted on: January 19, 2025 10:30 AM

View submission in admin:
https://yoursite.com/wp-admin/admin.php?page=uic-submissions
```

### Configure N8N Webhook Integration

Send form submissions to N8N automation platform in real-time!

#### What is N8N?

N8N is a powerful workflow automation tool that lets you connect your WordPress form to hundreds of apps and services like:
- Google Sheets
- Slack notifications
- CRM systems (Salesforce, HubSpot)
- Email marketing (Mailchimp, SendGrid)
- Databases (PostgreSQL, MySQL, MongoDB)
- And 400+ more integrations

#### Setup Steps

**1. Create N8N Webhook**

1. Log in to your N8N instance (Cloud or Self-hosted)
2. Create a new workflow
3. Add a **Webhook** trigger node
4. Select **POST** method
5. Copy the webhook URL (e.g., `https://your-n8n.app/webhook/abc123xyz`)

**2. Configure WordPress Plugin**

1. Go to **WordPress Admin → User Info → Settings**
2. Scroll to **N8N Webhook Integration** section
3. Check **"Enable N8N Webhook"**
4. Paste your N8N webhook URL
5. (Optional) Add API key if your N8N webhook requires authentication
6. Click **"Save Settings"**

**3. Test the Connection**

1. After saving, click **"Test N8N Webhook"** button
2. Check your N8N workflow - you should see the test payload arrive
3. If successful, you'll see: "Webhook test successful! (HTTP 200)"

**4. Process Form Data in N8N**

The webhook sends data in JSON format:

```json
{
  "meta": {
    "source": "WordPress - User Info Collector",
    "plugin_version": "1.0.0",
    "site_url": "https://yoursite.com",
    "site_name": "Your Site Name",
    "timestamp": "2025-01-19 10:30:00",
    "post_id": 123
  },
  "data": {
    "full_name": "John Doe",
    "telephone": "+1 (555) 123-4567",
    "email": "john@example.com",
    "description": "Sample form submission"
  },
  "event": {
    "type": "form_submission",
    "id": "uic_64f3b2a1c9e5d"
  }
}
```

#### Example N8N Workflows

**Example 1: Save to Google Sheets**
1. Webhook trigger (receives form data)
2. Google Sheets node (adds row with name, email, phone)

**Example 2: Send Slack Notification**
1. Webhook trigger
2. Slack node (sends message: "New form: {{ $json.data.full_name }}")

**Example 3: Add to CRM**
1. Webhook trigger
2. HubSpot/Salesforce node (creates new contact)

#### Troubleshooting

**Webhook Not Receiving Data?**
- Check the webhook URL is correct
- Verify N8N workflow is active (not paused)
- Check WordPress error logs: `wp-content/debug.log`
- Test the webhook using the "Test N8N Webhook" button

**Authentication Errors?**
- If your N8N instance requires authentication, add the API key in settings
- The plugin sends it as: `Authorization: Bearer YOUR_KEY`

**Timeout Issues?**
- Default timeout is 30 seconds
- For self-hosted N8N, ensure it's accessible from your WordPress server
- Check firewalls and network settings

#### Webhook Metadata

Each submission stores webhook status:
- Webhook status (sent/failed)
- Sent timestamp
- HTTP response code
- Error messages (if failed)

View this in the submission details or check post meta:
- `_uic_webhook_status`
- `_uic_webhook_sent_at`
- `_uic_webhook_response_code`

## Security Features

This plugin implements WordPress security best practices:

- ✅ **CSRF Protection**: Nonces on all form submissions
- ✅ **Input Sanitization**: All inputs sanitized with WordPress functions
- ✅ **Output Escaping**: All output escaped (`esc_html`, `esc_attr`, `esc_url`)
- ✅ **Email Validation**: Uses WordPress `is_email()` function
- ✅ **Phone Validation**: Regex pattern for safe formats
- ✅ **SQL Injection Prevention**: Uses WordPress post/meta API
- ✅ **Capability Checks**: Admin pages require `manage_options`
- ✅ **Nonce Verification**: Delete actions require nonce verification

## Technical Details

### Data Storage

This plugin uses **Custom Post Type** (`uic_submission`) to store form submissions.

**Why Custom Post Type?**
- Native WordPress integration
- No custom database tables to manage
- Automatic admin UI integration
- Easy to export/import
- Future extensibility

**Meta Fields:**
- `_uic_full_name` - Full name
- `_uic_telephone` - Phone number
- `_uic_email` - Email address
- `_uic_description` - Description text
- `_uic_submission_date` - Submission timestamp

### File Structure

- **user-info-collector.php** - Main plugin file with bootstrap
- **includes/class-uic-activator.php** - Activation hooks
- **includes/class-uic-cpt.php** - Custom Post Type registration & data handling
- **includes/class-uic-shortcode.php** - Shortcode rendering & form processing
- **includes/class-uic-admin.php** - Admin pages (submissions list & settings)
- **includes/class-uic-email.php** - Email notification handler
- **assets/css/uic-styles.css** - Front-end form styles

### Hooks & Filters

The plugin is designed to be extensible. All classes use WordPress hooks and can be extended.

## Customization

### Styling the Form

The form uses these CSS classes that you can override in your theme:

```css
.uic-form-wrapper      /* Form container */
.uic-form              /* Form element */
.uic-form-field        /* Field wrapper */
.uic-label             /* Field labels */
.uic-input             /* Text inputs */
.uic-textarea          /* Textarea */
.uic-submit-button     /* Submit button */
.uic-success-message   /* Success message */
.uic-error-message     /* Error messages */
.uic-field-error       /* Individual field errors */
```

### Validation Rules

Current validation includes:
- **Full Name**: Required, non-empty
- **Telephone**: Required, matches pattern `/^[0-9\s\+\(\)\-]+$/`
- **Email**: Required, valid email format
- **Description**: Optional, no validation

## Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher (standard WordPress requirements)

## Support & Development

### Testing the Plugin

1. Add shortcode to a test page
2. Try submitting with empty fields → Verify error messages
3. Try invalid email → Verify email validation
4. Try invalid phone → Verify phone validation
5. Submit valid data → Verify success message
6. Check admin area → Verify submission saved
7. Check email → Verify notification sent

### Troubleshooting

**Form doesn't display:**
- Check if shortcode is correctly typed: `[user_info_form]`
- Ensure plugin is activated

**Emails not sending:**
- Check Settings → Enable email notifications
- Verify WordPress can send emails (use a plugin like WP Mail SMTP if needed)
- Check spam folder

**Submissions not saving:**
- Check browser console for JavaScript errors
- Verify database permissions
- Check WordPress debug log

## License

This plugin is licensed under GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Front-end form with shortcode
- Custom Post Type for data storage
- Admin submissions management
- Email notifications
- Settings page
- Full security implementation
- Responsive design

## Credits

Developed following WordPress Plugin Development Best Practices and Security Guidelines.

---

**Need help?** Check the WordPress Codex or contact your WordPress administrator.
