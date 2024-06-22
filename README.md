# re@di-Cockpit

Anwendung zum Verwaltung von Gruppe (z.B. Projektgruppen), deren Mitglieder sich selbst zu Gruppen hinzufügen können sollen.

Gruppen können moderiert sein, sodass Gruppen-Admins die Anträge genehmigen müssen.

Gruppenmitgliedschaften können in Mailman2-Mailinglisten und Keycloak-Gruppen synchronisiert werden und die Mitglieder der beiden Anwendungen werden beim Aufrufen der Gruppen ins Cockpit synchronisiert, damit beide Systeme immer synchron laufen.

So können Berechtigungen z.B. für eine NextCloug (über OpenID-Connect) an Gruppen-Admins oder sogar an die Gruppenmitglieder selbst delegiert werden

## ToDo

* Datenbereinigung: Pflege von Domains und Zuständigen, die Daten und Benutzer von nicht mehr vorhandenen Mitarbeitenden bereinigen können (am besten auch in der NextCloud und im Keycloak User löschen oder zumindest ein Ticket erstellen)
