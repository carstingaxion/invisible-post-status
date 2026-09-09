# Invisible Post Status

**Contributors:** carstenbach  
**Tags:** invisible, post-status, wordpress  
**Tested up to:** 7.1  
**Stable tag:** 0.1.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

<!-- Playground badge -->
---

## Description

Working but still bound to fixed post types

```php
$parent_post_type  = 'gatherpress_play';
$subsite_post_type = 'gatherpress_play_sub';
```

### How It Works

1. Register the post status (hidden from archives, search, and standard queries).
2. Allow singular frontend queries (so direct URL access works for anyone).
3. Display "Invisible" badge in the admin post list table.
4. Add the UI controls to the Editor (Block Editor / Gutenberg).
5. Add "Invisible" option to Quick Edit (and Classic Editor).

---

## Frequently Asked Questions

...

---

## Screenshots

...

---

## Installation

1. Upload the plugin files to the `/wp-content/plugins/invisible-post-status` directory, ~~or install the plugin through the WordPress Plugins screen~~  
2. Activate the plugin through the **Plugins** screen in WordPress  
3. Open any post or page in the block editor  
4. Click the More Actions menu (⋮) to see the duplicate option  

---

## Changelog

All notable changes to this project will be documented in the [CHANGELOG.md](CHANGELOG.md).
