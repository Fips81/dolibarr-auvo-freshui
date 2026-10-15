<?php
/*
 * Copyright (C) 2026 auvo-design <r.vogel@auvo-design.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AuVo FreshUI - Such-Palette: direkte Treffer (Partner, Produkte, Belege ...) und Dolibarrs
 * "Suchen in"-Einträge in einer Antwort.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREDIRECTBYMAINTOLOGIN')) {
	define('NOREDIRECTBYMAINTOLOGIN', '1');
}

$res = 0;
if (!$res && file_exists(__DIR__.'/../../../main.inc.php')) {
	$res = @include __DIR__.'/../../../main.inc.php';
}
if (!$res && file_exists(__DIR__.'/../../../../main.inc.php')) {
	$res = @include __DIR__.'/../../../../main.inc.php';
}
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

top_httphead('application/json');

if (empty($res) || $res === 'ERROR_NOT_LOGGED' || empty($user->id)) {
	print json_encode(array('error' => 'notlogged', 'login' => DOL_URL_ROOT.'/index.php'));
	exit;
}

$langs->loadLangs(array('companies', 'bills', 'orders', 'propal', 'products', 'suppliers'));

$q = trim(GETPOST('q', 'restricthtml'));
$limit = 5;

/**
 * Einschränkung auf die Partner, die der Benutzer sehen darf (externer Benutzer:
 * nur der eigene; ohne Recht "alle Kunden sehen": nur die als Vertreter zugeordneten).
 *
 * @param  string $field Spalte mit der Partner-ID
 * @return string        SQL (beginnt mit AND) oder ''
 */
function freshui_socfilter($field)
{
	global $user;
	$sql = '';
	if ($user->socid > 0) {
		$sql .= ' AND '.$field.' = '.((int) $user->socid);
	} elseif (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ' AND EXISTS (SELECT 1 FROM '.MAIN_DB_PREFIX.'societe_commerciaux as sc WHERE sc.fk_soc = '.$field.' AND sc.fk_user = '.((int) $user->id).')';
	}
	return $sql;
}

/**
 * Sortierung: Treffer am Anfang von Ref/Name zuerst
 *
 * @param  string[] $fields Spalten
 * @param  string   $q      Suchbegriff
 * @return string           SQL-Ausdruck (0 = Präfix-Treffer)
 */
function freshui_prefixrank($fields, $q)
{
	global $db;
	$like = $db->escape($db->escapeforlike($q)).'%';
	$parts = array();
	foreach ($fields as $f) {
		$parts[] = $f." LIKE '".$like."'";
	}
	return '(CASE WHEN '.implode(' OR ', $parts).' THEN 0 ELSE 1 END)';
}

/**
 * Abfrage ausführen
 *
 * @param  string $sql SQL
 * @return array<int,object>
 */
function freshui_rows($sql)
{
	global $db;
	$rows = array();
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$rows[] = $obj;
		}
		$db->free($resql);
	} else {
		dol_syslog('freshui search: '.$db->lasterror(), LOG_WARNING);
	}
	return $rows;
}

/**
 * Untertitel aus nicht-leeren Teilen
 *
 * @param  array<int,mixed> $parts Teile
 * @return string
 */
function freshui_sub($parts)
{
	return implode(' · ', array_filter(array_map('strval', $parts), 'strlen'));
}

$groups = array();

