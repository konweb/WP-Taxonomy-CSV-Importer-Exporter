<?php
/**
 * Taxonomy Importer
 */
class Tax_CSV_importer {
	/**
	 * [import description]
	 * @param  [type] $file [description]
	 * @return [type]       [description]
	 */
	public static function import( $file ) {
		global $wpdb;

		// CSVを配列に変換
		$file = new SplFileObject( $file );
		$file->setFlags(SplFileObject::READ_CSV);
		foreach ($file as $line) {
			$records[] = $line; 
		}

		// キー名を振り直す
		$term_keys = $records[0];
		$term_arr  = [];
		unset( $records[0] );
		foreach ( $records as $record ) {
			$term_arr[] = array_combine( $term_keys, $record );
		}

		$default_keys = Tax_CSV_helper::get_default_keys();

		// $acf_field_names = array_unique( array_merge($default_keys, $term_keys) );
		$acf_field_names = array_merge($default_keys, $term_keys);
		$acf_field_names = array_unique($acf_field_names);
		var_dump($term_keys);
		var_dump($default_keys);
		var_dump($acf_field_names);

		foreach ( $term_arr as $key => $args ) {
			$id         = $args['term_id'];
			$tax_before = ($key-1) < 0 ? null : $term_arr[$key-1]['taxonomy'];
			$tax        = $args['taxonomy'];
			$name       = $args['name'];

			// taxonomyの値が変更した場合、カスタムフィールドキーを再取得
			if ( $tax_before != $tax ) {
				// $custom_field_keys = Tax_CSV_helper::get_acf_keys( $tax );
			}

			// 不要なキーを削除
			unset($args['term_id']);
			unset($args['taxonomy']);
			unset($args['name']);

			// 追加、更新
			if ( empty( $id ) ) {
				// $insert_term = wp_insert_term( $name, $tax, $args );
				// $id          = $insert_term;
			} else {
				// wp_update_term( $id, $tax, $args );
			}

			// Advanced Custom Fields 更新
			$acf_field_names = [];
			foreach ( $args as $arg_key => $arg_val) {
				$acf_field_name = str_replace( $tax . '_', '', $arg_key );

				if ( !in_array( $acf_field_name, $default_keys ) ) {
					$acf_object = get_field_object( $acf_field_name, $tax . '_' . $id );
					$acf_value  = $arg_val;
					$acf_key    = $acf_object['key'];

					// var_dump($acf_object);
					if ( $acf_key == 'field_' . $acf_field_name ) {
						$postmeta_rule  = $wpdb->get_results("SELECT post_id FROM ". $wpdb->postmeta . " WHERE meta_key='rule' AND meta_value LIKE '%".$tax."%'");
						$postmeta_field = $wpdb->get_results("SELECT post_id FROM ". $wpdb->postmeta . " WHERE meta_key LIKE 'field_%' AND meta_value LIKE '%".$acf_field_name."%'");
						// echo '===================================<br>';
						// echo 'acf_field_name: '.$acf_field_name.'<br>';
						// var_dump($postmeta_rule);
						// var_dump($postmeta_field);

						$postmeta_rule_id = array_map(function($obj) {
							return $obj->post_id;
						}, $postmeta_rule);
						$postmeta_field_id = array_map(function($obj) {
							return $obj->post_id;
						}, $postmeta_field);
						// var_dump($postmeta_rule_id);
						// var_dump($postmeta_field);

						$field_id = array_values(array_intersect($postmeta_rule_id, $postmeta_field_id))[0];
						// var_dump($field_id);

						$get_acf_object = $postmeta_field = $wpdb->get_results("SELECT * FROM ". $wpdb->postmeta . " WHERE meta_key LIKE 'field_%' AND post_id=".$field_id."");
						// var_dump($get_acf_object);
						if ( count($get_acf_object) > 0 ) {
							$acf_key = $get_acf_object[0]->meta_key;
						}
					}
					var_dump($acf_object);

					// echo 'acf_key: '.$id.'-'.$acf_key.'<br>';

					// チェックボックスの場合、「,」で区切り配列に変換
					// echo 'type: '.$acf_object['type'].'<br>';
					if ( $acf_object['type'] == 'checkbox' ) {
						$acf_value = explode( ',', $acf_value );
					}
					// echo "ok: ".$replace_key.'<br>';
					// var_dump($acf_value);
					if ( !empty( $acf_value ) ) {
						// update_field( $acf_object['key'], $acf_value, $tax . '_' . $id );
						// update_field( $acf_object['key'], $acf_value, $tax . '_' . $id );
					}
					// print_r('<pre>');
					// print_r($acf_object);
					// print_r('</pre>');
				}
			}
		}
		exit;
	}
}