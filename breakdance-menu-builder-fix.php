<?php
/**
 * Plugin Name: Breakdance Menu Builder Fix
 * Plugin URI: https://github.com/eMarketingcy/Breakdance-Menu-Builder-Fix
 * Description: Fixes active menu state issues in Breakdance Menu Builder for dropdown items and mobile navigation
 * Version: 2.1.1
 * Author: eMarketing Cyprus
 * License: GPL v2 or later
 * Text Domain: breakdance-menu-builder-fix
 */

if (!defined('ABSPATH')) {
    exit;
}

class BreakdanceMenuFix {

    private $plugin_url;
    private $plugin_path;

    public function __construct() {
        $this->plugin_url  = plugin_dir_url(__FILE__);
        $this->plugin_path = plugin_dir_path(__FILE__);

        // Ensure is_plugin_active is available when needed.
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        add_action('init', array($this, 'init'));
    }

    public function init() {
        // Front & Admin hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_custom_css'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Show admin notice if Breakdance is not active
        if (!$this->is_breakdance_active()) {
            add_action('admin_notices', array($this, 'breakdance_not_active_notice'));
        }
    }

    /**
     * Check if Breakdance plugin is active
     */
    private function is_breakdance_active() {
        return class_exists('Breakdance\PluginAPI\PluginAPI') || is_plugin_active('breakdance/plugin.php');
    }

    /**
     * Show admin notice if Breakdance is not active
     */
    public function breakdance_not_active_notice() {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php echo esc_html__('Breakdance Menu Builder Fix requires the Breakdance plugin to be installed and activated.', 'breakdance-menu-builder-fix'); ?></p>
        </div>
        <?php
    }

