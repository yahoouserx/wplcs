# WPLCS - WordPress License Control System

A comprehensive WordPress plugin for token-based allocation, access permission enforcement, session-aware transfer operations, and distributed package availability management.

## 🎯 Overview

WPLCS provides a secure, scalable solution for managing digital product licenses, user access control, and resource distribution through WordPress and WooCommerce integration.

### Key Features

- **Token-Based Authentication**: Secure access tokens linked to WooCommerce transactions
- **Session Management**: Real-time session control with IP and domain validation
- **Resource Distribution**: Secure file and data delivery with encrypted URLs
- **Tier-Based Access**: Flexible permission levels with customizable features
- **Rate Limiting**: Advanced API throttling and security controls
- **User Dashboard**: Comprehensive self-service portal for token management
- **Admin Interface**: Full administrative control with analytics and logging
- **REST API**: Complete API for external node integration

## 🏗️ System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   WordPress     │    │   WooCommerce   │    │   External      │
│   Core System   │◄──►│   Integration   │◄──►│   Nodes         │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Database      │    │   Token         │    │   Session       │
│   Management    │◄──►│   System        │◄──►│   Control       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Security      │    │   Resource      │    │   Analytics     │
│   Layer         │◄──►│   Provider      │◄──►│   & Logging     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 📋 Requirements

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **WooCommerce**: 5.0+
- **MySQL**: 5.7+ or MariaDB 10.2+
- **SSL Certificate**: Required for secure API communication

## 🚀 Installation

1. **Download and Install:**
   ```bash
   # Clone or download the plugin
   git clone https://github.com/yahoouserx/wplcs.git
   
   # Upload to WordPress plugins directory
   wp-content/plugins/wplcs/
   ```

2. **Activate Plugin:**
   - Navigate to WordPress Admin > Plugins
   - Find "WPLCS - WordPress License Control System"
   - Click "Activate"

3. **Database Setup:**
   - Plugin automatically creates required tables on activation
   - Default tiers are created automatically

4. **Configure Settings:**
   - Go to WPLCS > Settings
   - Configure rate limits, session timeouts, and security options

## 🔧 Configuration

### Basic Setup

1. **WooCommerce Product Configuration:**
   - Edit any product
   - Scroll to "WPLCS Settings" section
   - Enable WPLCS for the product
   - Select appropriate tier
   - Save product

2. **Tier Management:**
   - Navigate to WPLCS > Tiers
   - Create or modify tiers as needed
   - Set node limits, duration, and features

3. **Resource Management:**
   - Go to WPLCS > Resources
   - Upload files or configure data/URL resources
   - Assign resources to appropriate tiers

### Advanced Configuration

```php
// Custom constants in wp-config.php
define('WPLCS_SESSION_TIMEOUT', 7200); // 2 hours
define('WPLCS_MAX_API_REQUESTS_PER_HOUR', 2000);
define('WPLCS_TOKEN_LENGTH', 128); // Longer tokens
```

## 📚 Database Schema

### Core Tables

- `wp_wplcs_tokens`: Token storage and management
- `wp_wplcs_sessions`: Active session tracking
- `wp_wplcs_tiers`: Access tier definitions
- `wp_wplcs_logs`: System activity logging
- `wp_wplcs_rate_limits`: API rate limiting data

### Table Relationships

```sql
tokens (1) → (n) sessions
tokens (n) → (1) tiers
tokens (n) → (1) users
sessions (n) → (1) tokens
logs (n) → (1) tokens
logs (n) → (1) sessions
```

## 🔌 API Reference

### Base URL
```
https://yoursite.com/wp-json/wplcs/v1/
```

### Authentication
All requests require a valid token:
```
?token=your_access_token_here
```

### Core Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/status` | GET | Check token status |
| `/session` | POST | Create session |
| `/session` | DELETE | Remove session |
| `/heartbeat` | POST | Update session activity |
| `/resource` | GET | List available resources |
| `/serve` | GET | Download resource |

### Example Usage

```bash
# Check token status
curl -X GET "https://yoursite.com/wp-json/wplcs/v1/status?token=YOUR_TOKEN"

# Create session
curl -X POST "https://yoursite.com/wp-json/wplcs/v1/session" \
  -H "Content-Type: application/json" \
  -d '{"token":"YOUR_TOKEN","domain":"node.example.com"}'

# Download resource
curl -X GET "https://yoursite.com/wp-json/wplcs/v1/serve?token=YOUR_TOKEN&resource=RESOURCE_ID"
```

## 🖥️ User Interface

### Customer Dashboard

Accessible via WooCommerce My Account > WPLCS Console:

- **Token Overview**: Active tokens with status and expiration
- **Session Management**: View and remove active sessions
- **Usage Statistics**: API calls, node usage, activity history
- **Tier Information**: Current access level and features

### Admin Interface

