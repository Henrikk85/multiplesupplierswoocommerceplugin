<?php

if (!defined('ABSPATH')) {
    exit;
}

class MSOM_PDF_Generator {
    
    public function generate_supplier_pdf($order, $supplier, $items) {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/msom-temp/';
        
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        $filename = 'supplier-order-' . $order->get_id() . '-' . $supplier->id . '-' . time() . '.pdf';
        $file_path = $temp_dir . $filename;
        
        $html = $this->generate_supplier_html($order, $supplier, $items);
        
        if ($this->html_to_pdf($html, $file_path)) {
            return $file_path;
        }
        
        return false;
    }
    
    public function generate_transport_pdf($order, $supplier, $items) {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/msom-temp/';
        
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        $filename = 'transport-order-' . $order->get_id() . '-' . $supplier->id . '-' . time() . '.pdf';
        $file_path = $temp_dir . $filename;
        
        $html = $this->generate_transport_html($order, $supplier, $items);
        
        if ($this->html_to_pdf($html, $file_path)) {
            return $file_path;
        }
        
        return false;
    }
    
    private function generate_supplier_html($order, $supplier, $items) {
        $company_name = get_option('msom_pdf_company_name', get_bloginfo('name'));
        $company_address = get_option('msom_pdf_company_address', '');
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .company-info { margin-bottom: 20px; }
                .order-info { margin-bottom: 20px; }
                .supplier-info { margin-bottom: 20px; }
                .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .items-table th { background-color: #f2f2f2; }
                .total { text-align: right; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . esc_html($company_name) . '</h1>
                <h2>' . __('Supplier Order Document', 'multi-supplier-order-manager') . '</h2>
            </div>
            
            <div class="company-info">
                <h3>' . __('Company Information', 'multi-supplier-order-manager') . '</h3>
                <p>' . nl2br(esc_html($company_address)) . '</p>
            </div>
            
            <div class="order-info">
                <h3>' . __('Order Information', 'multi-supplier-order-manager') . '</h3>
                <p><strong>' . __('Order Number:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_order_number() . '</p>
                <p><strong>' . __('Order Date:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_date_created()->format('Y-m-d H:i:s') . '</p>
                <p><strong>' . __('Customer:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</p>
            </div>
            
            <div class="supplier-info">
                <h3>' . __('Supplier Information', 'multi-supplier-order-manager') . '</h3>
                <p><strong>' . __('Name:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->name) . '</p>
                <p><strong>' . __('Email:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->email) . '</p>
                <p><strong>' . __('Contact Person:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->contact_person) . '</p>
                <p><strong>' . __('Phone:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->phone) . '</p>
                <p><strong>' . __('Address:', 'multi-supplier-order-manager') . '</strong> ' . nl2br(esc_html($supplier->address)) . '</p>
            </div>
            
            <h3>' . __('Items to Supply', 'multi-supplier-order-manager') . '</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>' . __('Product', 'multi-supplier-order-manager') . '</th>
                        <th>' . __('SKU', 'multi-supplier-order-manager') . '</th>
                        <th>' . __('Quantity', 'multi-supplier-order-manager') . '</th>
                        <th>' . __('Price', 'multi-supplier-order-manager') . '</th>
                        <th>' . __('Total', 'multi-supplier-order-manager') . '</th>
                    </tr>
                </thead>
                <tbody>';
        
        $total = 0;
        foreach ($items as $item) {
            $product = $item->get_product();
            $item_total = $item->get_total();
            $total += $item_total;
            
            $html .= '<tr>
                <td>' . esc_html($item->get_name()) . '</td>
                <td>' . esc_html($product->get_sku()) . '</td>
                <td>' . $item->get_quantity() . '</td>
                <td>' . wc_price($item->get_total() / $item->get_quantity()) . '</td>
                <td>' . wc_price($item_total) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
            </table>
            
            <div class="total">
                <p><strong>' . __('Total Amount:', 'multi-supplier-order-manager') . ' ' . wc_price($total) . '</strong></p>
            </div>
            
            <p>' . __('Please prepare these items for pickup by our transportation company.', 'multi-supplier-order-manager') . '</p>
        </body>
        </html>';
        
        return $html;
    }
    
    private function generate_transport_html($order, $supplier, $items) {
        $company_name = get_option('msom_pdf_company_name', get_bloginfo('name'));
        $transport_company = get_option('msom_transport_company_name', '');
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .pickup-info { margin-bottom: 20px; background-color: #f9f9f9; padding: 15px; }
                .order-info { margin-bottom: 20px; }
                .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .items-table th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . __('Pickup Request', 'multi-supplier-order-manager') . '</h1>
                <h2>' . __('Transportation Document', 'multi-supplier-order-manager') . '</h2>
            </div>
            
            <div class="pickup-info">
                <h3>' . __('Pickup Information', 'multi-supplier-order-manager') . '</h3>
                <p><strong>' . __('Pickup Location:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->name) . '</p>
                <p><strong>' . __('Address:', 'multi-supplier-order-manager') . '</strong> ' . nl2br(esc_html($supplier->address)) . '</p>
                <p><strong>' . __('Contact Person:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->contact_person) . '</p>
                <p><strong>' . __('Phone:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->phone) . '</p>
                <p><strong>' . __('Email:', 'multi-supplier-order-manager') . '</strong> ' . esc_html($supplier->email) . '</p>
            </div>
            
            <div class="order-info">
                <h3>' . __('Order Information', 'multi-supplier-order-manager') . '</h3>
                <p><strong>' . __('Order Number:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_order_number() . '</p>
                <p><strong>' . __('Order Date:', 'multi-supplier-order-manager') . '</strong> ' . $order->get_date_created()->format('Y-m-d H:i:s') . '</p>
                <p><strong>' . __('Delivery Address:', 'multi-supplier-order-manager') . '</strong><br>
                ' . $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() . '<br>
                ' . $order->get_shipping_address_1() . '<br>
                ' . ($order->get_shipping_address_2() ? $order->get_shipping_address_2() . '<br>' : '') . '
                ' . $order->get_shipping_city() . ' ' . $order->get_shipping_postcode() . '<br>
                ' . $order->get_shipping_country() . '</p>
            </div>
            
            <h3>' . __('Items to Pickup', 'multi-supplier-order-manager') . '</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>' . __('Product', 'multi-supplier-order-manager') . '</th>
                        <th>' . __('SKU', 'multi-supplier-order-manager') . '</th>
                        <th>' . __('Quantity', 'multi-supplier-order-manager') . '</th>
                        <th>' . __('Weight', 'multi-supplier-order-manager') . '</th>
                        <th>' . __('Dimensions', 'multi-supplier-order-manager') . '</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($items as $item) {
            $product = $item->get_product();
            $weight = $product->get_weight() ? $product->get_weight() . ' ' . get_option('woocommerce_weight_unit') : __('N/A', 'multi-supplier-order-manager');
            $dimensions = '';
            if ($product->get_length() && $product->get_width() && $product->get_height()) {
                $dimensions = $product->get_length() . ' x ' . $product->get_width() . ' x ' . $product->get_height() . ' ' . get_option('woocommerce_dimension_unit');
            } else {
                $dimensions = __('N/A', 'multi-supplier-order-manager');
            }
            
            $html .= '<tr>
                <td>' . esc_html($item->get_name()) . '</td>
                <td>' . esc_html($product->get_sku()) . '</td>
                <td>' . $item->get_quantity() . '</td>
                <td>' . $weight . '</td>
                <td>' . $dimensions . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
            </table>
            
            <p><strong>' . __('Special Instructions:', 'multi-supplier-order-manager') . '</strong> ' . __('Please coordinate pickup time with the supplier before arrival.', 'multi-supplier-order-manager') . '</p>
        </body>
        </html>';
        
        return $html;
    }
    
    private function html_to_pdf($html, $file_path) {
        if (class_exists('TCPDF')) {
            return $this->generate_pdf_with_tcpdf($html, $file_path);
        } elseif (class_exists('mPDF')) {
            return $this->generate_pdf_with_mpdf($html, $file_path);
        } else {
            return $this->generate_pdf_fallback($html, $file_path);
        }
    }
    
    private function generate_pdf_with_tcpdf($html, $file_path) {
        try {
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor(get_bloginfo('name'));
            $pdf->SetTitle('Order Document');
            $pdf->SetSubject('Multi-Supplier Order');
            
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
            
            $pdf->Output($file_path, 'F');
            return true;
        } catch (Exception $e) {
            error_log('MSOM TCPDF Error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function generate_pdf_with_mpdf($html, $file_path) {
        try {
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($html);
            $mpdf->Output($file_path, 'F');
            return true;
        } catch (Exception $e) {
            error_log('MSOM mPDF Error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function generate_pdf_fallback($html, $file_path) {
        error_log('MSOM: PDF generation failed - no PDF libraries available (TCPDF, mPDF, wkhtmltopdf)');
        error_log('MSOM: Emails will not be sent as per user requirements when PDF generation fails');
        return false;
    }
}
