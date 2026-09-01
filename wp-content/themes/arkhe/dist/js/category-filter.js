/**
 * 首頁分類篩選
 *   點擊按鈕只顯示 #js-archiveList 內該分類的文章，再點一次恢復顯示全部
 */
document.addEventListener( 'DOMContentLoaded', function () {
	var filterWrap = document.querySelector( '[data-category-filter]' );
	var listWrap = document.getElementById( 'js-archiveList' );
	if ( ! filterWrap || ! listWrap ) return;

	var btns = filterWrap.querySelectorAll( '[data-cat-id]' );

	btns.forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var catId = btn.getAttribute( 'data-cat-id' );
			var isCurrent = btn.classList.contains( 'current' );
			var items = listWrap.querySelectorAll( '[data-cats]' );

			btns.forEach( function ( b ) {
				b.classList.remove( 'current' );
			} );

			if ( isCurrent ) {
				items.forEach( function ( item ) {
					item.hidden = false;
				} );
				return;
			}

			btn.classList.add( 'current' );

			items.forEach( function ( item ) {
				var cats = item.getAttribute( 'data-cats' ).split( ',' );
				item.hidden = -1 === cats.indexOf( catId );
			} );
		} );
	} );
} );
