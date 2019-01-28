import $ from 'jquery';
import {activeerLidHints} from './bbcode-hints';
import ctx from './ctx';
import {modalClose, modalOpen} from './modal';
import {html} from './util';

ctx.init({
	'div.bb-img-loading': initLazyImages,
	'textarea.BBCodeField': activeerLidHints,
});

function initLazyImages(el: HTMLElement) {
	const content = html`<img
													class="bb-img"
													alt="${el.getAttribute('title')!}"
													style="${el.getAttribute('style')!}"
													src="${el.getAttribute('src')!}"/>`;
	content.onerror = () => {
		el.setAttribute('title', 'Afbeelding bestaat niet of is niet toegankelijk!');
		el.setAttribute('src', '/plaetjes/famfamafm/picture_error.png');
		el.style.width = '16px';
		el.style.height = '16px';
		el.classList.replace('bb-img-loading', 'bb-img');
	};
	content.onload = () => {
		const foto = content.getAttribute('src')!.indexOf('/plaetjes/fotoalbum/') >= 0;
		const video = $(el).parent().parent().hasClass('bb-video-preview');
		const hasAnchor = $(el).closest('a').length !== 0;
		el.parentElement!.replaceWith(el);
		if (!foto && !video && !hasAnchor) {
			$(el).wrap(`<a class="lightbox-link" href="${$(el).attr('src')}" data-lightbox="page-lightbox"></a>`);
		}
	};

	el.append(content);
}

export function domUpdate(this: HTMLElement|void, htmlString: string) {
	htmlString = $.trim(htmlString);
	if (htmlString.substring(0, 9) === '<!DOCTYPE') {
		alert('response error');
		document.write(htmlString);
	}
	const elements = html`${htmlString}`;
	$(elements).each(function () {
		const id = $(this).attr('id');

		const elmnt = $('#' + id);
		if (elmnt.length === 1) {
			if ($(this).hasClass('remove')) {
				elmnt.effect('fade', {}, 400, () => {
					$(this).remove();
				});
			} else {
				elmnt.replaceWith($(this).show()).effect('highlight');
			}
		} else {
			const parentid = $(this).attr('parentid');
			if (parentid) {
				$(this).prependTo(`#${parentid}`).show().effect('highlight');
			} else {
				$(this).prependTo('#maalcie-tabel tbody:visible:first').show().effect('highlight'); // FIXME: make generic
			}
		}
		ctx.initContext(this);

		if (id === 'modal') {
			modalOpen();
		} else {
			modalClose();
		}
	});
}
