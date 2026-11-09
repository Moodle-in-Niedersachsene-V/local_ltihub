<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * German strings for local_ltihub.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitiescopied'] = '{$a} neue Aktivitäten wurden in Ihren Kursraum übernommen.';
$string['cli_addschool_done'] = 'Registrierung für die Schulinstanz angelegt (ID {$a->regid}).

Der Schul-Admin fügt diese Registrierungs-URL unter Website-Administration > Plugins > Externes Tool > Tools verwalten in das Feld „Tool-URL“ ein, klickt auf „LTI Advantage hinzufügen“ und aktiviert das Tool:
{$a->regurl}

Die URL ist einmal verwendbar.';
$string['cli_addschool_help'] = 'Bereitet den Hub für eine neue Schulinstanz vor: legt die Plattform-Registrierung an und gibt die Registrierungs-URL für den Schul-Admin aus.

Optionen:
-n, --name=NAME   Name der Schulinstanz, z. B. "Grundschule Baumhaus" (Pflicht)
-h, --help        Diese Hilfe anzeigen

Beispiel:
sudo -u www-data php public/local/ltihub/cli/add_school.php --name="Grundschule Baumhaus"';
$string['cli_ltidisabled'] = 'Bitte zuerst die Einschreibemethode „Als LTI-Tool veröffentlichen“ und die Authentifizierung „LTI“ aktivieren.';
$string['cli_noname'] = 'Bitte den Namen der Schulinstanz mit --name angeben.';
$string['coursecreated'] = 'Ihr Kursraum wurde angelegt. Wählen Sie jetzt die Aktivitäten, die Sie in Ihren Kurs einbinden möchten.';
$string['coursefullname'] = '{$a->title} · {$a->template} ({$a->platform})';
$string['createbutton'] = 'Kursraum anlegen';
$string['createheading'] = 'Eigenen Kursraum anlegen';
$string['createintro'] = 'Für Ihren Kurs „{$a}“ haben Sie noch keinen Kursraum auf dem Material-Hub. Wählen Sie eine Vorlage; danach erscheinen ihre Aktivitäten in der Liste darunter. Der Kursraum gehört Ihnen; Kolleg/innen erhalten einen eigenen.';
$string['eventhubcoursecreated'] = 'Kursraum angelegt';
$string['eventhubcourseupdated'] = 'Neue Vorlagen-Aktivitäten in Kursraum übernommen';
$string['eventschoolregistrationcreated'] = 'Registrierung für eine Schulinstanz angelegt';
$string['invalidtemplate'] = 'Bitte wählen Sie eine der angebotenen Vorlagen.';
$string['locktimeout'] = 'Ihr Kursraum wird gerade angelegt. Bitte laden Sie die Seite in ein paar Sekunden neu.';
$string['ltihub:createhubcourse'] = 'Kursraum aus einer Vorlage anlegen';
$string['nocategory'] = 'Es ist keine übergeordnete Kategorie für Kursräume eingestellt.';
$string['nolaunch'] = 'Diese Seite kann nur über „Inhalt auswählen“ des Hub-Tools auf Ihrer Schulinstanz geöffnet werden.';
$string['notemplates'] = 'Auf dem Hub sind noch keine Vorlagen eingerichtet. Bitte wenden Sie sich an die Administration.';
$string['parentcategory'] = 'Übergeordnete Kategorie';
$string['parentcategory_desc'] = 'Kursräume werden in je einer Unterkategorie pro Schulinstanz unterhalb dieser Kategorie angelegt.';
$string['pluginname'] = 'LTI-Hub Kursraum-Bereitstellung';
$string['privacy:metadata:local_ltihub_map'] = 'Zuordnung einer Lehrkraft und eines Kurses der Schulinstanz zu einem Kursraum.';
$string['privacy:metadata:local_ltihub_map:ownerid'] = 'Lehrkraft, der der Kursraum gehört.';
$string['privacy:metadata:local_ltihub_map:platformcontexttitle'] = 'Titel des Kurses auf der Schulinstanz.';
$string['privacy:metadata:local_ltihub_map:timecreated'] = 'Zeitpunkt der Erstellung.';
$string['restorefailed'] = 'Die Vorlage konnte nicht kopiert werden: {$a}';
$string['studentrole'] = 'Rolle für Lernende';
$string['studentrole_desc'] = 'Rolle der Lernenden in den veröffentlichten Aktivitäten. Standard: Schüler/in.';
$string['teacherrole'] = 'Rolle für Lehrende';
$string['teacherrole_desc'] = 'Rolle der Lehrkraft im eigenen Kursraum und in den Aktivitäten. Sie muss enrol/lti:config erlauben, sonst zeigt „Inhalt auswählen“ nichts an. Standard: Lehrer/in.';
$string['templatecategory'] = 'Vorlagen-Kategorie';
$string['templatecategory_desc'] = 'Jeder Kurs in dieser Kategorie wird als Vorlage angeboten. Die Vorlagen selbst dürfen nicht als LTI-Tool veröffentlicht sein.';
$string['templatechoice'] = 'Vorlage';
$string['templatechoice_help'] = 'Die Vorlage wird mit allen Aktivitäten in Ihren eigenen Kursraum kopiert. Die Wahl lässt sich später nicht ändern; für eine andere Vorlage muss die Administration den Kursraum löschen.';
$string['untitledcourse'] = 'Kurs';
$string['updatebutton'] = 'Neue Aktivitäten übernehmen';
$string['updateheading'] = 'Neue Aktivitäten verfügbar';
$string['updateintro'] = 'Die Vorlage enthält inzwischen Aktivitäten, die Ihr Kursraum noch nicht hat: {$a}. Übernehmen Sie sie in Ihren Kursraum, um sie unten auswählen zu können.';
