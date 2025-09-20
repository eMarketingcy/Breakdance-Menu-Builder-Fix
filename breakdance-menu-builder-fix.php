<?php
/**
 * Plugin Name: Breakdance Menu Builder Fix
 * Plugin URI: https://github.com/eMarketingcy/Breakdance-Menu-Builder-Fix
 * Description: Fixes active menu state issues in Breakdance Menu Builder for dropdown items and mobile navigation
 * Version: 2.1.0
 * Author: eMarketing Cyprus
 * License: GPL v2 or later
 * Text Domain: breakdance-menu-fix
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class BreakdanceMenuFix {
    
    private $plugin_url;
    private $plugin_path;
    
    public function __construct() {
        $this->plugin_url = plugin_dir_url(__FILE__);
        $this->plugin_path = plugin_dir_path(__FILE__);
        
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Hook into WordPress
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_custom_css'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Check if Breakdance is active
        if (!$this->is_breakdance_active()) {
            add_action('admin_notices', array($this, 'breakdance_not_active_notice'));
        }
    }
    
    /**
     * Check if Breakdance plugin is active
     */
    private function is_breakdance_active() {
        return class_exists('Breakdance\PluginAPI\PluginAPI') || 
               is_plugin_active('breakdance/plugin.php');
    }
    
    /**
     * Show admin notice if Breakdance is not active
     */
    public function breakdance_not_active_notice() {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php _e('Breakdance Menu Builder Fix requires the Breakdance plugin to be installed and activated.', 'breakdance-menu-fix'); ?></p>
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
        
        // Pass settings to JavaScript
        $settings = array(
            'mobileBreakpoint' => get_option('bdmf_mobile_breakpoint', 480),
            'activeColor' => get_option('bdmf_active_color', '#ff0000'),
            'activeBorderColor' => get_option('bdmf_active_border_color', '#ff0000'),
            'activeBorderWidth' => get_option('bdmf_active_border_width', '2'),
            'activeBorderStyle' => get_option('bdmf_active_border_style', 'solid'),
            'activeBorderPosition' => get_option('bdmf_active_border_position', 'bottom'),
            'enableBorderFix' => get_option('bdmf_enable_border_fix', true),
            'enableMobileFix' => get_option('bdmf_enable_mobile_fix', true),
            'enableDropdownFix' => get_option('bdmf_enable_dropdown_fix', true)
        );
        
        wp_localize_script('breakdance-menu-fix-js', 'bdmfSettings', $settings);
    }
    
    /**
     * Add custom CSS to head
     */
    public function add_custom_css() {
        if (!$this->is_breakdance_active()) {
            return;
        }
        
        $active_color = get_option('bdmf_active_color', '#ff0000');
        $enable_dropdown_fix = get_option('bdmf_enable_dropdown_fix', true);
        $enable_border_fix = get_option('bdmf_enable_border_fix', true);
        $border_color = get_option('bdmf_active_border_color', '#ff0000');
        $border_width = get_option('bdmf_active_border_width', '2');
        $border_style = get_option('bdmf_active_border_style', 'solid');
        $border_position = get_option('bdmf_active_border_position', 'bottom');
        
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
            background-color: rgba(<?php echo $this->hex_to_rgb($active_color); ?>, 0.05);
            padding: 8px 8px 8px 0px;
            border-radius: 5px;
}
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
     * Convert hex color to RGB
     */
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        return implode(',', array_map('hexdec', str_split($hex, 2)));
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
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('bdmf_settings', 'bdmf_active_color');
        register_setting('bdmf_settings', 'bdmf_active_border_color');
        register_setting('bdmf_settings', 'bdmf_active_border_width');
        register_setting('bdmf_settings', 'bdmf_active_border_style');
        register_setting('bdmf_settings', 'bdmf_active_border_position');
        register_setting('bdmf_settings', 'bdmf_enable_border_fix');
        register_setting('bdmf_settings', 'bdmf_mobile_breakpoint');
        register_setting('bdmf_settings', 'bdmf_enable_dropdown_fix');
        register_setting('bdmf_settings', 'bdmf_enable_mobile_fix');
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        // Enqueue admin styles
        wp_enqueue_style('bdmf-admin-styles', $this->plugin_url . 'assets/css/admin.css', array(), '2.1.0');
        
        ?>
        <div class="wrap bdmf-admin-wrap">
            <div class="bdmf-header">
                <div class="bdmf-header-content">
                    <div class="bdmf-logo">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="8" fill="#667eea"/>
                            <path d="M12 20L18 26L28 14" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="bdmf-header-text">
                        <h1><?php _e('Breakdance Menu Builder Fix', 'breakdance-menu-fix'); ?></h1>
                        <p><?php _e('Professional menu fixes for Breakdance Builder', 'breakdance-menu-fix'); ?></p>
                    </div>
                </div>
                <div class="bdmf-version">
                    <span class="bdmf-version-badge">v2.1.0</span>
                </div>
            </div>
            
            <?php if (!$this->is_breakdance_active()): ?>
                <div class="bdmf-alert bdmf-alert-error">
                    <div class="bdmf-alert-icon">⚠️</div>
                    <div class="bdmf-alert-content">
                        <strong><?php _e('Breakdance Required', 'breakdance-menu-fix'); ?></strong>
                        <p><?php _e('This plugin requires Breakdance to be installed and activated.', 'breakdance-menu-fix'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="bdmf-main-content">
                <div class="bdmf-settings-grid">
                    <div class="bdmf-settings-panel">
                        <form method="post" action="options.php" class="bdmf-form">
                            <?php settings_fields('bdmf_settings'); ?>
                            <?php do_settings_sections('bdmf_settings'); ?>
                            
                            <!-- Feature Toggles -->
                            <div class="bdmf-section">
                                <h2 class="bdmf-section-title">
                                    <span class="bdmf-section-icon">🔧</span>
                                    <?php _e('Feature Controls', 'breakdance-menu-fix'); ?>
                                </h2>
                                
                                <div class="bdmf-toggle-group">
                                    <div class="bdmf-toggle-item">
                                        <label class="bdmf-toggle">
                                            <input type="checkbox" name="bdmf_enable_dropdown_fix" value="1" <?php checked(get_option('bdmf_enable_dropdown_fix', true)); ?> />
                                            <span class="bdmf-toggle-slider"></span>
                                        </label>
                                        <div class="bdmf-toggle-content">
                                            <h4><?php _e('Dropdown Menu Fix', 'breakdance-menu-fix'); ?></h4>
                                            <p><?php _e('Fix active states for dropdown menu items', 'breakdance-menu-fix'); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="bdmf-toggle-item">
                                        <label class="bdmf-toggle">
                                            <input type="checkbox" name="bdmf_enable_border_fix" value="1" <?php checked(get_option('bdmf_enable_border_fix', true)); ?> />
                                            <span class="bdmf-toggle-slider"></span>
                                        </label>
                                        <div class="bdmf-toggle-content">
                                            <h4><?php _e('Active Menu Borders', 'breakdance-menu-fix'); ?></h4>
                                            <p><?php _e('Add customizable borders to active menu items', 'breakdance-menu-fix'); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="bdmf-toggle-item">
                                        <label class="bdmf-toggle">
                                            <input type="checkbox" name="bdmf_enable_mobile_fix" value="1" <?php checked(get_option('bdmf_enable_mobile_fix', true)); ?> />
                                            <span class="bdmf-toggle-slider"></span>
                                        </label>
                                        <div class="bdmf-toggle-content">
                                            <h4><?php _e('Mobile Menu Fix', 'breakdance-menu-fix'); ?></h4>
                                            <p><?php _e('Fix mobile menu behavior for dropdown toggles', 'breakdance-menu-fix'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Color Settings -->
                            <div class="bdmf-section">
                                <h2 class="bdmf-section-title">
                                    <span class="bdmf-section-icon">🎨</span>
                                    <?php _e('Color Settings', 'breakdance-menu-fix'); ?>
                                </h2>
                                
                                <div class="bdmf-color-grid">
                                    <div class="bdmf-color-item">
                                        <label><?php _e('Active Menu Color', 'breakdance-menu-fix'); ?></label>
                                        <div class="bdmf-color-input">
                                            <input type="color" name="bdmf_active_color" value="<?php echo esc_attr(get_option('bdmf_active_color', '#667eea')); ?>" />
                                            <span class="bdmf-color-value"><?php echo esc_attr(get_option('bdmf_active_color', '#667eea')); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="bdmf-color-item">
                                        <label><?php _e('Border Color', 'breakdance-menu-fix'); ?></label>
                                        <div class="bdmf-color-input">
                                            <input type="color" name="bdmf_active_border_color" value="<?php echo esc_attr(get_option('bdmf_active_border_color', '#667eea')); ?>" />
                                            <span class="bdmf-color-value"><?php echo esc_attr(get_option('bdmf_active_border_color', '#667eea')); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Border Settings -->
                            <div class="bdmf-section">
                                <h2 class="bdmf-section-title">
                                    <span class="bdmf-section-icon">📐</span>
                                    <?php _e('Border Settings', 'breakdance-menu-fix'); ?>
                                </h2>
                                
                                <div class="bdmf-border-controls">
                                    <div class="bdmf-control-group">
                                        <label><?php _e('Border Width', 'breakdance-menu-fix'); ?></label>
                                        <div class="bdmf-range-input">
                                            <input type="range" name="bdmf_active_border_width" min="1" max="10" value="<?php echo esc_attr(get_option('bdmf_active_border_width', '2')); ?>" class="bdmf-range" />
                                            <span class="bdmf-range-value"><?php echo esc_attr(get_option('bdmf_active_border_width', '2')); ?>px</span>
                                        </div>
                                    </div>
                                    
                                    <div class="bdmf-control-group">
                                        <label><?php _e('Border Style', 'breakdance-menu-fix'); ?></label>
                                        <select name="bdmf_active_border_style" class="bdmf-select">
                                            <option value="solid" <?php selected(get_option('bdmf_active_border_style', 'solid'), 'solid'); ?>><?php _e('Solid', 'breakdance-menu-fix'); ?></option>
                                            <option value="dashed" <?php selected(get_option('bdmf_active_border_style', 'solid'), 'dashed'); ?>><?php _e('Dashed', 'breakdance-menu-fix'); ?></option>
                                            <option value="dotted" <?php selected(get_option('bdmf_active_border_style', 'solid'), 'dotted'); ?>><?php _e('Dotted', 'breakdance-menu-fix'); ?></option>
                                            <option value="double" <?php selected(get_option('bdmf_active_border_style', 'solid'), 'double'); ?>><?php _e('Double', 'breakdance-menu-fix'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="bdmf-control-group">
                                        <label><?php _e('Border Position', 'breakdance-menu-fix'); ?></label>
                                        <div class="bdmf-radio-group">
                                            <label class="bdmf-radio">
                                                <input type="radio" name="bdmf_active_border_position" value="top" <?php checked(get_option('bdmf_active_border_position', 'bottom'), 'top'); ?> />
                                                <span><?php _e('Top', 'breakdance-menu-fix'); ?></span>
                                            </label>
                                            <label class="bdmf-radio">
                                                <input type="radio" name="bdmf_active_border_position" value="bottom" <?php checked(get_option('bdmf_active_border_position', 'bottom'), 'bottom'); ?> />
                                                <span><?php _e('Bottom', 'breakdance-menu-fix'); ?></span>
                                            </label>
                                            <label class="bdmf-radio">
                                                <input type="radio" name="bdmf_active_border_position" value="left" <?php checked(get_option('bdmf_active_border_position', 'bottom'), 'left'); ?> />
                                                <span><?php _e('Left', 'breakdance-menu-fix'); ?></span>
                                            </label>
                                            <label class="bdmf-radio">
                                                <input type="radio" name="bdmf_active_border_position" value="right" <?php checked(get_option('bdmf_active_border_position', 'bottom'), 'right'); ?> />
                                                <span><?php _e('Right', 'breakdance-menu-fix'); ?></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mobile Settings -->
                            <div class="bdmf-section">
                                <h2 class="bdmf-section-title">
                                    <span class="bdmf-section-icon">📱</span>
                                    <?php _e('Mobile Settings', 'breakdance-menu-fix'); ?>
                                </h2>
                                
                                <div class="bdmf-control-group">
                                    <label><?php _e('Mobile Breakpoint', 'breakdance-menu-fix'); ?></label>
                                    <div class="bdmf-range-input">
                                        <input type="range" name="bdmf_mobile_breakpoint" min="320" max="1200" step="10" value="<?php echo esc_attr(get_option('bdmf_mobile_breakpoint', 480)); ?>" class="bdmf-range" />
                                        <span class="bdmf-range-value"><?php echo esc_attr(get_option('bdmf_mobile_breakpoint', 480)); ?>px</span>
                                    </div>
                                    <p class="bdmf-help-text"><?php _e('Screen width below which mobile menu behavior applies', 'breakdance-menu-fix'); ?></p>
                                </div>
                            </div>
                            
                            <div class="bdmf-form-actions">
                                <?php submit_button(__('Save Settings', 'breakdance-menu-fix'), 'primary bdmf-button-primary', 'submit', false); ?>
                                <button type="button" class="bdmf-button-secondary" onclick="location.reload()"><?php _e('Reset Preview', 'breakdance-menu-fix'); ?></button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="bdmf-sidebar">
                        <div class="bdmf-info-card">
                            <div class="bdmf-info-header">
                                <h3><?php _e('Plugin Status', 'breakdance-menu-fix'); ?></h3>
                                <span class="bdmf-status-badge bdmf-status-active"><?php _e('Active', 'breakdance-menu-fix'); ?></span>
                            </div>
                            <div class="bdmf-info-content">
                                <div class="bdmf-stat">
                                    <span class="bdmf-stat-label"><?php _e('Fixes Applied', 'breakdance-menu-fix'); ?></span>
                                    <span class="bdmf-stat-value">
                                        <?php 
                                        $active_fixes = 0;
                                        if (get_option('bdmf_enable_dropdown_fix', true)) $active_fixes++;
                                        if (get_option('bdmf_enable_border_fix', true)) $active_fixes++;
                                        if (get_option('bdmf_enable_mobile_fix', true)) $active_fixes++;
                                        echo $active_fixes . '/3';
                                        ?>
                                    </span>
                                </div>
                                <div class="bdmf-stat">
                                    <span class="bdmf-stat-label"><?php _e('Breakdance Status', 'breakdance-menu-fix'); ?></span>
                                    <span class="bdmf-stat-value <?php echo $this->is_breakdance_active() ? 'bdmf-status-ok' : 'bdmf-status-error'; ?>">
                                        <?php echo $this->is_breakdance_active() ? __('Active', 'breakdance-menu-fix') : __('Inactive', 'breakdance-menu-fix'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bdmf-info-card">
                            <div class="bdmf-info-header">
                                <h3><?php _e('Issues Fixed', 'breakdance-menu-fix'); ?></h3>
                            </div>
                            <div class="bdmf-info-content">
                                <ul class="bdmf-feature-list">
                                    <li class="bdmf-feature-item">
                                        <span class="bdmf-feature-icon">✅</span>
                                        <?php _e('Dropdown active states', 'breakdance-menu-fix'); ?>
                                    </li>
                                    <li class="bdmf-feature-item">
                                        <span class="bdmf-feature-icon">✅</span>
                                        <?php _e('Parent menu highlighting', 'breakdance-menu-fix'); ?>
                                    </li>
                                    <li class="bdmf-feature-item">
                                        <span class="bdmf-feature-icon">✅</span>
                                        <?php _e('Mobile menu behavior', 'breakdance-menu-fix'); ?>
                                    </li>
                                    <li class="bdmf-feature-item">
                                        <span class="bdmf-feature-icon">✅</span>
                                        <?php _e('Custom border controls', 'breakdance-menu-fix'); ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="bdmf-info-card">
                            <div class="bdmf-info-header">
                                <h3><?php _e('Need Help?', 'breakdance-menu-fix'); ?></h3>
                            </div>
                            <div class="bdmf-info-content">
                                <p><?php _e('Having issues? Check our documentation or contact support.', 'breakdance-menu-fix'); ?></p>
                                <div class="bdmf-help-links">
                                    <a href="https://github.com/eMarketingcy/Breakdance-Menu-Builder-Fix/blob/main/README.md" class="bdmf-help-link"><?php _e('Documentation', 'breakdance-menu-fix'); ?></a>
                                    <a href="https://github.com/eMarketingcy/Breakdance-Menu-Builder-Fix/issues" class="bdmf-help-link"><?php _e('Support', 'breakdance-menu-fix'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bdmf-footer">
                <p><?php _e('Made with ❤️ for the Breakdance community', 'breakdance-menu-fix'); ?></p>
            </div>
        </div>
        
        <script>
        // Live preview updates
        document.addEventListener('DOMContentLoaded', function() {
            // Update color values in real-time
            const colorInputs = document.querySelectorAll('input[type="color"]');
            colorInputs.forEach(input => {
                const valueSpan = input.nextElementSibling;
                input.addEventListener('input', function() {
                    valueSpan.textContent = this.value;
                });
            });
            
            // Update range values in real-time
            const rangeInputs = document.querySelectorAll('input[type="range"]');
            rangeInputs.forEach(input => {
                const valueSpan = input.parentElement.querySelector('.bdmf-range-value');
                input.addEventListener('input', function() {
                    const unit = this.name.includes('breakpoint') ? 'px' : (this.name.includes('width') ? 'px' : '');
                    valueSpan.textContent = this.value + unit;
                });
            });
        });
        </script>
        <?php
    }
}

// Initialize the plugin
new BreakdanceMenuFix();

// Activation hook
register_activation_hook(__FILE__, function() {
    // Set default options
    add_option('bdmf_active_color', '#667eea');
    add_option('bdmf_active_border_color', '#667eea');
    add_option('bdmf_active_border_width', '2');
    add_option('bdmf_active_border_style', 'solid');
    add_option('bdmf_active_border_position', 'bottom');
    add_option('bdmf_enable_border_fix', true);
    add_option('bdmf_mobile_breakpoint', 480);
    add_option('bdmf_enable_dropdown_fix', true);
    add_option('bdmf_enable_mobile_fix', true);
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up if needed
});