<?php
class Tax_CSV_exporter {
	public static function export() {
		date_default_timezone_set('Asia/Tokyo');

		global $wpdb;

		//メモリ上に領域確保
		$fp = fopen('php://temp/maxmemory:'.(5*1024*1024),'r+');

		// タクソノミー名
		$tax_name = $_POST['tax_csv_export_select'];

		// タクソノミーの取得
		$terms    = get_terms( $tax_name, array( 'hide_empty' => false ) );

		// オブジェクトを配列に変換
		$terms    = json_decode(json_encode($terms), true);

		// csv格納用
		$csv_data = [];

		// 最初の配列にフィールド名を格納
		$csv_data[] = array_keys( $terms[0] );

		// カスタムフィールド key名格納用
		$custom_field_keys = Tax_CSV_helper::get_acf_keys( $tax_name );

		$csv_data[0] = array_merge( $csv_data[0], $custom_field_keys['add_tax_name'] );

		// CSV用配列に格納
		foreach ( $terms as $term ) {
			// カスタムフィールドの値を取得し、配列に追加
			foreach ( $custom_field_keys['original'] as $field_key ) {
				$field_val = get_field( $field_key, $tax_name . '_' .$term['term_id'] );
				if ( is_array( $field_val ) ) {
					$field_val = implode( ',', $field_val );
				}
				$term[] = $field_val;
			}
			$csv_data[] = array_values( $term );
		}

		foreach( $csv_data as $data ) {
			fputcsv( $fp, $data );
		}

		$dl_file_name = $tax_name . '-' . date( 'ymd-Gi', time() ) . '.csv';
		header('Content-Type: text/csv');
		header("Content-Disposition: attachment; filename=" . $dl_file_name);

		//ファイルポインタを先頭へ
		rewind( $fp );

		//リソースを読み込み文字列取得
		$csv = stream_get_contents( $fp );
		$csv = mb_convert_encoding( $csv, 'utf8' );
		$csv = urldecode( $csv );

		print $csv;

		fclose( $fp );
	}
}