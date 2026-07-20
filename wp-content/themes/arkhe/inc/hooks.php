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


/**
 * 首頁「過去文章一覽」列表：僅顯示 2026/7 以前的文章
 * （2026/7 起的新文章改由首頁最上方的輪播區塊顯示，見 hook_front_carousel()）
 */
add_action( 'pre_get_posts', __NAMESPACE__ . '\hook_home_list_before_july' );
function hook_home_list_before_july( $query ) {

	if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() ) return;

	$query->set( 'date_query', array(
		array(
			'before'    => '2026-07-01 00:00:00',
			'inclusive' => false,
		),
	) );
}


/**
 * 首頁最上方：2026/7 起新文章的輪播區塊
 */
add_action( 'arkhe_before_home_content', __NAMESPACE__ . '\hook_front_carousel' );
function hook_front_carousel() {

	if ( is_paged() ) return;

	$carousel_query = new \WP_Query( array(
		'post_type'           => 'post',
		'posts_per_page'      => -1,
		'ignore_sticky_posts' => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'date_query'          => array(
			array(
				'after'     => '2026-07-01 00:00:00',
				'inclusive' => true,
			),
		),
	) );

	if ( ! $carousel_query->have_posts() ) {
		wp_reset_postdata();
		return;
	}

	// 先把每張投影片的內容暫存起來，才能在首尾各複製一張做成無限循環輪播
	$slides = array();
	while ( $carousel_query->have_posts() ) :
		$carousel_query->the_post();
		ob_start();
		?>
		<a href="<?php the_permalink(); ?>" class="p-postList__link">
			<?php
				\Arkhe::get_part( 'post_list/item/thumb', array(
					'sizes' => '(min-width: 1000px) 66vw, (min-width: 600px) 88vw, 100vw',
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
				<?php
					\Arkhe::get_part( 'post_list/item/meta', array(
						'show_date' => true,
					) );
				?>
			</div>
		</a>
		<?php
		$slides[] = ob_get_clean();
	endwhile;
	wp_reset_postdata();

	$total = count( $slides );
	?>
	<section class="p-frontCarousel">
		<div class="p-frontCarousel__stage">
			<div class="p-frontCarousel__viewport" data-arkhe-carousel>
				<ul class="p-postList -type-carousel">
					<?php if ( $total > 1 ) : ?>
						<li class="p-postList__item" data-carousel-index="<?php echo esc_attr( $total - 1 ); ?>" inert aria-hidden="true">
							<?php echo $slides[ $total - 1 ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</li>
					<?php endif; ?>
					<?php foreach ( $slides as $index => $slide_html ) : ?>
						<li class="p-postList__item" data-carousel-index="<?php echo esc_attr( $index ); ?>">
							<?php echo $slide_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</li>
					<?php endforeach; ?>
					<?php if ( $total > 1 ) : ?>
						<li class="p-postList__item" data-carousel-index="0" inert aria-hidden="true">
							<?php echo $slides[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</li>
					<?php endif; ?>
				</ul>
			</div>
			<?php if ( $total > 1 ) : ?>
				<button type="button" class="p-frontCarousel__arrow -prev" data-arkhe-carousel-prev aria-label="<?php esc_attr_e( 'Previous', 'arkhe' ); ?>">
					<?php \Arkhe::the_svg( 'chevron-left' ); ?>
				</button>
				<button type="button" class="p-frontCarousel__arrow -next" data-arkhe-carousel-next aria-label="<?php esc_attr_e( 'Next', 'arkhe' ); ?>">
					<?php \Arkhe::the_svg( 'chevron-right' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php if ( $total > 1 ) : ?>
			<div class="p-frontCarousel__dots" data-arkhe-carousel-dots>
				<?php for ( $i = 0; $i < $total; $i++ ) : ?>
					<button
						type="button"
						class="p-frontCarousel__dot<?php echo ( 0 === $i ) ? ' is-active' : ''; ?>"
						data-arkhe-carousel-dot
						aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Go to slide %d', 'arkhe' ), $i + 1 ) ); ?>"
					></button>
				<?php endfor; ?>
			</div>
		<?php endif; ?>
	</section>
	<?php if ( $total > 1 ) : ?>
	<script>
	( function() {
		var carousel = document.currentScript.previousElementSibling;
		if ( ! carousel || ! carousel.classList.contains( 'p-frontCarousel' ) ) return;

		// slides 包含首尾各一張複製的投影片；索引 0 與最後一個是複製的，1~total 才是真實投影片
		var viewport = carousel.querySelector( '[data-arkhe-carousel]' );
		var slides   = Array.prototype.slice.call( carousel.querySelectorAll( '.p-postList__item' ) );
		var prevBtn  = carousel.querySelector( '[data-arkhe-carousel-prev]' );
		var nextBtn  = carousel.querySelector( '[data-arkhe-carousel-next]' );
		var dots     = Array.prototype.slice.call( carousel.querySelectorAll( '[data-arkhe-carousel-dot]' ) );
		var total    = dots.length;

		if ( ! viewport || ! slides.length || ! total ) return;

		var currentIndex = 1;
		var settleTimer   = null;

		// 讓指定投影片剛好置中在 viewport 內所需的 scrollLeft（用實際畫面座標計算，不依賴 offsetParent）
		function centerScrollLeft( slide ) {
			var slideRect    = slide.getBoundingClientRect();
			var viewportRect = viewport.getBoundingClientRect();
			var delta        = ( slideRect.left + slideRect.width / 2 ) - ( viewportRect.left + viewportRect.width / 2 );
			return viewport.scrollLeft + delta;
		}

		// 目前實際最靠近置中位置的投影片（用畫面座標比對，比 IntersectionObserver 的可視比例判斷更準確，不受相鄰投影片露出多少影響）
		function nearestSlideIndex() {
			var viewportRect   = viewport.getBoundingClientRect();
			var viewportCenter = viewportRect.left + viewportRect.width / 2;
			var closest        = 0;
			var minDist        = Infinity;

			slides.forEach( function( slide, i ) {
				var rect = slide.getBoundingClientRect();
				var dist = Math.abs( ( rect.left + rect.width / 2 ) - viewportCenter );
				if ( dist < minDist ) {
					minDist = dist;
					closest = i;
				}
			} );

			return closest;
		}

		function setActiveDot( slideIndex ) {
			var realIndex = parseInt( slides[ slideIndex ].dataset.carouselIndex, 10 );
			dots.forEach( function( dot, i ) {
				dot.classList.toggle( 'is-active', i === realIndex );
			} );
		}

		function goTo( slideIndex, smooth ) {
			currentIndex = slideIndex;
			// 'instant' 一定會跳過捲動動畫（不像 'auto' 會被 CSS 的 scroll-behavior 影響）
			viewport.scrollTo( { left: centerScrollLeft( slides[ slideIndex ] ), behavior: smooth ? 'smooth' : 'instant' } );
			setActiveDot( slideIndex );
		}

		if ( prevBtn ) prevBtn.addEventListener( 'click', function() { goTo( currentIndex - 1, true ); } );
		if ( nextBtn ) nextBtn.addEventListener( 'click', function() { goTo( currentIndex + 1, true ); } );

		dots.forEach( function( dot, i ) {
			dot.addEventListener( 'click', function() { goTo( i + 1, true ); } );
		} );

		// 捲動停止後（不論是使用者手動滑動還是點擊按鈕造成的），確認目前置中的投影片
		viewport.addEventListener( 'scroll', function() {
			clearTimeout( settleTimer );
			settleTimer = setTimeout( function() {
				currentIndex = nearestSlideIndex();
				setActiveDot( currentIndex );

				// 停在首尾複製的投影片上，無動畫（往同方向）跳回對應的真實投影片，做出無限循環的效果
				if ( 0 === currentIndex ) {
					goTo( total, false );
				} else if ( slides.length - 1 === currentIndex ) {
					goTo( 1, false );
				}
			}, 120 );
		}, { passive: true } );

		// 一開始先無動畫定位到第一張真實投影片（跳過開頭複製的「最後一張」）
		requestAnimationFrame( function() { goTo( 1, false ); } );
	} )();
	</script>
	<?php endif; ?>
	<?php
}
