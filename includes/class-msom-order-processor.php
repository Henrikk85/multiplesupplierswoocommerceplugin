<?php

if (!defined('ABSPATH')) {
    exit;
}

class MSOM_Order_Processor {
    
    private $supplier_manager;
    private $pdf_generator;
    private $email_sender;
    
    public function __construct() {
        $this->supplier_manager = new MSOM_Supplier_Manager();
        $this->pdf_generator = new MSOM_PDF_Generator();
        $this->email_sender = new MSOM_Email_Sender();
    }
    
    public function process_multi_supplier_order($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        $order_items = $order->get_items();
        $suppliers_data = $this->supplier_manager->get_suppliers_for_order_items($order_items);
        
        if (empty($suppliers_data)) {
            error_log('MSOM: No suppliers found for order ' . $order_id);
            return false;
        }
        
        if (count($suppliers_data) <= 1) {
            error_log('MSOM: Order ' . $order_id . ' has only one supplier, skipping multi-supplier processing');
            return false;
        }
        
        $this->send_supplier_emails($order, $suppliers_data);
        $this->send_transport_emails($order, $suppliers_data);
        
        $order->add_order_note(__('Multi-supplier order processed. Emails sent to suppliers and transport company.', 'multi-supplier-order-manager'));
        
        return true;
    }
    
    private function send_supplier_emails($order, $suppliers_data) {
        foreach ($suppliers_data as $supplier_data) {
            $supplier = $supplier_data['supplier'];
            $items = $supplier_data['items'];
            
            $pdf_path = $this->pdf_generator->generate_supplier_pdf($order, $supplier, $items);
            
            if ($pdf_path) {
                $this->email_sender->send_supplier_email($order, $supplier, $items, $pdf_path);
                
                wp_schedule_single_event(time() + 3600, 'msom_cleanup_temp_files', array($pdf_path));
            }
        }
    }
    
    private function send_transport_emails($order, $suppliers_data) {
        $transport_email = get_option('msom_transport_company_email');
        
        if (empty($transport_email)) {
            error_log('MSOM: Transport company email not configured');
            return;
        }
        
        foreach ($suppliers_data as $supplier_data) {
            $supplier = $supplier_data['supplier'];
            $items = $supplier_data['items'];
            
            $pdf_path = $this->pdf_generator->generate_transport_pdf($order, $supplier, $items);
            
            if ($pdf_path) {
                $this->email_sender->send_transport_email($order, $supplier, $items, $pdf_path);
                
                wp_schedule_single_event(time() + 3600, 'msom_cleanup_temp_files', array($pdf_path));
            }
        }
    }
}

add_action('msom_cleanup_temp_files', function($file_path) {
    if (file_exists($file_path)) {
        unlink($file_path);
    }
});
