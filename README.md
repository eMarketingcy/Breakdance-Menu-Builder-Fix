# Breakdance-Menu-Builder-Fix
Fixes active menu state issues in Breakdance Menu Builder for dropdown items and mobile navigation.

=== Breakdance Menu Builder Fix ===
Contributors: eMarketingcy
Tags: breakdance, menu, navigation, dropdown, mobile
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fixes active menu state issues in Breakdance Menu Builder for dropdown items and mobile navigation.

== Description ==

This plugin automatically fixes common issues with the Breakdance Menu Builder without requiring any theme modifications:

**Issues Fixed:**

1. **Dropdown Active States**: Active menu settings don't apply to dropdown menu items - only to top-level items
2. **Parent Menu Highlighting**: Parent menu items don't show active state when child pages are active  
3. **Mobile Menu Behavior**: Dropdown toggles don't work properly on mobile devices

**Features:**

* ✅ Automatic detection and fixing of active menu states
* ✅ Customizable active menu colors
* ✅ Mobile breakpoint configuration
* ✅ No theme file modifications required
* ✅ Works with both custom menus and WordPress menus
* ✅ Easy-to-use admin settings panel
* ✅ Lightweight and performance optimized

**Requirements:**

* Breakdance Plugin (Pro or Free)
* WordPress 5.0+
* PHP 7.4+

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/breakdance-menu-fix/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Breakdance Menu Fix to configure options
4. That's it! The fixes are applied automatically

== Frequently Asked Questions ==

= Does this work with any theme? =

Yes! This plugin works independently of your theme. It specifically targets Breakdance Menu Builder elements.

= Do I need to modify my theme files? =

No! Unlike manual fixes that require editing functions.php and creating JavaScript files, this plugin handles everything automatically.

= Will this affect my site's performance? =

The plugin is lightweight and only loads its JavaScript and CSS when Breakdance menus are present on the page.

= Can I customize the active menu colors? =

Yes! Go to Settings > Breakdance Menu Fix to customize colors, mobile breakpoints, and enable/disable specific fixes.

= What if I'm using WordPress menus instead of custom menus? =

The plugin automatically detects and works with both WordPress menus and custom Breakdance menus.

== Screenshots ==

1. Admin settings panel
2. Before: Dropdown items don't show active state
3. After: Dropdown items properly highlighted
4. Mobile menu behavior fixed

== Changelog ==

= 2.1.1 =
* Initial release
* Fix for dropdown menu active states
* Fix for parent menu highlighting
* Fix for mobile menu behavior
* Admin settings panel
* Customizable colors and breakpoints

== Upgrade Notice ==

= 2.1.1 =
Initial release of the Breakdance Menu Builder Fix plugin.
