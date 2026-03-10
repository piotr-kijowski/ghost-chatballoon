# Ghost Chat Balloon Bot

Contributors: piotr.kijowski  
Tags: chatbot, ai, openai, assistant, support, woocommerce  
Requires at least: 6.5  
Tested up to: 6.9  
Requires PHP: 7.4  
Stable tag: 1.2.1  
License: GPLv3 or later  
License URI: https://www.gnu.org/licenses/gpl-3.0.html  

*Floating AI chat assistant for WordPress with tone control, knowledge base, guardrails, logging, WooCommerce context, and full UI customization.*

## Description

Ghost Chat Balloon Bot adds a controlled, on-site AI-powered chat assistant to your WordPress website.

Designed for production environments, it allows site owners to provide contextual assistance while maintaining control over tone, knowledge scope, logging, and usage limits.

The chatbot appears as a floating launcher and opens into a fully customizable chat interface. It supports structured knowledge input, WooCommerce awareness, guardrails, logging with optional consent, and per-page overrides.

This plugin does not modify theme templates and operates independently of your theme structure.

## Core Capabilities

- Knowledge base via admin settings and Custom Post Type
- Tone presets (friendly, professional, funny, custom system instructions)
- Multiple visual themes (iMessage, WhatsApp, Minimal, Custom)
- Guardrails (rate limiting, max turns, profanity filtering)
- Optional logging with CSV export
- WooCommerce contextual awareness (shop URL + product categories)
- Per-page behavior overrides
- UI customization controls (launcher icon, size, colors, typography, bubble styling)
- Test Connection button for API verification

## Technical Overview

- Floating interface injected via standard WordPress front-end hooks
- Secure AJAX/REST endpoint for chat processing
- API key stored in WordPress options
- Nonces and capability checks for admin actions
- Server-side rate limiting and max-turn enforcement
- Optional WooCommerce context enrichment per request
- Logging stored via Custom Post Type (when enabled)
- Modular, settings-driven architecture

## Security & Privacy

- Conversations are not stored unless logging is enabled
- Logging can require explicit user consent
- Rate limiting helps prevent abuse and API overuse
- Maximum conversation turns configurable
- Profanity filtering available
- No theme files are modified
- No data is transmitted except chat requests to the configured AI provider

## Features

- Floating chat balloon launcher
- AI-powered responses via external provider
- Knowledge base support (settings + CPT)
- Tone presets with custom system instruction option
- Visual themes with style preview
- Per-page overrides
- Guardrails and usage throttling
- Optional conversation logging with CSV export
- WooCommerce contextual awareness
- Launcher icon customization (emoji or image)
- Adjustable font size and bubble radius
- Customizable send button icon and color
- Dedicated settings submenu in WordPress admin

## Installation

1. Upload the plugin folder to:


`/wp-content/plugins/ghost-chatballoon/`


2. Activate the plugin through the **Plugins** menu in WordPress.

3. Navigate to **Chat Bot** in the admin menu.

4. Enter your API key (OpenAI-compatible API required).

5. Configure tone, knowledge base, guardrails, and UI settings.

6. Click **Test Connection** to verify API access.

## Usage

After activation:

- Configure launcher position, open-on-load behavior, and mobile visibility
- Select a tone preset or define custom system instructions
- Add knowledge entries via settings or Custom Post Type
- Enable WooCommerce context (optional)
- Configure guardrails (max turns, filtering, rate limiting)
- Enable logging and export conversations if required
- Customize UI appearance
- Optionally configure per-page overrides

The chat balloon will automatically appear on the front end once enabled.

## Requirements

- WordPress 6.5 or newer
- PHP 7.4+
- Valid OpenAI-compatible API key
- WooCommerce (optional, for context features)

## FAQ

### Does this store conversations?

Only if logging is enabled. Logging can require user consent and supports CSV export.

### Does it work without WooCommerce?

Yes. WooCommerce context features are optional.

### Can I fully customize the appearance?

Yes. Launcher icon, sizes, colors, font size, bubble radius, and send button styling are adjustable.

### What are guardrails?

Guardrails limit conversation length, apply profanity filtering, and throttle requests to prevent abuse.

### Does it modify my theme?

No. The plugin injects its interface independently and does not alter theme templates.

## Changelog

### 1.2.1

- Merged prior feature set with UI customization updates
- Ensured Settings submenu stability
- Improved saving reliability
- Confirmed Test Connection availability

### 1.2.0

- Launcher icon customization (emoji or image)
- Advanced UI styling controls

### 1.1.3

- Added Test Connection button

### 1.1.2

- Improved settings saving via admin-post

### 1.1.1

- Added dedicated Settings submenu

### 1.1.0

- Knowledge Base CPT
- Logs CPT
- Guardrails and logging system
- WooCommerce context integration
- Style preview

### 1.0.0

- Initial release

## Upgrade Notice

### 1.2.1

Recommended update. Consolidates UI customization with stability improvements.

## License

This plugin is licensed under the GPL v3 or later.