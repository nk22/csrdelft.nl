/**
 * maalcie.js	|	P.W.G. Brussee (brussee@live.nl)
 *
 * requires jQuery & dragobject.js
 */
import $ from 'jquery';

import {ajaxRequest} from './ajax';
import {domUpdate} from './context';

$(function () {
	$('a.ruilen').each(function () {
		$(this).removeClass('ruilen');
		$(this).on('dragover', takenMagRuilen);
		$(this).on('drop', takenRuilen);
	});
});

/**
 * @see templates/maalcie/corveetaak/beheer_taak_datum.tpl
 * @see templates/maalcie/corveetaak/beheer_taak_head.tpl
 * @param datum
 */
window.taken_toggle_datum = function(datum) {
	takenToggleDatumFirst(datum, 0);
	$('.taak-datum-' + datum).toggle();
	takenToggleDatumFirst(datum, 1);
	takenColorDatum();

};

function takenToggleDatumFirst(datum, index) {
	if ('taak-datum-head-' + datum === $('#maalcie-tabel tr:visible').eq(index).attr('id')) {
		$('#taak-datum-head-first').toggle();
	}
}
function takenColorDatum() {
	$('tr.taak-datum-summary:visible:odd th').css('background-color', '#FAFAFA');
	$('tr.taak-datum-summary:visible:even th').css('background-color', '#f5f5f5');
}

/**
 * @see templates/maalcie/corveetaak/beheer_taken.tpl
 */
window.taken_show_old = function() {
	$('#taak-datum-head-first').show();
	$('tr.taak-datum-oud').show();
	takenColorDatum();
};

/**
 * @see templates/maalcie/corveetaak/suggesties_lijst.tpl
 * @see view/maalcie/forms/SuggestieLijst.php
 * @param soort
 * @param show
 */
window.taken_toggle_suggestie = function(soort, show) {
	$('#suggesties-tabel .' + soort).each(function () {
        let verborgen = 0;
        if (typeof show !== 'undefined') {
			if (show) {
				$(this).removeClass(soort + 'verborgen');
			}
			else {
				$(this).addClass(soort + 'verborgen');
			}
		}
		else {
			$(this).toggleClass(soort + 'verborgen');
		}
		if ($(this).hasClass('geenvoorkeurverborgen')) {
			verborgen++;
		}
		if ($(this).hasClass('recentverborgen')) {
			verborgen++;
		}
		if ($(this).hasClass('jongsteverborgen')) {
			verborgen++;
		}
		if ($(this).hasClass('oudereverborgen')) {
			verborgen++;
		}
		if (verborgen > 0) {
			$(this).hide();
		}
		else {
			$(this).show();
		}
	});
	taken_color_suggesties();
};

/**
 * @see view/maalcie/forms/SuggestieLijst.php
 */
window.taken_color_suggesties = function() {
    let $suggestiesTabel = $('#suggesties-tabel');
    $suggestiesTabel.find('tr:visible:odd').css('background-color', '#FAFAFA');
	$suggestiesTabel.find('tr:visible:even').css('background-color', '#EBEBEB');
};

let lastSelectedId;
/**
 * @see csrdelft.js
 * @param e
 */
export function takenSelectRange(e) {
	let withinRange = false;
	$('#maalcie-tabel').find('tbody tr td a input[name="' + $(e.target).attr('name') + '"]:visible').each(function () {
		let thisId = $(this).attr('id');
		if (thisId === lastSelectedId) {
			withinRange = !withinRange;
		}
		if (thisId === e.target.id) {
			withinRange = !withinRange;
			let check = $(this).prop('checked');
			setTimeout(function () { // workaround e.preventDefault()
				$('#' + thisId).prop('checked', check);
			}, 50);
		}
		else if (e.shiftKey && withinRange) {
			$(this).prop('checked', true);
		}
	});
	lastSelectedId = e.target.id;
}

/**
 * @see csrdelft.js
 * @param e
 * @returns {boolean}
 */
export function takenSubmitRange(e) {
	if (e.target.tagName.toUpperCase() === 'IMG') { // over an image inside of anchor
		e.target = $(e.target).parent();
	}
	$(e.target).find('input').prop('checked', true);
	if ($(e.target).hasClass('confirm') && !confirm($(e.target).attr('title') + '.\n\nWeet u het zeker?')) {
		return false;
	}
	$('input[name="' + $(e.target).find('input:first').attr('name') + '"]:visible').each(function () {
		if ($(this).prop('checked')) {
			ajaxRequest('POST', $(this).parent().attr('href'), $(this).parent().attr('post'), $(this).parent(), domUpdate, alert);
		}
	});
}

/* Ruilen van CorveeTaak */

function takenMagRuilen(e) {
	if (e.target.tagName.toUpperCase() === 'IMG') { // over an image inside of anchor
		e.target = $(e.target).parent();
	}
	let source = $('#' + window.dragobjectID);
	if ($(source).attr('id') !== $(e.target).attr('id')) {
		e.preventDefault();
	}
}
function takenRuilen(e) {
	e.preventDefault();
	let elmnt = e.target;
	if (elmnt.tagName.toUpperCase() === 'IMG') { // dropped on image inside of anchor
		elmnt = $(elmnt).parent();
	}
	let source = $('#' + window.dragobjectID);
	if (!confirm('Toegekende corveepunten worden meegeruild!\n\nDoorgaan met ruilen?')) {
		return;
	}
	let attr = $(source).attr('uid');
	if (typeof attr === 'undefined' || attr === false) {
		attr = '';
	}
	ajaxRequest('POST', $(elmnt).attr('href'), 'uid=' + attr, elmnt, domUpdate, alert);
	attr = $(elmnt).attr('uid');
	if (typeof attr === 'undefined' || attr === false) {
		attr = '';
	}
	ajaxRequest('POST', $(source).attr('href'), 'uid=' + attr, source, domUpdate, alert);
}
