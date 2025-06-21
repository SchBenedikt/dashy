# Weather Widget Configuration

Das Wetter-Widget kann mit echten Wetterdaten von OpenWeatherMap betrieben werden.

## OpenWeatherMap API Key konfigurieren

1. Kostenlosen API Key bei [OpenWeatherMap](https://openweathermap.org/api) erstellen
2. API Key in Nextcloud konfigurieren:

### Über die Kommandozeile (als Admin):
```bash
php occ config:app:set dashy openweather_api_key --value="YOUR_API_KEY_HERE"
```

### Oder über das Admin-Interface:
1. Als Administrator in Nextcloud einloggen
2. Zu "Einstellungen" → "Administration" → "Zusätzliche Einstellungen" gehen
3. Den Abschnitt "Dashy" finden
4. OpenWeatherMap API Key eingeben

## Ohne API Key

Wenn kein API Key konfiguriert ist, zeigt das Wetter-Widget Demo-Daten an.

## Unterstützte Features

- Aktuelle Temperatur
- Gefühlte Temperatur  
- Luftfeuchtigkeit
- Windgeschwindigkeit
- Wetterbeschreibung
- Anpassbare Einheiten (Celsius/Fahrenheit)
- Ortsspezifische Daten
