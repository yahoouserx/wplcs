# WPLCS Integration Client Guide

This guide explains how external nodes can interface with the WPLCS (WordPress License Control System) API for token authentication, resource access, and session management.

## Base API URL

All API endpoints are available at:
```
https://yoursite.com/wp-json/wplcs/v1/
```

## Authentication

All requests must include your access token as a query parameter:
```
?token=your_access_token_here
```

## Rate Limiting

API requests are limited to 1000 requests per hour per token by default. Rate limiting is enforced per IP address and token combination.

## Core Endpoints

### 1. Check Token Status

**Endpoint:** `GET /status`

**Parameters:**
- `token` (required) - Your access token

**Response:**
```json
{
  "success": true,
  "data": {
    "token_id": 123,
    "tier": "Professional",
    "max_nodes": 10,
    "current_nodes": 3,
    "expires_at": "2024-12-31 23:59:59",
    "is_trial": false,
    "features": ["basic_access", "priority_support", "advanced_features"],
    "usage_count": 1234,
    "last_used": "2024-01-15 10:30:00"
  }
}
```

### 2. Create Session

**Endpoint:** `POST /session`

**Parameters:**
- `token` (required) - Your access token
- `domain` (required) - Your node's domain name

**Response:**
```json
{
  "success": true,
  "data": {
    "session_id": "abc123def456...",
    "expires_at": "2024-01-15 11:30:00",
    "domain": "node.example.com"
  }
}
```

### 3. Session Heartbeat

**Endpoint:** `POST /heartbeat`

**Parameters:**
- `session_id` (required) - Your session ID

**Response:**
```json
{
  "success": true,
  "data": {
    "session_id": "abc123def456...",
    "expires_at": "2024-01-15 11:30:00",
    "last_activity": "2024-01-15 10:30:00"
  }
}
```

### 4. Check Available Resources

**Endpoint:** `GET /resource`

**Parameters:**
- `token` (required) - Your access token
- `type` (optional) - Resource type filter

**Response:**
```json
{
  "success": true,
  "data": {
    "resources": [
      {
        "id": "resource_1",
        "name": "Premium Package",
        "type": "file",
        "description": "Advanced features package"
      }
    ],
    "tier": "Professional",
    "has_access": true
  }
}
```

### 5. Download Resource

**Endpoint:** `GET /serve`

**Parameters:**
- `token` (required) - Your access token
- `resource` (required) - Resource identifier

**Response:**
```json
{
  "success": true,
  "data": {
    "download_url": "https://yoursite.com/?wplcs_download=1&file=...",
    "filename": "package.zip",
    "size": 1048576,
    "expires_at": "2024-01-15 11:30:00"
  }
}
```

## Error Handling

All API responses follow a consistent format. Errors return HTTP status codes along with error details:

```json
{
  "success": false,
  "error": "Token has expired",
  "code": "expired_token"
}
```

### Common Error Codes

- `invalid_token` - Token is invalid or not found
- `expired_token` - Token has expired
- `rate_limited` - Too many requests
- `node_limit_exceeded` - Maximum nodes reached
- `access_denied` - Insufficient permissions
- `invalid_session` - Session is invalid or expired

## Implementation Example (PHP)

```php
<?php

class WPLCSClient {
    private $api_base;
    private $token;
    private $session_id;
    
    public function __construct($api_base, $token) {
        $this->api_base = rtrim($api_base, '/');
        $this->token = $token;
    }
    
    /**
     * Check token status
     */
    public function checkStatus() {
        return $this->makeRequest('GET', '/status');
    }
    
    /**
     * Create session
     */
    public function createSession($domain) {
        $response = $this->makeRequest('POST', '/session', [
            'domain' => $domain
        ]);
        
        if ($response && $response['success']) {
            $this->session_id = $response['data']['session_id'];
        }
        
        return $response;
    }
    
    /**
     * Send heartbeat
     */
    public function heartbeat() {
        if (!$this->session_id) {
            throw new Exception('No active session');
        }
        
        return $this->makeRequest('POST', '/heartbeat', [
            'session_id' => $this->session_id
        ]);
    }
    
    /**
     * Get available resources
     */
    public function getResources($type = null) {
        $params = [];
        if ($type) {
            $params['type'] = $type;
        }
        
        return $this->makeRequest('GET', '/resource', $params);
    }
    
    /**
     * Download resource
     */
    public function downloadResource($resource_id) {
        return $this->makeRequest('GET', '/serve', [
            'resource' => $resource_id
        ]);
    }
    
    /**
     * Make API request
     */
    private function makeRequest($method, $endpoint, $params = []) {
        $url = $this->api_base . $endpoint;
        
        // Add token to all requests
        $params['token'] = $this->token;
        
        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
            $params = null;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'WPLCS-Client/1.0',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($params) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception('API request failed');
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception($data['error'] ?? 'API error', $httpCode);
        }
        
        return $data;
    }
}

// Usage example
try {
    $client = new WPLCSClient('https://yoursite.com/wp-json/wplcs/v1', 'your_token_here');
    
    // Check status
    $status = $client->checkStatus();
    echo "Token status: " . ($status['success'] ? 'valid' : 'invalid') . "\n";
    
    // Create session
    $session = $client->createSession('node.example.com');
    if ($session['success']) {
        echo "Session created: " . $session['data']['session_id'] . "\n";
        
        // Send periodic heartbeats
        $heartbeat = $client->heartbeat();
        echo "Heartbeat: " . ($heartbeat['success'] ? 'OK' : 'Failed') . "\n";
    }
    
    // Get resources
    $resources = $client->getResources();
    if ($resources['success'] && !empty($resources['data']['resources'])) {
        foreach ($resources['data']['resources'] as $resource) {
            echo "Available resource: " . $resource['name'] . "\n";
            
            // Download resource
            $download = $client->downloadResource($resource['id']);
            if ($download['success']) {
                echo "Download URL: " . $download['data']['download_url'] . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
```

## Best Practices

1. **Session Management**: Create a session when your node starts and send heartbeats every 30 minutes to keep it alive.

2. **Error Handling**: Always check the `success` field in responses and handle errors gracefully.

3. **Rate Limiting**: Implement exponential backoff for rate-limited requests.

4. **Security**: Store tokens securely and never expose them in logs or client-side code.

5. **Caching**: Cache resource information to reduce API calls.

6. **Monitoring**: Log all API interactions for debugging and monitoring purposes.

## Troubleshooting

### Token Issues
- Verify your token is active and not expired
- Check that your tier has sufficient node allowance
- Ensure your domain is properly formatted

### Session Issues
- Sessions expire after 1 hour by default
- Only one session per domain per token is allowed
- Send heartbeats regularly to maintain sessions

### Resource Access
- Verify your tier has access to the requested resource
- Check that the resource still exists
- Download URLs expire after 1 hour

### Rate Limiting
- Implement request queuing to stay under limits
- Use appropriate delays between requests
- Monitor your usage in the dashboard

For additional support, contact the WPLCS administrator or check the system logs in your dashboard.