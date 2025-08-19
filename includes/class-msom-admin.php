<?php

if (!defined('ABSPATH')) {
    exit;
}

class MSOM_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        add_action('save_post', array($this, 'save_product_supplier'), 10, 1);
        add_action('woocommerce_process_product_meta', array($this, 'save_product_supplier'), 10, 1);
        add_action('woocommerce_admin_process_product_object', array($this, 'save_product_supplier_wc'), 10, 1);
        add_action('wp_ajax_msom_create_tables', array($this, 'ajax_create_tables'));
        
        error_log('MSOM Debug: Admin class constructor called, hooks registered');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Multi-Supplier Manager', 'multi-supplier-order-manager'),
            __('Supplier Manager', 'multi-supplier-order-manager'),
            'manage_options',
            'msom-suppliers',
            array($this, 'suppliers_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'msom-suppliers',
            __('Suppliers', 'multi-supplier-order-manager'),
            __('Suppliers', 'multi-supplier-order-manager'),
            'manage_options',
            'msom-suppliers',
            array($this, 'suppliers_page')
        );
        
        add_submenu_page(
            'msom-suppliers',
            __('Settings', 'multi-supplier-order-manager'),
            __('Settings', 'multi-supplier-order-manager'),
            'manage_options',
            'msom-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'msom-suppliers',
            __('Database Setup', 'multi-supplier-order-manager'),
            __('Database Setup', 'multi-supplier-order-manager'),
            'manage_options',
            'msom-database-setup',
            array($this, 'database_setup_page')
        );
    }
    
    public function suppliers_page() {
        $supplier_manager = new MSOM_Supplier_Manager();
        
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add_supplier' && wp_verify_nonce($_POST['msom_nonce'], 'msom_add_supplier')) {
                $result = $supplier_manager->add_supplier($_POST);
                if ($result) {
                    echo '<div class="notice notice-success"><p>' . __('Supplier added successfully.', 'multi-supplier-order-manager') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Error adding supplier.', 'multi-supplier-order-manager') . '</p></div>';
                }
            } elseif ($_POST['action'] === 'update_supplier' && wp_verify_nonce($_POST['msom_nonce'], 'msom_update_supplier')) {
                $result = $supplier_manager->update_supplier($_POST['supplier_id'], $_POST);
                if ($result !== false) {
                    echo '<div class="notice notice-success"><p>' . __('Supplier updated successfully.', 'multi-supplier-order-manager') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Error updating supplier.', 'multi-supplier-order-manager') . '</p></div>';
                }
            } elseif ($_POST['action'] === 'delete_supplier' && wp_verify_nonce($_POST['msom_nonce'], 'msom_delete_supplier')) {
                $result = $supplier_manager->delete_supplier($_POST['supplier_id']);
                if ($result) {
                    echo '<div class="notice notice-success"><p>' . __('Supplier deleted successfully.', 'multi-supplier-order-manager') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Error deleting supplier.', 'multi-supplier-order-manager') . '</p></div>';
                }
            }
        }
        
        $suppliers = $supplier_manager->get_all_suppliers();
        $edit_supplier = null;
        
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['supplier_id'])) {
            $edit_supplier = $supplier_manager->get_supplier($_GET['supplier_id']);
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Supplier Management', 'multi-supplier-order-manager'); ?></h1>
            
            <?php if ($edit_supplier): ?>
                <h2><?php _e('Edit Supplier', 'multi-supplier-order-manager'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('msom_update_supplier', 'msom_nonce'); ?>
                    <input type="hidden" name="action" value="update_supplier">
                    <input type="hidden" name="supplier_id" value="<?php echo $edit_supplier->id; ?>">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Supplier Name', 'multi-supplier-order-manager'); ?></th>
                            <td><input type="text" name="name" value="<?php echo esc_attr($edit_supplier->name); ?>" required class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Email', 'multi-supplier-order-manager'); ?></th>
                            <td><input type="email" name="email" value="<?php echo esc_attr($edit_supplier->email); ?>" required class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Contact Person', 'multi-supplier-order-manager'); ?></th>
                            <td><input type="text" name="contact_person" value="<?php echo esc_attr($edit_supplier->contact_person); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Phone', 'multi-supplier-order-manager'); ?></th>
                            <td><input type="text" name="phone" value="<?php echo esc_attr($edit_supplier->phone); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Address', 'multi-supplier-order-manager'); ?></th>
                            <td><textarea name="address" rows="3" class="large-text"><?php echo esc_textarea($edit_supplier->address); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Additional Instructions', 'multi-supplier-order-manager'); ?></th>
                            <td><textarea name="additional_instructions" rows="3" class="large-text" placeholder="<?php _e('Special instructions for this supplier...', 'multi-supplier-order-manager'); ?>"><?php echo esc_textarea($edit_supplier->additional_instructions); ?></textarea></td>
                        </tr>
                    </table>
                    <?php submit_button(__('Update Supplier', 'multi-supplier-order-manager')); ?>
                    <a href="<?php echo admin_url('admin.php?page=msom-suppliers'); ?>" class="button button-secondary"><?php _e('Cancel', 'multi-supplier-order-manager'); ?></a>
                </form>
            <?php else: ?>
                <h2><?php _e('Add New Supplier', 'multi-supplier-order-manager'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('msom_add_supplier', 'msom_nonce'); ?>
                <input type="hidden" name="action" value="add_supplier">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Supplier Name', 'multi-supplier-order-manager'); ?></th>
                        <td><input type="text" name="name" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Email', 'multi-supplier-order-manager'); ?></th>
                        <td><input type="email" name="email" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Contact Person', 'multi-supplier-order-manager'); ?></th>
                        <td><input type="text" name="contact_person" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Phone', 'multi-supplier-order-manager'); ?></th>
                        <td><input type="text" name="phone" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Address', 'multi-supplier-order-manager'); ?></th>
                        <td><textarea name="address" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Additional Instructions', 'multi-supplier-order-manager'); ?></th>
                        <td><textarea name="additional_instructions" rows="3" class="large-text" placeholder="<?php _e('Special instructions for this supplier...', 'multi-supplier-order-manager'); ?>"></textarea></td>
                    </tr>
                </table>
                <?php submit_button(__('Add Supplier', 'multi-supplier-order-manager')); ?>
            </form>
            <?php endif; ?>
            
            <h2><?php _e('Existing Suppliers', 'multi-supplier-order-manager'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'multi-supplier-order-manager'); ?></th>
                        <th><?php _e('Email', 'multi-supplier-order-manager'); ?></th>
                        <th><?php _e('Contact Person', 'multi-supplier-order-manager'); ?></th>
                        <th><?php _e('Phone', 'multi-supplier-order-manager'); ?></th>
                        <th><?php _e('Instructions', 'multi-supplier-order-manager'); ?></th>
                        <th><?php _e('Actions', 'multi-supplier-order-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="6"><?php _e('No suppliers found.', 'multi-supplier-order-manager'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><?php echo esc_html($supplier->name); ?></td>
                                <td><?php echo esc_html($supplier->email); ?></td>
                                <td><?php echo esc_html($supplier->contact_person); ?></td>
                                <td><?php echo esc_html($supplier->phone); ?></td>
                                <td><?php echo esc_html(substr($supplier->additional_instructions ?? '', 0, 50)) . (strlen($supplier->additional_instructions ?? '') > 50 ? '...' : ''); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=msom-suppliers&action=edit&supplier_id=' . $supplier->id); ?>" class="button button-primary"><?php _e('Edit', 'multi-supplier-order-manager'); ?></a>
                                    <form method="post" style="display: inline; margin-left: 5px;">
                                        <?php wp_nonce_field('msom_delete_supplier', 'msom_nonce'); ?>
                                        <input type="hidden" name="action" value="delete_supplier">
                                        <input type="hidden" name="supplier_id" value="<?php echo $supplier->id; ?>">
                                        <input type="submit" value="<?php _e('Delete', 'multi-supplier-order-manager'); ?>" class="button button-secondary" onclick="return confirm('<?php _e('Are you sure?', 'multi-supplier-order-manager'); ?>')">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            if (wp_verify_nonce($_POST['msom_settings_nonce'], 'msom_save_settings')) {
                update_option('msom_transport_company_email', sanitize_email($_POST['transport_company_email']));
                update_option('msom_transport_company_name', sanitize_text_field($_POST['transport_company_name']));
                update_option('msom_email_subject_supplier', sanitize_text_field($_POST['email_subject_supplier']));
                update_option('msom_email_subject_transport', sanitize_text_field($_POST['email_subject_transport']));
                update_option('msom_pdf_company_name', sanitize_text_field($_POST['pdf_company_name']));
                update_option('msom_pdf_company_address', sanitize_textarea_field($_POST['pdf_company_address']));
                
                echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'multi-supplier-order-manager') . '</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Multi-Supplier Order Manager Settings', 'multi-supplier-order-manager'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('msom_save_settings', 'msom_settings_nonce'); ?>
                
                <h2><?php _e('Transportation Company', 'multi-supplier-order-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Company Name', 'multi-supplier-order-manager'); ?></th>
                        <td><input type="text" name="transport_company_name" value="<?php echo esc_attr(get_option('msom_transport_company_name')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Email Address', 'multi-supplier-order-manager'); ?></th>
                        <td><input type="email" name="transport_company_email" value="<?php echo esc_attr(get_option('msom_transport_company_email')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                
                <h2><?php _e('Email Settings', 'multi-supplier-order-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Supplier Email Subject', 'multi-supplier-order-manager'); ?></th>
                        <td><input type="text" name="email_subject_supplier" value="<?php echo esc_attr(get_option('msom_email_subject_supplier')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Transport Email Subject', 'multi-supplier-order-manager'); ?></th>
                        <td><input type="text" name="email_subject_transport" value="<?php echo esc_attr(get_option('msom_email_subject_transport')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                
                <h2><?php _e('PDF Settings', 'multi-supplier-order-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Company Name', 'multi-supplier-order-manager'); ?></th>
                        <td><input type="text" name="pdf_company_name" value="<?php echo esc_attr(get_option('msom_pdf_company_name')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Company Address', 'multi-supplier-order-manager'); ?></th>
                        <td><textarea name="pdf_company_address" rows="3" class="large-text"><?php echo esc_textarea(get_option('msom_pdf_company_address')); ?></textarea></td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function init_settings() {
        
    }
    
    public function add_product_meta_boxes() {
        add_meta_box(
            'msom_product_supplier',
            __('Supplier Information', 'multi-supplier-order-manager'),
            array($this, 'product_supplier_meta_box'),
            'product',
            'side',
            'default'
        );
    }
    
    public function product_supplier_meta_box($post) {
        wp_nonce_field('msom_save_product_supplier', 'msom_product_supplier_nonce');
        
        $supplier_manager = new MSOM_Supplier_Manager();
        $suppliers = $supplier_manager->get_all_suppliers();
        $current_suppliers = $supplier_manager->get_product_suppliers($post->ID);
        $current_supplier_ids = array();
        
        if ($current_suppliers) {
            foreach ($current_suppliers as $supplier_assignment) {
                $current_supplier_ids[] = $supplier_assignment->supplier_id;
            }
        }
        
        echo '<label>' . __('Select Suppliers:', 'multi-supplier-order-manager') . '</label>';
        echo '<div style="margin-top: 10px; max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">';
        
        foreach ($suppliers as $supplier) {
            $checked = in_array($supplier->id, $current_supplier_ids) ? 'checked' : '';
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="msom_supplier_ids[]" value="' . $supplier->id . '" ' . $checked . ' style="margin-right: 5px;">';
            echo esc_html($supplier->name);
            echo '</label>';
        }
        
        if (empty($suppliers)) {
            echo '<p style="color: #666; font-style: italic;">' . __('No suppliers available. Please add suppliers first.', 'multi-supplier-order-manager') . '</p>';
        }
        
        echo '</div>';
    }
    
    public function save_product_supplier($post_id) {
        error_log('MSOM Debug: save_product_supplier called with post_id=' . $post_id);
        error_log('MSOM Debug: Hook triggered: ' . current_action());
        
        if (!isset($_POST['msom_product_supplier_nonce'])) {
            error_log('MSOM Debug: No nonce field found in POST data');
            return;
        }
        
        if (!wp_verify_nonce($_POST['msom_product_supplier_nonce'], 'msom_save_product_supplier')) {
            error_log('MSOM Debug: Nonce verification failed');
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            error_log('MSOM Debug: Skipping due to autosave');
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            error_log('MSOM Debug: User cannot edit post');
            return;
        }
        
        if (get_post_type($post_id) !== 'product') {
            error_log('MSOM Debug: Post type is not product: ' . get_post_type($post_id));
            return;
        }
        
        $supplier_manager = new MSOM_Supplier_Manager();
        $supplier_ids = isset($_POST['msom_supplier_ids']) ? array_map('intval', $_POST['msom_supplier_ids']) : array();
        
        error_log('MSOM Debug: Product ID: ' . $post_id . ', Supplier IDs: ' . print_r($supplier_ids, true));
        
        $result = $supplier_manager->assign_product_to_suppliers($post_id, $supplier_ids);
        error_log('MSOM Debug: assign_product_to_suppliers result: ' . ($result ? 'true' : 'false'));
    }
    
    public function save_product_supplier_wc($product) {
        if (!$product || !is_a($product, 'WC_Product')) {
            error_log('MSOM Debug: save_product_supplier_wc called but no valid product object');
            return;
        }
        
        $post_id = $product->get_id();
        error_log('MSOM Debug: save_product_supplier_wc called with product ID=' . $post_id);
        
        $this->save_product_supplier($post_id);
    }
    
    public function database_setup_page() {
        global $wpdb;
        
        $suppliers_table = $wpdb->prefix . 'msom_suppliers';
        $product_suppliers_table = $wpdb->prefix . 'msom_product_suppliers';
        
        $suppliers_exists = $wpdb->get_var("SHOW TABLES LIKE '$suppliers_table'") == $suppliers_table;
        $product_suppliers_exists = $wpdb->get_var("SHOW TABLES LIKE '$product_suppliers_table'") == $product_suppliers_table;
        
        $table_creation_status = get_option('msom_tables_created', 'unknown');
        $table_creation_error = get_option('msom_table_creation_error', '');
        
        $can_create_tables = $this->check_database_permissions();
        
        echo '<div class="wrap">';
        echo '<h1>' . __('Multi-Supplier Order Manager - Database Setup', 'multi-supplier-order-manager') . '</h1>';
        
        echo '<div class="notice notice-info"><p>';
        echo __('This page helps you set up the required database tables for the Multi-Supplier Order Manager plugin safely through the WordPress admin interface.', 'multi-supplier-order-manager');
        echo '</p></div>';
        
        echo '<h2>' . __('Database Permissions Check', 'multi-supplier-order-manager') . '</h2>';
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Permission</th><th>Status</th><th>Description</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($can_create_tables as $permission => $status) {
            echo '<tr>';
            echo '<td><code>' . esc_html($permission) . '</code></td>';
            echo '<td><span class="dashicons ' . ($status['allowed'] ? 'dashicons-yes-alt' : 'dashicons-dismiss') . '" style="color: ' . ($status['allowed'] ? 'green' : 'red') . ';"></span> ';
            echo $status['allowed'] ? __('ALLOWED', 'multi-supplier-order-manager') : __('DENIED', 'multi-supplier-order-manager');
            echo '</td>';
            echo '<td>' . esc_html($status['description']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        echo '<h2>' . __('Database Table Status', 'multi-supplier-order-manager') . '</h2>';
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Table Name</th><th>Status</th><th>Description</th></tr></thead>';
        echo '<tbody>';
        
        echo '<tr>';
        echo '<td><code>' . $suppliers_table . '</code></td>';
        echo '<td><span class="dashicons ' . ($suppliers_exists ? 'dashicons-yes-alt' : 'dashicons-dismiss') . '" style="color: ' . ($suppliers_exists ? 'green' : 'red') . ';"></span> ';
        echo $suppliers_exists ? __('EXISTS', 'multi-supplier-order-manager') : __('MISSING', 'multi-supplier-order-manager');
        echo '</td>';
        echo '<td>' . __('Stores supplier information (name, email, contact details)', 'multi-supplier-order-manager') . '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<td><code>' . $product_suppliers_table . '</code></td>';
        echo '<td><span class="dashicons ' . ($product_suppliers_exists ? 'dashicons-yes-alt' : 'dashicons-dismiss') . '" style="color: ' . ($product_suppliers_exists ? 'green' : 'red') . ';"></span> ';
        echo $product_suppliers_exists ? __('EXISTS', 'multi-supplier-order-manager') : __('MISSING', 'multi-supplier-order-manager');
        echo '</td>';
        echo '<td>' . __('Links products to their suppliers (many-to-many relationship)', 'multi-supplier-order-manager') . '</td>';
        echo '</tr>';
        
        echo '</tbody></table>';
        
        if ($table_creation_status !== 'unknown') {
            echo '<h3>' . __('Last Table Creation Attempt', 'multi-supplier-order-manager') . '</h3>';
            echo '<p><strong>' . __('Status:', 'multi-supplier-order-manager') . '</strong> ';
            if ($table_creation_status === '1') {
                echo '<span style="color: green;">' . __('SUCCESS', 'multi-supplier-order-manager') . '</span>';
            } else {
                echo '<span style="color: red;">' . __('FAILED', 'multi-supplier-order-manager') . '</span>';
                if ($table_creation_error) {
                    echo '<br><strong>' . __('Error:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($table_creation_error);
                }
            }
            echo '</p>';
        }
        
        if (!$suppliers_exists || !$product_suppliers_exists) {
            $all_permissions_ok = true;
            foreach ($can_create_tables as $permission => $status) {
                if (!$status['allowed']) {
                    $all_permissions_ok = false;
                    break;
                }
            }
            
            if (!$all_permissions_ok) {
                echo '<div class="notice notice-error"><p>';
                echo __('<strong>Database Permission Issue:</strong> Your hosting environment does not allow table creation. Please contact your hosting provider to enable CREATE TABLE permissions or manually create the tables using the SQL provided below.', 'multi-supplier-order-manager');
                echo '</p></div>';
                
                echo '<h3>' . __('Manual SQL for Hosting Provider', 'multi-supplier-order-manager') . '</h3>';
                echo '<p>' . __('If automatic table creation fails due to permissions, provide this SQL to your hosting provider:', 'multi-supplier-order-manager') . '</p>';
                echo '<textarea readonly style="width: 100%; height: 200px; font-family: monospace;">';
                echo $this->get_manual_sql();
                echo '</textarea>';
            } else {
                echo '<div class="notice notice-warning"><p>';
                echo __('<strong>Action Required:</strong> One or more required database tables are missing. Please click the button below to create them safely.', 'multi-supplier-order-manager');
                echo '</p></div>';
                
                echo '<h3>' . __('Create Missing Tables', 'multi-supplier-order-manager') . '</h3>';
                echo '<p>' . __('Click the button below to safely create the missing database tables. This process uses WordPress built-in functions and is completely safe.', 'multi-supplier-order-manager') . '</p>';
                
                echo '<button type="button" id="msom-create-tables" class="button button-primary button-large">';
                echo __('Create Database Tables Safely', 'multi-supplier-order-manager');
                echo '</button>';
                
                echo '<div id="msom-table-creation-result" style="margin-top: 20px;"></div>';
                
                $this->output_table_creation_script();
            }
        } else {
            echo '<div class="notice notice-success"><p>';
            echo __('<strong>Great!</strong> All required database tables exist. Your plugin is ready to use.', 'multi-supplier-order-manager');
            echo '</p></div>';
            
            echo '<p><a href="' . admin_url('admin.php?page=msom-suppliers') . '" class="button button-primary">';
            echo __('Go to Supplier Manager', 'multi-supplier-order-manager');
            echo '</a></p>';
        }
        
        echo '</div>';
    }
    
    private function check_database_permissions() {
        global $wpdb;
        
        $permissions = array();
        
        $test_table = $wpdb->prefix . 'msom_test_permissions_' . time();
        $create_result = $wpdb->query("CREATE TABLE $test_table (id INT AUTO_INCREMENT PRIMARY KEY, test_col VARCHAR(10))");
        
        if ($create_result !== false) {
            $permissions['CREATE TABLE'] = array(
                'allowed' => true,
                'description' => 'Can create new database tables'
            );
            $wpdb->query("DROP TABLE IF EXISTS $test_table");
        } else {
            $permissions['CREATE TABLE'] = array(
                'allowed' => false,
                'description' => 'Cannot create new database tables - ' . $wpdb->last_error
            );
        }
        
        $show_result = $wpdb->get_results("SHOW TABLES");
        $permissions['SHOW TABLES'] = array(
            'allowed' => $show_result !== false,
            'description' => 'Can list existing database tables'
        );
        
        $select_result = $wpdb->get_var("SELECT 1");
        $permissions['SELECT'] = array(
            'allowed' => $select_result == 1,
            'description' => 'Can read from database'
        );
        
        return $permissions;
    }
    
    private function get_manual_sql() {
        global $wpdb;
        
        $suppliers_table = $wpdb->prefix . 'msom_suppliers';
        $product_suppliers_table = $wpdb->prefix . 'msom_product_suppliers';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "-- Multi-Supplier Order Manager Tables\n";
        $sql .= "-- Replace '{$wpdb->prefix}' with your actual WordPress table prefix if different\n\n";
        
        $sql .= "CREATE TABLE IF NOT EXISTS $suppliers_table (\n";
        $sql .= "    id mediumint(9) NOT NULL AUTO_INCREMENT,\n";
        $sql .= "    name varchar(255) NOT NULL,\n";
        $sql .= "    email varchar(255) NOT NULL,\n";
        $sql .= "    contact_person varchar(255),\n";
        $sql .= "    phone varchar(50),\n";
        $sql .= "    address text,\n";
        $sql .= "    additional_instructions text,\n";
        $sql .= "    created_at datetime DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
        $sql .= "    PRIMARY KEY (id),\n";
        $sql .= "    UNIQUE KEY email (email)\n";
        $sql .= ") $charset_collate;\n\n";
        
        $sql .= "CREATE TABLE IF NOT EXISTS $product_suppliers_table (\n";
        $sql .= "    id mediumint(9) NOT NULL AUTO_INCREMENT,\n";
        $sql .= "    product_id bigint(20) NOT NULL,\n";
        $sql .= "    supplier_id mediumint(9) NOT NULL,\n";
        $sql .= "    created_at datetime DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "    PRIMARY KEY (id),\n";
        $sql .= "    KEY product_id (product_id),\n";
        $sql .= "    KEY supplier_id (supplier_id)\n";
        $sql .= ") $charset_collate;";
        
        return $sql;
    }
    
    private function output_table_creation_script() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $("#msom-create-tables").click(function() {
                var button = $(this);
                var resultDiv = $("#msom-table-creation-result");
                
                button.prop("disabled", true).text("<?php echo esc_js(__('Creating Tables...', 'multi-supplier-order-manager')); ?>");
                resultDiv.html("<p><?php echo esc_js(__('Creating database tables, please wait...', 'multi-supplier-order-manager')); ?></p>");
                
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "msom_create_tables",
                        nonce: "<?php echo wp_create_nonce('msom_create_tables'); ?>"
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html("<div class=\"notice notice-success\"><p><strong><?php echo esc_js(__('Success!', 'multi-supplier-order-manager')); ?></strong> " + response.data.message + "</p></div>");
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            resultDiv.html("<div class=\"notice notice-error\"><p><strong><?php echo esc_js(__('Error:', 'multi-supplier-order-manager')); ?></strong> " + response.data.message + "</p></div>");
                            button.prop("disabled", false).text("<?php echo esc_js(__('Create Database Tables Safely', 'multi-supplier-order-manager')); ?>");
                        }
                    },
                    error: function() {
                        resultDiv.html("<div class=\"notice notice-error\"><p><strong><?php echo esc_js(__('Error:', 'multi-supplier-order-manager')); ?></strong> <?php echo esc_js(__('Failed to communicate with server. Please try again.', 'multi-supplier-order-manager')); ?></p></div>");
                        button.prop("disabled", false).text("<?php echo esc_js(__('Create Database Tables Safely', 'multi-supplier-order-manager')); ?>");
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function ajax_create_tables() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'msom_create_tables')) {
            wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'multi-supplier-order-manager')));
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'msom_suppliers';
        $product_supplier_table = $wpdb->prefix . 'msom_product_suppliers';
        $charset_collate = $wpdb->get_charset_collate();
        
        $errors = array();
        $success_messages = array();
        
        $permissions = $this->check_database_permissions();
        if (!$permissions['CREATE TABLE']['allowed']) {
            wp_send_json_error(array('message' => __('Database permissions insufficient: ', 'multi-supplier-order-manager') . $permissions['CREATE TABLE']['description']));
        }
        
        $suppliers_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$suppliers_exists) {
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
            
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                $errors[] = sprintf(__('Failed to create suppliers table: %s', 'multi-supplier-order-manager'), $wpdb->last_error);
            } else {
                $verify = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
                if ($verify) {
                    $success_messages[] = __('Suppliers table created successfully', 'multi-supplier-order-manager');
                } else {
                    $errors[] = __('Suppliers table creation appeared to succeed but table verification failed', 'multi-supplier-order-manager');
                }
            }
        } else {
            $success_messages[] = __('Suppliers table already exists', 'multi-supplier-order-manager');
        }
        
        $product_suppliers_exists = $wpdb->get_var("SHOW TABLES LIKE '$product_supplier_table'") == $product_supplier_table;
        if (!$product_suppliers_exists) {
            $sql2 = "CREATE TABLE $product_supplier_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                product_id bigint(20) NOT NULL,
                supplier_id mediumint(9) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY product_id (product_id),
                KEY supplier_id (supplier_id)
            ) $charset_collate;";
            
            $result2 = $wpdb->query($sql2);
            
            if ($result2 === false) {
                $errors[] = sprintf(__('Failed to create product-suppliers table: %s', 'multi-supplier-order-manager'), $wpdb->last_error);
            } else {
                $verify2 = $wpdb->get_var("SHOW TABLES LIKE '$product_supplier_table'") == $product_supplier_table;
                if ($verify2) {
                    $success_messages[] = __('Product-suppliers table created successfully', 'multi-supplier-order-manager');
                } else {
                    $errors[] = __('Product-suppliers table creation appeared to succeed but table verification failed', 'multi-supplier-order-manager');
                }
            }
        } else {
            $success_messages[] = __('Product-suppliers table already exists', 'multi-supplier-order-manager');
        }
        
        if (empty($errors)) {
            update_option('msom_tables_created', '1');
            delete_option('msom_table_creation_error');
            
            $message = implode('. ', $success_messages) . '. ' . __('Database setup completed successfully!', 'multi-supplier-order-manager');
            wp_send_json_success(array('message' => $message));
        } else {
            update_option('msom_tables_created', '0');
            update_option('msom_table_creation_error', implode('; ', $errors));
            
            $message = implode('; ', $errors);
            wp_send_json_error(array('message' => $message));
        }
    }
}
