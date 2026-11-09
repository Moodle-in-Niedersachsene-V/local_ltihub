# local_ltihub – Kursräume auf dem Material-Hub per LTI 1.3

Build 2026103101 · Release 0.5.1 (Alpha) · Moodle 5.2 (Hub) · PHP 8.2–8.4 · MariaDB, MySQL, PostgreSQL
Maintainer: Moodle in Niedersachsen e. V. · Lizenz: GNU GPL v3 oder später

Status: **Alpha**. Geprüft gegen den Quellcode von Moodle 5.2.4+ (Hub) und 5.3 (Schulinstanz), phpcs (moodle-extra) und Semgrep (`moodle-security.yml`) ohne Befund. PHPUnit-Tests fehlen noch.

## Modell

- **Ein Tool pro Schulinstanz.** Der Schul-Admin bindet den Hub einmal über die normale Oberfläche ein: Registrierungs-URL einfügen, „LTI Advantage hinzufügen“, aktivieren. Mehr ist nicht nötig, insbesondere keine benutzerdefinierten Parameter.
- **Ein Kursraum pro Lehrkraft und Schulkurs.** Die Lehrkraft legt ihn direkt im Fenster „Inhalt auswählen“ an. Danach erscheinen die Aktivitäten ihres Raums in derselben Liste, und bei jedem weiteren „Inhalt auswählen“ wählt sie eine andere Aktivität.
- **Empfohlen: eine Vorlage mit allen Aktivitäten.** Liegt nur ein Kurs in der Vorlagen-Kategorie, gibt es keine Auswahl, nur den Button „Kursraum anlegen“. Mehrere Vorlagen bleiben möglich; dann wird beim Anlegen einmal gewählt.
- **Neue Aktivitäten nachliefern:** Kommen später Aktivitäten in die Vorlage, zeigt „Inhalt auswählen“ bei bestehenden Kursräumen die Box „Neue Aktivitäten verfügbar“. Ein Klick kopiert sie in den Raum und veröffentlicht sie (auf Abruf, ohne nächtliche Aufgabe).
- Schüler/innen öffnen die verbundenen Aktivitäten und landen direkt im Raum ihrer Lehrkraft. Die Bewertungen fließen über AGS zurück.

```
Lehrkraft: Aktivität hinzufügen → Material-Hub → „Inhalt auswählen“
   └► enrol/lti/launch_deeplink.php
        ├─ noch kein Hub-Konto → auth/lti/login.php → local_ltihub legt das Konto an (Hook after_config) → zurück
        ├─ noch kein Kursraum → Box „Eigenen Kursraum anlegen“ (Hook before_standard_top_of_body_html_generation)
        │     └► local/ltihub/create.php: Vorlage kopieren, Lehrkraft einschreiben, Aktivitäten veröffentlichen → zurück
        └─ Liste der Aktivitäten des eigenen Kursraums → anhaken → „Inhalt hinzufügen“
```

## Warum so (Fakten aus dem Code von 5.2)

- `launch_deeplink.php` meldet Nutzer nur im Modus *PROMPT_EXISTING_ONLY* an. Eine neue Lehrkraft landet deshalb auf `auth/lti/login.php` mit der Frage nach einem bestehenden Konto. Auf dem Hub soll aber jede Lehrkraft ein eigenes Konto bekommen. `local_ltihub\local\account_provisioner` führt auf dieser Seite für **Lehrende bei Deep Linking** genau die Schritte aus, die auch die Auswahl „Neues Konto“ ausführen würde, und kehrt zurück. Lernende und normale Starts bleiben unberührt. Ist `authpreventaccountcreation` gesetzt, greift das Plugin nicht ein.
- Die Liste in „Inhalt auswählen“ zeigt nur Ressourcen, für die das Konto `enrol/lti:config` hat. Das ist der Fall, sobald die Lehrkraft im eigenen Kursraum Lehrer/in ist.
- Den Deep-Linking-Start liest das Plugin über enrol_lti's Klasse `launch_cache_session` aus der Session und prüft, ob er zum angemeldeten Nutzer gehört. Der Aufbau dieser Session-Daten ist intern; nach Upgrades `classes/local/launch_context.php` und `classes/local/account_provisioner.php` kontrollieren.

