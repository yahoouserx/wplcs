# WPLCS Modern Theme System

A comprehensive Light and Dark Mode theming system for the WordPress License Control System (WPLCS) plugin, inspired by cursor.com's modern UI design.

## Features

- **🌓 Light/Dark Mode Toggle**: Seamless switching between light and dark themes
- **🎨 Cursor.com Inspired Design**: Modern, clean, developer-focused UI
- **💾 Persistent Preferences**: Theme choice saved in localStorage
- **🔄 System Preference Detection**: Automatically detects and applies OS theme preference
- **📱 Fully Responsive**: Optimized for desktop, tablet, and mobile devices
- **♿ Accessibility First**: WCAG compliant with proper contrast ratios and focus indicators
- **⚡ Smooth Animations**: Buttery smooth transitions and micro-interactions
- **🔧 WordPress Integration**: Seamlessly integrates with WordPress admin interface

## Installation

The theme system is automatically loaded when the WPLCS plugin is activated. No additional installation steps required.

## Quick Start

### Automatic Activation

1. Navigate to any WPLCS admin page
2. The theme system loads automatically
3. Look for the theme toggle button in the top-right corner
4. Click to switch between light and dark modes

### Keyboard Shortcut

Use `Ctrl/Cmd + Shift + L` to quickly toggle between themes.

## File Structure

```
assets/
├── css/
│   ├── wplcs-theme.css      # Main theme system CSS
│   ├── admin.css            # WordPress admin integration
│   └── dashboard.css        # Legacy dashboard styles
└── js/
    ├── wplcs-theme.js       # Theme system JavaScript
    ├── admin.js             # Admin functionality
    └── dashboard.js         # Dashboard specific JS
```

## CSS Architecture

### CSS Custom Properties (Variables)

The theme system uses CSS custom properties for dynamic theming:

```css
:root {
  /* Light theme variables */
  --wplcs-bg-primary: #ffffff;
  --wplcs-text-primary: #1a1d21;
  --wplcs-primary: #3b82f6;
  /* ... more variables */
}

[data-theme="dark"] {
  /* Dark theme variables */
  --wplcs-bg-primary: #0d1117;
  --wplcs-text-primary: #f0f6fc;
  --wplcs-primary: #58a6ff;
  /* ... more variables */
}
```

### Component Classes

#### Cards
```html
<div class="wplcs-card">
  <div class="wplcs-card-header">
    <h3 class="wplcs-card-title">Card Title</h3>
    <p class="wplcs-card-subtitle">Subtitle</p>
  </div>
  <!-- Card content -->
</div>
```

#### Buttons
```html
<button class="wplcs-btn wplcs-btn-primary">Primary Button</button>
<button class="wplcs-btn wplcs-btn-secondary">Secondary Button</button>
<button class="wplcs-btn wplcs-btn-success">Success Button</button>
<button class="wplcs-btn wplcs-btn-danger">Danger Button</button>
```

#### Status Badges
```html
<span class="wplcs-badge wplcs-badge-success">
  <span class="wplcs-badge-dot"></span>
  Active
</span>
```

#### Form Elements
```html
<div class="wplcs-form-group">
  <label class="wplcs-form-label">Label</label>
  <input type="text" class="wplcs-form-input" placeholder="Enter text">
</div>
```

## JavaScript API

### Theme Management

```javascript
// Set theme programmatically
window.wplcs.setTheme('dark');
window.wplcs.setTheme('light');

// Get current theme
const currentTheme = window.wplcs.getCurrentTheme();

// Check if dark mode is active
const isDark = window.wplcs.isDarkMode();

// Show notifications
window.wplcs.notify('Success message', 'success');
window.wplcs.notify('Error message', 'error');
window.wplcs.notify('Warning message', 'warning');
window.wplcs.notify('Info message', 'info');
```

### Event Listeners

```javascript
// Listen for theme changes
$(document).on('wplcs:themeChanged', function(event, theme) {
  console.log('Theme changed to:', theme);
});

// Listen for system initialization
$(document).on('wplcs:initialized', function() {
  console.log('WPLCS theme system initialized');
});
```

## Customization

### Adding Custom Colors

Add your custom colors to the CSS variables:

```css
:root {
  --wplcs-custom-color: #your-color;
}

[data-theme="dark"] {
  --wplcs-custom-color: #your-dark-color;
}
```

### Creating Custom Components

Use the existing design tokens to create consistent components:

```css
.your-custom-component {
  background: var(--wplcs-bg-elevated);
  border: 1px solid var(--wplcs-border-primary);
  border-radius: var(--wplcs-radius-lg);
  padding: var(--wplcs-space-lg);
  color: var(--wplcs-text-primary);
  box-shadow: var(--wplcs-shadow-sm);
  transition: all 0.3s ease;
}

.your-custom-component:hover {
  box-shadow: var(--wplcs-shadow-md);
  border-color: var(--wplcs-border-hover);
}
```

