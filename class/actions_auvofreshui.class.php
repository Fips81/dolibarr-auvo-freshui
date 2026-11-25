<?php
/*
 * Copyright (C) 2026 auvo-design <r.vogel@auvo-design.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AuVo FreshUI - Hook: gibt Akzentfarbe und Layout-Klassen schon im <head> aus (kein Aufblitzen).
 */

/**
 * Hook-Klasse, Kontext 'main'
 */
class ActionsAuvofreshui
{
	/** @var string */
	public $resprints = '';
	/** @var string[] */
	public $errors = array();

	/**
	 * Hook addHtmlHeader
	 *
	 * @param  array<string,mixed> $parameters Hook parameters
	 * @param  object              $object     Object
	 * @param  string              $action     Action
	 * @param  HookManager         $hookmanager Hook manager
	 * @return int                             0 = Ausgabe anhängen
	 */
	public function addHtmlHeader($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs;

		if (GETPOST('optioncss', 'aZ09') == 'print') {
			return 0;
		}
		$langs->load('auvofreshui@auvofreshui');

		$isLogin = isset($parameters['currentcontext']) && $parameters['currentcontext'] === 'mainloginpage';
		if (!$isLogin && !empty($conf->loginpage_done)) {
			return 0;
		}

		$accent = getDolGlobalString('AUVOFRESHUI_ACCENT', '#00ffff');
		if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
			$accent = '#00ffff';
		}
		$r = hexdec(substr($accent, 1, 2));
		$g = hexdec(substr($accent, 3, 2));
		$b = hexdec(substr($accent, 5, 2));
		$strong = sprintf('#%02x%02x%02x', (int) ($r * 0.62), (int) ($g * 0.62), (int) ($b * 0.62));
		$vars = self::accentVars($r, $g, $b, $accent, $strong);