## Einrichtung auf dem Material-Hub (einmalig)

1. Den Ordner `ltihub` nach `public/local/ltihub` kopieren und die Aktualisierung starten.
2. Das Einschreibe-Plugin „Als LTI-Tool veröffentlichen“ und das Authentifizierungs-Plugin „LTI“ aktivieren. `allowframembedding` einschalten (*Sicherheit → HTTP-Sicherheit*).
3. Kategorien anlegen:
   - **LTI-Hub**: Kursräume, ohne Gastzugang. Darunter entsteht automatisch je Schulinstanz eine Unterkategorie.
   - **LTI-Hub-Vorlagen** (versteckt): die Vorlagenkurse. Name und Kurzbeschreibung erscheinen in der Auswahl. Vorlagen selbst **nicht** als LTI-Tool veröffentlichen.
4. *Website-Administration → Plugins → Lokale Plugins → LTI-Hub Kursraum-Bereitstellung*: Vorlagen-Kategorie und übergeordnete Kategorie wählen. Rollen leer lassen, dann gelten Lehrer/in und Schüler/in.
5. Die Capability `local/ltihub:createhubcourse` ist für „Authentifizierte/r Nutzer/in“ erlaubt. Wer sie entzieht, sperrt das Anlegen.

## Schulinstanz verbinden

**Auf dem Hub** (du, einmal pro Schule), entweder

```bash
sudo -u www-data php public/local/ltihub/cli/add_school.php --name="Grundschule Baumhaus"
```

oder über die Oberfläche: *Plugins → Einschreibung → Tool registration → Plattform registrieren*. Beide Wege liefern eine **Registrierungs-URL**, die einmal verwendbar ist. Der Name wird zur Unterkategorie der Kursräume.

**Auf der Schulinstanz** (Schul-Admin):

1. *Website-Administration → Plugins → Aktivitäten → Externes Tool → Tools verwalten*.
2. Die Registrierungs-URL in das Feld **„Tool-URL“** einfügen und auf **„LTI Advantage hinzufügen“** klicken, dann bestätigen.
3. Das Tool unter „Ausstehend“ **aktivieren**.
4. Empfohlen: das Tool bearbeiten und unter „Tool-Konfiguration verwenden“ **„Im Aktivitätenauswahlmenü und als vorkonfiguriertes Tool anzeigen“** wählen. Unter „Datenschutz“ legt der Schul-Admin fest, ob Name und E-Mail übertragen werden.

Das ist alles: keine benutzerdefinierten Parameter, kein zweites Tool.

## Ablauf für Lehrkräfte

1. Im eigenen Kurs **Aktivität hinzufügen → Material-Hub → „Inhalt auswählen“**.
2. Beim ersten Mal erscheint oben **„Eigenen Kursraum anlegen“**: Vorlage wählen (mit Hilfe-Icon) und **Kursraum anlegen** klicken.
3. Die Aktivitäten des neuen Raums erscheinen in der Liste. Gewünschte anhaken, bei bewerteten Aktivitäten zusätzlich „Zu den Bewertungen hinzufügen“, dann **„Inhalt hinzufügen“**, im Formular speichern.

Später geht es direkt mit Schritt 3 weiter. Im Kursraum auf dem Hub kann die Lehrkraft Aktivitäten anpassen oder neue anlegen; neue werden automatisch veröffentlicht. Nachrichtenforen werden nicht veröffentlicht.

## Testen

Mit einer eigenen Test-Lehrkraft im **privaten Fenster** testen. Ist der Browser am Hub als Admin angemeldet, wird diese Sitzung durch das neue Lehrerkonto ersetzt. Das ist gewollt, denn ein Admin-Konto wird nie verknüpft, verwirrt aber beim Testen.

