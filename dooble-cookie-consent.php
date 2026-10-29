<?php
/*
Plugin Name: dooble cookies consent
Description: An accessible cookies consent plugin with customizable message and buttons text. (Native WordPress Settings)
Author: dooble
Version: 3.1
Last updated: 29/10/2026
https://github.com/yuvalAshkenaz/cookies-consent/
*/

define('DOOBLE_COOKIE_CONSENT_VERSION', '3.1');

add_action('wp_enqueue_scripts', 'dooble_cookie_consent_enqueue_scripts');
add_action('admin_enqueue_scripts', 'dooble_cookie_consent_admin_scripts');
add_action('admin_menu', 'dooble_cookie_consent_create_menu');
add_action('admin_init', 'dooble_cookie_consent_register_settings');
add_action('init', 'dooble_cookie_consent_init');

// שמירה על משתנים גלובליים ברירת מחדל
global $default_values, $current_lang;
$default_values = array(
    'message' => array(
        'he' => 'אנו משתמשים בקובצי Cookie לתפקודים חיוניים, ניתוח נתונים ושיווק. באפשרותך לקבל או לדחות קובצי Cookie שאינם חיוניים.',
        'en' => 'We use cookies for essential functions, analytics and marketing. You can accept or decline non-essential cookies.',
        'ar' => 'نستخدم ملفات تعريف الارتباط للوظائف الأساسية والتحليلات والتسويق. يمكنك قبول أو رفض ملفات تعريف الارتباط غير الأساسية.',
    ),
    'accept' => array(
        'he' => 'מסכים',
        'en' => 'Accept',
        'ar' => 'يقبل',
    ),
    'decline' => array(
        'he' => 'לא מסכים',
        'en' => 'Decline',
        'ar' => 'انخفاض',
    ),
);

function dooble_cookie_consent_init() {
    global $current_lang;
    
    // קביעת השפה
    if (defined('ICL_LANGUAGE_CODE')) {
        $current_lang = ICL_LANGUAGE_CODE;
    } elseif (isset($GLOBALS['ICL_LANGUAGE_CODE'])) {
        $current_lang = $GLOBALS['ICL_LANGUAGE_CODE'];
    } else {
        $site_locale = get_locale();
        $current_lang = substr($site_locale, 0, 2);
    }

    $supported_langs = ['he', 'en', 'ar'];
    if (!in_array($current_lang, $supported_langs)) {
        $current_lang = 'he';
    }
}

// טעינת עיצוב וסקריפטים לאתר עצמו
function dooble_cookie_consent_enqueue_scripts() {
    wp_register_script('cookie-consent', plugin_dir_url(__FILE__) . 'assets/cookie-consent.js', array(), DOOBLE_COOKIE_CONSENT_VERSION, true);
	wp_localize_script('cookie-consent', 'doobleConsent', array(
		'cookieDays' => (int) get_option('cookie_time', 90),
	));
	wp_enqueue_script('cookie-consent');
    wp_enqueue_style('cookie-consent', plugin_dir_url(__FILE__) . 'assets/cookie-consent.css', array(), DOOBLE_COOKIE_CONSENT_VERSION, 'all');

    // צבעי כפתורים מותאמים כמשתני CSS
    $color_vars = '';
    foreach (dooble_cookie_consent_color_fields() as $option => $field) {
        $value = sanitize_hex_color(get_option($option, ''));
        if ($value) {
            $color_vars .= $field['var'] . ':' . $value . ';';
        }
    }
    if ($color_vars) {
        wp_add_inline_style('cookie-consent', '.consent{' . $color_vars . '}');
    }
}

// טעינת עיצוב וסקריפט לבחירת מדיה במערכת הניהול
function dooble_cookie_consent_admin_scripts($hook) {
    if ($hook != 'toplevel_page_cookie-consent-settings') {
        return;
    }
    wp_enqueue_media(); // טוען את ספריית המדיה של וורדפרס
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('cookie-admin-style', plugin_dir_url(__FILE__) . 'assets/admin-style.css', array(), DOOBLE_COOKIE_CONSENT_VERSION, 'all');
}

