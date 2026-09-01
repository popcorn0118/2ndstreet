<?php
/**
 * 「最新の投稿」 or 「投稿一覧」 のコンテンツ
 *   front-page.php と home.php から呼ばれることに注意。
 */

// 「投稿ページ」に設定された固定ページの場合
if ( ! is_front_page() ) {
	$page_obj     = get_queried_object();
	$page_content = $page_obj->post_content;

	if ( ! empty( $page_content ) ) {

		// the_content()を参考: https://core.trac.wordpress.org/browser/tags/5.8/src/wp-includes/post-template.php#L243
		$page_content = apply_filters( 'the_content', $page_content );
		$page_content = str_replace( ']]>', ']]&gt;', $page_content );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="' . esc_attr( Arkhe::get_post_content_class() ) . '">' . $page_content . '</div>';
	}
}

// 分類篩選（只在第一頁顯示）
if ( is_home() && ! is_paged() ) :
	?>
	<section class="p-categorySection">
		<h2 class="c-bottomSection__title u-mb-30 u-mt-60 c-titleMark"><?php esc_html_e( 'CATEGORY', 'arkhe' ); ?></h2>
		<?php Arkhe::get_part( 'other/category_filter' ); ?>
	</section>
	<?php
endif;

// ARCHIVE
?>
<section class="p-archiveSection" id="js-archiveList">
	<div class="l-container">
		<?php if ( is_home() && ! is_paged() ) : ?>
			<h2 class="c-bottomSection__title u-mb-30 c-titleMark"><?php esc_html_e( 'ARCHIVE', 'arkhe' ); ?></h2>
		<?php endif; ?>

		<?php
			// 投稿一覧
			Arkhe::get_part( 'post_list/main_query', array(
				'list_type' => apply_filters( 'arkhe_list_type_on_home', ARKHE_LIST_TYPE ),
			) );

			// ページャー
			the_posts_pagination( array(
				'mid_size' => 2,
				// 'screen_reader_text' => 'ページネーション',
			) );
		?>
	</div>
</section>