### Custom Animations

Add animations using the provided animation classes:

```html
<div class="wplcs-card wplcs-fade-in">
  <!-- Content with fade-in animation -->
</div>

<div class="wplcs-card wplcs-slide-in">
  <!-- Content with slide-in animation -->
</div>
```

## Color Palette

### Light Theme
- **Primary**: #3b82f6 (Blue)
- **Accent**: #10b981 (Green)
- **Background**: #ffffff (White)
- **Surface**: #f8f9fa (Light Gray)
- **Text**: #1a1d21 (Dark Gray)

### Dark Theme
- **Primary**: #58a6ff (Light Blue)
- **Accent**: #56d364 (Light Green)
- **Background**: #0d1117 (Dark)
- **Surface**: #161b22 (Dark Gray)
- **Text**: #f0f6fc (Light)

## Typography

The theme system uses a carefully selected font stack:

```css
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
```

### Font Weights
- **400**: Regular text
- **500**: Medium (labels, buttons)
- **600**: Semibold (headings)
- **700**: Bold (titles, stats)

## Responsive Design

### Breakpoints
- **Mobile**: < 480px
- **Tablet**: 481px - 768px
- **Desktop**: 769px - 1024px
- **Large Desktop**: > 1024px

### Grid System
The theme uses CSS Grid for layouts:

```css
.wplcs-stats-grid-modern {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: var(--wplcs-space-lg);
}
```

## Accessibility Features

### Keyboard Navigation
- All interactive elements are keyboard accessible
- Proper focus indicators
- Tab order follows logical flow

### Screen Readers
- Semantic HTML structure
- ARIA labels and roles
- Screen reader only text using `.wplcs-sr-only`

### Color Contrast
- WCAG AA compliant contrast ratios
- High contrast mode support
- Color is not the only means of conveying information

### Reduced Motion
Respects user's motion preferences:

```css
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

## Browser Support

- **Chrome**: 88+
- **Firefox**: 85+
- **Safari**: 14+
- **Edge**: 88+

### CSS Features Used
- CSS Custom Properties
- CSS Grid
- Flexbox
- CSS Transitions & Animations
- Media Queries (including prefers-color-scheme)

## Performance

### Optimizations
- CSS variables for efficient theme switching
- Hardware-accelerated animations
- Minimal repaints and reflows
- Lazy loading for non-critical features

### Bundle Sizes
- **CSS**: ~45KB (uncompressed)
- **JavaScript**: ~20KB (uncompressed)

## WordPress Integration

### Admin Bar Theming
The dark theme extends to WordPress admin elements:

```css
[data-theme="dark"] #wpadminbar {
  background: #161b22 !important;
  color: #f0f6fc !important;
}
```

### Menu Integration
Custom icon and styling for WPLCS menu items:

```css
#adminmenu .toplevel_page_wplcs .wp-menu-image:before {
  content: '\f332';
  font-family: dashicons;
}
```

## Troubleshooting

### Theme Not Switching
1. Check browser console for JavaScript errors
2. Verify localStorage is enabled
3. Clear browser cache and cookies

### Colors Not Updating
1. Ensure CSS custom properties are supported
2. Check for conflicting CSS rules
3. Verify the `data-theme` attribute is set on `<html>`

### Performance Issues
1. Disable animations with reduced motion preference
2. Check for CSS specificity conflicts
3. Minimize DOM manipulation during theme switches

## Migration from Legacy Styles

### Step 1: Replace Old Classes
```css
/* Old */
.wplcs-stat-box → .wplcs-stat-card-modern
.wplcs-admin-stats → .wplcs-stats-grid-modern

/* New */
.wplcs-card
.wplcs-btn
.wplcs-badge
```

### Step 2: Update Color References
```css
/* Old */
color: #0073aa;

/* New */
color: var(--wplcs-primary);
```

### Step 3: Use New Layout System
```css
/* Old */
.wplcs-stat-boxes {
  display: flex;
}

/* New */
.wplcs-stats-grid-modern {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}
```

## Contributing

### Adding New Components
1. Follow the existing naming convention
2. Use CSS custom properties for colors
3. Include both light and dark variants
4. Add hover and focus states
5. Ensure responsive behavior

### Testing
- Test in both light and dark modes
- Verify keyboard accessibility
- Check responsive behavior
- Test with reduced motion preferences

## License

This theme system is part of the WPLCS plugin and follows the same GPL v2 license.

## Support

For issues or feature requests related to the theme system:
1. Check the troubleshooting section
2. Review browser console for errors
3. Create an issue on the plugin repository
4. Include browser version and steps to reproduce

---

Built with ❤️ for the WordPress community