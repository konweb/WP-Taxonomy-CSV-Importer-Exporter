<?php

/**
 * 管理画面生成
 */
class Tax_CSV_importer_exporter_admin {
  /**
   * [__construct description]
   */
  public static function add_action() {
    add_action( 'admin_menu', array( __CLASS__, 'add_sub_menu' ) );
  }

  /**
   * サブメニュー追加
   */
  public function add_sub_menu() {
    add_submenu_page('tools.php', TAX_CSV_PLUGIN_NAME, 'Taxonomy CSV', 'manage_options', 'tax-csv-importer-exporter', array( __CLASS__, 'admin_html' ) );
  }

  /**
   * 管理画面HTML
   * @return [type] [description]
   */
  public function admin_html() {
    $taxs = get_taxonomies( '', 'objects' );

    // 不要なタクソノミーを除外
    unset($taxs['nav_menu']);
    unset($taxs['link_category']);
    unset($taxs['post_format']);
    ?>
      <div class="wrap">
        <h2><?php echo TAX_CSV_PLUGIN_NAME; ?></h2>
        <h3>エクスポート</h3>
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
          <select name="tax_csv_export_select">
          <?php foreach ( $taxs as $tax ) { ?>
            <option value="<?php echo $tax->name; ?>"><?php echo $tax->label; ?></option>
          <?php } ?>
          </select>
          <p class="submit"><input type="submit" name="tax_csv_export_submit" id="submit" class="button" value="エクスポート"></p>
        </form>

        <h3>インポート</h3>
        <?php wp_import_upload_form( add_query_arg('step', 1) ); ?>
      </div>
    <?php
  }
}