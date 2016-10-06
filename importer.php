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

		/**
		 * CSVを配列に変換
		 * @var SplFileObject
		 */
		$file = new SplFileObject( $file );
		$file->setFlags(SplFileObject::READ_CSV);
		foreach ($file as $line) {
			$records[] = $line; 
		}

		/**
		 * キー名を振り直す
		 * @var [type]
		 */
		$term_keys = $records[0];
		$term_arr  = [];
		unset( $records[0] );
		foreach ( $records as $record ) {
			$term_arr[] = array_combine( $term_keys, $record );
		}

		/**
		 * カスタムフィールドのキーのみを抽出
		 * @var [type]
		 */
		$default_keys     = Tax_CSV_helper::get_default_keys();
		$merge_keys       = array_merge( $default_keys, $term_keys );
		$merge_keys_value = array_count_values( $merge_keys );
		foreach( $merge_keys_value as $k => $v ) {
			if($v === 1) {
				$acf_field_names[] = $k;
			}
		}

		/**
		 * 値を更新・追加
		 * @var [type]
		 */
		foreach ( $term_arr as $key => $args ) {
			$id         = $args['term_id'];
			$tax_before = ($key-1) < 0 ? null : $term_arr[$key-1]['taxonomy'];
			$tax        = $args['taxonomy'];
			$name       = $args['name'];

			/**
			 * タクソノミーの追加・更新
			 */
			if ( empty( $id ) ) {
				$insert = wp_insert_term( $name, $tax, $args );
				$id     = $insert['term_id'];
			} else {
				wp_update_term( $id, $tax, $args );
			}

			/**
			 * Advanced custom fieldsの値を更新
			 * @var [type]
			 */
			foreach ( $acf_field_names as $acf_field_name) {
				$acf_value      = $args[$acf_field_name];
				$acf_prefix     = $tax . '_';
				$acf_field_name = str_replace( $acf_prefix, '', $acf_field_name );
				$acf_object     = get_field_object( $acf_field_name, $acf_prefix . $id );
				$acf_key        = $acf_object['key'];

				/**
				 * フィールド名が 'field_'{field_name} となっている場合
				 * ACFのキーが取得できていないため、DBから検索
				 * @var [type]
				 */
				if ( $acf_key == 'field_' . $acf_field_name ) {
					/**
					 * DB検索
					 * @var [type]
					 */
					$postmeta_rule  = $wpdb->get_results("SELECT post_id FROM ". $wpdb->postmeta . " WHERE meta_key='rule' AND meta_value LIKE '%".$tax."%'");
					$postmeta_field = $wpdb->get_results("SELECT post_id FROM ". $wpdb->postmeta . " WHERE meta_key LIKE 'field_%' AND meta_value LIKE '%".$acf_field_name."%'");

					/**
					 * post_idの抽出
					 * @var array
					 */
					$postmeta_rule_id  = array_map( function( $obj ) { return $obj->post_id; }, $postmeta_rule);
					$postmeta_field_id = array_map( function( $obj ) { return $obj->post_id; }, $postmeta_field);

					/**
					 * ACFのpost_idを抽出
					 * @var array
					 */
					$field_id = array_values( array_intersect( $postmeta_rule_id, $postmeta_field_id ) )[0];

					/**
					 * post_idからACFのデータを再取得
					 * @var [type]
					 */
					$get_acf_object = $postmeta_field = $wpdb->get_results("SELECT * FROM ". $wpdb->postmeta . " WHERE meta_key LIKE 'field_%' AND post_id=".$field_id."");
					if ( count( $get_acf_object ) > 0 ) {
						$acf_object = get_field_object( $get_acf_object[0]->meta_key, $acf_prefix . $id );
					}
				}

				/**
				 * チェックボックスの場合、「,」で区切り配列に変換
				 */
				if ( $acf_object['type'] == 'checkbox' ) {
					$acf_value = explode( ',', $acf_value );
				}

				/**
				 * 画像の場合は、URLから画像をダウンロードしIDをセット
				 */
				if ( $acf_object['type'] == 'image' ) {
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
					require_once( ABSPATH . 'wp-admin/includes/media.php' );

					/**
					 * 画像 insert
					 * @var [type]
					 */
					if ( file_exists( $acf_value ) ) {
						$filename       = $acf_value;
						$filetype       = wp_check_filetype( basename( $filename ), null );
						$wp_upload_dir  = wp_upload_dir();
						$parent_post_id = 0;
						$attachment     = array(
							'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
							'post_mime_type' => $filetype['type'],
							'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
							'post_content'   => '',
							'post_status'    => 'inherit'
						);
						$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );
						$acf_value = $attach_id;

						/**
						 * 添付ファイルのメタデータを生成し、データベースを更新。
						 */
						$attach_data = wp_generate_attachment_metadata( $attach_id, $wp_upload_dir['path'] . '/' . basename( $filename ) );
						wp_update_attachment_metadata( $attach_id, $attach_data );
					}
				}

				/**
				 * 更新
				 */
				if ( !empty( $acf_value ) ) {
					update_field( $acf_object['key'], $acf_value, $acf_prefix . $id );
				}
			}
		}
	}
}