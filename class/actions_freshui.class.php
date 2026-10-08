<?php
/* FreshUI - Hook: gibt Akzentfarbe und Layout-Klassen schon im <head> aus (kein Aufblitzen). */

/**
 * Hook-Klasse, Kontext 'main'
 */
class ActionsFreshui
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
		$langs->load('freshui@freshui');

		$isLogin = isset($parameters['currentcontext']) && $parameters['currentcontext'] === 'mainloginpage';
		if (!$isLogin && !empty($conf->loginpage_done)) {
			return 0;
		}

		$accent = getDolGlobalString('FRESHUI_ACCENT', '#f97316');
		if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
			$accent = '#f97316';
		}
		$r = hexdec(substr($accent, 1, 2));
		$g = hexdec(substr($accent, 3, 2));
		$b = hexdec(substr($accent, 5, 2));
		$strong = sprintf('#%02x%02x%02x', (int) ($r * 0.62), (int) ($g * 0.62), (int) ($b * 0.62));

		if ($isLogin) {
			if (!getDolGlobalInt('FRESHUI_LOGIN', 1)) {
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
			$out = '<style'.$nonce.'>html.fu-login{--fu-accent:'.$accent.';--fu-accent-rgb:'.$r.','.$g.','.$b.';--fu-accent-strong:'.$strong.';}</style>'."\n";
			$out .= '<script'.$nonce.'>document.documentElement.classList.add("fu-login");window.FRESHUI='
				.json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE).';</script>'."\n";
			$this->resprints = $out;
			return 0;
		}

		// Layout (Icon-Leiste + Seitenmenü) nur, wenn die Seite beide Menüs hat
		$layout = getDolGlobalInt('FRESHUI_SIDEBAR', 1)
			&& empty($conf->dol_hide_leftmenu) && empty($conf->dol_hide_topmenu)
			&& !GETPOST('dol_openinpopup')
			&& !(defined('NOREQUIREMENU') && constant('NOREQUIREMENU'));
		$aurora = getDolGlobalInt('FRESHUI_AURORA', 1);
		$brand = $layout && getDolGlobalInt('FRESHUI_BRAND', 1);

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
		$default = getDolGlobalString('FRESHUI_DARK_DEFAULT', 'auto');
		if (!in_array($default, array('auto', 'light', 'dark'))) {
			$default = 'auto';
		}

		$nonce = function_exists('getNonce') ? ' nonce="'.getNonce().'"' : '';
		$labels = array(
			'filter' => $langs->transnoentities('FreshuiFilterMenu'),
			'dark' => $langs->transnoentities('FreshuiToggleDark'),
			'collapse' => $langs->transnoentities('FreshuiToggleSidebar'),
		);
		$data = array('labels' => $labels, 'logo' => $logo, 'home' => DOL_URL_ROOT.'/index.php?mainmenu=home&leftmenu=home');
		$data['name'] = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');

		$out = '<style'.$nonce.'>html.fu-on{--fu-accent:'.$accent.';--fu-accent-rgb:'.$r.','.$g.','.$b.';--fu-accent-strong:'.$strong.';}</style>'."\n";
		$out .= '<script'.$nonce.'>(function(){var d=document.documentElement,c=d.classList;c.add("fu-on");'
			.($layout ? 'c.add("fu-layout");' : '')
			.($aurora ? 'c.add("fu-aurora");' : '')
			.($brand ? 'c.add("fu-brand");' : '')
			.'try{var m=localStorage.getItem("freshui.theme")||"'.$default.'";'
			.'if(m==="auto"){m=window.matchMedia&&matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light";}'
			.'if(m==="dark"){c.add("fu-dark");}'
			.'if(localStorage.getItem("freshui.collapsed")==="1"){c.add("fu-collapsed");}}catch(e){}'
			.'window.FRESHUI='.json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE).';})();</script>'."\n";

		$this->resprints = $out;
		return 0;
	}
}
