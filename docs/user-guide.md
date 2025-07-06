# WPLCS User Guide

Welcome to the WordPress License Control System (WPLCS) - a comprehensive solution for managing software licenses, site activations, and user access control through WordPress and WooCommerce.

## Table of Contents

1. [Installation](#installation)
2. [Initial Setup](#initial-setup)
3. [Creating License Plans](#creating-license-plans)
4. [WooCommerce Integration](#woocommerce-integration)
5. [User Experience](#user-experience)
6. [Admin Management](#admin-management)
7. [API Documentation](#api-documentation)
8. [Shortcodes](#shortcodes)
9. [Troubleshooting](#troubleshooting)
10. [FAQ](#faq)

## Installation

### Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- WooCommerce plugin (for e-commerce functionality)
- MySQL 5.7 or higher

### Step-by-Step Installation

1. **Download and Upload**
   - Download the WPLCS plugin files
   - Upload the `wplcs` folder to `/wp-content/plugins/`
   - Ensure all files have proper permissions

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "WordPress License Control System"
   - Click "Activate"

3. **Database Setup**
   - The plugin automatically creates necessary database tables on activation
   - Check for any activation errors in the WordPress admin

4. **Verify Installation**
   - Navigate to WordPress Admin → WPLCS → Dashboard
   - You should see the main WPLCS dashboard

## Initial Setup

### 1. Configure Basic Settings

Navigate to **WPLCS → Dashboard** and review the system status:

- ✅ Database tables created
- ✅ API endpoints active
- ✅ WooCommerce integration ready

### 2. Create Your First License Plan

Go to **WPLCS → Plans** and click "Add New Plan":

**Basic Plan Example:**
- **Name:** "Single Site License"
- **Price:** $29.00
- **Max Sites:** 1
- **Duration:** 365 days
- **Trial:** No
- **Active:** Yes

**Pro Plan Example:**
- **Name:** "Multi-Site License"
- **Price:** $99.00
- **Max Sites:** 5
- **Duration:** 365 days
- **Trial:** No
- **Active:** Yes

**Developer Plan Example:**
- **Name:** "Unlimited License"
- **Price:** $199.00
- **Max Sites:** -1 (unlimited)
- **Duration:** 365 days
- **Trial:** No
- **Active:** Yes

## Creating License Plans

### Plan Configuration Options

#### Basic Information
- **Plan Name:** User-friendly name displayed to customers
- **Description:** Optional detailed description
- **Price:** Cost in your store's currency (0 for free plans)
- **Sort Order:** Display priority (lower numbers first)

#### License Limits
- **Maximum Sites:** Number of sites allowed (-1 for unlimited)
- **Duration:** License validity period in days
- **Trial Plan:** Mark as trial (useful for free trials)
- **Active Status:** Enable/disable plan availability

#### Advanced Features
- **Features JSON:** Custom feature flags for API validation
- **Renewal Rules:** Automatic renewal settings
- **Upgrade Paths:** Define available upgrade options

### Plan Management

#### Activating/Deactivating Plans
- Use the toggle button on each plan card
- Deactivated plans remain for existing customers but aren't available for new purchases

#### Editing Plans
- Click "Edit" on any plan card
- Changes apply to new licenses only
- Existing licenses retain their original settings

#### Deleting Plans
- Only plans without active licenses can be deleted
- System prevents accidental deletion of plans with customers

## WooCommerce Integration

### Creating Products for License Plans

1. **Create WooCommerce Product**
   - Go to Products → Add New
   - Choose "Simple Product" type
   - Set product details (name, description, price)

2. **Attach License Plan**
   - In WPLCS → Plans, edit your desired plan
   - Under "WooCommerce Products," select your product
   - Save the plan

3. **Product Configuration**
   - The product price should match the plan price
   - Set as "Virtual" product (no shipping required)
   - Configure any additional WooCommerce settings

### Automatic License Generation

When a customer completes a purchase:

1. **Order Processing**
   - WooCommerce processes the payment
   - WPLCS detects the completed order
   - License token is automatically generated

2. **Customer Notification**
   - License details added to order confirmation email
   - Customer receives license key and activation instructions
   - Account page updated with new license

3. **License Activation**
   - Customer can immediately use the license key
   - No manual intervention required

## User Experience

### Customer License Dashboard

Customers can manage their licenses through:

#### My Account Page (WooCommerce)
- Navigate to My Account → Licenses
- View all purchased licenses
- See site activations and status
- Manage site connections

#### Shortcode Dashboard
Add `[wplcs_account_dashboard]` to any page for a dedicated license management area.

**Shortcode Options:**
```
[wplcs_account_dashboard columns="2" show_trials="yes" show_expired="yes" show_upgrades="yes"]
```

### License Activation Process

1. **Receive License Key**
   - Customer gets license key via email after purchase
   - Key format: `wplcs_xxxxxxxxxxxxxxxxxx`

2. **Install Licensed Software**
   - Customer downloads and installs your software
   - Software should include WPLCS client integration

3. **Activate License**
   - Enter license key in software
   - Software contacts your WPLCS API
   - Activation confirmed if valid and within limits

4. **Site Management**
   - View connected sites in dashboard
   - Deactivate sites when no longer needed
   - Transfer licenses between sites

### License Actions

#### Regenerate License Key
- Creates new license key
- Invalidates old key immediately
- Useful if key is compromised

#### Deactivate Site
- Removes site from license
- Frees up activation slot
- Site can be reactivated later

#### Renew License
- Extends license duration
- Redirects to WooCommerce checkout
- Automatic renewal options available

#### Upgrade Plan
- Move to higher tier plan
- Price difference charged
- Immediate access to new features

## Admin Management

### Dashboard Overview

The main dashboard provides:

- **System Status:** API health, database status
- **Quick Stats:** Total licenses, active sessions, revenue
- **Recent Activity:** Latest activations and deactivations
- **Performance Metrics:** Usage trends and analytics

### Session Management

Monitor and control site sessions:

#### Session Information
- **Domain:** Connected website URL
- **IP Address:** Server location
- **Last Activity:** Recent API calls
- **Status:** Active, inactive, or expired
- **User:** License owner details

#### Session Actions
- **View Details:** Complete session information
- **Deactivate:** Force disconnect site
- **Block IP:** Prevent future connections
- **Reset User:** Clear all user sessions

### User Management

#### User Overview
View all users with licenses:

- **User Information:** Name, email, registration date
- **License Summary:** Active/total licenses, tiers used
- **Activity:** Last seen, current sessions
- **Actions:** View details, reset sessions, edit user

#### Individual User Details
- **Complete License History:** All tokens issued
- **Session Timeline:** Site connection history
- **Purchase History:** WooCommerce order integration
- **Support Actions:** Quick admin tools

### License Tiers Management

#### Plan Configuration
- **Visual Plan Cards:** Easy-to-scan plan overview
- **Bulk Actions:** Activate/deactivate multiple plans
- **WooCommerce Sync:** Product attachment management
- **Usage Analytics:** Plan popularity metrics

#### Plan Templates
Create common plan types quickly:

- **Starter Plan:** Single site, 1 year
- **Business Plan:** 5 sites, 1 year
- **Agency Plan:** Unlimited sites, 1 year
- **Trial Plan:** 1 site, 30 days

### Logging and Analytics

#### System Logs
- **API Requests:** All license validation calls
- **Error Tracking:** Failed activations and reasons
- **Security Events:** Suspicious activity detection
- **Performance Monitoring:** Response times and load

#### Business Intelligence
- **Revenue Tracking:** License sales analytics
- **Usage Patterns:** Site activation trends
- **Customer Insights:** User behavior analysis
- **Renewal Forecasting:** Subscription predictions

## API Documentation

### Authentication

All API requests require a valid license token:

```
Authorization: Bearer wplcs_your_license_token_here
```

### Core Endpoints

#### License Validation
```
POST /wp-json/wplcs/v1/status
{
    "domain": "example.com",
    "ip": "192.168.1.1"
}
```

#### Session Management
```
POST /wp-json/wplcs/v1/session
{
    "action": "create",
    "domain": "example.com",
    "ip": "192.168.1.1"
}
```

#### Heartbeat
```
POST /wp-json/wplcs/v1/heartbeat
{
    "session_id": "session_uuid"
}
```

#### Resource Access
```
GET /wp-json/wplcs/v1/resource/{resource_id}
```

### Response Formats

#### Success Response
```json
{
    "success": true,
    "data": {
        "message": "Operation completed",
        "session_id": "uuid",
        "expires_at": "2024-12-31 23:59:59"
    }
}
```

#### Error Response
```json
{
    "success": false,
    "error": {
        "code": "INVALID_LICENSE",
        "message": "License key not found or expired"
    }
}
```

### Rate Limiting

- **Default Limit:** 100 requests per hour per IP
- **Burst Allowance:** 10 requests per minute
- **Headers:** Rate limit info in response headers
- **Upgrades:** Higher limits for premium plans

## Shortcodes

### License Dashboard Shortcode

Display customer license management interface:

```
[wplcs_account_dashboard]
```

#### Parameters

- `columns` - Grid columns (1-4, default: 2)
- `show_trials` - Display trial licenses (yes/no, default: yes)
- `show_expired` - Show expired licenses (yes/no, default: yes)
- `show_upgrades` - Display upgrade options (yes/no, default: yes)

#### Examples

```
[wplcs_account_dashboard columns="1" show_expired="no"]
```

```
[wplcs_account_dashboard columns="3" show_upgrades="no"]
```

### License Status Widget

Simple license status display:

```
[wplcs_license_status]
```

### Quick Activation Form

Inline license activation:

```
[wplcs_activation_form]
```

## Troubleshooting

### Common Issues

#### 1. License Activation Fails

**Symptoms:**
- "License key not found" error
- "Maximum sites exceeded" message
- Connection timeout

**Solutions:**
1. Verify license key is correctly entered
2. Check if license has expired
3. Confirm site count hasn't exceeded limit
4. Test API connectivity
5. Review server logs for errors

#### 2. Session Expires Too Quickly

**Symptoms:**
- Frequent re-activation required
- "Session invalid" errors
- Inconsistent API responses

**Solutions:**
1. Check session duration settings
2. Verify heartbeat implementation
3. Review server timezone configuration
4. Confirm database connectivity
5. Check for conflicting plugins

#### 3. WooCommerce Integration Issues

**Symptoms:**
- Licenses not generated after purchase
- Wrong plan assigned to order
- Email notifications missing license info

**Solutions:**
1. Verify plan-product connections
2. Check WooCommerce order status
3. Review email template customizations
4. Test with simple product setup
5. Check user permissions

#### 4. API Performance Problems

**Symptoms:**
- Slow response times
- Timeout errors
- High server load

**Solutions:**
1. Enable object caching
2. Optimize database queries
3. Implement CDN for static assets
4. Review rate limiting settings
5. Scale server resources

### Debug Mode

Enable debug logging:

1. Add to wp-config.php:
```php
define('WPLCS_DEBUG', true);
```

2. View logs in:
- WordPress Admin → WPLCS → Logs
- Server error logs
- WordPress debug.log file

### Performance Optimization

#### Database Optimization
- Regular cleanup of expired sessions
- Index optimization for large datasets
- Query caching implementation
- Archive old log entries

#### Caching Strategy
- API response caching
- Database query caching
- Object caching for session data
- CDN for static resources

#### Server Configuration
- PHP memory limit: 256MB minimum
- MySQL max connections: 100+
- Web server keepalive settings
- SSL certificate for API security

## FAQ

### General Questions

**Q: Can I migrate from another license system?**
A: Yes, WPLCS provides import tools for common license formats. Contact support for migration assistance.

**Q: Does WPLCS work with membership plugins?**
A: Yes, WPLCS integrates with popular membership plugins through WooCommerce and custom hooks.

**Q: Can I customize the license dashboard appearance?**
A: Yes, use CSS customizations and template overrides to match your brand.

**Q: Is WPLCS compatible with multisite networks?**
A: Yes, WPLCS works with WordPress multisite installations.

### Licensing Questions

**Q: What happens when a license expires?**
A: Expired licenses stop working for new activations, but existing sessions may continue based on grace period settings.

**Q: Can customers transfer licenses between sites?**
A: Yes, customers can deactivate from one site and activate on another within their site limits.

**Q: How do I handle refunds?**
A: Deactivate the customer's license through WPLCS → Users, then process the refund in WooCommerce.

**Q: Can I offer lifetime licenses?**
A: Yes, set the duration to a very high number (e.g., 36500 days for 100 years).

### Technical Questions

**Q: How secure is the license validation?**
A: WPLCS uses SHA-256 hashing, rate limiting, IP validation, and secure API tokens.

**Q: Can I integrate with third-party services?**
A: Yes, WPLCS provides webhooks and action hooks for custom integrations.

**Q: Does WPLCS affect site performance?**
A: Minimal impact with proper caching. API calls are optimized and rate-limited.

**Q: Can I run WPLCS on shared hosting?**
A: Yes, but VPS or dedicated hosting recommended for high-volume usage.

### Billing Questions

**Q: How do I set up recurring billing?**
A: Use WooCommerce Subscriptions plugin with WPLCS plans for automatic renewals.

**Q: Can I offer free trials?**
A: Yes, create trial plans with limited duration and mark them as trial plans.

**Q: How do I handle pro-rated upgrades?**
A: WooCommerce handles pricing, WPLCS manages license tier changes automatically.

**Q: Can customers downgrade their plans?**
A: Admin can manually change license tiers, but automated downgrades require custom development.

### Support and Updates

**Q: How do I get support?**
A: Contact support through your purchase platform or the plugin documentation site.

**Q: How often is WPLCS updated?**
A: Regular updates include security patches, new features, and compatibility improvements.

**Q: Can I customize WPLCS for my specific needs?**
A: Yes, WPLCS is developer-friendly with extensive hooks and filters for customization.

**Q: Is there a staging/test environment?**
A: Yes, you can run WPLCS on staging sites using the same license system architecture.

---

## Support

For additional help:

- **Documentation:** Complete API docs and developer guides
- **Community Forum:** User discussions and shared solutions
- **Priority Support:** Available for premium customers
- **Custom Development:** Professional services for advanced integrations

Last updated: December 2024
Version: 1.0.0