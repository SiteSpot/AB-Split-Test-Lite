# ABSPLITTEST Design System & Style Guide

## Design Philosophy
**"Clean, spacious, SaaS-inspired."**

Inspired by modern WordPress plugins like WPMU DEV Defender. The interface should feel like a premium SaaS application - spacious, organized, with clear visual hierarchy and subtle depth through shadows rather than heavy borders.

---

## 1. Color Palette

### Primary Brand
- **Brand Blue**: `#17A8E3` (Primary actions, active states, links)
- **Brand Blue Hover**: `#1289ba` (Darker for hover states)

### Status Colors
- **Success Green**: `#1ABC9C` or `#10B981` (Active badges, success states)
- **Warning Orange**: `#FF6D3A` or `#F97316` (Warnings, attention items)
- **Error Red**: `#FF5C5C` or `#EF4444` (Errors, critical issues)
- **Info Blue**: `#3B82F6` (Informational badges)

### Neutrals (Critical for the clean look)
- **Page Background**: `#F0F0F0` or `#F5F5F5` (Light gray canvas)
- **Card Background**: `#FFFFFF` (Pure white cards)
- **Text Primary**: `#1F2937` or `#333333` (Headings, strong text)
- **Text Secondary**: `#6B7280` or `#666666` (Descriptions, meta)
- **Text Muted**: `#9CA3AF` (Hints, placeholders)
- **Border Light**: `#E5E7EB` (Subtle card borders)
- **Border Divider**: `#F3F4F6` (Internal dividers)

---

## 2. Typography

**Font Stack**: System fonts for performance
```css
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```

### Hierarchy
| Element | Size | Weight | Color | Notes |
|---------|------|--------|-------|-------|
| Page Title | 24-28px | 700 | `#1F2937` | Bold, commanding |
| Section Title | 16-18px | 600 | `#1F2937` | Card headers |
| Subsection | 14px | 600 | `#374151` | With icon prefix |
| Body Text | 14px | 400 | `#4B5563` | Readable |
| Description | 13px | 400 | `#6B7280` | Lighter, secondary |
| Label | 12-13px | 500 | `#374151` | Form labels |
| Badge | 11-12px | 600 | varies | Uppercase optional |

---

## 3. The Card System

### Base Card
The foundation of the UI - every section lives in a card:
```css
.abst-card {
    background: #FFFFFF;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 
                0 1px 2px rgba(0, 0, 0, 0.06);
    padding: 24px;
    margin-bottom: 24px;
    border: none; /* Shadow provides depth, no border needed */
}
```

### Card Header
```css
.abst-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #F3F4F6;
}

.abst-card-title {
    font-size: 16px;
    font-weight: 600;
    color: #1F2937;
    margin: 0;
}
```

### Card with Icon Header (like Defender)
```css
.abst-card-icon {
    width: 24px;
    height: 24px;
    color: #6B7280;
}
```

---

## 4. Interactive Elements

### Selection Cards (Radio/Checkbox Cards)
For test type selection - full-width clickable cards:
```css
.abst-selection-card {
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.abst-selection-card:hover {
    border-color: #17A8E3;
    box-shadow: 0 2px 8px rgba(23, 168, 227, 0.1);
}

.abst-selection-card.selected {
    background: #17A8E3;
    border-color: #17A8E3;
    color: #FFFFFF;
}

.abst-selection-card.selected .title {
    color: #FFFFFF;
}

.abst-selection-card.selected .description {
    color: rgba(255, 255, 255, 0.85);
}
```

### Accordion Sections
Collapsible sections with clean headers:
```css
.abst-accordion-header {
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 6px;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 500;
    color: #1F2937;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.abst-accordion-header:hover {
    background: #F3F4F6;
}

.abst-accordion-content {
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-top: none;
    border-radius: 0 0 6px 6px;
    padding: 16px;
}
```

### Buttons
```css
/* Primary */
.abst-btn-primary {
    background: #17A8E3;
    color: #FFFFFF;
    border: none;
    border-radius: 6px;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease;
}

.abst-btn-primary:hover {
    background: #1289ba;
}

/* Secondary/Ghost */
.abst-btn-secondary {
    background: #FFFFFF;
    color: #374151;
    border: 1px solid #D1D5DB;
    border-radius: 6px;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 500;
}

.abst-btn-secondary:hover {
    background: #F9FAFB;
    border-color: #9CA3AF;
}
```

### Badges/Pills
```css
.abst-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.abst-badge-success {
    background: #D1FAE5;
    color: #059669;
}

.abst-badge-warning {
    background: #FEF3C7;
    color: #D97706;
}

.abst-badge-error {
    background: #FEE2E2;
    color: #DC2626;
}

.abst-badge-info {
    background: #DBEAFE;
    color: #2563EB;
}
```

