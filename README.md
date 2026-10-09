# re@di-Cockpit

Anwendung zum Verwaltung von Gruppe (z.B. Projektgruppen), deren Mitglieder sich selbst zu Gruppen hinzufügen können sollen.

Gruppen können moderiert sein, sodass Gruppen-Admins die Anträge genehmigen müssen.

Gruppenmitgliedschaften können in Mailman2-Mailinglisten und Keycloak-Gruppen synchronisiert werden und die Mitglieder der beiden Anwendungen werden beim Aufrufen der Gruppen ins Cockpit synchronisiert, damit beide Systeme immer synchron laufen.

So können Berechtigungen z.B. für eine NextCloug (über OpenID-Connect) an Gruppen-Admins oder sogar an die Gruppenmitglieder selbst delegiert werden

Das Cockpit basiert auf [Laravel](https://laravel.com/) und [Filamentphp](https://filamentphp.com/). Zwei großartigen Frameworks!

Die erste Version wurde von [Alexander Gabriel](https://www.digital-infinity.de/) für [re@di](https://www.readi.de) erstellt. Nutzt das Cockpit gerne auch für eure Gruppen, spart euch viel Arbeit und macht gerne auch mit.



# Transkripierung

Die MP3s werden nicht mehr über Verzeichnisse ausgetauscht. Der Whisper-Server holt sich die Aufträge per API ab und lädt die Ergebnisse per `curl` (oder `wget`) wieder hoch.

Ablauf:

1. User lädt eine MP3 hoch → Status `hochgeladen`.
2. Whisper-Server ruft `POST /api/whisper/jobs/next` auf und bekommt den ältesten Auftrag → Status `geplant`.
3. Whisper-Server lädt die MP3 über `GET /api/whisper/jobs/{id}/audio` herunter und transkribiert sie.
4. Whisper-Server lädt das Ergebnis als .zip über `POST /api/whisper/jobs/{id}/result` hoch → Status `erledigt`, der User bekommt eine Mail mit der .zip im Anhang.

Bleibt ein Auftrag länger als `WHISPER_JOB_TIMEOUT_HOURS` (Standard 12) auf `geplant` (z.B. weil whisper abgestürzt ist), wird er beim nächsten `next` erneut ausgegeben.

Der Scheduler wird nur noch zum Löschen erledigter Transkripierungen nach `KEEP_TRANSCRIPTIONS_DAYS` Tagen benötigt:

```
php artisan schedule:work
```

### Konfiguration

In der `.env` des Cockpits ein langes, zufälliges Token setzen (z.B. `openssl rand -hex 32`). Ohne Token ist die API gesperrt.

```
WHISPER_API_TOKEN=geheimes-token
WHISPER_JOB_TIMEOUT_HOURS=12
```

### API

Alle Aufrufe brauchen den Header `Authorization: Bearer <WHISPER_API_TOKEN>`, sonst kommt `401`.

| Methode & Pfad | Antwort |
| --- | --- |
| `POST /api/whisper/jobs/next` | `200` mit `{"id":1,"name":"abc","audio_url":"…","result_url":"…"}` oder `204`, wenn nichts zu tun ist |
| `GET /api/whisper/jobs/{id}/audio` | die MP3 (`404`, wenn die Datei fehlt) |
| `POST /api/whisper/jobs/{id}/result` | Ergebnis-.zip als Multipart-Feld `result` oder als Body mit `Content-Type: application/zip`; `422` ohne Datei |

Beispiele mit curl:

```
TOKEN=geheimes-token
API=https://cockpit.example.org/api/whisper/jobs

curl -X POST -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $API/next
curl -H "Authorization: Bearer $TOKEN" -o abc.mp3 $API/1/audio
curl -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" -F "result=@abc.zip" $API/1/result
```

Oder mit wget:

```
wget -qO- --post-data="" --header="Authorization: Bearer $TOKEN" $API/next
wget --header="Authorization: Bearer $TOKEN" -O abc.mp3 $API/1/audio
wget -qO- --header="Authorization: Bearer $TOKEN" --header="Content-Type: application/zip" --post-file=abc.zip $API/1/result
```

### Whisper-Server

Auf dem Whisper-Server läuft `whisper_worker.sh` (benötigt `curl`, `ffmpeg`, `whisper`, `zip`). Es arbeitet alle wartenden Aufträge ab und beendet sich dann, z.B. per Cron alle 5 Minuten (`flock` verhindert parallele Läufe):

```
*/5 * * * * COCKPIT_URL=https://cockpit.example.org WHISPER_API_TOKEN=geheimes-token flock -n /tmp/whisper.lock /opt/whisper_worker.sh
```

Optional: `WHISPER_MODEL` (Standard `medium`) und `WHISPER_WORK_DIR` (Standard `./whisper_work_dir`).


## Testen mit Whisper lokal

### Whisper installieren:
https://askubuntu.com/questions/837408/convert-speech-mp3-audio-files-to-text

```
$ # Creates a new environment called "newenv" (also creates a subfolder with the same name)
$ python -m venv whisper
$ # Activate the new environment by sourcing the bin/activate script from the new folder
$ source ./newenv/bin/activate
(whisper)$ # pip will now install modules in the venv, and python will use modules from there
(whisper)$ pip install -U openai-whisper
```

Test mit whisper lokal (Cockpit läuft z.B. unter http://localhost:8000):

```
COCKPIT_URL=http://localhost:8000 WHISPER_API_TOKEN=geheimes-token sh whisper_worker.sh
```


# ToDo

* Datenbereinigung: Pflege von Domains und Zuständigen, die Daten und Benutzer von nicht mehr vorhandenen Mitarbeitenden bereinigen können (am besten auch in der NextCloud und im Keycloak User löschen oder zumindest ein Ticket erstellen)