if (dol_strlen($q) >= 2) {
	$picto = function ($name) {
		return img_picto('', $name, 'class="pictofixedwidth"');
	};

	// Geschäftspartner
	if (isModEnabled('societe') && $user->hasRight('societe', 'lire')) {
		$f = array('s.nom', 's.name_alias', 's.code_client', 's.code_fournisseur', 's.email', 's.tva_intra');
		$sql = 'SELECT s.rowid, s.nom, s.name_alias, s.code_client, s.code_fournisseur, s.client, s.fournisseur, s.town, s.status';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s WHERE s.entity IN ('.getEntity('societe').')';
		$sql .= natural_search($f, $q).freshui_socfilter('s.rowid');
		$sql .= ' ORDER BY s.status DESC, '.freshui_prefixrank(array('s.nom', 's.name_alias', 's.code_client', 's.code_fournisseur'), $q).', s.nom'.$db->plimit($limit);
		$items = array();
		foreach (freshui_rows($sql) as $o) {
			$type = array();
			if ($o->client == 1 || $o->client == 3) {
				$type[] = $langs->transnoentities('Customer');
			} elseif ($o->client == 2) {
				$type[] = $langs->transnoentities('Prospect');
			}
			if ($o->fournisseur) {
				$type[] = $langs->transnoentities('Supplier');
			}
			$items[] = array(
				'title' => $o->nom.($o->name_alias ? ' ('.$o->name_alias.')' : ''),
				'sub' => freshui_sub(array($o->code_client ?: $o->code_fournisseur, implode(', ', $type), $o->town)),
				'url' => DOL_URL_ROOT.'/societe/card.php?socid='.((int) $o->rowid),
				'icon' => $picto('company'),
				'off' => empty($o->status),
			);
		}
		$groups[] = array('label' => $langs->transnoentities('ThirdParties'), 'items' => $items);
	}

	// Kontakte
	if (isModEnabled('societe') && $user->hasRight('societe', 'contact', 'lire')) {
		$f = array('p.lastname', 'p.firstname', 'p.email', 'p.phone', 'p.phone_mobile');
		$sql = 'SELECT p.rowid, p.lastname, p.firstname, p.email, p.statut, s.nom as socname';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'socpeople as p LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = p.fk_soc';
		$sql .= ' WHERE p.entity IN ('.getEntity('contact').')';
		$sql .= " AND (p.priv = 0 OR p.fk_user_creat = ".((int) $user->id).')';
		$sql .= natural_search($f, $q);
		if ($user->socid > 0 || !$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ' AND (p.fk_soc IS NULL OR (1 = 1'.freshui_socfilter('p.fk_soc').'))';
		}
		$sql .= ' ORDER BY p.statut DESC, '.freshui_prefixrank(array('p.lastname', 'p.firstname'), $q).', p.lastname'.$db->plimit($limit);
		$items = array();
		foreach (freshui_rows($sql) as $o) {
			$items[] = array(
				'title' => trim($o->firstname.' '.$o->lastname),
				'sub' => freshui_sub(array($o->socname, $o->email)),
				'url' => DOL_URL_ROOT.'/contact/card.php?id='.((int) $o->rowid),
				'icon' => $picto('contact'),
				'off' => empty($o->statut),
			);
		}
		$groups[] = array('label' => $langs->transnoentities('Contacts'), 'items' => $items);
	}

	// Produkte und Leistungen (inkl. Lieferanten-Artikelnummer)
	$prod = isModEnabled('product') && $user->hasRight('produit', 'lire');
	$serv = isModEnabled('service') && $user->hasRight('service', 'lire');
	if ($prod || $serv) {
		$sql = 'SELECT p.rowid, p.ref, p.label, p.fk_product_type, p.tosell, p.tobuy, p.stock';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p WHERE p.entity IN ('.getEntity('product').')';
		if (!$prod) {
			$sql .= ' AND p.fk_product_type = 1';
		} elseif (!$serv) {
			$sql .= ' AND p.fk_product_type = 0';
		}
		$like = "'%".$db->escape($db->escapeforlike($q))."%'";
		$sql .= ' AND ('.natural_search(array('p.ref', 'p.label', 'p.barcode'), $q, 0, 1).' OR EXISTS (SELECT 1 FROM '.MAIN_DB_PREFIX.'product_fournisseur_price as pfp WHERE pfp.fk_product = p.rowid AND pfp.ref_fourn LIKE '.$like.'))';
		$sql .= ' ORDER BY (p.tosell + p.tobuy) DESC, '.freshui_prefixrank(array('p.ref', 'p.label'), $q).', p.ref'.$db->plimit($limit);
		$items = array();
		foreach (freshui_rows($sql) as $o) {
			$isserv = ((int) $o->fk_product_type === 1);
			$items[] = array(
				'title' => $o->ref.' – '.$o->label,
				'sub' => freshui_sub(array(
					$langs->transnoentities($isserv ? 'Service' : 'Product'),
					(!$isserv && isModEnabled('stock')) ? $langs->transnoentities('Stock').': '.price2num($o->stock, 'MS') : '',
				)),
				'url' => DOL_URL_ROOT.'/product/card.php?id='.((int) $o->rowid),
				'icon' => $picto($isserv ? 'service' : 'product'),
				'off' => empty($o->tosell) && empty($o->tobuy),
			);
		}
		$groups[] = array('label' => $langs->transnoentities('ProductsAndServices'), 'items' => $items);
	}

	// Belege: Tabelle, Alias, Ref-Spalten, Datum, Entity-Key, Karte, Picto
	$docs = array(
		array('ok' => isModEnabled('invoice') && $user->hasRight('facture', 'lire'),
			'table' => 'facture', 'refs' => array('ref', 'ref_client'), 'date' => 'datef', 'entity' => 'invoice',
			'url' => '/compta/facture/card.php?facid=', 'picto' => 'bill', 'label' => 'BillsCustomers'),
		array('ok' => isModEnabled('supplier_invoice') && ($user->hasRight('fournisseur', 'facture', 'lire') || $user->hasRight('supplier_invoice', 'lire')),
			'table' => 'facture_fourn', 'refs' => array('ref', 'ref_supplier'), 'date' => 'datef', 'entity' => 'facture_fourn',
			'url' => '/fourn/facture/card.php?facid=', 'picto' => 'supplier_invoice', 'label' => 'BillsSuppliers'),
		array('ok' => isModEnabled('order') && $user->hasRight('commande', 'lire'),
			'table' => 'commande', 'refs' => array('ref', 'ref_client'), 'date' => 'date_commande', 'entity' => 'commande',
			'url' => '/commande/card.php?id=', 'picto' => 'order', 'label' => 'CustomersOrders'),
		array('ok' => isModEnabled('supplier_order') && ($user->hasRight('fournisseur', 'commande', 'lire') || $user->hasRight('supplier_order', 'lire')),
			'table' => 'commande_fournisseur', 'refs' => array('ref', 'ref_supplier'), 'date' => 'date_commande', 'entity' => 'supplier_order',
			'url' => '/fourn/commande/card.php?id=', 'picto' => 'supplier_order', 'label' => 'SuppliersOrders'),
		array('ok' => isModEnabled('propal') && $user->hasRight('propal', 'lire'),
			'table' => 'propal', 'refs' => array('ref', 'ref_client'), 'date' => 'datep', 'entity' => 'propal',
			'url' => '/comm/propal/card.php?id=', 'picto' => 'propal', 'label' => 'Proposals'),
	);
	foreach ($docs as $d) {
		if (!$d['ok']) {
			continue;
		}
		$f = array_map(function ($c) {
			return 't.'.$c;
		}, $d['refs']);
		$sql = 'SELECT t.rowid, t.ref, t.'.$d['refs'][1].' as ref2, t.'.$d['date'].' as d, t.total_ttc, s.nom as socname';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$d['table'].' as t LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = t.fk_soc';
		$sql .= ' WHERE t.entity IN ('.getEntity($d['entity']).')';
		$sql .= natural_search($f, $q).freshui_socfilter('t.fk_soc');
		$sql .= ' ORDER BY '.freshui_prefixrank($f, $q).', t.rowid DESC'.$db->plimit($limit);
		$items = array();
		foreach (freshui_rows($sql) as $o) {
			$items[] = array(
				'title' => $o->ref.($o->ref2 ? ' ('.$o->ref2.')' : ''),
				'sub' => freshui_sub(array($o->socname, $o->d ? dol_print_date($db->jdate($o->d), 'day') : '', price($o->total_ttc, 0, $langs, 1, -1, -1, $conf->currency))),
				'url' => DOL_URL_ROOT.$d['url'].((int) $o->rowid),
				'icon' => $picto($d['picto']),
				'off' => false,
			);
		}
		$groups[] = array('label' => $langs->transnoentities($d['label']), 'items' => $items);
	}
}

$groups = array_values(array_filter($groups, function ($g) {
	return count($g['items']) > 0;
}));

// Dolibarrs eigene "Suchen in"-Liste (Rechte, Module und Hooks anderer Module inklusive)
$usedbyinclude = 1;
$arrayresult = array();
$_GET['q'] = $q;
include DOL_DOCUMENT_ROOT.'/core/ajax/selectsearchbox.php';
$into = array();
foreach ($arrayresult as $key => $val) {
	if (empty($val['url'])) {
		continue;
	}
	$into[] = array(
		'title' => dol_string_nohtmltag(!empty($val['label']) ? $val['label'] : $val['text']),
		'url' => $val['url'],
		'icon' => !empty($val['img']) ? img_picto('', $val['img'], 'class="pictofixedwidth"') : '',
	);
}

print json_encode(array('q' => $q, 'groups' => $groups, 'into' => $into), JSON_UNESCAPED_UNICODE);
$db->close();
