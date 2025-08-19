<?php

if (!defined('ABSPATH')) {
    exit;
}

class MSOM_Email_Sender {
    
    public function send_supplier_email($order, $supplier, $items, $pdf_path) {
        $subject = get_option('msom_email_subject_supplier', __('New Order - Items for Supply', 'multi-supplier-order-manager'));
        $subject = str_replace('{order_number}', $order->get_order_number(), $subject);
        
        $message = $this->get_supplier_email_template($order, $supplier, $items);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $attachments = array($pdf_path);
        
        $sent = wp_mail($supplier->email, $subject, $message, $headers, $attachments);
        
        if ($sent) {
            $order->add_order_note(sprintf(__('Email sent to supplier %s (%s)', 'multi-supplier-order-manager'), $supplier->name, $supplier->email));
        } else {
            $order->add_order_note(sprintf(__('Failed to send email to supplier %s (%s)', 'multi-supplier-order-manager'), $supplier->name, $supplier->email));
            error_log('MSOM: Failed to send email to supplier ' . $supplier->email);
        }
        
        return $sent;
    }
    
    public function send_transport_email($order, $supplier, $items, $pdf_path) {
        $transport_email = get_option('msom_transport_company_email');
        
        if (empty($transport_email)) {
            error_log('MSOM: Transport company email not configured');
            return false;
        }
        
        $subject = get_option('msom_email_subject_transport', __('Pickup Required - Order Items', 'multi-supplier-order-manager'));
        $subject = str_replace('{order_number}', $order->get_order_number(), $subject);
        $subject = str_replace('{supplier_name}', $supplier->name, $subject);
        
        $message = $this->get_transport_email_template($order, $supplier, $items);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $attachments = array($pdf_path);
        
        $sent = wp_mail($transport_email, $subject, $message, $headers, $attachments);
        
        if ($sent) {
            $order->add_order_note(sprintf(__('Transport email sent for pickup at %s', 'multi-supplier-order-manager'), $supplier->name));
        } else {
            $order->add_order_note(sprintf(__('Failed to send transport email for pickup at %s', 'multi-supplier-order-manager'), $supplier->name));
            error_log('MSOM: Failed to send transport email for supplier ' . $supplier->name);
        }
        
        return $sent;
    }
    
