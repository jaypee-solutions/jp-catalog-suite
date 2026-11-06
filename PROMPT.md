# Aufgabe: WooCommerce-Erweiterung „JPS Catalog Suite for WooCommerce“

Du bist ein erfahrener WordPress-/WooCommerce-Entwickler. Wir bauen eine GENERISCHE,

wiederverwendbare WooCommerce-Erweiterung von JayPee Solutions, nicht kundenspezifisch.

Erster Einsatz: ein B2B-Kundenprojekt (Anfrage-Shop für technische Produkte mit CAD-Dateien).

Alles Kundenspezifische (Texte, Mailadressen, Tab-Namen) ist Konfiguration, kein Code.

Arbeitsweise: offene Fragen klären → Architektur/Plan vorschlagen →

nach meinem OK modulweise implementieren. Kein Code vor dem Plan.

## Identität

- Name: JayPee Solutions Catalog Suite for WooCommerce

- Slug/Text-Domain: `jp-catalog-suite`

- Präfix: `jps_cs_` (Funktionen, Optionen, Meta-Keys, Hooks), Namespace `JayPee\CatalogSuite`

- Autor: JayPee Solutions, Lizenz: GPLv2+

- Verteilung: vorerst privat (Git); Update-Mechanismus über Git-Releases vorsehen

  (z. B. gebündelte plugin-update-checker-Lib), [wordpress.org](http://wordpress.org)-tauglich bleiben

## Zielumgebung (erster Einsatz)

- Dev-Site: [https://dev.koeberle-schmid.com](https://dev.koeberle-schmid.com)
- Betrieb: Docker Compose, Traefik, Apache, WP-CLI, Ansible; Auto-Updates aktiv
- Theme: Block-Theme; Cart/Checkout: Blocks
- Rechtstexte-Plugin: Germanized
- Bereits vorhanden: zwei eigene Plugins 
  
  | Plugin Name                       | Beschreibung                                                                                                                         | Pfad                     |
  | :--------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ | ------------------------ |
  | **JP Dynamic Product Categories** | Hierarchischer Produktkategorie-Shortcode im Hero-Card-Stil für WooCommerce.                                                         | ../jp-dynamic-categories |
  | **JP Shipping by Category**       | Versandkosten nach Produktkategorie, mit Gratisversand über die Versandzonen von WooCommerce. Verwaltbar im WooCommerce-Versand-Tab. | ../wc-shipping           |
  

  , die NICHT angefasst werden;

  Kopplung nur über Hooks
- Mehrsprachigkeit dort: DE + EN via TranslatePress Free + Loco Translate.

  Das Plugin muss generisch auch mit Polylang und WPML funktionieren (wpml-config.xml).

## Grundsätze

- So wenig PHP wie nötig, lesbar, kein Over-Engineering, keine Laufzeit-Abhängigkeiten

  außer gebündelten Libs. Ich mag PHP nicht.

- WooCommerce-Bordmittel statt Parallelstrukturen (kein eigener Warenkorb).

- Modular: jedes Feature ein Modul, einzeln in den Einstellungen aktivierbar.

- Einstellungen unter WooCommerce → Einstellungen → Tab „Catalog Suite“.

- Alle Strings übersetzbar (Text-Domain `jps-catalog-suite`), .pot via `wp i18n make-pot`.

- HPOS-kompatibel deklarieren, Blocks-Cart/Checkout-Kompatibilität ehrlich bewerten.

- Sicherheit: Nonces, Capabilities, Escaping, Sanitizing konsequent.

- Hooks/Filter an sinnvollen Stellen für Erweiterbarkeit; dokumentiert.

- uninstall.php: Optionen weg, Produktdaten/Dateien nur nach expliziter Einstellung.

## Modul 1: Anfrage-Modus (Quote Mode)

- Global aktivierbar (später ggf. pro Produkt/Kategorie, Architektur offen halten).

- Produkte ohne Preis kaufbar machen (`woocommerce_is_purchasable`), Preise überall

  ausblenden: Shop, Produkt, Mini-Cart, Warenkorb, Checkout, Mails, Konto,

  [schema.org](http://schema.org)-Daten.

- Texte konfigurierbar, Defaults: „Anfragekorb“, „Zur Anfrage hinzufügen“,

  „Anfrage absenden“. Nie „zahlungspflichtig bestellen“, denn eine Anfrage ist

  rechtlich keine Bestellung.

- Keine Zahlung/Versand/Steuern; bei Summe 0 keine Zahlungsart, sonst minimales

  Gateway „Anfrage“.

- Checkout-Felder konfigurierbar reduzieren (Default: Firma, Name, E-Mail, Telefon,

  Nachricht). Hinweise von Germanized/German Market entfernen, Datenschutz-Checkbox

  behalten.

- Eigener Bestellstatus `wc-jps-quote` („Anfrage“).

- Mails: Admin „Neue Anfrage“ an konfigurierbare Adresse(n), Kunde „Anfragebestätigung“,

  beide ohne Preise (eigene WC_Email-Klassen).

## Modul 2: Zusätzliche Produkt-Tabs

- Funktionsumfang vergleichbar mit „Product Tabs for WooCommerce“ / „Smart Tabs“:

  - Globale Tabs (für alle Produkte oder nach Kategorie) + produktspezifische Tabs

  - Pro Produkt: globale Tabs überschreiben oder ausblenden

  - Titel, Reihenfolge, Priorität konfigurierbar; Standard-Woo-Tabs umbenennen/

    ausblenden/umsortieren

  - Leere Tabs automatisch ausblenden

- Inhalt: Rich-Text (Editor), Medien aus der Mediathek (Bilder, PDFs), Dateiliste.

- Ausgabe über `woocommerce_product_tabs`; muss mit Block-Theme

  (Product Details Block) und Classic-Templates funktionieren.

- Inhalte serverseitig rendern, damit Übersetzungs-Plugins sie erfassen.

## Modul 3: Tabs mit Zugriffsstufen (gehört zu Modul 2)

- Jeder Tab kann ZWEI Inhalte haben:

  - öffentlicher Inhalt (für nicht angemeldete Besucher)

  - geschützter Inhalt (nur für angemeldete Nutzer)

- Zugriffsregel erweiterbar halten: Default „eingeloggt“, vorbereitet für Rollen

  (z. B. nur `customer`/B2B-Rolle) per Filter.

- Default-Text für den öffentlichen Teil konfigurierbar, inkl. Login/Registrierungs-

  Link mit Redirect zurück zum Produkt.

- Geschützte Dateien (Anwendungsfall: STEP/.stp, .dxf, PDFs):

  - Dateitypen konfigurierbar, MIME-Typen per `upload_mimes` erlauben

  - Ablage NICHT direkt per URL abrufbar: eigenes Verzeichnis außerhalb des Webroots

    oder mit Deny-Regel

  - Auslieferung über eigenen Endpoint mit Zugriffsprüfung; X-Sendfile wenn verfügbar,

    sonst gestreamt (große Dateien)

  - Optional: Download-Log (Nutzer, Datei, Zeitpunkt)

- Caching beachten: Seiten mit geschützten Inhalten dürfen nicht für Gäste gecacht

  ausgeliefert werden (bzw. geschützter Teil per Fragment/AJAX nachladen; bewerten).

- Hinweis an Doku: Selbstregistrierung braucht Bot-Schutz; Empfehlung ALTCHA

  (Open Source, self-hosted).

## Modul 4: Variable Länge

- Statt einer Längen-Variante: Längeneingabe durch den Kunden.

- Pro Produkt aktivierbar, auf einfachen UND variablen Produkten (Länge zusätzlich zu

  Varianten wie Farbe); pro Variante überschreibbar.

- Konfiguration: Einheit (Default cm), Untergrenze, Obergrenze, Schrittweite, Default-Wert.

- Validierung client- und serverseitig (`woocommerce_add_to_cart_validation`).

- Gleiche Produkte mit unterschiedlicher Länge = getrennte Warenkorbpositionen.

- Wert als Cart-Item-Data → Bestellposition → Admin, Mails, Kundenkonto.

- Preisberechnung pro Längeneinheit als optionale Funktion vorsehen (für Einsatz

  ohne Anfrage-Modus), im ersten Einsatz deaktiviert.

- Vor der Umsetzung mit mir klären: Option auf bestehenden Produkttypen

  (empfohlen) vs. eigener Produkttyp.

## Offene Fragen, bitte zuerst mit mir klären

1. Anfragen auch als Gast oder nur mit Konto?

2. Sollen Kunden frühere Anfragen im Konto sehen?

3. Tab-Inhalte: klassischer Editor-Feld-Ansatz oder Gutenberg-Blöcke im Tab?

4. Ist mod\_xsendfile im Apache-Container verfügbar bzw. installierbar?

5. Maximale Dateigröße geschützter Dateien?

6. Welcher Seiten-Cache läuft (Plugin, Traefik, keiner)?

## Liefergegenstände

- Plugin-Struktur (Hauptdatei, Module, Einstellungen, Assets, Sprachen/.pot, wpml-config.xml)

- README (Installation, Konfiguration, Hooks/Filter) + CHANGELOG

- Testcheckliste pro Modul (Gast vs. eingeloggt, DE + EN, Classic + Blocks)