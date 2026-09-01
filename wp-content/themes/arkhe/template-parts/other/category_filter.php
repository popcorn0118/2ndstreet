<?php
/**
 * 分類篩選按鈕
 *   只顯示 ACF 欄位 show=true 的分類，點擊後篩選下方文章列表（js: dist/js/category-filter.js）
 */
if ( ! function_exists( 'get_field' ) ) return;

$categories       = get_categories( array( 'hide_empty' => true ) );
$show_categories  = array();

foreach ( $categories as $category ) {
	if ( get_field( 'show', $category ) ) {
		$show_categories[] = $category;
	}
}

if ( empty( $show_categories ) ) return;
?>
<div class="p-categoryFilter" data-category-filter>
	<ul class="p-categoryFilter__list">
		<?php foreach ( $show_categories as $category ) : ?>
			<?php
				$img     = get_field( 'cat_img', $category );
				$img_url = '';
				if ( is_array( $img ) ) {
					$img_url = isset( $img['sizes']['medium'] ) ? $img['sizes']['medium'] : ( isset( $img['url'] ) ? $img['url'] : '' );
				} elseif ( is_numeric( $img ) ) {
					$img_url = wp_get_attachment_image_url( $img, 'medium' );
				} elseif ( is_string( $img ) ) {
					$img_url = $img;
				}

				$name_en = get_field( 'name_en', $category );
			?>
			<li class="p-categoryFilter__item">
				<button type="button" class="p-categoryFilter__btn" data-cat-id="<?php echo esc_attr( $category->term_id ); ?>">
					<?php if ( $img_url ) : ?>
						<span class="p-categoryFilter__imgWrap">
							<img class="p-categoryFilter__img" src="<?php echo esc_url( $img_url ); ?>" alt="" loading="lazy">
						</span>
					<?php endif; ?>
					<span class="p-categoryFilter__body">
						<span class="p-categoryFilter__name"><?php echo esc_html( $category->name ); ?></span>
						<?php if ( $name_en ) : ?>
							<span class="p-categoryFilter__nameEn"><?php echo esc_html( $name_en ); ?></span>
						<?php endif; ?>
						<span class="p-categoryFilter__arrow"></span>
					</span>
				</button>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
