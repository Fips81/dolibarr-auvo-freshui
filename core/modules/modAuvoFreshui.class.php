<?php
/*
 * Copyright (C) 2026 auvo-design <r.vogel@auvo-design.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Module descriptor for AuVo FreshUI (formerly FreshUI): modern layout over
 * the Dolibarr theme eldy (icon bar, menu filter, dark mode).
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Moduldeskriptor AuVo FreshUI
 */
class modAuvoFreshui extends DolibarrModules
{
	/**
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		// Block 194202–194211 reserved for auvo-design in the Dolibarr wiki
		$this->numero = 194204;
		$this->rights_class = 'auvofreshui';
		$this->family = 'interface';
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = 'Modernes Layout für Dolibarr: Icon-Leiste, Such-Palette, Dark Mode';
		$this->descriptionlong = 'Legt ein modernes Layout über das Dolibarr-Standardtheme eldy: Icon-Leiste mit Seitenmenü, Menü-Filter (Strg+K), Such-Palette, Dark Mode, Aurora-Hintergrund, neu gestaltete Karten, Tabellen und Formulare sowie ein Kassen-Design für TakePOS.';
		$this->editor_name = 'auvo-design';
		$this->editor_url = 'https://auvo-design.de';
		$this->version = '1.3.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'generic';

		$this->module_parts = array(
			'hooks' => array('data' => array('main', 'mainloginpage'), 'entity' => '0'),
			'css' => array('/auvofreshui/css/freshui.css'),
			'js' => array('/auvofreshui/js/freshui.js'),
		);

		$this->dirs = array();
		$this->config_page_url = array('setup.php@auvofreshui');
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		// Old module (FreshUI): both active would load the layout twice
		$this->conflictwith = array('modFreshui');
		$this->langfiles = array('auvofreshui@auvofreshui');
		$this->phpmin = array(7, 4);
		$this->need_dolibarr_version = array(18, 0);

		$this->const = array();
		$this->tabs = array();
		$this->rights = array();
		$this->menu = array();
	}

	/**
	 * @param  string $options Options when enabling module
	 * @return int             1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		$this->migrateFromFreshui();
		return $this->_init(array(), $options);
	}

	/**
	 * Settings of the former module FreshUI (FRESHUI_*) are copied to
	 * AUVOFRESHUI_* in every entity, unless the new one exists already.
	 * The old constants stay, so FreshUI could be switched back on.
	 * Per-browser settings (dark mode, collapsed menu) live in localStorage
	 * under "freshui.*" and are kept as they are.
	 *
	 * @return void
	 */
	private function migrateFromFreshui()
	{
		$sql = "SELECT name, value, type, visible, note, entity FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE name LIKE 'FRESHUI\\_%'";
		$resql = $this->db->query($sql);
		if (!$resql) {
			return;
		}
		require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
		while ($obj = $this->db->fetch_object($resql)) {
			$newName = 'AUVO'.$obj->name;
			$sqlc = "SELECT rowid FROM ".MAIN_DB_PREFIX."const WHERE name = '".$this->db->escape($newName)."' AND entity = ".((int) $obj->entity);
			$resc = $this->db->query($sqlc);
			if ($resc && $this->db->num_rows($resc) > 0) {
				continue;
			}
			dolibarr_set_const($this->db, $newName, $obj->value, $obj->type, (int) $obj->visible, (string) $obj->note, (int) $obj->entity);
		}
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
