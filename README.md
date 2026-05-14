# re@di-Cockpit

Anwendung zum Verwaltung von Gruppe (z.B. Projektgruppen), deren Mitglieder sich selbst zu Gruppen hinzufügen können sollen.

Gruppen können moderiert sein, sodass Gruppen-Admins die Anträge genehmigen müssen.

Gruppenmitgliedschaften können in Mailman2-Mailinglisten und Keycloak-Gruppen synchronisiert werden und die Mitglieder der beiden Anwendungen werden beim Aufrufen der Gruppen ins Cockpit synchronisiert, damit beide Systeme immer synchron laufen.

So können Berechtigungen z.B. für eine NextCloug (über OpenID-Connect) an Gruppen-Admins oder sogar an die Gruppenmitglieder selbst delegiert werden

Das Cockpit basiert auf [Laravel](https://laravel.com/) und [Filamentphp](https://filamentphp.com/). Zwei großartigen Frameworks!

Die erste Version wurde von [Alexander Gabriel](https://www.digital-infinity.de/) für [re@di](https://www.readi.de) erstellt. Nutzt das Cockpit gerne auch für eure Gruppen, spart euch viel Arbeit und macht gerne auch mit.



# Transkripierung

Whisper installieren:
https://askubuntu.com/questions/837408/convert-speech-mp3-audio-files-to-text

```
$ # Creates a new environment called "newenv" (also creates a subfolder with the same name)
$ python -m venv whisper
$ # Activate the new environment by sourcing the bin/activate script from the new folder
$ source ./newenv/bin/activate
(whisper)$ # pip will now install modules in the venv, and python will use modules from there
(whisper)$ pip install -U openai-whisper
```

Test mit whisper lokal:

```
bash whisper_work_local.sh
```


# ToDo

* Datenbereinigung: Pflege von Domains und Zuständigen, die Daten und Benutzer von nicht mehr vorhandenen Mitarbeitenden bereinigen können (am besten auch in der NextCloud und im Keycloak User löschen oder zumindest ein Ticket erstellen)