    /**
     * Enqueue JavaScript files
     */
    public function enqueue_scripts() {
        if (!$this->is_breakdance_active()) {
            return;
        }

        wp_enqueue_script(
            'breakdance-menu-fix-js',
            $this->plugin_url . 'assets/js/menu-fix.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Pass settings to JavaScript (keep current behavior)
        $settings = array(
            'mobileBreakpoint'    => (int) get_option('bdmf_mobile_breakpoint', 480),
            'activeColor'         => (string) get_option('bdmf_active_color', '#ff0000'),
            'activeBorderColor'   => (string) get_option('bdmf_active_border_color', '#ff0000'),
            'activeBorderWidth'   => (string) get_option('bdmf_active_border_width', '2'),
            'activeBorderStyle'   => (string) get_option('bdmf_active_border_style', 'solid'),
            'activeBorderPosition'=> (string) get_option('bdmf_active_border_position', 'bottom'),
            'enableBorderFix'     => (bool) get_option('bdmf_enable_border_fix', true),
            'enableMobileFix'     => (bool) get_option('bdmf_enable_mobile_fix', true),
            'enableDropdownFix'   => (bool) get_option('bdmf_enable_dropdown_fix', true),
        );

        wp_localize_script('breakdance-menu-fix-js', 'bdmfSettings', $settings);
    }

    /**
     * Add custom CSS to head
     * (Fixed extra stray '}' and kept the rest intact)
     */
    public function add_custom_css() {
        if (!$this->is_breakdance_active()) {
            return;
        }

        // Cast + sanitize values used in CSS
        $active_color         = $this->sanitize_color(get_option('bdmf_active_color', '#ff0000'));
        $enable_dropdown_fix  = (bool) get_option('bdmf_enable_dropdown_fix', true);
        $enable_border_fix    = (bool) get_option('bdmf_enable_border_fix', true);
        $border_color         = $this->sanitize_color(get_option('bdmf_active_border_color', '#ff0000'));
        $border_width         = (int) $this->sanitize_int_range(get_option('bdmf_active_border_width', '2'), 0, 10);
        $border_style         = $this->sanitize_border_style(get_option('bdmf_active_border_style', 'solid'));
        $border_position      = $this->sanitize_border_position(get_option('bdmf_active_border_position', 'bottom'));

        if (!$enable_dropdown_fix && !$enable_border_fix) {
            return;
        }
        ?>
        <style type="text/css" id="breakdance-menu-fix-css">
        <?php if ($enable_dropdown_fix): ?>
        /* Breakdance Menu Builder Fix - Active dropdown items */
        .breakdance-dropdown-item--active .breakdance-dropdown-link__label,
        .breakdance-dropdown-item--active .breakdance-dropdown-link__text {
            color: <?php echo esc_attr($active_color); ?> !important;
        }

        /* Additional styling for better visual feedback */
        .breakdance-dropdown-item--active {
            background-color: rgba(<?php echo esc_html($this->hex_to_rgb($active_color)); ?>, 0.05);
            padding: 8px 8px 8px 0px;
            border-radius: 5px;
        }

        /* Parent menu item active state when child is active */
        .bdmf-parent-active > a,
        .bdmf-parent-active .breakdance-menu-link {
            color: <?php echo esc_attr($active_color); ?> !important;
        }
        <?php endif; ?>

        <?php if ($enable_border_fix): ?>
        /* Active menu item border styling */
        .bde-menu-dropdown.breakdance-menu-item.bdmf-parent-active .breakdance-dropdown {
            border-<?php echo esc_attr($border_position); ?>: <?php echo esc_attr($border_width); ?>px <?php echo esc_attr($border_style); ?> <?php echo esc_attr($border_color); ?> !important;
        }

        /* Ensure proper spacing for borders */
        .breakdance-menu-link {
            position: relative;
            transition: all 0.3s ease;
        }
        <?php endif; ?>
        </style>
        <?php
    }

    /**
     * Convert hex color to RGB (kept same behavior)
     */
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', (string) $hex);
        if (strlen($hex) === 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2)
                 . str_repeat(substr($hex, 1, 1), 2)
                 . str_repeat(substr($hex, 2, 1), 2);
        }
        $parts = str_split(substr($hex, 0, 6), 2);
        $parts = array_map(function($h){ return hexdec($h); }, $parts);
        return implode(',', $parts);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Breakdance Menu Fix Settings',
            'Breakdance Menu Fix',
            'manage_options',
            'breakdance-menu-fix',
            array($this, 'admin_page')
        );
    }

    /**
     * Register plugin settings (with sanitize_callback for Plugin Check)
     */
    public function register_settings() {

        register_setting('bdmf_settings', 'bdmf_active_color', array(
            'type'              => 'string',
            'sanitize_callback' => array($this, 'sanitize_color'),
            'default'           => '#ff0000',
            'show_in_rest'      => false,
        ));

        register_setting('bdmf_settings', 'bdmf_active_border_color', array(
            'type'              => 'string',
            'sanitize_callback' => array($this, 'sanitize_color'),
            'default'           => '#ff0000',
            'show_in_rest'      => false,
        ));

        register_setting('bdmf_settings', 'bdmf_active_border_width', array(
            'type'              => 'integer',
            'sanitize_callback' => function($v){ return $this->sanitize_int_range($v, 0, 10); },
            'default'           => 2,
            'show_in_rest'      => false,
        ));

        register_setting('bdmf_settings', 'bdmf_active_border_style', array(
            'type'              => 'string',
            'sanitize_callback' => array($this, 'sanitize_border_style'),
            'default'           => 'solid',
            'show_in_rest'      => false,
        ));

        register_setting('bdmf_settings', 'bdmf_active_border_position', array(
            'type'              => 'string',
            'sanitize_callback' => array($this, 'sanitize_border_position'),
            'default'           => 'bottom',
            'show_in_rest'      => false,
        ));

        register_setting('bdmf_settings', 'bdmf_enable_border_fix', array(
            'type'              => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_bool'),
            'default'           => 1,
            'show_in_rest'      => false,
        ));

        register_setting('bdmf_settings', 'bdmf_mobile_breakpoint', array(
            'type'              => 'integer',
            'sanitize_callback' => function($v){ return $this->sanitize_int_range($v, 320, 3000); },
            'default'           => 480,
            'show_in_rest'      => false,
        ));

        register_setting('bdmf_settings', 'bdmf_enable_dropdown_fix', array(
            'type'              => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_bool'),
            'default'           => 1,
            'show_in_rest'      => false,
        ));

        register_setting('bdmf_settings', 'bdmf_enable_mobile_fix', array(
            'type'              => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_bool'),
            'default'           => 1,
            'show_in_rest'      => false,
        ));
    }

    /**
     * Admin page content (kept your UI & structure)
     */
    public function admin_page() {
        // Enqueue admin styles
        wp_enqueue_style('bdmf-admin-styles', $this->plugin_url . 'assets/css/admin.css', array(), '2.1.0');
        ?>
        <div class="wrap bdmf-admin-wrap">
            <div class="bdmf-header">
                <div class="bdmf-header-content">
                    <div class="bdmf-logo" aria-hidden="true">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" focusable="false">
                            <rect width="40" height="40" rx="8" fill="#667eea"/>
                            <path d="M12 20L18 26L28 14" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="bdmf-header-text">
                        <h1><?php echo esc_html__('Breakdance Menu Builder Fix', 'breakdance-menu-builder-fix'); ?></h1>
                        <p><?php echo esc_html__('Professional menu fixes for Breakdance Builder', 'breakdance-menu-builder-fix'); ?></p>
                    </div>
                </div>
                <div class="bdmf-version">
                    <span class="bdmf-version-badge">v2.1.1</span>
                </div>
            </div>

            <?php if (!$this->is_breakdance_active()): ?>
                <div class="bdmf-alert bdmf-alert-error">
                    <div class="bdmf-alert-icon" aria-hidden="true">⚠️</div>
                    <div class="bdmf-alert-content">
                        <strong><?php echo esc_html__('Breakdance Required', 'breakdance-menu-builder-fix'); ?></strong>
                        <p><?php echo esc_html__('This plugin requires Breakdance to be installed and activated.', 'breakdance-menu-builder-fix'); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="bdmf-main-content">
                <div class="bdmf-settings-grid">
                    <div class="bdmf-settings-panel">
                        <form method="post" action="options.php" class="bdmf-form">
                            <?php
                            settings_fields('bdmf_settings');
                            do_settings_sections('bdmf_settings');
                            ?>

                            <!-- Feature Toggles -->
                            <div class="bdmf-section">
                                <h2 class="bdmf-section-title">
                                    <span class="bdmf-section-icon" aria-hidden="true">🔧</span>
                                    <?php echo esc_html__('Feature Controls', 'breakdance-menu-builder-fix'); ?>
                                </h2>

                                <div class="bdmf-toggle-group">
                                    <div class="bdmf-toggle-item">
                                        <label class="bdmf-toggle">
                                            <input type="checkbox" name="bdmf_enable_dropdown_fix" value="1" <?php checked((bool) get_option('bdmf_enable_dropdown_fix', true)); ?> />
                                            <span class="bdmf-toggle-slider"></span>
                                        </label>
                                        <div class="bdmf-toggle-content">
                                            <h4><?php echo esc_html__('Dropdown Menu Fix', 'breakdance-menu-builder-fix'); ?></h4>
                                            <p><?php echo esc_html__('Fix active states for dropdown menu items', 'breakdance-menu-builder-fix'); ?></p>
                                        </div>
                                    </div>

                                    <div class="bdmf-toggle-item">
                                        <label class="bdmf-toggle">
                                            <input type="checkbox" name="bdmf_enable_border_fix" value="1" <?php checked((bool) get_option('bdmf_enable_border_fix', true)); ?> />
                                            <span class="bdmf-toggle-slider"></span>
                                        </label>
                                        <div class="bdmf-toggle-content">
                                            <h4><?php echo esc_html__('Active Menu Borders', 'breakdance-menu-builder-fix'); ?></h4>
                                            <p><?php echo esc_html__('Add customizable borders to active menu items', 'breakdance-menu-builder-fix'); ?></p>
                                        </div>
                                    </div>

                                    <div class="bdmf-toggle-item">
                                        <label class="bdmf-toggle">
                                            <input type="checkbox" name="bdmf_enable_mobile_fix" value="1" <?php checked((bool) get_option('bdmf_enable_mobile_fix', true)); ?> />
                                            <span class="bdmf-toggle-slider"></span>
                                        </label>
                                        <div class="bdmf-toggle-content">
                                            <h4><?php echo esc_html__('Mobile Menu Fix', 'breakdance-menu-builder-fix'); ?></h4>
                                            <p><?php echo esc_html__('Fix mobile menu behavior for dropdown toggles', 'breakdance-menu-builder-fix'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Color Settings -->
                            <div class="bdmf-section">
                                <h2 class="bdmf-section-title">
                                    <span class="bdmf-section-icon" aria-hidden="true">🎨</span>
                                    <?php echo esc_html__('Color Settings', 'breakdance-menu-builder-fix'); ?>
                                </h2>

                                <div class="bdmf-color-grid">
                                    <div class="bdmf-color-item">
                                        <label><?php echo esc_html__('Active Menu Color', 'breakdance-menu-builder-fix'); ?></label>
                                        <div class="bdmf-color-input">
                                            <?php $c1 = (string) get_option('bdmf_active_color', '#667eea'); ?>
                                            <input type="color" name="bdmf_active_color" value="<?php echo esc_attr($c1); ?>" />
                                            <span class="bdmf-color-value"><?php echo esc_html($c1); ?></span>
                                        </div>
                                    </div>

                                    <div class="bdmf-color-item">
                                        <label><?php echo esc_html__('Border Color', 'breakdance-menu-builder-fix'); ?></label>
                                        <div class="bdmf-color-input">
                                            <?php $c2 = (string) get_option('bdmf_active_border_color', '#667eea'); ?>
                                            <input type="color" name="bdmf_active_border_color" value="<?php echo esc_attr($c2); ?>" />
                                            <span class="bdmf-color-value"><?php echo esc_html($c2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Border Settings -->
                            <div class="bdmf-section">
                                <h2 class="bdmf-section-title">
                                    <span class="bdmf-section-icon" aria-hidden="true">📐</span>
                                    <?php echo esc_html__('Border Settings', 'breakdance-menu-builder-fix'); ?>
                                </h2>

                                <div class="bdmf-border-controls">
                                    <div class="bdmf-control-group">
                                        <label><?php echo esc_html__('Border Width', 'breakdance-menu-builder-fix'); ?></label>
                                        <div class="bdmf-range-input">
                                            <?php $w = (int) get_option('bdmf_active_border_width', 2); ?>
                                            <input type="range" name="bdmf_active_border_width" min="1" max="10" value="<?php echo esc_attr($w); ?>" class="bdmf-range" />
                                            <span class="bdmf-range-value"><?php echo esc_html($w); ?>px</span>
                                        </div>
                                    </div>

                                    <div class="bdmf-control-group">
                                        <label><?php echo esc_html__('Border Style', 'breakdance-menu-builder-fix'); ?></label>
                                        <?php $style = (string) get_option('bdmf_active_border_style', 'solid'); ?>
                                        <select name="bdmf_active_border_style" class="bdmf-select">
                                            <option value="solid"  <?php selected($style, 'solid');  ?>><?php echo esc_html__('Solid', 'breakdance-menu-builder-fix'); ?></option>
                                            <option value="dashed" <?php selected($style, 'dashed'); ?>><?php echo esc_html__('Dashed', 'breakdance-menu-builder-fix'); ?></option>
                                            <option value="dotted" <?php selected($style, 'dotted'); ?>><?php echo esc_html__('Dotted', 'breakdance-menu-builder-fix'); ?></option>
                                            <option value="double" <?php selected($style, 'double'); ?>><?php echo esc_html__('Double', 'breakdance-menu-builder-fix'); ?></option>
                                        </select>
                                    </div>

                                    <div class="bdmf-control-group">
                                        <label><?php echo esc_html__('Border Position', 'breakdance-menu-builder-fix'); ?></label>
                                        <?php $pos = (string) get_option('bdmf_active_border_position', 'bottom'); ?>
                                        <div class="bdmf-radio-group">
                                            <label class="bdmf-radio">
                                                <input type="radio" name="bdmf_active_border_position" value="top"    <?php checked($pos, 'top'); ?> />
                                                <span><?php echo esc_html__('Top', 'breakdance-menu-builder-fix'); ?></span>
                                            </label>
                                            <label class="bdmf-radio">
                                                <input type="radio" name="bdmf_active_border_position" value="bottom" <?php checked($pos, 'bottom'); ?> />
                                                <span><?php echo esc_html__('Bottom', 'breakdance-menu-builder-fix'); ?></span>
                                            </label>
                                            <label class="bdmf-radio">
                                                <input type="radio" name="bdmf_active_border_position" value="left"   <?php checked($pos, 'left'); ?> />
                                                <span><?php echo esc_html__('Left', 'breakdance-menu-builder-fix'); ?></span>
                                            </label>
                                            <label class="bdmf-radio">
                                                <input type="radio" name="bdmf_active_border_position" value="right"  <?php checked($pos, 'right'); ?> />
                                                <span><?php echo esc_html__('Right', 'breakdance-menu-builder-fix'); ?></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Settings -->
                            <div class="bdmf-section">
                                <h2 class="bdmf-section-title">
                                    <span class="bdmf-section-icon" aria-hidden="true">📱</span>
                                    <?php echo esc_html__('Mobile Settings', 'breakdance-menu-builder-fix'); ?>
                                </h2>

                                <div class="bdmf-control-group">
                                    <label><?php echo esc_html__('Mobile Breakpoint', 'breakdance-menu-builder-fix'); ?></label>
                                    <div class="bdmf-range-input">
                                        <?php $bp = (int) get_option('bdmf_mobile_breakpoint', 480); ?>
                                        <input type="range" name="bdmf_mobile_breakpoint" min="320" max="1200" step="10" value="<?php echo esc_attr($bp); ?>" class="bdmf-range" />
                                        <span class="bdmf-range-value"><?php echo esc_html($bp); ?>px</span>
                                    </div>
                                    <p class="bdmf-help-text"><?php echo esc_html__('Screen width below which mobile menu behavior applies', 'breakdance-menu-builder-fix'); ?></p>
                                </div>
                            </div>

                            <div class="bdmf-form-actions">
                                <?php submit_button(__('Save Settings', 'breakdance-menu-builder-fix'), 'primary bdmf-button-primary', 'submit', false); ?>
                                <button type="button" class="bdmf-button-secondary" onclick="location.reload()"><?php echo esc_html__('Reset Preview', 'breakdance-menu-builder-fix'); ?></button>
                            </div>
                        </form>
                    </div>

                    <div class="bdmf-sidebar">
                        <div class="bdmf-info-card">
                            <div class="bdmf-info-header">
                                <h3><?php echo esc_html__('Plugin Status', 'breakdance-menu-builder-fix'); ?></h3>
                                <span class="bdmf-status-badge bdmf-status-active"><?php echo esc_html__('Active', 'breakdance-menu-builder-fix'); ?></span>
                            </div>
                            <div class="bdmf-info-content">
                                <div class="bdmf-stat">
                                    <span class="bdmf-stat-label"><?php echo esc_html__('Fixes Applied', 'breakdance-menu-builder-fix'); ?></span>
                                    <span class="bdmf-stat-value">
                                        <?php
                                        $active_fixes = 0;
                                        if ((bool) get_option('bdmf_enable_dropdown_fix', true)) $active_fixes++;
                                        if ((bool) get_option('bdmf_enable_border_fix', true))   $active_fixes++;
                                        if ((bool) get_option('bdmf_enable_mobile_fix', true))   $active_fixes++;
                                        echo esc_html($active_fixes . '/3');
                                        ?>
                                    </span>
                                </div>
                                <div class="bdmf-stat">
                                    <span class="bdmf-stat-label"><?php echo esc_html__('Breakdance Status', 'breakdance-menu-builder-fix'); ?></span>
                                    <span class="bdmf-stat-value <?php echo $this->is_breakdance_active() ? 'bdmf-status-ok' : 'bdmf-status-error'; ?>">
                                        <?php echo $this->is_breakdance_active() ? esc_html__('Active', 'breakdance-menu-builder-fix') : esc_html__('Inactive', 'breakdance-menu-builder-fix'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="bdmf-info-card">
                            <div class="bdmf-info-header">
                                <h3><?php echo esc_html__('Issues Fixed', 'breakdance-menu-builder-fix'); ?></h3>
                            </div>
                            <div class="bdmf-info-content">
                                <ul class="bdmf-feature-list">
                                    <li class="bdmf-feature-item">
                                        <span class="bdmf-feature-icon" aria-hidden="true">✅</span>
                                        <?php echo esc_html__('Dropdown active states', 'breakdance-menu-builder-fix'); ?>
                                    </li>
                                    <li class="bdmf-feature-item">
                                        <span class="bdmf-feature-icon" aria-hidden="true">✅</span>
                                        <?php echo esc_html__('Parent menu highlighting', 'breakdance-menu-builder-fix'); ?>
                                    </li>
                                    <li class="bdmf-feature-item">
                                        <span class="bdmf-feature-icon" aria-hidden="true">✅</span>
                                        <?php echo esc_html__('Mobile menu behavior', 'breakdance-menu-builder-fix'); ?>
                                    </li>
                                    <li class="bdmf-feature-item">
                                        <span class="bdmf-feature-icon" aria-hidden="true">✅</span>
                                        <?php echo esc_html__('Custom border controls', 'breakdance-menu-builder-fix'); ?>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="bdmf-info-card">
                            <div class="bdmf-info-header">
                                <h3><?php echo esc_html__('Need Help?', 'breakdance-menu-builder-fix'); ?></h3>
                            </div>
                            <div class="bdmf-info-content">
                                <p><?php echo esc_html__('Having issues? Check our documentation or contact support.', 'breakdance-menu-builder-fix'); ?></p>
                                <div class="bdmf-help-links">
                                    <a href="https://github.com/eMarketingcy/Breakdance-Menu-Builder-Fix/blob/main/README.md" class="bdmf-help-link"><?php echo esc_html__('Documentation', 'breakdance-menu-builder-fix'); ?></a>
                                    <a href="https://github.com/eMarketingcy/Breakdance-Menu-Builder-Fix/issues" class="bdmf-help-link"><?php echo esc_html__('Support', 'breakdance-menu-builder-fix'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div> <!-- .bdmf-sidebar -->
                </div> <!-- .bdmf-settings-grid -->
            </div> <!-- .bdmf-main-content -->

            <div class="bdmf-footer">
                <p><?php echo wp_kses_post(__('Made with ❤️ by <a href="https://emarketing.cy">eMarketing Cyprus</a> for the Breakdance community', 'breakdance-menu-builder-fix')); ?></p>
            </div>
        </div>

        <script>
        // Live preview updates (kept your behavior)
        document.addEventListener('DOMContentLoaded', function() {
            // Update color values in real-time
            const colorInputs = document.querySelectorAll('input[type="color"]');
            colorInputs.forEach(input => {
                const valueSpan = input.nextElementSibling;
                if (valueSpan) {
                    input.addEventListener('input', function() {
                        valueSpan.textContent = this.value;
                    });
                }
            });

            // Update range values in real-time
            const rangeInputs = document.querySelectorAll('input[type="range"]');
            rangeInputs.forEach(input => {
                const valueSpan = input.parentElement ? input.parentElement.querySelector('.bdmf-range-value') : null;
                input.addEventListener('input', function() {
                    const unit = this.name.includes('breakpoint') ? 'px' : (this.name.includes('width') ? 'px' : '');
                    if (valueSpan) valueSpan.textContent = this.value + unit;
                });
            });
        });
        </script>
        <?php
    }

    /* =======================
     * Sanitizers (for Plugin Check)
     * ======================= */

    /** Sanitize checkbox/boolean to 0/1 */
    public function sanitize_bool($value) {
        return (!empty($value) && $value !== '0') ? 1 : 0;
    }

    /** Sanitize hex colors like #112233 or #fff */
    public function sanitize_color($value) {
        $v = sanitize_hex_color(is_string($value) ? $value : '');
        return $v ? $v : '#000000';
    }

    /** Sanitize integer within range */
    public function sanitize_int_range($value, $min, $max) {
        $n = (int) $value;
        if ($n < $min) $n = $min;
        if ($n > $max) $n = $max;
        return $n;
    }

    /** Sanitize border style to an allowlist */
    public function sanitize_border_style($value) {
        $allowed = array('solid','dashed','dotted','double','none');
        $v = is_string($value) ? strtolower(trim($value)) : '';
        return in_array($v, $allowed, true) ? $v : 'solid';
    }

    /** Sanitize border position to an allowlist */
    public function sanitize_border_position($value) {
        $allowed = array('bottom','top','left','right');
        $v = is_string($value) ? strtolower(trim($value)) : '';
        return in_array($v, $allowed, true) ? $v : 'bottom';
    }
}

// Initialize the plugin
new BreakdanceMenuFix();

// Activation hook (kept)
register_activation_hook(__FILE__, function() {
    add_option('bdmf_active_color', '#667eea');
    add_option('bdmf_active_border_color', '#667eea');
    add_option('bdmf_active_border_width', '2');
    add_option('bdmf_active_border_style', 'solid');
    add_option('bdmf_active_border_position', 'bottom');
    add_option('bdmf_enable_border_fix', 1);
    add_option('bdmf_mobile_breakpoint', 480);
    add_option('bdmf_enable_dropdown_fix', 1);
    add_option('bdmf_enable_mobile_fix', 1);
});

// Deactivation hook (kept)
register_deactivation_hook(__FILE__, function() {
    // Clean up if needed
});
