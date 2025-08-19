<?php

if (!defined('ABSPATH')) {
    exit;
}

class MSOM_Supplier_Manager {
    
    public function get_all_suppliers() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_suppliers';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");
    }
    
    public function get_supplier($supplier_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_suppliers';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $supplier_id));
    }
    
    public function add_supplier($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_suppliers';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => sanitize_text_field($data['name']),
                'email' => sanitize_email($data['email']),
                'contact_person' => sanitize_text_field($data['contact_person']),
                'phone' => sanitize_text_field($data['phone']),
                'address' => sanitize_textarea_field($data['address']),
                'additional_instructions' => sanitize_textarea_field($data['additional_instructions'])
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('MSOM: Failed to add supplier. Error: ' . $wpdb->last_error);
        }
        
        return $result;
    }
    
    public function update_supplier($supplier_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_suppliers';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'name' => sanitize_text_field($data['name']),
                'email' => sanitize_email($data['email']),
                'contact_person' => sanitize_text_field($data['contact_person']),
                'phone' => sanitize_text_field($data['phone']),
                'address' => sanitize_textarea_field($data['address']),
                'additional_instructions' => sanitize_textarea_field($data['additional_instructions'])
            ),
            array('id' => $supplier_id),
            array('%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            error_log('MSOM: Failed to update supplier. Error: ' . $wpdb->last_error);
            return false;
        }
        
        return $result;
    }
    
    public function delete_supplier($supplier_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_suppliers';
        $product_supplier_table = $wpdb->prefix . 'msom_product_suppliers';
        
        $wpdb->delete($product_supplier_table, array('supplier_id' => $supplier_id), array('%d'));
        
        return $wpdb->delete($table_name, array('id' => $supplier_id), array('%d'));
    }
    
    public function assign_product_to_suppliers($product_id, $supplier_ids) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_product_suppliers';
        
        error_log('MSOM Debug: assign_product_to_suppliers called with product_id=' . $product_id . ', supplier_ids=' . print_r($supplier_ids, true));
        
        $wpdb->delete($table_name, array('product_id' => $product_id), array('%d'));
        
        if (empty($supplier_ids)) {
            error_log('MSOM Debug: No supplier IDs provided, returning true');
            return true;
        }
        
        $success = true;
        foreach ($supplier_ids as $supplier_id) {
            if (empty($supplier_id)) {
                error_log('MSOM Debug: Skipping empty supplier_id');
                continue;
            }
            
            error_log('MSOM Debug: Inserting product_id=' . $product_id . ', supplier_id=' . $supplier_id);
            
            $result = $wpdb->insert(
                $table_name,
                array(
                    'product_id' => $product_id,
                    'supplier_id' => $supplier_id
                ),
                array('%d', '%d')
            );
            
            if ($result === false) {
                $success = false;
                error_log('MSOM: Failed to assign product to supplier. Error: ' . $wpdb->last_error);
            } else {
                error_log('MSOM Debug: Successfully inserted assignment with ID: ' . $wpdb->insert_id);
            }
        }
        
        error_log('MSOM Debug: assign_product_to_suppliers returning: ' . ($success ? 'true' : 'false'));
        return $success;
    }
    
    public function assign_product_to_supplier($product_id, $supplier_id) {
        return $this->assign_product_to_suppliers($product_id, array($supplier_id));
    }
    
    public function remove_product_from_supplier($product_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_product_suppliers';
        
        return $wpdb->delete($table_name, array('product_id' => $product_id), array('%d'));
    }
    
    public function get_product_suppliers($product_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_product_suppliers';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE product_id = %d",
            $product_id
        ));
    }
    
    public function get_product_supplier($product_id) {
        $suppliers = $this->get_product_suppliers($product_id);
        return !empty($suppliers) ? $suppliers[0] : null;
    }
    
    public function get_suppliers_for_order_items($order_items) {
        global $wpdb;
        $suppliers_table = $wpdb->prefix . 'msom_suppliers';
        $product_suppliers_table = $wpdb->prefix . 'msom_product_suppliers';
        
        $suppliers_data = array();
        
        foreach ($order_items as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            
            $check_id = $variation_id ? $variation_id : $product_id;
            
            $suppliers = $wpdb->get_results($wpdb->prepare(
                "SELECT s.* FROM $suppliers_table s 
                 INNER JOIN $product_suppliers_table ps ON s.id = ps.supplier_id 
                 WHERE ps.product_id = %d",
                $check_id
            ));
            
            foreach ($suppliers as $supplier) {
                if (!isset($suppliers_data[$supplier->id])) {
                    $suppliers_data[$supplier->id] = array(
                        'supplier' => $supplier,
                        'items' => array()
                    );
                }
                $suppliers_data[$supplier->id]['items'][] = $item;
            }
        }
        
        return $suppliers_data;
    }
}
