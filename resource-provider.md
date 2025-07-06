# WPLCS Resource Provider Guide

This guide explains how to configure and manage secured package delivery in the WPLCS (WordPress License Control System).

## Overview

The WPLCS resource provider system allows you to:
- Upload and manage digital resources (files, data, URLs)
- Assign resources to specific tiers
- Control access based on user permissions
- Generate secure, time-limited download URLs
- Monitor resource access and usage

## Resource Types

### 1. File Resources
Physical files stored on your server that can be downloaded by authorized users.

**Examples:**
- Software packages (ZIP, TAR)
- Documentation (PDF, DOC)
- Media files (MP4, MP3)
- Configuration files

### 2. Data Resources
JSON data or structured information provided directly through the API.

**Examples:**
- Configuration data
- API endpoints
- License keys
- Feature flags

### 3. URL Resources
External links or redirect URLs provided to authorized users.

**Examples:**
- Third-party download links
- Documentation sites
- Video streaming URLs
- External APIs

## Resource Management

### Adding Resources

1. **Via Admin Panel:**
   - Navigate to WPLCS > Resources
   - Click "Upload New Resource"
   - Fill in resource details
   - Select applicable tiers
   - Upload file (if applicable)

2. **Via API (for developers):**
```php
// Add a file resource
$resource_data = array(
    'id' => 'premium_package_v1',
    'name' => 'Premium Package v1.0',
    'type' => 'file',
    'path' => '/path/to/premium-package.zip',
    'description' => 'Advanced features package',
    'version' => '1.0.0',
    'size' => 1048576,
    'mime_type' => 'application/zip'
);

$all_resources = get_option('wplcs_resources', array());
$all_resources[$resource_data['id']] = $resource_data;
update_option('wplcs_resources', $all_resources);

// Assign to tiers
$tier_resources = get_option('wplcs_tier_resources_2', array()); // Tier ID 2
$tier_resources[] = array(
    'id' => 'premium_package_v1',
    'type' => 'file',
    'access_level' => 'full'
);
update_option('wplcs_tier_resources_2', $tier_resources);
```

### Resource Structure

Each resource follows this structure:

```json
{
  "id": "unique_resource_id",
  "name": "Human readable name",
  "type": "file|data|url",
  "description": "Resource description",
  "path": "/absolute/path/to/file",
  "url": "https://external.url",
  "data": {...},
  "version": "1.0.0",
  "size": 1048576,
  "mime_type": "application/zip",
  "metadata": {
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "checksum": "sha256_hash"
  }
}
```

## Tier Assignment

Resources are assigned to tiers to control access:

```php
// Get tier resources
$tier_id = 2; // Professional tier
$tier_resources = get_option('wplcs_tier_resources_' . $tier_id, array());

// Add resource to tier
$tier_resources[] = array(
    'id' => 'resource_id',
    'type' => 'file',
    'access_level' => 'full', // full|limited|preview
    'restrictions' => array(
        'max_downloads' => 10,
        'time_limit' => 3600 // 1 hour
    )
);

update_option('wplcs_tier_resources_' . $tier_id, $tier_resources);
```

## Security Configuration

### File Security

1. **Secure File Storage:**
```php
// Store files outside web root
define('WPLCS_SECURE_PATH', '/var/wplcs-files/');

// Or use protected directory with .htaccess
// .htaccess content:
/*
Order Deny,Allow
Deny from all
*/
```

2. **File Access Control:**
```php
// Validate file access
function wplcs_validate_file_access($file_path, $token_data) {
    // Check file exists
    if (!file_exists($file_path)) {
        return false;
    }
    
    // Validate file is in allowed directory
    $secure_path = realpath(WPLCS_SECURE_PATH);
    $file_real_path = realpath($file_path);
    
    if (strpos($file_real_path, $secure_path) !== 0) {
        return false;
    }
    
    // Additional security checks
    return true;
}
```

### Signed URLs

WPLCS generates secure, time-limited URLs for file downloads:

```php
// Generate signed download URL
function wplcs_generate_download_url($file_path, $expires = 3600) {
    $security = new \WPLCS\Core\Security();
    $expires_timestamp = time() + $expires;
    
    $data = $file_path . $expires_timestamp;
    $signature = $security->generate_hash($data);
    
    return add_query_arg(array(
        'wplcs_download' => '1',
        'file' => base64_encode($file_path),
        'expires' => $expires_timestamp,
        'signature' => $signature
    ), home_url());
}

// Validate and serve file
function wplcs_serve_secure_file() {
    if (!isset($_GET['wplcs_download'])) {
        return;
    }
    
    $file = base64_decode($_GET['file']);
    $expires = intval($_GET['expires']);
    $signature = $_GET['signature'];
    
    // Validate signature
    $security = new \WPLCS\Core\Security();
    $expected_signature = $security->generate_hash($file . $expires);
    
    if (!hash_equals($signature, $expected_signature)) {
        wp_die('Invalid signature');
    }
    
    // Check expiration
    if (time() > $expires) {
        wp_die('Download link expired');
    }
    
    // Validate file access
    if (!wplcs_validate_file_access($file, null)) {
        wp_die('Access denied');
    }
    
    // Serve file
    wplcs_serve_file($file);
}
add_action('init', 'wplcs_serve_secure_file');
```

