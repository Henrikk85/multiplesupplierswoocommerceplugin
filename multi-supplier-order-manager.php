<?php
/**
 * Plugin Name: Multi-Supplier Order Manager
 * Plugin URI: https://github.com/Henrikk85/multi-supplier-order-manager
 * Description: Automatically splits WooCommerce orders by supplier and sends separate emails with PDF attachments to suppliers and transportation companies.
 * Version: 1.0.2
 * Author: Henrik Kriiger
 * Author URI: https://github.com/Henrikk85
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: multi-supplier-order-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Woo: 8.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('MSOM_PLUGIN_FILE')) {
    define('MSOM_PLUGIN_FILE', __FILE__);
}

if (!defined('MSOM_PLUGIN_DIR')) {
    define('MSOM_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('MSOM_PLUGIN_URL')) {
    define('MSOM_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('MSOM_VERSION')) {
    define('MSOM_VERSION', '1.0.2');
}

class MultiSupplierOrderManager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        $this->load_textdomain();
        $this->includes();
        $this->init_hooks();
    }
    
    private function load_textdomain() {
        load_plugin_textdomain('multi-supplier-order-manager', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    private function includes() {
        require_once MSOM_PLUGIN_DIR . 'includes/class-msom-admin.php';
        require_once MSOM_PLUGIN_DIR . 'includes/class-msom-order-processor.php';
        require_once MSOM_PLUGIN_DIR . 'includes/class-msom-pdf-generator.php';
        require_once MSOM_PLUGIN_DIR . 'includes/class-msom-email-sender.php';
        require_once MSOM_PLUGIN_DIR . 'includes/class-msom-supplier-manager.php';
    }
    
    private function init_hooks() {
        register_activation_hook(MSOM_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(MSOM_PLUGIN_FILE, array($this, 'deactivate'));
        
        add_action('woocommerce_order_status_processing', array($this, 'process_order'), 10, 1);
        add_action('woocommerce_order_status_completed', array($this, 'process_order'), 10, 1);
        
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
        
        if (is_admin()) {
            new MSOM_Admin();
        }
    }
    
    public function activate() {
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Multi-Supplier Order Manager requires WooCommerce to be installed and active.', 'multi-supplier-order-manager'));
        }
        
        $this->create_tables();
        $this->set_default_options();
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('msom_cleanup_temp_files');
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'msom_suppliers';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            contact_person varchar(255),
            phone varchar(50),
            address text,
            additional_instructions text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        $product_supplier_table = $wpdb->prefix . 'msom_product_suppliers';
        
        $sql2 = "CREATE TABLE $product_supplier_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            supplier_id mediumint(9) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY supplier_id (supplier_id)
        ) $charset_collate;";
        
        dbDelta($sql2);
    }
    
    private function set_default_options() {
        add_option('msom_transport_company_email', '');
        add_option('msom_transport_company_name', '');
        add_option('msom_email_subject_supplier', __('New Order - Items for Supply', 'multi-supplier-order-manager'));
        add_option('msom_email_subject_transport', __('Pickup Required - Order Items', 'multi-supplier-order-manager'));
        add_option('msom_pdf_company_logo', '');
        add_option('msom_pdf_company_name', get_bloginfo('name'));
        add_option('msom_pdf_company_address', '');
    }
    
    public function process_order($order_id) {
        $order_processor = new MSOM_Order_Processor();
        $order_processor->process_multi_supplier_order($order_id);
    }
    
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', MSOM_PLUGIN_FILE, true);
        }
    }
    
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('Multi-Supplier Order Manager requires WooCommerce to be installed and active.', 'multi-supplier-order-manager');
        echo '</p></div>';
    }
}

MultiSupplierOrderManager::get_instance();
