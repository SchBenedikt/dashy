# Dashy - Personal Dashboard für Nextcloud

Eine anpassbare Dashboard-App für Nextcloud, die es Benutzern ermöglicht, ihr persönliches Dashboard mit verschiedenen Widgets zu personalisieren. **Alle Widgets verwenden echte Daten aus den entsprechenden Nextcloud Apps.**

## Features

### 🎯 Personalisiertes Dashboard
- Jeder Benutzer kann sein eigenes Dashboard erstellen und anpassen
- Dashboard-Konfiguration wird pro Benutzer gespeichert
- Intuitives Drag-and-Drop Interface

### 📐 Flexibles Rastersystem
- 12-Spalten Grid-Layout basierend auf `vue-grid-layout`
- Widgets können per Drag & Drop verschoben werden
- Größe der Widgets ist frei anpassbar
- Responsive Design für verschiedene Bildschirmgrößen

### 🧩 Verfügbare Widgets (mit echten Daten)

#### 📅 Kalender Widget
- **Echte Integration** mit Nextcloud Kalender-App
- Zeigt anstehende Termine der nächsten 30 Tage an
- Unterstützt mehrere Kalender
- Konfigurierbare Anzahl der angezeigten Ereignisse
- Zeigt Ort, Zeit und Kalender-Zugehörigkeit an

#### ✅ Aufgaben Widget (Todo)
- **Echte Integration** mit Nextcloud Tasks-App
- Laden und Anzeigen echter Aufgaben aus der Tasks-App
- Erstellen neuer Aufgaben direkt im Widget
- Aufgaben als erledigt markieren
- Aufgaben löschen
- Automatische Synchronisation alle 30 Sekunden
- Konfigurierbare maximale Anzahl angezeigter Aufgaben

#### 🕐 Uhr Widget
- Aktuelle Zeit und Datum
- Wahlweise 12h oder 24h Format
- Optional Sekunden anzeigen
- Optional Zeitzone anzeigen
- Live-Updates jede Sekunde

#### 🌤️ Wetter Widget
- **Echte Wetterdaten** von OpenWeatherMap API
- Aktuelle Wetterbedingungen für jeden Ort
- Temperatur, gefühlte Temperatur, Luftfeuchtigkeit
- Windgeschwindigkeit und Wetterbeschreibung
- Konfigurierbare Einheiten (Celsius/Fahrenheit)
- Ortsspezifische Wetterinformationen
- Automatische Aktualisierung alle 10 Minuten

### ⚙️ Widget-Einstellungen
- Jedes Widget hat eigene Konfigurationsmöglichkeiten
- Einstellungen werden pro Widget-Instanz gespeichert
- Einfacher Zugriff über das Zahnrad-Symbol im Widget-Header
- Live-Updates bei Einstellungsänderungen

### 🎨 Benutzerfreundliches Interface
- Moderne und saubere Benutzeroberfläche
- Nextcloud Design-System Integration
- Responsive Layout für Desktop und Mobile
- Drag & Drop für intuitive Bedienung

## Installation

1. Die App in das Nextcloud `apps` Verzeichnis kopieren
2. Dependencies installieren:
   ```bash
   npm install
   ```
3. Frontend builden:
   ```bash
   npm run build
   ```
4. App in Nextcloud aktivieren

## Konfiguration

### Wetter Widget
Das Wetter Widget benötigt einen OpenWeatherMap API Key für echte Wetterdaten:

```bash
# Als Administrator
php occ config:app:set dashy openweather_api_key --value="YOUR_API_KEY_HERE"
```

Kostenloser API Key verfügbar bei: https://openweathermap.org/api

Siehe [WEATHER_CONFIG.md](./WEATHER_CONFIG.md) für Details.

## Abhängige Apps

Für die volle Funktionalität sollten folgende Nextcloud Apps installiert sein:

- **Calendar App**: Für das Kalender-Widget
- **Tasks App**: Für das Aufgaben-Widget

Widgets funktionieren auch ohne die entsprechenden Apps, zeigen dann aber keine/leere Daten an.

## Technische Details

### Frontend
- **Vue.js 2.7**: Hauptframework
- **@nextcloud/vue**: Nextcloud UI-Komponenten
- **vue-grid-layout**: Drag & Drop Grid-System
- **vue-material-design-icons**: Icons
- **@nextcloud/axios**: HTTP Client für API-Aufrufe
- **Vite**: Build-Tool

### Backend
- **PHP 8+**: Server-seitige Logik
- **Nextcloud App Framework**: Integration in Nextcloud
- **OCS API**: RESTful API für Dashboard-Operationen
- **Calendar Manager**: Integration mit Kalender-App
- **Database Access**: Direkte Integration mit Tasks-App

### API Endpoints

#### Dashboard Management
- `GET /apps/dashy/api/dashboard` - Dashboard-Konfiguration laden
- `POST /apps/dashy/api/dashboard` - Dashboard-Konfiguration speichern

#### Kalender Integration
- `GET /apps/dashy/api/calendar/events` - Kalender-Ereignisse abrufen

#### Tasks Integration
- `GET /apps/dashy/api/tasks` - Aufgaben abrufen
- `POST /apps/dashy/api/tasks` - Neue Aufgabe erstellen
- `PUT /apps/dashy/api/tasks/{id}` - Aufgabe aktualisieren
- `DELETE /apps/dashy/api/tasks/{id}` - Aufgabe löschen

#### Wetter Integration
- `GET /apps/dashy/api/weather?location=...&unit=...` - Wetterdaten abrufen

## Entwicklung

### Development Server starten
```bash
npm run dev
```

### Linting
```bash
npm run lint
npm run stylelint
```

### Build für Produktion
```bash
npm run build
```

## Erweiterbarkeit

Das Widget-System ist erweiterbar. Neue Widgets können einfach hinzugefügt werden:

1. Widget-Komponente in `src/components/widgets/` erstellen
2. Entsprechende Settings-Komponente erstellen
3. Widget in `App.vue` registrieren
4. Widget-Typ zu `availableWidgets` hinzufügen
5. Optional: Backend-Endpunkte für Datenintegration hinzufügen

## Roadmap

- [x] Integration mit Nextcloud Kalender-App
- [x] Integration mit Nextcloud Tasks-App
- [x] Weather API Integration
- [ ] RSS Feed Widget
- [ ] Notizen Widget mit Files-App Integration
- [ ] Shared Widgets zwischen Benutzern
- [ ] Widget-Themes
- [ ] Export/Import von Dashboard-Konfigurationen
- [ ] Mail Widget mit Mail-App Integration
- [ ] News Widget mit News-App Integration

## Lizenz

AGPL-3.0-or-later

### Help for developers:

- Official community chat: https://cloud.nextcloud.com/call/xs25tz5y
- Official community forum: https://help.nextcloud.com/c/dev/11