		if ($isLogin) {
			if (!getDolGlobalInt('AUVOFRESHUI_LOGIN', 1)) {
				return 0;
			}
			$conf->loginpage_done = 1;
			$nonce = function_exists('getNonce') ? ' nonce="'.getNonce().'"' : '';
			$data = array(
				'company' => getDolGlobalString('MAIN_INFO_SOCIETE_NOM', 'Dolibarr'),
				'labels' => array(
					'morning' => $langs->transnoentities('FreshuiGreetMorning'),
					'day' => $langs->transnoentities('FreshuiGreetDay'),
					'evening' => $langs->transnoentities('FreshuiGreetEvening'),
					'loginTo' => $langs->transnoentities('FreshuiLoginTo'),
					'show' => $langs->transnoentities('FreshuiShow'),
				),
			);
			$out = '<style'.$nonce.'>html.fu-login{'.$vars.'}</style>'."\n";
			$out .= '<script'.$nonce.'>document.documentElement.classList.add("fu-login");window.FRESHUI='
				.json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE).';</script>'."\n";
			$this->resprints = $out;
			return 0;
		}

		// Layout (Icon-Leiste + Seitenmenü) nur, wenn die Seite beide Menüs hat
		$layout = getDolGlobalInt('AUVOFRESHUI_SIDEBAR', 1)
			&& empty($conf->dol_hide_leftmenu) && empty($conf->dol_hide_topmenu)
			&& !GETPOST('dol_openinpopup')
			&& !(defined('NOREQUIREMENU') && constant('NOREQUIREMENU'));
		$aurora = getDolGlobalInt('AUVOFRESHUI_AURORA', 1);
		$brand = $layout && getDolGlobalInt('AUVOFRESHUI_BRAND', 1);
		// Dieselbe Einstellung wie im Standardtheme (Benutzeroberfläche > Disposition du menu
		// haut) entscheidet, ob die Icon-Leiste zusätzlich den Menünamen zeigt: 2/3/4 = "Icon only"-
		// Varianten, 0 = Icon und Text (Default), 1 = Text only.
		$menutext = $layout && !in_array(getDolGlobalInt('THEME_TOPMENU_DISABLE_IMAGE'), array(2, 3, 4), true);
		$listfit = $layout && getDolGlobalInt('AUVOFRESHUI_LISTFIT', 1);
		$palette = getDolGlobalInt('AUVOFRESHUI_PALETTE', 1) && !empty($conf->use_javascript_ajax);

		// Logo für die Kopfleiste: kleines Firmenlogo, sonst Originallogo, sonst Dolibarr-Logo
		$logo = '';
		if ($brand) {
			$dir = isset($conf->mycompany->dir_output) ? $conf->mycompany->dir_output : '';
			$small = getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SMALL');
			$full = getDolGlobalString('MAIN_INFO_SOCIETE_LOGO');
			if ($dir && $small && is_readable($dir.'/logos/thumbs/'.$small)) {
				$logo = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode('logos/thumbs/'.$small);
			} elseif ($dir && $full && is_readable($dir.'/logos/'.$full)) {
				$logo = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode('logos/'.$full);
			} else {
				$logo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
			}
		}
		$default = getDolGlobalString('AUVOFRESHUI_DARK_DEFAULT', 'auto');
		if (!in_array($default, array('auto', 'light', 'dark'))) {
			$default = 'auto';
		}

		$nonce = function_exists('getNonce') ? ' nonce="'.getNonce().'"' : '';
		$labels = array(
			'filter' => $langs->transnoentities('FreshuiFilterMenu'),
			'dark' => $langs->transnoentities('FreshuiToggleDark'),
			'collapse' => $langs->transnoentities('FreshuiToggleSidebar'),
		);
		if ($palette) {
			foreach (array('search' => 'Search', 'searchPh' => 'FreshuiSearchPlaceholder', 'searchIn' => 'FreshuiSearchIn',
				'goTo' => 'FreshuiSearchGoTo', 'none' => 'FreshuiSearchNone', 'nav' => 'FreshuiSearchNavigate',
				'open' => 'FreshuiSearchOpen', 'newTab' => 'FreshuiSearchNewTab', 'close' => 'FreshuiSearchClose') as $k => $key) {
				$labels[$k] = $langs->transnoentities($key, '%s');
			}
		}
		$data = array('labels' => $labels, 'logo' => $logo, 'home' => DOL_URL_ROOT.'/index.php?mainmenu=home&leftmenu=home');
		$data['name'] = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');
		if ($palette) {
			$data['search'] = dol_buildpath('/auvofreshui/ajax/search.php', 1);
		}

		$out = '<style'.$nonce.'>html.fu-on{'.$vars.'}</style>'."\n";
		// localStorage keys keep the old prefix "freshui." so dark mode and the
		// collapsed menu survive the rename to AuVo FreshUI (also in freshui.js).
		$out .= '<script'.$nonce.'>(function(){var d=document.documentElement,c=d.classList;c.add("fu-on");'
			.($layout ? 'c.add("fu-layout");' : '')
			.($aurora ? 'c.add("fu-aurora");' : '')
			.($brand ? 'c.add("fu-brand");' : '')
			.($menutext ? 'c.add("fu-menutext");' : '')
			.($listfit ? 'c.add("fu-listfit");' : '')
			.($palette ? 'c.add("fu-palette");' : '')
			.'try{var m=localStorage.getItem("freshui.theme")||"'.$default.'";'
			.'if(m==="auto"){m=window.matchMedia&&matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light";}'
			.'if(m==="dark"){c.add("fu-dark");}'
			.'if(localStorage.getItem("freshui.collapsed")==="1"){c.add("fu-collapsed");}}catch(e){}'
			.'window.FRESHUI='.json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE).';})();</script>'."\n";

		$out .= $this->takeposTouchFix($nonce);
		$out .= $this->takeposDesign($nonce);

		$this->resprints = $out;
		return 0;
	}

	/**
	 * Modernes Design für TakePOS (css/takepos.css, js/takepos.js), nur auf /takepos/-Seiten.
	 * Der Hook läuft nach TakePOS' eigenen Stylesheets, das Design gewinnt also.
	 *
	 * @param  string $nonce CSP-Nonce-Attribut
	 * @return string        HTML oder ''
	 */
	private function takeposDesign($nonce)
	{
		if (strpos($_SERVER['PHP_SELF'] ?? '', '/takepos/') === false || !getDolGlobalInt('AUVOFRESHUI_TAKEPOS', 1)) {
			return '';
		}
		// Eigene Versionsnummer je Datei, damit Tablets nach Änderungen nicht die alte Fassung aus dem Cache nehmen
		$url = function ($file) {
			return dol_buildpath('/auvofreshui/'.$file, 1).'?v='.((int) @filemtime(dol_buildpath('/auvofreshui/'.$file, 0)));
		};
		return '<script'.$nonce.'>document.documentElement.classList.add("fu-tp");</script>'."\n"
			.'<link rel="stylesheet" href="'.dol_escape_htmltag($url('css/takepos.css')).'">'."\n"
			.'<script'.$nonce.' src="'.dol_escape_htmltag($url('js/takepos.js')).'" defer></script>'."\n";
	}

	/**
	 * TakePOS auf Tablets: TakePOS setzt nach jedem Antippen (Kategorie, Artikel) den
	 * Fokus ins Suchfeld - außer bei Layout "Tablet/Telefon". iPads melden sich aber als
	 * Mac-Desktop, also geht jedes Mal die Bildschirmtastatur auf. Auf Touch-Geräten
	 * bekommt das Suchfeld den Fokus nur noch, wenn es selbst angetippt wurde. Inline im
	 * Kopf (nicht in freshui.js), damit es vor TakePOS' eigenen Skripten greift und
	 * Safari keine alte Version aus dem Cache nimmt.
	 *
	 * @param  string $nonce CSP-Nonce-Attribut
	 * @return string        Script oder ''
	 */
	private function takeposTouchFix($nonce)
	{
		if (strpos($_SERVER['PHP_SELF'] ?? '', '/takepos/') === false) {
			return '';
		}
		return '<script'.$nonce.'>(function(){'
			.'if(!((navigator.maxTouchPoints||0)>1||(window.matchMedia&&matchMedia("(pointer: coarse)").matches))){return;}'
			.'var tap=null,at=0;'
			.'document.addEventListener("pointerdown",function(e){tap=e.target;at=Date.now();},true);'
			.'function own(el){return tap&&(el===tap||el.contains(tap))&&Date.now()-at<1500;}'
			.'var f=HTMLElement.prototype.focus;'
			.'HTMLElement.prototype.focus=function(){if(this.id==="search"&&!own(this)){return;}return f.apply(this,arguments);};'
			.'document.addEventListener("focusin",function(e){var t=e.target;if(t&&t.id==="search"&&!own(t)){t.blur();}},true);'
			.'document.addEventListener("DOMContentLoaded",function(){window.setFocusOnSearchField=function(){};});'
			.'})();</script>'."\n";
	}

	/**
	 * CSS custom properties derived from the accent colour.
	 *
	 * Besides the accent itself, readable variants are computed so that any
	 * accent colour works (the default #00ffff is very bright):
	 *  - --fu-on-accent / --fu-on-accent-strong: text colour on accent-coloured
	 *    buttons (normal / hover), white or dark, whichever contrasts more;
	 *  - --fu-accent-text: accent darkened until it reaches 4.5:1 on the light
	 *    page background (links, active menu item in light mode);
	 *  - --fu-accent-text-dark: accent lightened until it reaches 4.5:1 on the
	 *    dark card background (same in dark mode).
	 *
	 * @param int    $r      Red 0-255
	 * @param int    $g      Green 0-255
	 * @param int    $b      Blue 0-255
	 * @param string $accent Accent as #rrggbb
	 * @param string $strong Darker accent for hover, as #rrggbb
	 * @return string        Declarations without selector and braces
	 */
	public static function accentVars($r, $g, $b, $accent, $strong)
	{
		$dark = array(31, 36, 48);       // --fu-text (light mode)
		$white = array(255, 255, 255);
		$lightBg = array(247, 245, 242); // --fu-bg (light mode)
		$darkBg = array(29, 32, 39);     // --fu-card (dark mode)
		$rgb = array($r, $g, $b);
		$strongRgb = array(hexdec(substr($strong, 1, 2)), hexdec(substr($strong, 3, 2)), hexdec(substr($strong, 5, 2)));

		$on = function ($c) use ($dark, $white) {
			return self::contrast($c, $white) >= self::contrast($c, $dark) ? '#fff' : '#1f2430';
		};
		$text = $rgb;
		for ($i = 0; $i < 60 && self::contrast($text, $lightBg) < 4.5; $i++) {
			$text = array_map(function ($v) {
				return $v * 0.94;
			}, $text);
		}
		$textDark = $rgb;
		for ($i = 0; $i < 60 && self::contrast($textDark, $darkBg) < 4.5; $i++) {
			$textDark = array_map(function ($v) {
				return $v + (255 - $v) * 0.08;
			}, $textDark);
		}
		$hex = function ($c) {
			return sprintf('#%02x%02x%02x', (int) round($c[0]), (int) round($c[1]), (int) round($c[2]));
		};

		return '--fu-accent:'.$accent.';--fu-accent-rgb:'.$r.','.$g.','.$b.';--fu-accent-strong:'.$strong
			.';--fu-on-accent:'.$on($rgb).';--fu-on-accent-strong:'.$on($strongRgb)
			.';--fu-accent-text:'.$hex($text).';--fu-accent-text-dark:'.$hex($textDark).';';
	}

	/**
	 * WCAG 2 contrast ratio of two sRGB colours (arrays of 0-255).
	 *
	 * @param array<int|float> $a First colour
	 * @param array<int|float> $b Second colour
	 * @return float              Ratio 1..21
	 */
	public static function contrast($a, $b)
	{
		$lum = function ($c) {
			$l = array();
			foreach ($c as $v) {
				$v /= 255;
				$l[] = $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
			}
			return 0.2126 * $l[0] + 0.7152 * $l[1] + 0.0722 * $l[2];
		};
		$la = $lum($a);
		$lb = $lum($b);
		return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
	}
}
