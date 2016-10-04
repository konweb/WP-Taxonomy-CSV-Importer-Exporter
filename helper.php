<?php
class Tax_CSV_helper {
  public function get_acf_keys( $tax_name = 'category' ) {
    global $wpdb;

    $keys = [];

    // カスタムフィールドを検索
    $get_custom_field = $wpdb->get_results("SELECT * FROM ". $wpdb->options . " WHERE option_name REGEXP '^" . $tax_name . "_[0-9]'");

    // カスタムフィールドが存在する場合、キー配列に追加
    if ( count( $get_custom_field ) > 0 ) {
      foreach ( $get_custom_field as $field ) {
        $split  = preg_split( "/" . $tax_name . "_[0-9]*_/", $field->option_name );
        $keys['original'][]     = $split[count( $split ) -1];
        $keys['add_tax_name'][] = $tax_name . '_' . $split[count( $split ) -1];
      }
    }

    return $keys;
  }
}