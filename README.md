# AuVo FreshUI

[![Neueste Version](https://img.shields.io/github/v/release/Fips81/dolibarr-auvo-freshui?label=version&sort=semver)](https://github.com/Fips81/dolibarr-auvo-freshui/releases/latest)

**Modernes Layout für Dolibarr.** Das Modul legt sich über das Standardtheme
eldy und verändert nur die Darstellung, nicht die Daten:

- **Icon-Leiste mit Seitenmenü** statt der Menüleiste oben, Seitenmenü
  einklappbar, Firmenlogo in einer Leiste über die volle Breite
- **Menü-Filter** (Strg+K): Menüpunkte durch Tippen finden
- **Such-Palette** für die globale Suche (Alt+S oder Strg+Umschalt+F):
  direkte Treffer bei Geschäftspartnern, Kontakten, Produkten (auch über
  Lieferanten-Artikelnummern), Rechnungen, Aufträgen, Bestellungen und
  Angeboten, unter Beachtung der Benutzerrechte
- **Dark Mode**, automatisch nach Systemeinstellung oder per Umschalter,
  wird pro Browser gemerkt
- **Neu gestaltete Karten, Tabellen und Formulare** in einer frei wählbaren
  Akzentfarbe, optional mit Aurora-Hintergrund
- **Listen auf Bildschirmhöhe:** Kopf- und Filterzeile bleiben stehen, die
  Liste scrollt in sich
- **Neue Anmeldeseite** mit Begrüßung
- **Kasse (TakePOS)** im selben Design: Kacheln, Ziffernblock und Bon als
  Karten, Dark Mode, für Tablets angepasst

Jede Funktion lässt sich in den Moduleinstellungen einzeln abschalten.

Gehört zur Reihe der AuVo-Module für Dolibarr, neben
[AuVo E-Rechnung](https://github.com/Fips81/dolibarr-auvo-erechnung)
(Ausgangsrechnungen als ZUGFeRD und XRechnung) und
[AuVo Rechnungsimport](https://github.com/Fips81/dolibarr-auvo-rechnungsimport)
(Eingangsrechnungen). Früherer Name: FreshUI (bis 1.2.x).

> AuVo FreshUI ist ein unabhängiges Modul und kein offizielles Produkt der
> Dolibarr Foundation. Dolibarr ist eine Marke der Dolibarr Foundation.

## Voraussetzungen

- **Dolibarr** ab 18.0, entwickelt und im Einsatz mit 24.0
- **PHP** 7.4 oder neuer
- Theme **eldy** und Menümanager **eldy_menu.php** (Dolibarr-Standard)
- Keine weiteren Bibliotheken und keine externen Dienste: Styles und Skripte
  kommen vom eigenen Server, Schriften werden nicht nachgeladen (Inter, falls
  installiert, sonst die Systemschrift)

## Installation

1. `module_auvofreshui-X.Y.Z.zip` aus den
   [Releases](https://github.com/Fips81/dolibarr-auvo-freshui/releases)
   herunterladen.
2. Entpacken und den Ordner `auvofreshui/` nach `htdocs/custom/` kopieren
   (Besitzer: Webserver-Benutzer, z. B. `www-data`). Alternativ in Dolibarr
   unter *Einstellungen → Module → Externes Modul hochladen*.
3. Unter *Einstellungen → Module* **AuVo FreshUI** aktivieren.
4. Seite mit **Strg+F5** neu laden.

**Einstellungen** (*Einstellungen → Module → AuVo FreshUI*): Akzentfarbe,
Standard für den Dark Mode, Icon-Leiste und Seitenmenü (aus = nur Optik, das
Dolibarr-Layout bleibt), Logo-Leiste, Anmeldeseite, Aurora-Hintergrund,
Listen auf Bildschirmhöhe, Such-Palette, Kassen-Design. Nach Änderungen die
Seite mit Strg+F5 neu laden.

**Update:** Ordner `custom/auvofreshui` durch den neuen ersetzen und die Seite
mit Strg+F5 neu laden. Ein erneutes Aktivieren ist nur nötig, wenn der
Changelog es sagt.

### Umstieg von FreshUI (1.2.x)

Bis 1.2.x hieß das Modul FreshUI (Ordner `freshui`, Modulnummer 500310).
Beim ersten Aktivieren übernimmt AuVo FreshUI (Modulnummer 194204) die
Einstellungen (`FRESHUI_*` → `AUVOFRESHUI_*`). Dark Mode und eingeklapptes
Seitenmenü merkt sich der Browser; diese Einstellungen bleiben erhalten. Die
alten Einstellungen werden nur kopiert, nicht gelöscht.

1. FreshUI deaktivieren und den Ordner `custom/freshui` **außerhalb** von
   `custom/` sichern.
2. `auvofreshui` nach `custom/` kopieren, aktivieren, Seite mit Strg+F5 neu
   laden.

Solange FreshUI aktiv ist, lässt sich AuVo FreshUI nicht aktivieren, sonst
würde das Layout doppelt geladen.

## Wie es funktioniert

- Ein Hook im Seitenkopf setzt die Akzentfarbe und die Layout-Klassen, bevor
  die Seite gezeichnet wird; dadurch blitzt beim Laden nichts auf.
- Das Stylesheet hängt vollständig an der Klasse `html.fu-on` und setzt die
  Farb-Variablen von eldy auf die eigene Palette. Ist das Modul aus, sieht
  Dolibarr aus wie immer.
- Die Such-Palette fragt `ajax/search.php` ab. Die Abfragen prüfen die
  Benutzerrechte und die Einschränkung auf eigene Kunden wie Dolibarrs eigene
  Suche.
- Das Kassen-Design wird nur auf den Seiten von TakePOS geladen.

## Entwicklung und Releases

Bei jedem Push prüft die GitHub-CI die Syntax aller PHP- und JavaScript-
Dateien. Das Release-ZIP `module_auvofreshui-X.Y.Z.zip` entsteht automatisch,
sobald ein Tag `vX.Y.Z` gepusht wird (`.github/workflows/release-zip.yml`:
Der Tag muss zur Modulversion passen, das ZIP ohne `.github/` hängt am
Release).

## Changelog

### 1.3.0

- Umbenannt in **AuVo FreshUI** (Ordner `auvofreshui`, Modulnummer 194204)
  mit Übernahme der Einstellungen von FreshUI.
- Lizenz GPLv3 als Datei im Repository, Dateiköpfe mit Copyright.
- GitHub-CI und automatisches Release-ZIP.

### 1.2.2

- Der aktive Reiter ist wieder erkennbar (Unterstreichung in der
  Akzentfarbe).
- Such-Palette, Listen auf Bildschirmhöhe, Kassen-Design für TakePOS,
  Logo-Leiste über die volle Breite, kleinere Korrekturen.

## Lizenz

[GPLv3](LICENSE) (oder später) – Copyright (C) 2026
[auvo-design](https://auvo-design.de).

## Über dieses Projekt

AuVo FreshUI wird von auvo-design gepflegt und steht allen Dolibarr-Nutzern
frei zur Verfügung. Fehler und Wünsche bitte als
[GitHub-Issue](https://github.com/Fips81/dolibarr-auvo-freshui/issues)
melden. Sicherheitslücken bitte nicht als Issue, sondern an
r.vogel@auvo-design.de.
