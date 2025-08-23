# Buy Now Button for WooCommerce

A lightweight WooCommerce plugin that adds a **Buy Now** button to product pages and shop archives.  
It lets customers add products instantly and go straight to **Checkout**, streamlining the flow and reducing cart abandonment.

<p align="left">
  <a href="#-features"><img alt="Feature Badge" src="https://img.shields.io/badge/Features-Buy%20Now%20%7C%20AJAX%20%7C%20Customizable-blue"></a>
  <a href="#-license"><img alt="License: MIT" src="https://img.shields.io/badge/License-MIT-green.svg"></a>
</p>

---

## Table of Contents

- [✨ Features](#-features)
- [✅ Requirements](#-requirements)
- [📥 Installation](#-installation)
- [⚙️ Configuration](#️-configuration)
- [🔧 Usage](#-usage)
- [🧩 Troubleshooting](#-troubleshooting)
- [🗒️ Changelog](#️-changelog)
- [📄 License](#-license)
- [👨‍💻 Author](#-author)

---

## ✨ Features

- **Buy Now button** on:
  - **Single product pages** (below Add to Cart; supports simple & variable products).
  - **Shop/archive pages** (for **simple** products).
- **Direct-to-checkout** after add-to-cart via **AJAX**, with a **non-JS fallback**.
- **Customization UI** in **WP Admin → WooCommerce → Buy Now Button**:
  - **Button Text** (e.g., “Buy Now”, “Quick Checkout”, etc.)
  - **Text Align**: Left / Center / Right (default: Center)
  - **Colors**: Normal / Hover / Active (hex input + color picker)
  - **Border Radius** (px)
  - **Animation**: None, Pulse, Bounce, Wiggle, Shake, Glow, Ripple
  - **Animation Trigger**: Hover or Always (Normal)
- Works nicely with caching/CDN and heavy themes (injects dynamic CSS with `!important`).
- Clean, lightweight, and developer-friendly.

---

## ✅ Requirements

- WordPress
- WooCommerce

---

## 📥 Installation

**Option A — Upload Folder**
1. Download or clone this repository.
2. Upload the plugin folder to `wp-content/plugins/`.
3. Activate it from **WordPress → Plugins**.

**Option B — Upload ZIP**
1. Zip the plugin folder.
2. Go to **Plugins → Add New → Upload Plugin** and upload the ZIP.
3. Activate.

---

## ⚙️ Configuration

Open **WooCommerce → Buy Now Button** and configure:

- **Button Text**
- **Button Text Align** — Left / Center / Right (default: Center)
- **Colors** — set hex (or use the color picker) for:
  - Normal
  - Hover
  - Active
- **Border Radius** — in pixels
- **Animation** — None, Pulse, Bounce, Wiggle, Shake, Glow, Ripple
- **Animation Trigger** — Apply on **Hover** or **Always (Normal)**

> After saving, purge your cache if you use a caching plugin or CDN.

---

## 🔧 Usage

- **Single product pages:** A **Buy Now** button appears under Add to Cart.  
  For variable products, the chosen attributes & variation are respected.
- **Shop/archive pages:** A **Buy Now** button appears for **simple** products.
- **Behavior:** Clicking **Buy Now** adds the item to cart (AJAX) and immediately redirects to **Checkout**.  
  With JavaScript disabled, a fallback route handles add-to-cart and redirects to Checkout.

---

## 🧩 Troubleshooting

**Settings don’t apply (colors/radius/text/align)**
- Purge any page/cache plugin and your CDN; hard-refresh the page (Ctrl/Cmd + Shift + R).
- Ensure the plugin is active and you saved changes.
- Inspect the page `<head>` for inline styles injected for handle `abb-bn-css`. If missing, verify theme doesn’t deregister styles.

**Clicking Buy Now doesn’t redirect to Checkout**
- Make sure **WooCommerce** is active.
- Check **browser console** (F12 → Console) for JS errors.
- Security plugins or firewalls sometimes block `admin-ajax.php`. Whitelist it and ensure it’s not cached.
- If you see `403` or nonce errors, log out/in or clear server-side caches that might cache logged-in pages.

**Variable product won’t add**
- Ensure a valid **variation** is selected (dropdowns/attributes) before clicking Buy Now.

**Button not visible**
- On archives, the button shows for **simple** products only (by design).  
  If your theme overrides Woo hooks/templates, confirm it still calls:
  - `woocommerce_after_add_to_cart_button` (single)
  - `woocommerce_after_shop_loop_item` (archive)

**Conflicts with other Buy Now/checkout plugins**
- Disable other similar plugins to avoid duplicate handlers, CSS, or AJAX actions.

**Replace (not append) cart contents on Buy Now**
- In `buy-now-button.php`, inside the AJAX handler, uncomment:
  ```php
  // WC()->cart->empty_cart();

## 🧩 Troubleshooting

- **Settings not applying:** Clear any page cache/minifier and CDN after saving.
- **No redirect to Checkout:** Ensure WooCommerce is active; check the browser console for JS errors and disable conflicting “Buy Now” plugins.
- **Variable products:** Ensure a valid variation is selected before clicking **Buy Now**.

---

## 🗒️ Changelog

### 1.4.0
- Added **Button Text Align** (Left / Center / Right). Default: **Center**.

### 1.3.0
- Added **Button Text** customization.
- Added **Animation Trigger** (Hover / Always).
- Added **Hex color inputs** with WordPress Color Picker.

### 1.2.0
- Switched to **AJAX** add-to-cart + redirect (with non-JS fallback).
- Improved reliability with caching/CDN and heavy themes.

### 1.1.0
- Initial customization options (colors, animation, radius) + settings UI.

---

## 📄 License

MIT — use, modify, and distribute freely.

---

## 👨‍💻 Author

**absoftlab**  
Website: https://absoftlab.com
