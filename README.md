# dooble cookies consent – WordPress Plugin

An accessible WordPress plugin that shows a cookie consent banner. It works in Hebrew and also supports English and Arabic. You can edit the message, the button text and the button colors, and run scripts only after the visitor accepts.

- **Version:** 3.1
- **Last updated:** 29/10/2026
- **Author:** dooble
- **Repository:** https://github.com/yuvalAshkenaz/cookies-consent/

## Installation

1. Copy the `dooble-cookie-consent` folder to `wp-content/plugins/`.
2. Activate the plugin on the WordPress **Plugins** screen.
3. Set it up under **הסכמה לקובצי Cookies** (Cookie consent) in the admin menu.

## Settings

### General
| Setting | Description |
|---|---|
| Plugin active | Turns the banner on or off on the site |
| Test mode (admins only) | Shows the banner only to logged-in administrators |
| Cookie lifetime (days) | How many days until the banner shows again to a visitor who accepted. The default is 90 |

### Banner content and design
| Setting | Description |
|---|---|
| Banner image | A logo or illustration shown next to the message |
| Message | The text shown in the banner. It can include links |
| Accept button | The accept button text |
| Accept button colors | Background color and text color |
| Hide decline button | Hides the decline button |
| Decline button | The decline button text |
| Decline button colors | Background color and text color |

The default colors meet the WCAG AA contrast requirement (at least 4.5:1):

| Button | Background | Text | Contrast |
|---|---|---|---|
| Accept | `#0f7cab` | `#ffffff` | 4.68:1 |
| Decline | `#ffffff` | `#000000` | 21:1 |

If you change the colors, check that the contrast between background and text stays at 4.5:1 or higher.

### Codes and scripts
Plain JavaScript, without `<script>` tags. The code runs inside `enableNonEssentialScripts()` when the visitor clicks accept, and on every page load after they have accepted.

Example of loading an external script:

```js
const s = document.createElement('script');
s.src = 'https://example.com/script.js';
s.async = true;
document.body.appendChild(s);
```

## Google Tag Manager (Consent Mode)

1. In the site `<head>`, before the GTM snippet, deny everything by default:

```html
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('consent', 'default', {
		'ad_storage': 'denied',
		'ad_user_data': 'denied',
		'ad_personalization': 'denied',
		'analytics_storage': 'denied'
	});
</script>
```

2. In the **Scripts to run after approval** setting, paste:

```js
gtag('consent', 'update', {
	'ad_storage': 'granted',
	'ad_user_data': 'granted',
	'ad_personalization': 'granted',
	'analytics_storage': 'granted'
});
```

More examples are in `הטמעת סקריפטים.txt`.

## How it works

- **Accept:** saves an `od_consent` cookie with the value `1` for the configured number of days, and runs the scripts.
- **Decline:** saves the choice in `sessionStorage` only. The banner stays hidden until the end of the visit, and no scripts load.
- **After a choice:** the banner is not shown to a visitor who already has the consent cookie.

## Accessibility

- The banner is a region (`role="region"`) with an `aria-label`.
- The buttons are real `button` elements, and focus moves to the accept button when the banner appears.
- The default colors meet WCAG AA contrast.

## Customizing with CSS variables

All styles are built on CSS variables that the theme can override, for example:

```css
.consent {
	--cookie-consent-bg: rgba(17, 17, 17, 0.8);
	--cookie-consent-color: #fff;
	--cookie-consent-font-size: 16px;
	--cookie-consent-btn-radius: 4px;
	--cookie-consent-accept-bg: #0f7cab;
	--cookie-consent-accept-color: #fff;
	--cookie-consent-decline-bg: #fff;
	--cookie-consent-decline-color: #000;
}
```

Colors chosen in the plugin settings are set on `.consent`, so they win over values the theme sets on `:root`.

The full list of variables is in `assets/cookie-consent.css`.

## Changelog

### 3.1 (29/10/2026)
- Added background and text color pickers for the accept and decline buttons.
- Changed the default accept button color to `#0f7cab` to meet WCAG AA contrast.
- The decline button color row is hidden together with the button.
- The version is added to the CSS and JS URLs so browsers don't load an old cached copy.

### 3.0 (18/06/2026)
- New tabbed settings screen built on the native WordPress Settings API.