## Bewertungen

Der Hub überträgt Noten per AGS über die geplante Aufgabe `\enrol_lti\local\ltiadvantage\task\sync_grades` (alle 30 Minuten). Sie erzeugt je Aktivität eine Ad-hoc-Aufgabe, die beim nächsten Cron-Lauf sendet. Gesendet werden nur geänderte Noten von Lernenden, die die Aktivität mindestens einmal über die Schulinstanz geöffnet haben. Sofort auslösen kannst du das im Moodle-Hauptverzeichnis (über `public/`):

```bash
sudo -u www-data php admin/cli/scheduled_task.php --execute='\enrol_lti\local\ltiadvantage\task\sync_grades'
sudo -u www-data php admin/cli/adhoc_task.php --execute
```

## Fehlersuche

| Symptom | Ursache | Lösung |
|---|---|---|
| „Inhalt auswählen“ fehlt im Formular | Deep Linking am Tool ausgeschaltet | Tool bearbeiten: „Unterstützt Deep Linking“ an, Deep-Linking-URL `…/enrol/lti/launch_deeplink.php` |
| Kontoabfrage „Bestehendes Konto verwenden“ | `authpreventaccountcreation` ist auf dem Hub gesetzt, oder der Start kam nicht als Lehrkraft | Einstellung prüfen; Rolle der Person im Schulkurs prüfen. Nie ein bestehendes (Admin-)Konto verbinden |
| Bei „Inhalt auswählen“ erscheinen fremde Kurse | LTI-Konto ist mit einem Hub-Admin verknüpft (aus Tests vor 0.4.0) | `SELECT l.id, u.username, u.auth FROM mdl_auth_lti_linked_login l JOIN mdl_user u ON u.id = l.userid;` falsche Zeile löschen |
| Box „Eigenen Kursraum anlegen“ erscheint nicht | Kursraum existiert schon, keine Vorlagen, oder Capability entzogen | Hinweis in der Box bzw. Einstellungen prüfen |
| Verbundene Aktivität öffnet etwas anderes | Am Tool steht ein benutzerdefinierter Parameter `id=…` (aus dem Zwei-Tool-Modell bis 0.3.0) | Parameter am Tool löschen |

## Umstieg von 0.3.0 und älter

- Die Einstellung „UUID der Einstiegs-Ressource“ entfällt; die Aktualisierung entfernt sie.
- Lobby-Kurs und Einstiegs-Link werden nicht mehr gebraucht und können gelöscht werden.
- Auf Schulinstanzen das Tool „Material-Hub: Kursraum einrichten“ löschen und beim Tool „Material-Hub“ die benutzerdefinierten Parameter leeren.

## Dateien

| Datei | Zweck |
|---|---|
| `db/hooks.php`, `classes/hook_callbacks.php` | Hooks `after_config` (Kontoanlage) und `before_standard_top_of_body_html_generation` (Box im Deep Linking) |
| `classes/local/account_provisioner.php` | Hub-Konto für Lehrende beim ersten „Inhalt auswählen“ |
| `classes/local/deeplinking_panel.php`, `classes/form/create_form.php` | Box „Eigenen Kursraum anlegen“ mit Vorlagenauswahl und Hilfe-Icon |
| `create.php` | Seitenskript: Kursraum anlegen (POST, Sesskey über moodleform, Capability), zurück zum Deep Linking |
| `classes/local/launch_context.php` | Deep-Linking-Start aus der Session lesen, Bindung an `$USER` prüfen, Deployment auflösen, Schlüssel bilden |
| `classes/local/course_provisioner.php` | Vorlagen, Lock, Backup/Restore, Kategorie pro Schulinstanz, Veröffentlichung (LTI 1.3, AGS an, NRPS aus, ohne Nachrichtenforen), Einschreibung, Nachliefern neuer Vorlagen-Aktivitäten (wie Moodles „Duplizieren“), Events |
| `sync.php` | Seitenskript: neue Vorlagen-Aktivitäten übernehmen (POST mit Sesskey, Capability), zurück zum Deep Linking |
| `classes/local/school_registrar.php`, `cli/add_school.php` | Registrierung einer Schulinstanz auf dem Hub, Event `school_registration_created` |
| `classes/observer.php` | Später im Kursraum angelegte Aktivitäten automatisch veröffentlichen |
| `classes/privacy/provider.php` | Privacy API für `ownerid` |
| `db/install.xml` | `local_ltihub_map`: sha256(Issuer, Kontext-ID, `sub`) → Kurs, Vorlage, Eigentümer; `local_ltihub_item`: welche Kursraum-Aktivität aus welcher Vorlagen-Aktivität stammt |

