<?php
/**
 * 投稿リストのカテゴリー
 */
$the_cat = \Arkhe::get_a_catgory_for_list();
if ( empty( $the_cat ) ) return;

$cat_name = function_exists( 'get_field' ) ? get_field( 'name_en', $the_cat ) : '';
if ( empty( $cat_name ) ) $cat_name = $the_cat->name;
?>
<div class="p-postList__category u-color-thin u-flex--aic">
	<?php Arkhe::the_svg( 'folder', array( 'class' => 'c-postMetas__icon' ) ); ?>
	<span data-cat-id="<?php echo esc_attr( $the_cat->term_id ); ?>"><?php echo esc_html( $cat_name ); ?></span>
</div>
