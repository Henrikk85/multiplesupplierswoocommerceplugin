<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'msom_suppliers';
$product_supplier_table = $wpdb->prefix . 'msom_product_suppliers';

$wpdb->query("DROP TABLE IF EXISTS $product_supplier_table");
$wpdb->query("DROP TABLE IF EXISTS $table_name");

delete_option('msom_transport_company_email');
delete_option('msom_transport_company_name');
delete_option('msom_email_subject_supplier');
delete_option('msom_email_subject_transport');
delete_option('msom_pdf_company_logo');
delete_option('msom_pdf_company_name');
delete_option('msom_pdf_company_address');

$upload_dir = wp_upload_dir();
$temp_dir = $upload_dir['basedir'] . '/msom-temp/';

if (is_dir($temp_dir)) {
    $files = glob($temp_dir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($temp_dir);
}

wp_clear_scheduled_hook('msom_cleanup_temp_files');
