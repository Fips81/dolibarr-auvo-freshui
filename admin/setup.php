<?php
/* FreshUI - Einstellungsseite */

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
$langs->loadLangs(array('admin', 'freshui@freshui'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

if ($action == 'update') {
	$accent = GETPOST('FRESHUI_ACCENT', 'alphanohtml');
	if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
		$accent = '#f97316';
	}
	$dark = GETPOST('FRESHUI_DARK_DEFAULT', 'aZ09');
	if (!in_array($dark, array('auto', 'light', 'dark'))) {
		$dark = 'auto';
	}
	dolibarr_set_const($db, 'FRESHUI_ACCENT', $accent, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'FRESHUI_DARK_DEFAULT', $dark, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'FRESHUI_SIDEBAR', GETPOSTINT('FRESHUI_SIDEBAR') ? 1 : 0, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'FRESHUI_BRAND', GETPOSTINT('FRESHUI_BRAND') ? 1 : 0, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'FRESHUI_LOGIN', GETPOSTINT('FRESHUI_LOGIN') ? 1 : 0, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'FRESHUI_AURORA', GETPOSTINT('FRESHUI_AURORA') ? 1 : 0, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	header('Location: '.$_SERVER['PHP_SELF']);
	exit;
}

llxHeader('', 'FreshUI');
print load_fiche_titre('FreshUI', '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>', 'title_setup');

$accent = getDolGlobalString('FRESHUI_ACCENT', '#f97316');
$dark = getDolGlobalString('FRESHUI_DARK_DEFAULT', 'auto');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiAccent").'</td><td><input type="color" name="FRESHUI_ACCENT" value="'.dol_escape_htmltag($accent).'"></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiSidebar").'<br><span class="opacitymedium small">'.$langs->trans("FreshuiSidebarHelp").'</span></td><td>';
print '<input type="checkbox" name="FRESHUI_SIDEBAR" value="1"'.(getDolGlobalInt('FRESHUI_SIDEBAR', 1) ? ' checked' : '').'></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiBrand").'<br><span class="opacitymedium small">'.$langs->trans("FreshuiBrandHelp").'</span></td><td>';
print '<input type="checkbox" name="FRESHUI_BRAND" value="1"'.(getDolGlobalInt('FRESHUI_BRAND', 1) ? ' checked' : '').'></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiLogin").'</td><td>';
print '<input type="checkbox" name="FRESHUI_LOGIN" value="1"'.(getDolGlobalInt('FRESHUI_LOGIN', 1) ? ' checked' : '').'></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiAurora").'</td><td>';
print '<input type="checkbox" name="FRESHUI_AURORA" value="1"'.(getDolGlobalInt('FRESHUI_AURORA', 1) ? ' checked' : '').'></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("FreshuiDarkDefault").'<br><span class="opacitymedium small">'.$langs->trans("FreshuiDarkDefaultHelp").'</span></td><td>';
print '<select name="FRESHUI_DARK_DEFAULT">';
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
