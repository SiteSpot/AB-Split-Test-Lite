# ABSPLITTEST Security Notes

## Directory Protection

This plugin includes security measures to prevent directory browsing and unauthorized access:

### 1. Index Files
All directories contain `index.php` files with "Silence is golden" to prevent directory listing.

### 2. .htaccess Rules
The plugin includes `.htaccess` rules that:
- Disable directory indexes (`Options -Indexes`)
- Block direct access to PHP files (except the main plugin file)
- Block access to sensitive files (`.md`, `.json`, `.log`, `.txt`)
- Allow CSS, JS, and image files for frontend functionality

## Recommended robots.txt Additions

If you've experienced SEO index pollution from plugin directories being crawled, add these rules to your site's `robots.txt`:

```
# Block plugin internal directories
Disallow: /wp-content/plugins/bt-bb-ab/
Disallow: /wp-content/plugins/ABSPLITTEST/

# If using a different plugin folder name
Disallow: /wp-content/plugins/*/includes/
Disallow: /wp-content/plugins/*/admin/
Disallow: /wp-content/plugins/*/modules/
```

## Removing Indexed URLs from Google

If Google has already indexed plugin URLs:

1. **Google Search Console**: Use the URL Removal Tool
   - Go to Search Console > Removals > New Request
   - Enter the plugin directory URL pattern
   - Request temporary removal

2. **Submit Updated Sitemap**: Ensure your sitemap doesn't include plugin URLs

3. **Wait for Re-crawl**: After implementing these fixes, Google will eventually de-index the URLs

## Server Configuration

### Apache (via .htaccess - included)
The `.htaccess` file in this plugin handles protection automatically.

### Nginx
Add to your server block:
```nginx
location ~* /wp-content/plugins/bt-bb-ab/ {
    location ~* \.(css|js|svg|png|jpg|jpeg|gif|ico|webp)$ {
        allow all;
    }
    deny all;
}
```

### IIS (web.config)
```xml
<configuration>
  <system.webServer>
    <directoryBrowse enabled="false" />
  </system.webServer>
</configuration>
```

## Questions?

Contact support at absplittest.com if you have security concerns.
