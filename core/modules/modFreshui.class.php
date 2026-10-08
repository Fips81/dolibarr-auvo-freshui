<?php
/* FreshUI - modernes Layout fuer Dolibarr (Icon-Leiste, Menue-Filter, Dark Mode).
 * Eigenstaendige Neuentwicklung, GPL v3 oder neuer. */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Moduldeskriptor FreshUI
 */
class modFreshui extends DolibarrModules
{
	/**
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 500310;
		$this->rights_class = 'freshui';
		$this->family = 'interface';
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = 'Frisches Layout: Icon-Leiste, Menü-Filter, Dark Mode';
		$this->descriptionlong = 'Legt ein modernes Layout über das Dolibarr-Standardtheme eldy: Icon-Leiste mit Seitenmenü, Menü-Filter (Strg+K), Dark Mode, Aurora-Hintergrund und neu gestaltete Karten, Tabellen und Formulare.';
		$this->editor_name = 'Eigenentwicklung';
		$this->version = '1.2.1';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'generic';

		$this->module_parts = array(
			'hooks' => array('data' => array('main', 'mainloginpage'), 'entity' => '0'),
			'css' => array('/freshui/css/freshui.css'),
			'js' => array('/freshui/js/freshui.js'),
		);

		$this->dirs = array();
		$this->config_page_url = array('setup.php@freshui');
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array('freshui@freshui');
		$this->phpmin = array(7, 4);
		$this->need_dolibarr_version = array(18, 0);

		$this->const = array();
		$this->tabs = array();
		$this->rights = array();
		$this->menu = array();

		if (!isModEnabled('freshui')) {
			$conf->freshui = new stdClass();
			$conf->freshui->enabled = 0;
		}
	}

	/**
	 * @param  string $options Options when enabling module
	 * @return int             1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		return $this->_init(array(), $options);
	}

	/**
	 * @param  string $options Options when disabling module
	 * @return int             1 if OK, <=0 if KO
	 */
	public function remove($options = '')
	{
		return $this->_remove(array(), $options);
	}
}