    private function get_supplier_email_template($order, $supplier, $items) {
        $company_name = get_option('msom_pdf_company_name', get_bloginfo('name'));
        
        $items_list = '';
        foreach ($items as $item) {
            $product = $item->get_product();
            $items_list .= '<li>' . esc_html($item->get_name()) . ' - ' . __('Quantity:', 'multi-supplier-order-manager') . ' ' . $item->get_quantity() . '</li>';
        }
        
        $message = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .order-details { background-color: #e9ecef; padding: 15px; margin: 20px 0; }
                .items-list { background-color: #f8f9fa; padding: 15px; margin: 20px 0; }
                .footer { background-color: #6c757d; color: white; padding: 15px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . esc_html($company_name) . '</h1>
                <h2>' . __('New Order Notification', 'multi-supplier-order-manager') . '</h2>
            </div>
            
            <div class="content">
                <p>' . sprintf(__('Dear %s,', 'multi-supplier-order-manager'), esc_html($supplier->name)) . '</p>
                
                <p>' . __('We have received a new order that includes items from your inventory. Please find the details below:', 'multi-supplier-order-manager') . '</p>
                
                <div class="order-details">
                    <h3>' . __('Order Information', 'multi-supplier-order-manager') . '</h3>
                    <p><strong>' . __('Order Number:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_order_number() . '</p>
                    <p><strong>' . __('Order Date:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_date_created()->format('Y-m-d H:i:s') . '</p>
                    <p><strong>' . __('Customer:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</p>
                </div>
                
                <div class="items-list">
                    <h3>' . __('Items to Supply', 'multi-supplier-order-manager') . '</h3>
                    <ul>' . $items_list . '</ul>
                </div>
                
                <p>' . __('Please find the detailed order document attached as a PDF. This document contains all the information you need to prepare the items for pickup.', 'multi-supplier-order-manager') . '</p>
                
                <p>' . __('Our transportation company will contact you to arrange the pickup time. Please ensure the items are ready for collection.', 'multi-supplier-order-manager') . '</p>
                
                <p>' . __('If you have any questions or concerns, please don\'t hesitate to contact us.', 'multi-supplier-order-manager') . '</p>
                
                <p>' . __('Thank you for your continued partnership.', 'multi-supplier-order-manager') . '</p>
                
                <p>' . __('Best regards,', 'multi-supplier-order-manager') . '<br>' . esc_html($company_name) . '</p>
            </div>
            
            <div class="footer">
                <p>' . __('This is an automated message. Please do not reply to this email.', 'multi-supplier-order-manager') . '</p>
            </div>
        </body>
        </html>';
        
        return $message;
    }
    
    private function get_transport_email_template($order, $supplier, $items) {
        $company_name = get_option('msom_pdf_company_name', get_bloginfo('name'));
        $transport_company = get_option('msom_transport_company_name', '');
        
        $items_count = count($items);
        $total_quantity = 0;
        foreach ($items as $item) {
            $total_quantity += $item->get_quantity();
        }
        
        $message = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #007cba; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .pickup-details { background-color: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107; }
                .order-details { background-color: #d1ecf1; padding: 15px; margin: 20px 0; border-left: 4px solid #17a2b8; }
                .delivery-details { background-color: #d4edda; padding: 15px; margin: 20px 0; border-left: 4px solid #28a745; }
                .footer { background-color: #6c757d; color: white; padding: 15px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . __('Pickup Request', 'multi-supplier-order-manager') . '</h1>
                <h2>' . __('Transportation Assignment', 'multi-supplier-order-manager') . '</h2>
            </div>
            
            <div class="content">
                <p>' . sprintf(__('Dear %s,', 'multi-supplier-order-manager'), esc_html($transport_company)) . '</p>
                
                <p>' . __('We have a new pickup request for you. Please coordinate with the supplier to arrange the pickup time.', 'multi-supplier-order-manager') . '</p>
                
                <div class="pickup-details">
                    <h3>' . __('Pickup Location', 'multi-supplier-order-manager') . '</h3>
                    <p><strong>' . __('Supplier:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->name) . '</p>
                    <p><strong>' . __('Contact Person:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->contact_person) . '</p>
                    <p><strong>' . __('Phone:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->phone) . '</p>
                    <p><strong>' . __('Email:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->email) . '</p>
                    <p><strong>' . __('Address:', 'multi-supplier-order-manager') . '</strong><br>' . nl2br(esc_html($supplier->address)) . '</p>
                </div>
                
                <div class="order-details">
                    <h3>' . __('Order Information', 'multi-supplier-order-manager') . '</h3>
                    <p><strong>' . __('Order Number:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_order_number() . '</p>
                    <p><strong>' . __('Order Date:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_date_created()->format('Y-m-d H:i:s') . '</p>
                    <p><strong>' . __('Items Count:', 'multi-supplier-order-manager') . '</strong> ' . $items_count . ' ' . __('different products', 'multi-supplier-order-manager') . '</p>
                    <p><strong>' . __('Total Quantity:', 'multi-supplier-order-manager') . '</strong> ' . $total_quantity . ' ' . __('units', 'multi-supplier-order-manager') . '</p>
                </div>
                
                <div class="delivery-details">
                    <h3>' . __('Delivery Address', 'multi-supplier-order-manager') . '</h3>
                    <p><strong>' . __('Customer:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() . '</p>
                    <p><strong>' . __('Address:', 'multi-supplier-order-manager') . '</strong><br>
                    ' . $order->get_shipping_address_1() . '<br>
                    ' . ($order->get_shipping_address_2() ? $order->get_shipping_address_2() . '<br>' : '') . '
                    ' . $order->get_shipping_city() . ' ' . $order->get_shipping_postcode() . '<br>
                    ' . $order->get_shipping_country() . '</p>
                    <p><strong>' . __('Phone:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_billing_phone() . '</p>
                </div>
                
                <p>' . __('Please find the detailed pickup document attached as a PDF with complete item information, weights, and dimensions.', 'multi-supplier-order-manager') . '</p>
                
                <p><strong>' . __('Important:', 'multi-supplier-order-manager') . '</strong> ' . __('Please coordinate the pickup time with the supplier before arriving at their location.', 'multi-supplier-order-manager') . '</p>
                
                <p>' . __('If you have any questions or need additional information, please contact us immediately.', 'multi-supplier-order-manager') . '</p>
                
                <p>' . __('Thank you for your service.', 'multi-supplier-order-manager') . '</p>
                
                <p>' . __('Best regards,', 'multi-supplier-order-manager') . '<br>' . esc_html($company_name) . '</p>
            </div>
            
            <div class="footer">
                <p>' . __('This is an automated message. Please do not reply to this email.', 'multi-supplier-order-manager') . '</p>
            </div>
        </body>
        </html>';
        
        return $message;
    }
}
