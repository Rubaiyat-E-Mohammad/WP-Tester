# IDE Setup Guide for WP Tester Plugin

This guide helps you configure your IDE to properly recognize WordPress functions and eliminate false error messages.

## PhpStorm/IntelliJ IDEA

1. **Install WordPress Plugin:**
   - Go to `Settings` → `Plugins`
   - Search for "WordPress" and install the official WordPress plugin
   - Restart PhpStorm

2. **Configure WordPress Support:**
   - Go to `Settings` → `Languages & Frameworks` → `PHP` → `WordPress`
   - Check "Enable WordPress integration"
   - Set "WordPress installation path" to your local WordPress installation
   - Set "WordPress path URL" to your local WordPress URL

3. **Using WordPress Stubs:**
   - Install via Composer: `composer require --dev php-stubs/wordpress-stubs`
   - Or manually download WordPress stubs and include them in your project

## VS Code

1. **Install Extensions:**
   - PHP Intelephense
   - WordPress Snippets
   - PHP DocBlocker

2. **Configure Settings:**
   Add to your `settings.json`:
   ```json
   {
     "php.suggest.basic": false,
     "intelephense.stubs": [
       "wordpress",
       "woocommerce"
     ],
     "intelephense.files.exclude": [
       "**/node_modules/**",
       "**/vendor/**"
     ]
   }
   ```

## Sublime Text

1. **Install Packages:**
   - Package Control
   - PHP Completions Kit
   - WordPress Completions

2. **Configure WordPress Support:**
   - Add WordPress function definitions to your project

## General Solution

If you're still seeing red error markers for WordPress functions:

1. **Use the provided stub files:**
   - `wordpress-stubs.php` - Contains WordPress function definitions
   - `.phpstorm.meta.php` - PhpStorm-specific metadata

2. **Install WordPress Stubs via Composer:**
   ```bash
   composer require --dev php-stubs/wordpress-stubs
   composer require --dev php-stubs/woocommerce-stubs
   ```

3. **Verify Syntax:**
   All files pass PHP syntax checking:
   ```bash
   find . -name "*.php" -exec php -l {} \;
   ```

## Note

The "red marked errors" you see are typically false positives from your IDE not recognizing WordPress functions. These are not actual PHP syntax errors - the plugin will work correctly in WordPress environment.

## Troubleshooting

If you're still seeing errors:

1. Clear your IDE cache and restart
2. Ensure WordPress core is properly configured in your IDE
3. Check that the stub files are being loaded
4. Verify your PHP version matches the plugin requirements (PHP 7.4+)

The plugin has been tested and all PHP files pass syntax validation.
