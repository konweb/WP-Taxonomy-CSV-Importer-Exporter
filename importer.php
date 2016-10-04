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
	public function import( $file ) {
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

		foreach ( $term_arr as $key => $args ) {
			$id         = $args['term_id'];
			$tax_before = ($key-1) < 0 ? null : $term_arr[$key-1]['taxonomy'];
			$tax        = $args['taxonomy'];
			$name       = $args['name'];

			// taxonomyの値が変更した場合、カスタムフィールドキーを再取得
			if ( $tax_before != $tax ) {
				$custom_field_keys = Tax_CSV_helper::get_acf_keys( $tax );
			}

			// 不要なキーを削除
			unset($args[['term_id']]);
			unset($args[['taxonomy']]);
			unset($args[['name']]);

			// 追加、更新
			if ( empty( $id ) ) {
				$insert_term = wp_insert_term( $name, $tax, $args );

				// カスタムフィールドの追加
				foreach ( $custom_field_keys['original'] as $key => $field_key ) {
					add_option( $tax . '_' . $insert_term['term_id'] . '_' . $field_key, $args[$custom_field_keys['add_tax_name'][$key]] );
				}
			} else {
				wp_update_term( $id, $tax, $args );

				// カスタムフィールドの更新
				foreach ( $custom_field_keys['original'] as $key => $field_key ) {
					update_option( $tax . '_' . $id . '_' . $field_key, $args[$custom_field_keys['add_tax_name'][$key]] );
				}
			}

		}
	}
}