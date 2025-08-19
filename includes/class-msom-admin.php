<?php

if (!defined('ABSPATH')) {
    exit;
}

class MSOM_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        add_action('save_post', array($this, 'save_product_supplier'));
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
        $current_supplier = $supplier_manager->get_product_supplier($post->ID);
        
        echo '<label for="msom_supplier_id">' . __('Select Supplier:', 'multi-supplier-order-manager') . '</label>';
        echo '<select name="msom_supplier_id" id="msom_supplier_id" style="width: 100%; margin-top: 5px;">';
        echo '<option value="">' . __('No Supplier', 'multi-supplier-order-manager') . '</option>';
        
        foreach ($suppliers as $supplier) {
            $selected = ($current_supplier && $current_supplier->supplier_id == $supplier->id) ? 'selected' : '';
            echo '<option value="' . $supplier->id . '" ' . $selected . '>' . esc_html($supplier->name) . '</option>';
        }
        
        echo '</select>';
    }
    
    public function save_product_supplier($post_id) {
        if (!isset($_POST['msom_product_supplier_nonce']) || !wp_verify_nonce($_POST['msom_product_supplier_nonce'], 'msom_save_product_supplier')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        $supplier_manager = new MSOM_Supplier_Manager();
        $supplier_id = isset($_POST['msom_supplier_id']) ? intval($_POST['msom_supplier_id']) : 0;
        
        if ($supplier_id > 0) {
            $supplier_manager->assign_product_to_supplier($post_id, $supplier_id);
        } else {
            $supplier_manager->remove_product_from_supplier($post_id);
        }
    }
}
