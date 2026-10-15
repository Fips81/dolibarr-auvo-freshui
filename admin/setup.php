<?php
/*
 * Copyright (C) 2026 auvo-design <r.vogel@auvo-design.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AuVo FreshUI - Einstellungsseite
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

/** @var Translate $langs */
/** @var User $user */
/** @var DoliDB $db */
/** @var Conf $conf */
$langs->loadLangs(array('admin', 'auvofreshui@auvofreshui'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

if ($action == 'update') {
	$accent = GETPOST('AUVOFRESHUI_ACCENT', 'alphanohtml');
	if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
		$accent = '#00ffff';
	}
	$dark = GETPOST('AUVOFRESHUI_DARK_DEFAULT', 'aZ09');
	if (!in_array($dark, array('auto', 'light', 'dark'))) {
		$dark = 'auto';
	}
	dolibarr_set_const($db, 'AUVOFRESHUI_ACCENT', $accent, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AUVOFRESHUI_DARK_DEFAULT', $dark, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AUVOFRESHUI_SIDEBAR', GETPOSTINT('AUVOFRESHUI_SIDEBAR') ? 1 : 0, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AUVOFRESHUI_BRAND', GETPOSTINT('AUVOFRESHUI_BRAND') ? 1 : 0, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AUVOFRESHUI_LOGIN', GETPOSTINT('AUVOFRESHUI_LOGIN') ? 1 : 0, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AUVOFRESHUI_AURORA', GETPOSTINT('AUVOFRESHUI_AURORA') ? 1 : 0, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AUVOFRESHUI_TAKEPOS', GETPOSTINT('AUVOFRESHUI_TAKEPOS') ? 1 : 0, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AUVOFRESHUI_PALETTE', GETPOSTINT('AUVOFRESHUI_PALETTE') ? 1 : 0, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AUVOFRESHUI_LISTFIT', GETPOSTINT('AUVOFRESHUI_LISTFIT') ? 1 : 0, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	header('Location: '.$_SERVER['PHP_SELF']);
	exit;
}

llxHeader('', 'FreshUI');
print load_fiche_titre('FreshUI', '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>', 'title_setup');

$accent = getDolGlobalString('AUVOFRESHUI_ACCENT', '#00ffff');
$dark = getDolGlobalString('AUVOFRESHUI_DARK_DEFAULT', 'auto');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiAccent").'</td><td><input type="color" name="AUVOFRESHUI_ACCENT" value="'.dol_escape_htmltag($accent).'"></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiSidebar").'<br><span class="opacitymedium small">'.$langs->trans("FreshuiSidebarHelp").'</span></td><td>';
print '<input type="checkbox" name="AUVOFRESHUI_SIDEBAR" value="1"'.(getDolGlobalInt('AUVOFRESHUI_SIDEBAR', 1) ? ' checked' : '').'></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiBrand").'<br><span class="opacitymedium small">'.$langs->trans("FreshuiBrandHelp").'</span></td><td>';
print '<input type="checkbox" name="AUVOFRESHUI_BRAND" value="1"'.(getDolGlobalInt('AUVOFRESHUI_BRAND', 1) ? ' checked' : '').'></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiLogin").'</td><td>';
print '<input type="checkbox" name="AUVOFRESHUI_LOGIN" value="1"'.(getDolGlobalInt('AUVOFRESHUI_LOGIN', 1) ? ' checked' : '').'></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiAurora").'</td><td>';
print '<input type="checkbox" name="AUVOFRESHUI_AURORA" value="1"'.(getDolGlobalInt('AUVOFRESHUI_AURORA', 1) ? ' checked' : '').'></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiListfit").'<br><span class="opacitymedium small">'.$langs->trans("FreshuiListfitHelp").'</span></td><td>';
print '<input type="checkbox" name="AUVOFRESHUI_LISTFIT" value="1"'.(getDolGlobalInt('AUVOFRESHUI_LISTFIT', 1) ? ' checked' : '').'></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiPalette").'<br><span class="opacitymedium small">'.$langs->trans("FreshuiPaletteHelp").'</span></td><td>';
print '<input type="checkbox" name="AUVOFRESHUI_PALETTE" value="1"'.(getDolGlobalInt('AUVOFRESHUI_PALETTE', 1) ? ' checked' : '').'></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiTakepos").'<br><span class="opacitymedium small">'.$langs->trans("FreshuiTakeposHelp").'</span></td><td>';
print '<input type="checkbox" name="AUVOFRESHUI_TAKEPOS" value="1"'.(getDolGlobalInt('AUVOFRESHUI_TAKEPOS', 1) ? ' checked' : '').'></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiDarkDefault").'<br><span class="opacitymedium small">'.$langs->trans("FreshuiDarkDefaultHelp").'</span></td><td>';
print '<select name="AUVOFRESHUI_DARK_DEFAULT">';
foreach (array('auto' => 'FreshuiDarkAuto', 'light' => 'FreshuiDarkLight', 'dark' => 'FreshuiDarkDark') as $k => $lab) {
	print '<option value="'.$k.'"'.($dark == $k ? ' selected' : '').'>'.$langs->trans($lab).'</option>';
}
print '</select></td></tr>';

print '</table></div>';
print '<div class="center"><input type="submit" class="button button-save" value="'.dol_escape_htmltag($langs->trans("Save")).'"></div>';
print '</form>';

print '<br><span class="opacitymedium">'.$langs->trans("FreshuiHint").'</span>';

llxFooter();
$db->close();
