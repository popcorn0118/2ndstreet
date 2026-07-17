<?php
namespace Arkhe_Theme\Hooks;

require_once __DIR__ . '/hooks/self_hooks.php';


/**
 * 5.9から最初の画像に loading lazy が付かなくなるのを回避する
 */
add_filter( 'wp_omit_loading_attr_threshold', function() {
	return 0;
} );


/**
 * 抜粋文字数を変更する
 */
add_filter( 'excerpt_length', __NAMESPACE__ . '\hook_excerpt_length' );
add_filter( 'excerpt_mblength', __NAMESPACE__ . '\hook_excerpt_length' );
function hook_excerpt_length( $length ) {
	if ( is_admin() ) return $length;

	if ( null !== \Arkhe::$excerpt_length ) {
		return \Arkhe::$excerpt_length;
	}
	return $length;
}


/**
 * 抜粋文の末尾を ... に
 */
add_filter( 'excerpt_more', __NAMESPACE__ . '\hook_excerpt_more' );
function hook_excerpt_more( $more ) {
	if ( is_admin() ) return $more;
	return '&hellip;';
}


/**
 * Feedlyでアイキャッチ画像を取得できるようにする
 */
add_filter( 'the_excerpt_rss', __NAMESPACE__ . '\add_rss_thumb' );
add_filter( 'the_content_feed', __NAMESPACE__ . '\add_rss_thumb' );
function add_rss_thumb( $content ) {
	global $post;

	$thumb = get_the_post_thumbnail_url( $post->ID, 'large' );
	if ( $thumb ) {
		$content = '<p><img src="' . esc_url( $thumb ) . '" class="webfeedsFeaturedVisual" /></p>' . $content;
	}
	return $content;
}


/**
 * カテゴリーリストのカスタマイズ
 */
add_action( 'wp_list_categories', __NAMESPACE__ . '\hook_wp_list_categories', 10, 2 );
function hook_wp_list_categories( $output, $args ) {

	// walker指定された特殊なリストには何もしない
	// if ( isset( $args['walker'] ) ) return $output;

	if ( apply_filters( 'arkhe_move_post_count_into_a', true, 'wp_list_categories' ) ) {
		// 投稿数を<a>の中に移動
		$output = preg_replace( '/<\/a>\s*\((\d+)\)/', ' <span class="c-postCount">($1)</span></a>', $output );
	}

	if ( ! $args['hierarchical'] ) return $output;

	// memo: ul を含む li
	// $regex = '/<li[^>]*>(?:(?!<\/li>).)*<ul/s';

	// サブメニューがある場合（ </a><ul> ）、liにクラスを追加してトグルボタンを追加
	//   (?:  )→グループ化するが、キャプチャしない。
	//   (?!<\/a>). → </a> が続かない任意の文字列
	//   (?:(?!<\/a>).)* → </a> が続かない任意の文字列を0回以上繰り返す
	$regex  = '/<li class="([^"]*)">\s*(<a(?:(?!<\/a>).)*)<\/a>\s*<ul/s';
	$output = preg_replace_callback( $regex, function( $matches ) {
		$li_class = $matches[1];
		$a_tag    = $matches[2] . ark_get__submenu_toggle_btn() . '</a>';
		return '<li class="' . $li_class . ' has-child--acc">' . $a_tag . '<ul';
	}, $output   );

	return $output;
}


/**
 * 固定ページリストにサブメニューがある場合（ </a><ul> ）、トグルボタンを追加
 * ブロックの方にフックが効かないので、サブメニューのアコーディオン化はしない
 */
// add_action( 'wp_list_pages', __NAMESPACE__ . '\hook_wp_list_pages' );
// function hook_wp_list_pages( $output ) {
//  $output = preg_replace(
//      '/<\/a>([^<]*)<ul/',
//      ark_get__submenu_toggle_btn() . '</a><ul',
//      $output
//  );
//  return $output;
// }


/**
 * 年別アーカイブリストの投稿件数 を</a>の中に置換
 */
add_action( 'get_archives_link', __NAMESPACE__ . '\hook_get_archives_link', 10, 6 );
function hook_get_archives_link( $link_html, $url, $text, $format, $before, $after ) {
	if ( ! apply_filters( 'arkhe_move_post_count_into_a', true, 'get_archives_link' ) ) return $link_html;
	if ( 'html' === $format ) {
		$after     = str_replace( '&nbsp;', '', $after );
		$link_html = '<li>' . $before . '<a href="' . $url . '">' . $text . '<span class="c-postCount">' . $after . '</span></a></li>';
	}
	return $link_html;
}


/**
 * カテゴリーチェック時、順番をそのままに保つ
 */
add_action( 'wp_terms_checklist_args', __NAMESPACE__ . '\hook_terms_checklist_args', 10 );
function hook_terms_checklist_args( $args ) {
	$args['checked_ontop'] = false;
	return $args;
}


/**
 * ページネーションの構造を書き換える
 */
add_filter( 'navigation_markup_template', __NAMESPACE__ . '\hook_navigation_markup', 10, 2 );
function hook_navigation_markup( $template, $class ) {
	if ( 'pagination' === $class ) {
		return '<nav class="navigation %1$s" role="navigation" aria-label="%4$s">%3$s</nav>';
	}
	return $template;
}


/**
 * 延伸閱讀（ACF: related_post）を記事コンテンツの末尾に挿入
 */
add_action( 'arkhe_after_entry_content', __NAMESPACE__ . '\hook_extended_reading' );
function hook_extended_reading( $the_id ) {

	if ( 'post' !== get_post_type( $the_id ) || ! function_exists( 'get_field' ) ) return;

	$related_posts = get_field( 'related_post', $the_id );
	if ( empty( $related_posts ) ) return;

	$list_type = \Arkhe::get_setting( 'related_posts_layout' );

	global $post;
	$original_post = $post;
	?>
	<section class="p-entry__extendedReading c-bottomSection">
		<h3 class="c-bottomSection__title u-ta-c"><?php esc_html_e( '延伸閱讀', 'arkhe' ); ?></h3>
		<ul class="p-postList -type-<?php echo esc_attr( $list_type ); ?> -related">
			<?php foreach ( (array) $related_posts as $related_post ) : ?>
				<?php
					$post = $related_post;
					setup_postdata( $post );
				?>
				<li class="p-postList__item">
					<a href="<?php the_permalink(); ?>" class="p-postList__link">
						<?php
							\Arkhe::get_part( 'post_list/item/thumb', array(
								'size'  => 'medium',
								'sizes' => '(min-width: 600px) 400px, 50vw',
							) );
						?>
						<div class="p-postList__body">
							<h2 class="p-postList__title">
								<?php
									$title       = get_the_title();
									$title_parts = preg_split( '/<br\s*\/?>/i', $title, 2 );
									if ( isset( $title_parts[1] ) ) {
										echo '<span class="title-top">' . $title_parts[0] . '</span><br><span class="title-bottom">' . $title_parts[1] . '</span>';
									} else {
										echo $title;
									}
								?>
							</h2>
							<div class="p-postList__excerpt"><?php the_excerpt(); ?></div>
							<?php
								\Arkhe::get_part( 'post_list/item/meta', array(
									'show_date' => true,
								) );
							?>
						</div>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
	$post = $original_post;
	wp_reset_postdata();
}
