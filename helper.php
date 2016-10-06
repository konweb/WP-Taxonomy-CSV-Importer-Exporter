<?php
class Tax_CSV_helper {
	/**
	 * [get_default_keys description]
	 * @return [type] [description]
	 */
	public static function get_default_keys() {
		return ['term_id', 'name', 'slug', 'term_order', 'taxonomy', 'description', 'parent'];
	}

	/**
	 * [get_acf_keys description]
	 * @param  string $tax_name [description]
	 * @return [type]           [description]
	 */
	public static function get_acf_keys( $tax_name = 'category' ) {
		global $wpdb;

		$keys = [];

		/**
		 * [$get_custom_field description]
		 * @var [type]
		 */
		$get_custom_field = $wpdb->get_results("SELECT * FROM ". $wpdb->options . " WHERE option_name REGEXP '^" . $tax_name . "_[0-9]'");

		/**
		 * カスタムフィールドが存在する場合、キー配列に追加
		 */
		if ( count( $get_custom_field ) > 0 ) {
			foreach ( $get_custom_field as $field ) {
				$split  = preg_split( "/" . $tax_name . "_[0-9]*_/", $field->option_name );
				$keys['original'][]     = $split[count( $split ) -1];
				$keys['add_tax_name'][] = $tax_name . '_' . $split[count( $split ) -1];
			}

			/**
			 * 重複削除
			 */
			$keys['original']     = array_unique( $keys['original'] );
			$keys['add_tax_name'] = array_unique( $keys['add_tax_name'] );

			/**
			 * キーを再設定
			 */
			$keys['original']     = array_values( $keys['original'] );
			$keys['add_tax_name'] = array_values( $keys['add_tax_name'] );
		}
		
		return $keys;
	}
}