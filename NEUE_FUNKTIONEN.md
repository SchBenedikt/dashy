# Neue Widget-Funktionen für Dashy

Die Dashy-App wurde um verschiedene neue Widgets und Funktionen erweitert, die eine tiefere Integration mit Nextcloud-Apps ermöglichen.

## Neue Widgets

### 📱 Kontakte-Widget
- **Funktionen:**
  - Zeigt eine Liste der letzten/favorisierten Kontakte
  - Kompakt- und Vollansicht je nach Widget-Größe
  - Suche durch Kontakte
  - Direkte E-Mail-Funktionen (öffnet Nextcloud Mail oder Standard-E-Mail-Client)
  - Schnellzugriff auf die Kontakte-App

- **Einstellungen:**
  - Maximale Anzahl anzuzeigender Kontakte (1-50)
  - Option für nur kürzlich kontaktierte Personen
  - Ein-/Ausblenden von E-Mail-Adressen
  - Ein-/Ausblenden von Telefonnummern

### 📁 Dateien-Widget
- **Funktionen:**
  - Anzeige der zuletzt bearbeiteten Dateien
  - Favoriten-Ansicht
  - Automatische Dateityp-Erkennung mit passenden Icons
  - Dateigrößen und Änderungsdaten
  - Direkte Download- und Freigabe-Funktionen
  - Schnellzugriff auf die Dateien-App

- **Einstellungen:**
  - Maximale Anzahl anzuzeigender Dateien (1-50)
  - Standard-Ansicht (Neueste oder Favoriten)
  - Ein-/Ausblenden von Dateigrößen
  - Ein-/Ausblenden von Änderungsdaten
  - Option zum Einschließen von Ordnern

### 📝 Notizen-Widget
- **Funktionen:**
  - Integration mit der Nextcloud Notes-App
  - Fallback für einfache Notizen-Speicherung
  - Schnelle Notiz-Erstellung direkt im Widget
  - Vorschau des Notiz-Inhalts
  - Kategorien-Unterstützung

- **Einstellungen:**
  - Maximale Anzahl anzuzeigender Notizen (1-50)
  - Ein-/Ausblenden von Notiz-Vorschauen
  - Ein-/Ausblenden von Kategorien
  - Aktivierung der Schnell-Erstellung

### 🔖 Lesezeichen-Widget
- **Funktionen:**
  - Integration mit der Nextcloud Bookmarks-App
  - Fallback für einfache Lesezeichen-Speicherung
  - Favicon-Anzeige für bessere Erkennung
  - Zugriffs-Tracking für "Zuletzt verwendet"
  - Link kopieren und schnelles Hinzufügen

- **Einstellungen:**
  - Maximale Anzahl anzuzeigender Lesezeichen (1-50)
  - Ein-/Ausblenden von Favicons
  - Ein-/Ausblenden von Beschreibungen
  - Aktivierung der Schnell-Erstellung

## Technische Funktionen

### Responsive Design
- **Automatische Anpassung:** Alle Widgets haben eine kompakte und eine Vollansicht
- **Größenerkennung:** Basierend auf Widget-Dimensionen (w/h) wird automatisch die beste Anzeige gewählt
- **Optimierte Navigation:** Touch-freundliche Bedienelemente

### API-Integration
- **Neue Endpunkte:**
  - `/api/contacts` - Kontakte-Integration
  - `/api/files` - Dateien-Integration  
  - `/api/notes` - Notizen-Integration
  - `/api/bookmarks` - Lesezeichen-Integration

### Fallback-Mechanismen
- **App-Erkennung:** Prüft automatisch, ob entsprechende Nextcloud-Apps installiert sind
- **Einfache Speicherung:** Bei fehlenden Apps wird eine einfache Speicherung in der App-Konfiguration verwendet
- **Graceful Degradation:** Widgets funktionieren auch ohne spezielle Apps

## Widget-Einstellungen

Jedes Widget verfügt über ein Einstellungs-Modal, das über das Zahnrad-Symbol im Widget-Header erreicht werden kann. Die Einstellungen werden automatisch gespeichert und beim nächsten Laden der Seite wiederhergestellt.

## Datenschutz und Sicherheit

- **Benutzer-spezifisch:** Alle Daten sind benutzer-spezifisch und werden nur für den angemeldeten Benutzer angezeigt
- **Sichere APIs:** Alle API-Endpunkte nutzen Nextcloud's Authentifizierung
- **Keine externe Abhängigkeiten:** Alle Funktionen arbeiten ausschließlich mit Nextcloud-internen Systemen

## Performance-Optimierungen

- **Lazy Loading:** Widgets laden ihre Daten erst beim Anzeigen
- **Caching:** Intelligentes Caching verhindert unnötige API-Aufrufe
- **Automatische Aktualisierung:** Daten werden bei Bedarf automatisch aktualisiert
- **Chunk-Splitting:** JavaScript wird in optimierte Chunks aufgeteilt

## Installation und Verwendung

Die neuen Widgets sind automatisch verfügbar, sobald die App aktualisiert wurde. Über den "Widget hinzufügen"-Button können die neuen Widget-Typen ausgewählt und dem Dashboard hinzugefügt werden.
