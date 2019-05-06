<?php

namespace CsrDelft\view;

/**
 * Icon dingetje voor csrdelft.nl.
 *
 * Gaat samen met 'layout/css/icons.less' en 'layout/css/icons.png'
 *
 * Icon::getTag('bewerken'); geeft <span class="ico pencil"></span>
 *
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 *
 */
class Icon {
	//handige dingen die we graag gebruiken in csrdelft.nl. Moeten geen namen zijn die al voorkomen
	//in de lijst met icons.
	public static $alias = array(
		// algemeen
		'toevoegen' => 'add',
		'bewerken' => 'edit',
		'verwijderen' => 'times',
		'alert' => 'stop',
		'goedkeuren' => 'tick',
		'verjaardag' => 'cake',
		'vraagteken' => 'help',
		'fout' => 'error',
		'show' => 'eye',
		//documumenten
		'mime-onbekend' => 'page_white',
		'mime-audio' => 'sound',
		'mime-html' => 'page_white_world',
		'mime-word' => 'page_white_word',
		'mime-excel' => 'page_white_excel',
		'mime-powerpoint' => 'page_white_powerpoint',
		'mime-image' => 'page_white_picture',
		'mime-pdf' => 'page_white_acrobat',
		'mime-plain' => 'page_white_text',
		'mime-zip' => 'page_white_zip',
		// forum
		'citeren' => 'comments',
		'slotje' => 'lock',
		'plakkerig' => 'note',
		'belangrijk' => 'asterisk_orange',
		// corvee
		'taken_bewerken' => 'text_list_bullets',
		'punten_bewerken' => 'award_star_gold_1',
		'punten_bewerken_toegekend' => 'award_star_gold_2',
		'gemaild' => 'email_go',
		'gemaildoranje' => 'email_go_orange',
		'niet_gemaild' => 'email',
		// profiel
		'stats' => 'server_chart',
		'su' => 'user_go',
		'resetpassword' => 'user_gray',
		'instellingen' => 'cog',
		// mededelingen
		'legenda' => 'tag_yellow',
		// Melding
		'alert-danger' => 'exclamation',
		'alert-info' => 'information',
		'alert-success' => 'accept',
		'alert-warning' => 'bell',
		// Overig
		'table' => 'table_normal',
		'log' => 'report',
		'lock_open' => 'lock',
		'pencil' => 'edit',
		'tab' => 'bookmark',
		'thumb_down' => 'thumbs-down',
		'arrow_right' => 'truck-moving',
	);

	public static $layer = [
		'email_delete' => [['fas fa-envelope'], ['fas fa-ban', 'shrink-6 right-5 down-4', 'color:Tomato']],
		'email_error' => [['fas fa-envelope'], ['fas fa-exclamation-triangle', 'shrink-6 right-5 down-4', 'color:Orange']],
		'email_add' => [['fas fa-envelope'], ['fas fa-plus-circle', 'shrink-6 right-5 down-4', 'color:Green']],
		'layout' => [['fas fa-caret-square-left'], ['fas fa-plus-circle', 'shrink-6 right-5 down-4', 'color:Green']],
		'neus2013' => [['fas fa-circle', 'down-1.8 shrink-6', 'width: 9px;margin-left: -2px;color:Tomato']],
	];

	public static function get($key) {
		if (array_key_exists($key, self::$alias)) {
			return self::$alias[$key];
		} else {
			return $key;
		}
	}

	/**
	 * @param string $key Naam van het icoon, mag een alias zijn
	 * @param null $hover string Naam van het icoon bij muis-over
	 * @param null $title string Titel van het icoon
	 * @param string $class
	 * @param null $content string Inhoud van dit icoon, is verborgen in de browser, maar wordt wel
	 * geselecteerd en door eventuele schermlezers opgevangen
	 * @return string
	 */
	public static function getTag($key, $hover = null, $title = null, $class = null, $content = null) {
		$icon = self::get($key);
		if ($hover !== null) {
			$hover = 'hover-' . self::get($hover);
		}
		if ($title !== null) {
			$title = 'title="' . str_replace('&amp;', '&', htmlspecialchars($title)) . '" ';
		}

		if (array_key_exists($key, static::$layer)) {
			$tags = array_map(function ($el) {
				if (count($el) == 1) {
					return vsprintf('<i class="%s"></i>', $el);
				} else if (count($el) == 2) {
					return vsprintf('<i class="%s" data-fa-transform="%s"></i>', $el);
				} else if (count($el) == 3) {
					return vsprintf('<i class="%s" data-fa-transform="%s" style="%s"></i>', $el);
				}

				return '';
			}, static::$layer[$key]);
			return vsprintf('<span class="fa-layers">%s</span>', [implode('',$tags)]);
		}

		return sprintf('<span class="fa fa-%s %s %s" %s>%s</span>', htmlspecialchars($icon), htmlspecialchars($hover), htmlspecialchars($class), $title, htmlspecialchars($content));
	}
}