// יצירת פריט תפריט ראשי עצמאי בוורדפרס
function dooble_cookie_consent_create_menu() {
    add_menu_page(
        'הגדרות הסכמה לקובצי Cookies',
        'הסכמה לקובצי Cookies',
        'manage_options',
        'cookie-consent-settings',
        'dooble_cookie_consent_render_admin_page',
        'dashicons-privacy',
        80
    );
}

// רישום ההגדרות במערכת
function dooble_cookie_consent_register_settings() {
    register_setting('dooble_cookie_group', 'cookie_time');
    register_setting('dooble_cookie_group', 'cookie_img');
    register_setting('dooble_cookie_group', 'cookie_message');
    register_setting('dooble_cookie_group', 'cookie_accept_btn_text');
    register_setting('dooble_cookie_group', 'cookie_decline_btn_text');
    register_setting('dooble_cookie_group', 'cookie_hide_decline_btn');
    register_setting('dooble_cookie_group', 'cookie_active');
    register_setting('dooble_cookie_group', 'cookie_admin_active');
    register_setting('dooble_cookie_group', 'cookie_scripts_after_approve');
    foreach (array_keys(dooble_cookie_consent_color_fields()) as $color_option) {
        register_setting('dooble_cookie_group', $color_option, array('sanitize_callback' => 'sanitize_hex_color'));
    }
}

// הגדרות צבעי הכפתורים: שם אפשרות => משתנה CSS וברירת מחדל
function dooble_cookie_consent_color_fields() {
    return array(
        'cookie_accept_btn_bg'     => array('var' => '--cookie-consent-accept-bg', 'default' => '#0f7cab'),
        'cookie_accept_btn_color'  => array('var' => '--cookie-consent-accept-color', 'default' => '#ffffff'),
        'cookie_decline_btn_bg'    => array('var' => '--cookie-consent-decline-bg', 'default' => '#ffffff'),
        'cookie_decline_btn_color' => array('var' => '--cookie-consent-decline-color', 'default' => '#000000'),
    );
}