Complete administrative control via WordPress Admin > WPLCS:

- **Dashboard**: System overview and statistics
- **Tokens**: Search, view, and manage all tokens
- **Sessions**: Monitor active sessions across all users
- **Tiers**: Create and configure access levels
- **Resources**: Upload and assign digital assets
- **Logs**: Detailed activity monitoring
- **Settings**: System configuration options

## 🔒 Security Features

### Token Security
- SHA-256 hashed storage
- Cryptographically secure generation
- Time-based expiration
- Usage tracking and limits

### Session Security
- IP address validation
- Domain verification
- Automatic timeout
- Heartbeat monitoring

### API Security
- Rate limiting per IP/token
- Request signing
- CORS protection
- User agent validation

### File Security
- Signed download URLs
- Time-limited access
- Path traversal protection
- Secure file storage

## 📊 Monitoring & Analytics

### System Metrics
- Token creation and usage rates
- Session activity patterns
- Resource download statistics
- API performance metrics

### User Analytics
- Individual usage tracking
- Session duration analysis
- Resource access patterns
- Geographic distribution

### Security Monitoring
- Failed authentication attempts
- Suspicious activity detection
- Rate limit violations
- Unauthorized access attempts

## 🔧 Development

### PSR-4 Autoloading
```php
// Namespace structure
WPLCS\
├── Core\
│   ├── Database
│   ├── Tokens
│   ├── Sessions
│   └── Security
├── Includes\
│   └── WooCommerce_Integration
├── Endpoints\
│   └── API
├── UI\
│   └── Dashboard
└── Admin\
    └── Admin_Panel
```

### Hooks and Filters

```php
// Available actions
do_action('wplcs_loaded');
do_action('wplcs_activated');
do_action('wplcs_token_created', $token_id, $user_id);
do_action('wplcs_session_created', $session_id, $token_id);

// Available filters
apply_filters('wplcs_token_expiry', $expiry, $tier_id);
apply_filters('wplcs_resource_access', $has_access, $resource_id, $token_data);
apply_filters('wplcs_rate_limit', $limit, $identifier);
```

### Custom Extensions

```php
// Example: Custom tier validation
add_filter('wplcs_validate_tier_access', function($access, $tier_id, $user_id) {
    // Custom validation logic
    if ($tier_id === 'premium' && !user_has_premium_status($user_id)) {
        return false;
    }
    return $access;
}, 10, 3);
```

## 📖 Documentation

- [Integration Client Guide](integration-client.md) - For external node developers
- [Resource Provider Guide](resource-provider.md) - For server administrators
- [API Documentation](https://yoursite.com/wp-json/wplcs/v1/) - Interactive API docs

## 🧪 Testing

### Unit Tests
```bash
# Run PHPUnit tests
composer test

# Run specific test suite
composer test -- --testsuite=Core
```

### Integration Tests
```bash
# Test API endpoints
composer test:api

# Test WooCommerce integration
composer test:woocommerce
```

## 🐛 Troubleshooting

### Common Issues

1. **Database Tables Not Created**
   - Deactivate and reactivate plugin
   - Check MySQL user permissions
   - Verify WordPress database constants

2. **API Not Responding**
   - Check permalink structure
   - Verify SSL certificate
   - Test without caching plugins

3. **Token Generation Fails**
   - Verify WooCommerce is active
   - Check product WPLCS settings
   - Review order completion hooks

4. **Session Creation Errors**
   - Validate domain format
   - Check IP restrictions
   - Verify token is active

### Debug Mode

Enable debug logging:
```php
// wp-config.php
define('WPLCS_DEBUG', true);
```

View logs in WPLCS > Logs or check the debug.log file.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Follow WordPress coding standards
4. Add tests for new functionality
5. Submit a pull request

### Code Standards
- PSR-4 autoloading
- WordPress coding standards
- PHPDoc documentation
- Unit test coverage

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Documentation**: Check the guides in this repository
- **Issues**: Report bugs via GitHub Issues
- **Community**: WordPress.org plugin forums
- **Commercial**: Contact for enterprise support

## 🚧 Roadmap

### Version 1.1
- [ ] Multi-site support
- [ ] Advanced analytics dashboard
- [ ] Webhook integrations
- [ ] Mobile app API

### Version 1.2
- [ ] Geographic restrictions
- [ ] Advanced caching layer
- [ ] Third-party integrations
- [ ] Custom notification system

### Version 2.0
- [ ] Microservices architecture
- [ ] GraphQL API
- [ ] Real-time notifications
- [ ] Advanced AI analytics

## 📊 Statistics

- **Lines of Code**: ~5,000
- **Files**: 25+
- **Database Tables**: 5
- **API Endpoints**: 10+
- **Admin Pages**: 7
- **Supported Languages**: Extensible via WordPress i18n

---

**Made with ❤️ for the WordPress community**