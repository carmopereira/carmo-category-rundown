/**
 * Front-end behaviour for the category carousel: prev/next buttons that
 * scroll the track by one card, and that grey out at either end.
 */

function getStep( track ) {
	const card = track.querySelector( ':scope > li' );

	if ( ! card ) {
		return track.clientWidth;
	}

	const styles = window.getComputedStyle( track );
	const gap = parseFloat( styles.columnGap || styles.gap || 0 ) || 0;

	return card.getBoundingClientRect().width + gap;
}

function initCarousel( carousel ) {
	const track = carousel.querySelector( '.carmo-rundown__track' );

	if ( ! track ) {
		return;
	}

	const prevButton = carousel.querySelector( '.carmo-rundown__nav--prev' );
	const nextButton = carousel.querySelector( '.carmo-rundown__nav--next' );

	const update = () => {
		// 1px of slack for sub-pixel scroll positions.
		const maxScroll = track.scrollWidth - track.clientWidth - 1;
		const atStart = track.scrollLeft <= 1;
		const atEnd = track.scrollLeft >= maxScroll;

		if ( prevButton ) {
			prevButton.disabled = atStart;
		}
		if ( nextButton ) {
			nextButton.disabled = atEnd;
		}
		carousel.classList.toggle( 'carmo-rundown--scrollable', maxScroll > 0 );
	};

	prevButton?.addEventListener( 'click', () => {
		track.scrollBy( { left: -getStep( track ), behavior: 'smooth' } );
	} );

	nextButton?.addEventListener( 'click', () => {
		track.scrollBy( { left: getStep( track ), behavior: 'smooth' } );
	} );

	track.addEventListener( 'scroll', update, { passive: true } );

	if ( 'ResizeObserver' in window ) {
		new window.ResizeObserver( update ).observe( track );
	} else {
		window.addEventListener( 'resize', update );
	}

	update();
}

document
	.querySelectorAll( '.carmo-rundown' )
	.forEach( ( carousel ) => initCarousel( carousel ) );