// רינדור זוג בוחרי צבע (רקע + טקסט) לכפתור
function dooble_cookie_consent_render_color_pair($bg_option, $color_option, $label) {
    $fields = dooble_cookie_consent_color_fields();
    $pair = array($bg_option => 'רקע', $color_option => 'טקסט');
    ?>
    <div class="dcc-color-pair">
        <?php foreach ($pair as $option => $part) : ?>
            <div class="dcc-color-item">
                <label for="<?php echo esc_attr($option); ?>"><?php echo esc_html($part . ' ' . $label); ?></label>
                <input type="text" name="<?php echo esc_attr($option); ?>" id="<?php echo esc_attr($option); ?>" value="<?php echo esc_attr(get_option($option, '') ?: $fields[$option]['default']); ?>" data-default-color="<?php echo esc_attr($fields[$option]['default']); ?>" class="dcc-color-field" />
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

// רינדור דף ההגדרות ב-Admin עם עיצוב מודרני
function dooble_cookie_consent_render_admin_page() {
    global $default_values, $current_lang;
    dooble_cookie_consent_init(); // ודאות שהשפה מעודכנת
    ?>
    <div class="wrap dooble-cookie-admin-wrap">
		<h1 style="display:none"></h1>
        <div class="dcc-header">
            <div class="dcc-header-title">
                <span class="dashicons dashicons-privacy"></span>
                <h1>הגדרות הסכמה לקובצי Cookies</h1>
            </div>
            <div class="dcc-version">גרסה <?php echo DOOBLE_COOKIE_CONSENT_VERSION; ?></div>
        </div>

        <form method="post" action="options.php">
            <?php
            settings_fields('dooble_cookie_group');
            do_settings_sections('dooble_cookie_group');
            
            // שליפת ערכים קיימים או ברירות מחדל
            $cookie_time = get_option('cookie_time', 90);
            $cookie_img = get_option('cookie_img', '');
            $cookie_message = get_option('cookie_message', $default_values['message'][$current_lang]);
            $accept_text = get_option('cookie_accept_btn_text', $default_values['accept'][$current_lang]);
            $decline_text = get_option('cookie_decline_btn_text', $default_values['decline'][$current_lang]);
            $hide_decline = get_option('cookie_hide_decline_btn', 0);
            $active = get_option('cookie_active', 1);
            $admin_active = get_option('cookie_admin_active', 0);
            $scripts = get_option('cookie_scripts_after_approve', '');
            ?>

            <!-- ניווט כרטיסיות פנימי -->
            <h2 class="nav-tab-wrapper dcc-nav-tabs">
                <a href="#tab-general" class="nav-tab nav-tab-active">הגדרות כלליות</a>
                <a href="#tab-design" class="nav-tab">תוכן ועיצוב הבאנר</a>
                <a href="#tab-scripts" class="nav-tab">קודים וסקריפטים</a>
            </h2>

            <div class="dcc-tabs-content">
                
                <!-- כרטיסייה 1: כללי -->
                <div id="tab-general" class="dcc-tab-panel dcc-active">
                    <div class="dcc-card">
                        <div class="dcc-row toggle-row">
                            <div class="dcc-label">
                                <label>תוסף פעיל באתר</label>
                                <span class="dcc-desc">הפעלה או כיבוי מלא של באנר העוגיות ב-Frontend</span>
                            </div>
                            <div class="dcc-field">
                                <label class="dcc-switch">
                                    <input type="checkbox" name="cookie_active" value="1" <?php checked(1, $active); ?> />
                                    <span class="dcc-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="dcc-row toggle-row">
                            <div class="dcc-label">
                                <label>מצב בדיקה (מנהלים בלבד)</label>
                                <span class="dcc-desc">הבאנר יוצג רק למשתמשים מחוברים עם הרשאות ניהול</span>
                            </div>
                            <div class="dcc-field">
                                <label class="dcc-switch">
                                    <input type="checkbox" name="cookie_admin_active" value="1" <?php checked(1, $admin_active); ?> />
                                    <span class="dcc-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="dcc-row">
                            <div class="dcc-label">
                                <label>תוקף העוגייה (בימים)</label>
                                <span class="dcc-desc">אחרי כמה ימים הבאנר יקפוץ שוב לגולש שביצע בחירה?</span>
                            </div>
                            <div class="dcc-field">
                                <input type="number" name="cookie_time" value="<?php echo esc_attr($cookie_time); ?>" step="1" class="dcc-input-number" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- כרטיסייה 2: עיצוב ותוכן -->
                <div id="tab-design" class="dcc-tab-panel">
                    <div class="dcc-card">
                        <div class="dcc-row">
                            <div class="dcc-label">
                                <label>תמונת הבאנר</label>
                                <span class="dcc-desc">לוגו או איור שיופיע לצד הודעת העוגיות</span>
                            </div>
                            <div class="dcc-field">
                                <div class="dcc-media-uploader">
                                    <input type="hidden" name="cookie_img" id="cookie_img" value="<?php echo esc_attr($cookie_img); ?>" />
                                    <div class="dcc-buttons-group">
                                        <button type="button" class="button button-secondary upload_image_button">בחר תמונה</button>
                                        <button type="button" class="button remove_image_button dcc-btn-danger" style="<?php echo !$cookie_img ? 'display:none;' : ''; ?>">הסר תמונה</button>
                                    </div>
                                    <div class="cookie_img_preview dcc-preview-box">
                                        <?php if ($cookie_img) { echo wp_get_attachment_image($cookie_img, 'thumbnail'); } else { echo '<span class="dcc-no-img">אין תמונה נבחרת</span>'; } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dcc-row">
                            <div class="dcc-label">
                                <label>תוכן ההודעה</label>
                                <span class="dcc-desc">הטקסט שיופיע לגולשים בתוך הבאנר</span>
                            </div>
                            <div class="dcc-field">
                                <?php wp_editor($cookie_message, 'cookie_message', array('textarea_rows' => 6, 'media_buttons' => false)); ?>
                            </div>
                        </div>

                        <div class="dcc-row">
                            <div class="dcc-label">
                                <label>כפתור אישור</label>
                            </div>
                            <div class="dcc-field">
                                <input type="text" name="cookie_accept_btn_text" value="<?php echo esc_attr($accept_text); ?>" class="regular-text dcc-input-text" />
                            </div>
                        </div>

                        <div class="dcc-row">
                            <div class="dcc-label">
                                <label>צבעי כפתור אישור</label>
                                <span class="dcc-desc">צבע הרקע וצבע הטקסט של הכפתור</span>
                            </div>
                            <div class="dcc-field">
                                <?php dooble_cookie_consent_render_color_pair('cookie_accept_btn_bg', 'cookie_accept_btn_color', 'כפתור אישור'); ?>
                            </div>
                        </div>

                        <div class="dcc-row toggle-row">
                            <div class="dcc-label">
                                <label>הסתר כפתור סירוב</label>
                                <span class="dcc-desc">הסתרת האפשרות של הגולש לסרב לחלוטין</span>
                            </div>
                            <div class="dcc-field">
                                <label class="dcc-switch">
                                    <input type="checkbox" name="cookie_hide_decline_btn" value="1" <?php checked(1, $hide_decline); ?> id="dcc_hide_decline_toggle" />
                                    <span class="dcc-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="dcc-row" id="dcc_decline_text_row" style="<?php echo $hide_decline ? 'display:none;' : ''; ?>">
                            <div class="dcc-label">
                                <label>כפתור אי-הסכמה / סירוב</label>
                            </div>
                            <div class="dcc-field">
                                <input type="text" name="cookie_decline_btn_text" value="<?php echo esc_attr($decline_text); ?>" class="regular-text dcc-input-text" />
                            </div>
                        </div>

                        <div class="dcc-row" id="dcc_decline_colors_row" style="<?php echo $hide_decline ? 'display:none;' : ''; ?>">
                            <div class="dcc-label">
                                <label>צבעי כפתור סירוב</label>
                                <span class="dcc-desc">צבע הרקע וצבע הטקסט של הכפתור</span>
                            </div>
                            <div class="dcc-field">
                                <?php dooble_cookie_consent_render_color_pair('cookie_decline_btn_bg', 'cookie_decline_btn_color', 'כפתור סירוב'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- כרטיסייה 3: סקריפטים -->
                <div id="tab-scripts" class="dcc-tab-panel">
                    <div class="dcc-card">
                        <div class="dcc-row">
                            <div class="dcc-label">
                                <label>סקריפטים להפעלה לאחר אישור</label>
                                <span class="dcc-desc">כאן ניתן להזין קוד JavaScript נקי (ללא תגיות &lt;script&gt;) שירוץ אוטומטית ברגע שהמשתמש לוחץ "מסכים".</span>
                            </div>
                            <div class="dcc-field">
                                <textarea name="cookie_scripts_after_approve" rows="12" class="large-text code dcc-textarea-code" placeholder="// דוגמה: console.log('Cookies approved!');"><?php echo esc_textarea($scripts); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="dcc-submit-zone">
                <?php submit_button('שמירת כל השינויים', 'primary button-large'); ?>
            </div>
        </form>
    </div>

    <!-- סקריפט אינטראקטיבי לניהול הכרטיסיות והתצוגה הדינמית -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {

		// ניהול כרטיסיות
		document.querySelectorAll('.dcc-nav-tabs .nav-tab').forEach(function (tab) {
			tab.addEventListener('click', function (e) {
				e.preventDefault();
				document.querySelectorAll('.dcc-nav-tabs .nav-tab').forEach(function (t) {
					t.classList.remove('nav-tab-active');
				});
				this.classList.add('nav-tab-active');

				document.querySelectorAll('.dcc-tab-panel').forEach(function (panel) {
					panel.classList.remove('dcc-active');
				});
				var target = this.getAttribute('href');
				document.querySelector(target).classList.add('dcc-active');
			});
		});

		// הצגה/הסתרה של שורת כפתור סירוב
		var hideDeclineToggle = document.getElementById('dcc_hide_decline_toggle');
		var declineRows = [document.getElementById('dcc_decline_text_row'), document.getElementById('dcc_decline_colors_row')];
		if (hideDeclineToggle) {
			hideDeclineToggle.addEventListener('change', function () {
				var checked = this.checked;
				declineRows.forEach(function (row) {
					if (row) row.style.display = checked ? 'none' : '';
				});
			});
		}

		// Button color pickers
		if (window.jQuery && jQuery.fn.wpColorPicker) {
			jQuery('.dcc-color-field').wpColorPicker();
		}

		// בחירת תמונה מהמדיה
		var uploadBtn = document.querySelector('.upload_image_button');
		if (uploadBtn) {
			uploadBtn.addEventListener('click', function (e) {
				e.preventDefault();
				var custom_uploader = wp.media({
					title: 'בחר תמונה לבאנר',
					button: { text: 'בחר תמונה' },
					multiple: false
				}).on('select', function () {
					var attachment = custom_uploader.state().get('selection').first().toJSON();
					document.getElementById('cookie_img').value = attachment.id;
					document.querySelector('.cookie_img_preview').innerHTML =
						'<img src="' + attachment.url + '" style="max-width:120px;border-radius:4px;display:block;" />';
					var removeBtn = document.querySelector('.remove_image_button');
					if (removeBtn) removeBtn.style.display = '';
					custom_uploader.close();
				}).open();
			});
		}

		// הסרת תמונה
		var removeBtn = document.querySelector('.remove_image_button');
		if (removeBtn) {
			removeBtn.addEventListener('click', function (e) {
				e.preventDefault();
				document.getElementById('cookie_img').value = '';
				document.querySelector('.cookie_img_preview').innerHTML =
					'<span class="dcc-no-img">אין תמונה נבחרת</span>';
				this.style.display = 'none';
			});
		}

	});
    </script>
    <?php
}

// הצגת הבאנר באתר (Frontend)
add_action('wp_footer', 'dooble_cookie_consent_banner');
function dooble_cookie_consent_banner() {
    global $default_values, $current_lang;
    dooble_cookie_consent_init(); // ווידוא קבלת השפה הנוכחית ב-Frontend
    
    $cookie_active = get_option('cookie_active', 1);
    $cookie_admin_active = get_option('cookie_admin_active', 0);
    $cookie_scripts_after_approve = get_option('cookie_scripts_after_approve', '');
    
    // 1. הדפסת הסקריפטים שרצים לאחר אישור
    echo '<script>
    function enableNonEssentialScripts() {' .
        $cookie_scripts_after_approve . '
    }
    </script>';
    
    // 2. תנאי יציאה מוקדמת
    if (isset($_COOKIE['od_consent']) || !$cookie_active) {
        return;
    }
    if ($cookie_admin_active && !current_user_can('administrator')) {
        return;
    }
    
    // 3. שליפת שאר הנתונים
    $cookie_img_id = get_option('cookie_img', '');
    $cookie_message = get_option('cookie_message', '');
    $accept_btn_text = get_option('cookie_accept_btn_text', '');
    $decline_btn_text = get_option('cookie_decline_btn_text', '');
    $hide_decline_btn = get_option('cookie_hide_decline_btn', 0);
    
    // ערכי ברירת מחדל אם ריק
    if (empty($cookie_message)) {
        $cookie_message = $default_values['message'][$current_lang];
    }
    if (empty($accept_btn_text)) {
        $accept_btn_text = $default_values['accept'][$current_lang];
    }
    if (empty($decline_btn_text)) {
        $decline_btn_text = $default_values['decline'][$current_lang];
    }

    // הכנת ה-HTML של התמונה
    $cookie_img_html = '';
    if (!empty($cookie_img_id)) {
        $cookie_img_html = wp_get_attachment_image($cookie_img_id, 'full', false, array('class' => 'cookie-img'));
    }
    
    // הכנת ה-HTML של כפתור הסירוב
    $decline_btn_html = '';
    if (!$hide_decline_btn) {
        $decline_btn_html = '
        <button type="button" id="od-decline" class="consent-decline" aria-controls="cookie-banner"> 
            ' . esc_html($decline_btn_text) . '
        </button>';
    }

    // הדפסת הבאנר
    echo '
    <div id="cookie-banner" class="consent" role="region" aria-label="' . esc_attr__('Cookie consent', 'dooble_cookies_consent') . '">
        <div class="consent-inner1">
            ' . $cookie_img_html . '
            <div class="consent-inner2">
                <div class="consent-content">
                    ' . wp_kses_post($cookie_message) . '
                </div>
                <div class="consent-btns">
                    ' . $decline_btn_html . '
                    <button type="button" id="od-accept" class="consent-accept" aria-controls="cookie-banner"> 
                        ' . esc_html($accept_btn_text) . '
                    </button>
                </div>
            </div>
        </div>
    </div>
    ';
}