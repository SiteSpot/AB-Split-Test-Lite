# ABSPLITTEST Security Notes

## Directory Protection

Heatmap (journey) data is stored as daily text files in `wp-content/uploads/abst/journeys/` (`abst_journeys_YYYYMMDD.txt`, compressed to `.txt.gz` after a day). The debug log is `wp-content/uploads/abst_log_<hash>.log`, named from `AUTH_KEY`. Visitor-supplied fields are sanitized and stripped of the `|` delimiter before they are written.

### 1. Index Files
The journey directory and its parent receive `index.php` files to discourage directory listing. Configure directory browsing on the web server as well.

### 2. .htaccess Rules
The journey directory receives a `Deny from all` rule, which blocks direct downloads on servers that honor `.htaccess` (Apache, LiteSpeed). Other servers need the rules under **Server Configuration**.

## Consent

When **Wait for cookie consent** is enabled, the script waits for current provider consent or `setAbstApprovalStatus(true)` before persisting tracking cookies, browser storage, or sending events. Custom banners must confirm consent on each page load and call `setAbstApprovalStatus(false)` on withdrawal. Withdrawal clears identifiers and pending history; subsequent approval starts fresh.

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

### Apache / LiteSpeed
The journey directory's `.htaccess` blocks direct access. Make sure `AllowOverride` permits it.

### Nginx
Nginx ignores `.htaccess`, so deny the journey directory in your server block (adjust the path if uploads uses a different URL):
```nginx
location ^~ /wp-content/uploads/abst/ {
    deny all;
}
```

### IIS (web.config)
Deny `/wp-content/uploads/abst/` through request filtering. Disabling directory browsing alone does not prevent direct file access:
```xml
<configuration>
  <system.webServer>
    <directoryBrowse enabled="false" />
  </system.webServer>
</configuration>
```

## Questions?

Contact support at absplittest.com if you have security concerns.
