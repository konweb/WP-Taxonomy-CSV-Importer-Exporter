<?php
/*
Plugin Name: Taxonomy CSV Importer & Exporter
Version: 1.0.0
Description: Taxonomy CSV Importer & Exporter
Author: Naoto Kondou
Author URI: 
Plugin URI: 
Text Domain: tax-csv-importer-exporter
Domain Path: /languages
*/

define('TAX_CSV_PLUGIN_NAME', 'Taxonomy CSV Importer & Exporter');
define('TAX_CSV_PLUGIN_DIR' , plugin_dir_path(__FILE__));
define('TAX_CSV_PLUGIN_URL' , plugin_dir_url(__FILE__));

/**
 * Load Importer API
 */
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( !class_exists( 'WP_Importer' ) ) {
  $class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
  if ( file_exists( $class_wp_importer ) ) {
    require_once $class_wp_importer;
  }
}

/**
 * load modules
 */
require TAX_CSV_PLUGIN_DIR . 'helper.php';
require TAX_CSV_PLUGIN_DIR . 'admin.php';
require TAX_CSV_PLUGIN_DIR . 'exporter.php';
require TAX_CSV_PLUGIN_DIR . 'importer.php';

/**
 * Taxonomy CSV Importer & Exporter
 */
class Tax_CSV_importer_exporter extends WP_Importer {
  /**
   * [__construct description]
   */
  public function __construct() {
    Tax_CSV_importer_exporter_admin::add_action();
    add_action( 'admin_init', array( __CLASS__, 'init' ) );
  }

  /**
   * [init description]
   * @return [type] [description]
   */
  public static function init() {
    /**
     * エクスポートがリクエストされた場合
     */
    if ( isset( $_POST['tax_csv_export_submit'] ) ) {
      Tax_CSV_exporter::export();
      exit;
    }

    /**
     * アップロードファイルの取得
     * @var [type]
     */
    $get_files = wp_import_handle_upload();

    /**
     * ファイルがアップロードされた場合
     */
    if ( $get_files['file'] ) {
      Tax_CSV_importer::import( $get_files['file'] );
    }
  }
}

/**
 * 初期化
 * @return [type] [description]
 */
function tax_csv_importer_exporter() {
  $tax_csv_importer_exporter = new Tax_CSV_importer_exporter();
}
add_action( 'plugins_loaded', 'tax_csv_importer_exporter' );