## Response Format

### Status Check Response
```json
{
  "success": true,
  "data": {
    "resources": [
      {
        "id": "premium_package_v1",
        "name": "Premium Package v1.0",
        "type": "file",
        "description": "Advanced features package",
        "version": "1.0.0",
        "size": 1048576,
        "access_level": "full"
      }
    ],
    "tier": "Professional",
    "has_access": true
  }
}
```

### Download Response
```json
{
  "success": true,
  "data": {
    "download_url": "https://site.com/?wplcs_download=1&file=...",
    "filename": "premium-package.zip",
    "size": 1048576,
    "expires_at": "2024-01-15 11:30:00",
    "checksum": "sha256_hash"
  }
}
```

## Advanced Configuration

### Custom Resource Handlers

```php
// Register custom resource type
add_filter('wplcs_resource_types', function($types) {
    $types['custom_api'] = array(
        'name' => 'Custom API',
        'handler' => 'handle_custom_api_resource'
    );
    return $types;
});

// Handle custom resource
function handle_custom_api_resource($resource, $token_data) {
    // Generate API endpoint for this user
    $endpoint = 'https://api.example.com/user/' . $token_data->user_id;
    $api_key = generate_temp_api_key($token_data->user_id);
    
    return array(
        'endpoint' => $endpoint,
        'api_key' => $api_key,
        'expires_at' => date('Y-m-d H:i:s', time() + 3600)
    );
}
```

### Resource Versioning

```php
// Version management
function wplcs_add_resource_version($resource_id, $version, $file_path) {
    $resources = get_option('wplcs_resources', array());
    
    if (!isset($resources[$resource_id])) {
        return false;
    }
    
    // Store version info
    if (!isset($resources[$resource_id]['versions'])) {
        $resources[$resource_id]['versions'] = array();
    }
    
    $resources[$resource_id]['versions'][$version] = array(
        'path' => $file_path,
        'created_at' => current_time('mysql'),
        'size' => filesize($file_path),
        'checksum' => hash_file('sha256', $file_path)
    );
    
    // Update current version
    $resources[$resource_id]['version'] = $version;
    $resources[$resource_id]['path'] = $file_path;
    
    update_option('wplcs_resources', $resources);
    return true;
}
```

### Access Logging

```php
// Log resource access
function wplcs_log_resource_access($resource_id, $token_data, $action) {
    global $wpdb;
    $database = new \WPLCS\Core\Database();
    $logs_table = $database->get_table('logs');
    
    $log_data = array(
        'token_id' => $token_data->id,
        'user_id' => $token_data->user_id,
        'action' => 'resource_' . $action,
        'resource' => $resource_id,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    );
    
    $wpdb->insert($logs_table, $log_data);
}
```

## Monitoring and Analytics

### Resource Usage Tracking

```php
// Track downloads
function wplcs_track_download($resource_id, $user_id) {
    $stats = get_option('wplcs_resource_stats', array());
    
    if (!isset($stats[$resource_id])) {
        $stats[$resource_id] = array(
            'total_downloads' => 0,
            'unique_users' => array(),
            'bandwidth_used' => 0
        );
    }
    
    $stats[$resource_id]['total_downloads']++;
    
    if (!in_array($user_id, $stats[$resource_id]['unique_users'])) {
        $stats[$resource_id]['unique_users'][] = $user_id;
    }
    
    update_option('wplcs_resource_stats', $stats);
}
```

### Performance Optimization

1. **CDN Integration:**
```php
function wplcs_get_cdn_url($file_path) {
    $cdn_base = 'https://cdn.example.com/wplcs/';
    $relative_path = str_replace(WPLCS_SECURE_PATH, '', $file_path);
    return $cdn_base . $relative_path;
}
```

2. **Caching:**
```php
function wplcs_cache_resource_list($tier_id) {
    $cache_key = 'wplcs_tier_resources_' . $tier_id;
    $cached = wp_cache_get($cache_key);
    
    if ($cached === false) {
        $resources = get_option('wplcs_tier_resources_' . $tier_id, array());
        wp_cache_set($cache_key, $resources, '', 3600);
        return $resources;
    }
    
    return $cached;
}
```

## Best Practices

1. **Security:**
   - Store files outside web root
   - Use signed URLs with expiration
   - Validate all file paths
   - Log all access attempts

2. **Performance:**
   - Use CDN for large files
   - Implement caching
   - Compress files when possible
   - Monitor bandwidth usage

3. **Organization:**
   - Use clear naming conventions
   - Version your resources
   - Document resource purposes
   - Regular cleanup of old versions

4. **Monitoring:**
   - Track download statistics
   - Monitor failed access attempts
   - Set up alerts for unusual activity
   - Regular security audits

## Troubleshooting

### Common Issues

1. **File Not Found:**
   - Check file path is correct
   - Verify file permissions
   - Ensure file exists on server

2. **Access Denied:**
   - Verify tier has access to resource
   - Check token is active and valid
   - Validate user permissions

3. **Download Timeouts:**
   - Increase PHP execution time
   - Use chunked downloads for large files
   - Consider CDN for better performance

4. **Signature Errors:**
   - Check system clock synchronization
   - Verify signature generation logic
   - Ensure proper URL encoding

For additional support and advanced configurations, consult the WPLCS administrator documentation or contact support.