---

## 5. Form Elements

### Text Inputs
```css
.abst-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #D1D5DB;
    border-radius: 6px;
    font-size: 14px;
    color: #1F2937;
    background: #FFFFFF;
    transition: all 0.15s ease;
}

.abst-input:focus {
    border-color: #17A8E3;
    box-shadow: 0 0 0 3px rgba(23, 168, 227, 0.1);
    outline: none;
}

.abst-input::placeholder {
    color: #9CA3AF;
}
```

### Select Dropdowns
```css
.abst-select {
    appearance: none;
    padding: 10px 40px 10px 14px;
    border: 1px solid #D1D5DB;
    border-radius: 6px;
    background: #FFFFFF url('chevron-down.svg') right 12px center no-repeat;
    background-size: 16px;
}
```

### Checkboxes (Custom)
```css
.abst-checkbox {
    width: 18px;
    height: 18px;
    border: 2px solid #D1D5DB;
    border-radius: 4px;
    cursor: pointer;
}

.abst-checkbox:checked {
    background: #17A8E3;
    border-color: #17A8E3;
}
```

### Toggle Switches
```css
.abst-toggle {
    width: 44px;
    height: 24px;
    background: #D1D5DB;
    border-radius: 12px;
    position: relative;
    cursor: pointer;
    transition: background 0.2s ease;
}

.abst-toggle.active {
    background: #17A8E3;
}

.abst-toggle::after {
    content: '';
    width: 20px;
    height: 20px;
    background: #FFFFFF;
    border-radius: 50%;
    position: absolute;
    top: 2px;
    left: 2px;
    transition: transform 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.abst-toggle.active::after {
    transform: translateX(20px);
}
```

---

## 6. Layout & Spacing

### Spacing Scale
```
4px  - Tight (inline elements)
8px  - Small (related items)
12px - Medium (form groups)
16px - Default (card padding, gaps)
24px - Large (section spacing)
32px - XL (major sections)
```

### Card Grid
```css
.abst-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
}
```

---

## 7. Specific Component Patterns

### List Items with Status (like Defender's security tweaks)
```css
.abst-list-item {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    border-left: 3px solid transparent;
    background: #FFFFFF;
    border-bottom: 1px solid #F3F4F6;
}

.abst-list-item.warning {
    border-left-color: #F97316;
    background: #FFFBEB;
}

.abst-list-item.success {
    border-left-color: #10B981;
}
```

### Stats Display (like "56.1K" in Defender)
```css
.abst-stat {
    font-size: 36px;
    font-weight: 700;
    color: #1F2937;
    line-height: 1;
}

.abst-stat-label {
    font-size: 13px;
    color: #6B7280;
    margin-top: 4px;
}
```

---

## 8. Shadows & Depth

Use shadows sparingly for hierarchy:
```css
--shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
--shadow-md: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.06);
--shadow-lg: 0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.06);
--shadow-hover: 0 4px 12px rgba(0, 0, 0, 0.1);
```

---

## 9. Key Principles

1. **White space is your friend** - Don't crowd elements
2. **Shadows over borders** - Use subtle shadows for depth instead of heavy borders
3. **Consistent radius** - Use 6-8px for cards, 4-6px for buttons/inputs
4. **Color sparingly** - Most UI is neutral, color draws attention
5. **Clear hierarchy** - Size and weight differentiate importance
6. **Smooth transitions** - 0.15s ease for hover states

---

## 10. CSS Variables (Root)

```css
:root {
    /* Brand */
    --abst-primary: #17A8E3;
    --abst-primary-hover: #1289ba;
    
    /* Status */
    --abst-success: #10B981;
    --abst-warning: #F97316;
    --abst-error: #EF4444;
    
    /* Neutrals */
    --abst-bg: #F5F5F5;
    --abst-card: #FFFFFF;
    --abst-text: #1F2937;
    --abst-text-secondary: #6B7280;
    --abst-text-muted: #9CA3AF;
    --abst-border: #E5E7EB;
    --abst-border-light: #F3F4F6;
    
    /* Spacing */
    --abst-space-xs: 4px;
    --abst-space-sm: 8px;
    --abst-space-md: 12px;
    --abst-space-lg: 16px;
    --abst-space-xl: 24px;
    
    /* Radius */
    --abst-radius-sm: 4px;
    --abst-radius-md: 6px;
    --abst-radius-lg: 8px;
    
    /* Shadows */
    --abst-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
    --abst-shadow-hover: 0 4px 12px rgba(0,0,0,0.1);
}
```
