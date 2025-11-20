# Moodle Plugin: VMRequest

Dieses Repository enthält den Quellcode des Moodle-Plugins `mod_vmrequest`, mit dem Studierende und Lehrende virtuelle Maschinen beantragen und verwalten können. Das Plugin wird sowohl lokal zu Entwicklungszwecken betrieben als auch zentral im Rahmen der Server-Infrastruktur ausgerollt.

---

## Inhalt

- [Repository-Klon (SSH)](#repository-klon-ssh)
- [Lokale Entwicklungsinstanz](#lokale-entwicklungsinstanz)
- [Zentrale Produktivinstanz](#zentrale-produktivinstanz)
- [Plugin-Installation über Moodle UI](#plugin-installation-über-moodle-ui)
- [Upgrade und Datenbankaktualisierung](#upgrade-und-datenbankaktualisierung)
- [Weitere Hinweise](#weitere-hinweise)

---

## Repository-Klon (SSH)

Das Plugin kann per SSH über GitLab wie folgt geklont werden:

```bash
git clone git@gitlab.example.com:projektgruppe/vmrequest.git
```

Stelle sicher, dass dein SSH-Key im GitLab-Konto hinterlegt ist.

---

## Lokale Entwicklungsinstanz

Für lokale Entwicklung (z. B. auf einer VM oder Docker) wird Moodle üblicherweise unter folgendem Pfad installiert:

```plaintext
/var/www/html/moodle/
```

Das Plugin muss in folgendes Verzeichnis kopiert bzw. verlinkt werden:

```plaintext
/var/www/html/moodle/mod/vmrequest
```

Achte darauf, den Ordnernamen **genau** als `vmrequest` zu benennen, da Moodle den Verzeichnisnamen mit dem internen Komponentenpräfix (`mod_`) verknüpft.

---

## Zentrale Produktivinstanz

Das Deployment auf die zentrale Moodle-Instanz (Produktivumgebung) erfolgt nicht manuell, sondern automatisiert durch das Teilprojekt **Infrastructure**. Änderungen am Plugin müssen entsprechend über Merge Requests in das GitLab-Hauptrepository integriert werden. Eine direkte Bearbeitung auf dem Server ist **nicht vorgesehen**.

---

## Plugin-Installation über Moodle UI

Nach dem Einfügen oder Aktualisieren des Plugin-Verzeichnisses (lokal oder zentral) muss Moodle die neue Version erkennen. Dies geschieht wie folgt:

1. Im Browser die Moodle-Startseite als Administrator aufrufen.
2. Moodle erkennt automatisch neue oder geänderte Plugins.
3. Dem Dialog **„Datenbank aktualisieren“** folgen.
4. Installation bzw. Upgrade bestätigen und abschließen.

Alternativ kann der Vorgang auch über die Kommandozeile ausgeführt werden:

```bash
sudo -u www-data /usr/bin/php /var/www/html/moodle/admin/cli/upgrade.php
```

---

## Weitere Hinweise

- Die Datei `version.php` steuert, ob Moodle das Plugin als aktualisiert erkennt. Änderungen am Code sollten mit einer neuen `version` versehen werden.
- Konfigurationsdaten und Capabilitys werden über `db/install.xml` und `db/access.php` definiert.
- Nachträgliche Änderungen an der Datenbankstruktur müssen über `db/upgrade.php` abgebildet werden.
- Das Plugin nutzt teilweise externe REST-APIs und redundante Datenhaltung. Für produktiven Betrieb sind entsprechende API-Endpunkte freizuschalten.

---

Für Rückfragen zum Deployment oder zum Aufbau der Infrastruktur steht das Teilprojekt **Infrastructure** zur Verfügung.