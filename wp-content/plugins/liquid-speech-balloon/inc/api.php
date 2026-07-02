<?php
/**
 * JSONデータを取得する無名関数
 *
 * キャッシュが存在する場合はキャッシュを利用し、
 * キャッシュがない場合は外部リクエストを実行します。
 *
 * @param string $url           JSONデータを取得するURL
 * @param string $cache_key     キャッシュキー
 * @param int    $cache_duration キャッシュの有効期間（秒）
 *
 * @return array JSONデータ
 */
return function($url, $cache_key, $cache_duration = 12 * HOUR_IN_SECONDS) {
    // 管理画面内の特定のページでのみ動作
    if ( is_admin() && ( in_array($GLOBALS['pagenow'], ['index.php', 'themes.php', 'plugins.php', 'options-general.php']) || (isset($_GET['page']) && strpos($_GET['page'], 'liquid') !== false) ) ) {
        
        // キャッシュからデータを取得
        $data = get_transient($cache_key);

        // キャッシュが存在する場合はそのまま返す
        if ( $data !== false ) {
            return $data; // キャッシュデータを返す
        }

        // キャッシュがない場合、外部リクエストを実行
        $response = wp_remote_get($url);

        // リクエストエラーの処理
        if ( is_wp_error($response) ) {
            return []; // データは空の配列を返す
        }

        // レスポンスボディを取得してJSONデコード
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // デコード成功かつ配列の場合、キャッシュを保存して返す
        if ( json_last_error() === JSON_ERROR_NONE && is_array($data) ) {
            set_transient($cache_key, $data, $cache_duration); // キャッシュを保存
            return $data; // 正常なデータを返す
        }

        // デコードエラー時の処理
        return []; // データは空の配列を返す
    }

    // 条件に一致しない場合、データは空の配列を返す
    return []; // データは空の配列を返す
};
?>