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
    
    public function delete_supplier($supplier_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_suppliers';
        $product_supplier_table = $wpdb->prefix . 'msom_product_suppliers';
        
        $wpdb->delete($product_supplier_table, array('supplier_id' => $supplier_id), array('%d'));
        
        return $wpdb->delete($table_name, array('id' => $supplier_id), array('%d'));
    }
    
    public function assign_product_to_supplier($product_id, $supplier_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_product_suppliers';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE product_id = %d",
            $product_id
        ));
        
        if ($existing) {
            return $wpdb->update(
                $table_name,
                array('supplier_id' => $supplier_id),
                array('product_id' => $product_id),
                array('%d'),
                array('%d')
            );
        } else {
            return $wpdb->insert(
                $table_name,
                array(
                    'product_id' => $product_id,
                    'supplier_id' => $supplier_id
                ),
                array('%d', '%d')
            );
        }
    }
    
    public function remove_product_from_supplier($product_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_product_suppliers';
        
        return $wpdb->delete($table_name, array('product_id' => $product_id), array('%d'));
    }
    
    public function get_product_supplier($product_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msom_product_suppliers';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE product_id = %d",
            $product_id
        ));
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
            
            $supplier = $wpdb->get_row($wpdb->prepare(
                "SELECT s.* FROM $suppliers_table s 
                 INNER JOIN $product_suppliers_table ps ON s.id = ps.supplier_id 
                 WHERE ps.product_id = %d",
                $check_id
            ));
            
            if ($supplier) {
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
