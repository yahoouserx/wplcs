# WPLCS - WordPress License Control System

## Overview

The WordPress License Control System (WPLCS) is a comprehensive WordPress plugin designed to manage software licensing, site activations, and secure downloads. It integrates with WooCommerce to automate license generation after purchase and provides a complete license management ecosystem for both administrators and end users.

## System Architecture

### Frontend Architecture
- **Admin Interface**: jQuery-based dashboard with modal dialogs for license management
- **User Dashboard**: Shortcode-based interface (`[wplcs_account_dashboard]`) integrated into WooCommerce My Account page
- **Responsive Design**: CSS Grid layout with mobile-first approach for admin panels

### Backend Architecture
- **Plugin Structure**: Object-oriented PHP following PSR-4 autoloading standards
- **WordPress Integration**: Hooks into WordPress core, WooCommerce, and REST API
- **Data Layer**: Custom database tables for license management with WordPress user integration
- **Security Layer**: WordPress nonces, capability checks, and token-based authentication

### Database Schema
- **License Plans**: Stores plan configurations (name, price, max sites, duration, features)
- **User Licenses**: Links users to license tokens with activation limits and expiration
- **Site Activations**: Tracks domain activations per license
- **Activity Logs**: Records all license-related activities for audit trails

## Key Components

### 1. License Management System
- **Problem**: Need to control software distribution and site usage
- **Solution**: Token-based licensing with domain validation and activation limits
- **Benefits**: Prevents unauthorized usage while providing flexibility for legitimate users

### 2. WooCommerce Integration
- **Problem**: Manual license generation after purchases
- **Solution**: Automated license token generation on order completion
- **Benefits**: Seamless customer experience and reduced administrative overhead

### 3. REST API Layer
- **Problem**: External applications need license validation
- **Solution**: Secure REST endpoints for activation, validation, and resource fetching
- **Benefits**: Enables third-party integrations and mobile applications

### 4. Auto-update Server
- **Problem**: Plugin updates for licensed users
- **Solution**: Custom update server with license verification
- **Benefits**: Ensures only licensed users receive updates

### 5. Security Framework
- **Problem**: Unauthorized access to resources and license manipulation
- **Solution**: Multi-layer security with nonces, capability checks, and signed URLs
- **Benefits**: Protects intellectual property and user data

## Data Flow

1. **Purchase Flow**: Customer purchases → WooCommerce order completion → License token generation → Email notification
2. **Activation Flow**: User activates license → Domain validation → Database update → API response
3. **Validation Flow**: Plugin checks license → API call → Domain/token verification → Access granted/denied
4. **Download Flow**: User requests download → License validation → Signed URL generation → Secure file delivery

## External Dependencies

### WordPress Ecosystem
- **WordPress Core**: 5.0+ required for REST API and modern features
- **WooCommerce**: 3.0+ for e-commerce integration
- **jQuery**: For admin interface interactions

### PHP Requirements
- **PHP Version**: 7.4+ for modern syntax and security
- **Extensions**: cURL for API calls, JSON for data handling

## Deployment Strategy

### Plugin Structure
- **Main Plugin File**: `wplcs.php` - Plugin header and initialization
- **Assets Directory**: CSS and JavaScript files for admin interface
- **Classes Directory**: PSR-4 autoloaded PHP classes
- **Documentation**: User guides and API documentation

### Database Setup
- **Installation Hook**: Creates custom tables on plugin activation
- **Migration System**: Handles database schema updates
- **Cleanup**: Removes data on uninstall (optional)

### Security Considerations
- **File Permissions**: Restrict direct access to PHP files
- **Input Validation**: Sanitize all user inputs
- **Output Escaping**: Prevent XSS attacks
- **SQL Injection Prevention**: Use WordPress prepared statements

## Changelog

```
Changelog:
- July 07, 2025. Initial setup
```

## User Preferences

```
Preferred communication style: Simple, everyday language.
```

## Development Notes

### Architecture Decisions
- **Chosen**: Object-oriented design with PSR-4 autoloading
- **Rationale**: Maintainable, testable, and follows WordPress coding standards
- **Alternatives**: Procedural approach (rejected for scalability reasons)

### Integration Strategy
- **Chosen**: Deep WooCommerce integration with custom license products
- **Rationale**: Leverages existing e-commerce infrastructure
- **Benefits**: Familiar checkout process for customers

### API Design
- **Chosen**: WordPress REST API with custom endpoints
- **Rationale**: Native WordPress integration and standardized approach
- **Security**: Token-based authentication with rate limiting

This system provides a complete license management solution that balances security, usability, and integration with the WordPress ecosystem.