## Sicherheit

- Ein Hub-Konto entsteht nur aus einem per JWT geprüften Deep-Linking-Start mit Lehrenden-Rolle, so wie es Moodle bei „Neues Konto anlegen“ selbst tut. Bestehende Konten werden nie automatisch verknüpft.
- Kursräume entstehen nur über `create.php`: angemeldet, gültiger Deep-Linking-Start des Nutzers, Lehrenden-Rolle, Capability, POST mit Sesskey (moodleform). Die Vorlage wird serverseitig gegen die Vorlagen-Kategorie geprüft.
- Kein `unserialize()` im Plugin. Backup und Restore laufen ohne Nutzerdaten, Rollen, Einschreibungen, Logs und Kommentare.

## Änderungen

- 2026103101: Upgrade-Fehler „Das kann während der Aktualisierung nicht ausgeführt werden“ behoben (Zuordnung bestehender Kursräume ohne Modinfo-Cache).
- 2026103100: Neue Vorlagen-Aktivitäten lassen sich in bestehende Kursräume übernehmen (Box „Neue Aktivitäten verfügbar“). Bei nur einer Vorlage entfällt die Auswahl. Bestehende Kursräume werden beim Update anhand von Aktivitätstyp und Name ihrer Vorlage zugeordnet.
- 2026103007: Nach dem automatischen Anlegen des Lehrerkontos wird der geprüfte Deep-Linking-Start erneut in der neuen Sitzung abgelegt (Fehler `LtiMessageLaunch::getAud(): Return value must be of type string, null returned` in `launch_deeplink.php`).
- 2026103006: Ein-Tool-Modell. Kursraum wird direkt in „Inhalt auswählen“ angelegt, Hub-Konten für Lehrende entstehen automatisch. Lobby, Einstiegs-Link, UUID-Einstellung, Einrichtungs-Tool und Schul-Skript entfallen. `cli/add_school.php` legt nur noch eine Registrierung an.
- 2026103005: Skripte zum Verbinden ohne Oberfläche (ersetzt durch 2026103006).
- 2026103004: Nachrichtenforen werden nicht mehr veröffentlicht.
- 2026103003: Fehler „Call to a member function export_for_template() on string“ behoben.

## Offene Punkte

- Hat eine Lehrkraft eine Aktivität aus einem **vor 0.5.0** angelegten Kursraum gelöscht oder umbenannt, bietet die Box sie erneut an. In neu angelegten Kursräumen passiert das nicht.
- Aktivitäten, die Fragen aus einer Fragensammlung nutzen (z. B. videolearn), hängen beim Nachliefern davon ab, dass das Plugin seine Fragen im Aktivitäts-Backup mitnimmt. Beim Test prüfen.

- PHPUnit-Tests: Launch-Simulation mit Hilfe von `enrol/lti/tests/local/ltiadvantage/lti_advantage_testcase.php`.
- Bei großen Vorlagen läuft der Restore synchron. Falls das zu lange dauert, auf einen Ad-hoc-Task umstellen.
- `lang/de` wird mitgeliefert, weil der Hub deutschsprachig ist. Für den Marktplatz (CODING_STANDARDS N3) später nur `lang/en`.
