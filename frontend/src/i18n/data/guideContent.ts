import {
  CalendarDaysIcon,
  CheckCircleIcon,
  BellAlertIcon,
  CurrencyEuroIcon,
  CurrencyDollarIcon,
  UserGroupIcon,
  AcademicCapIcon,
  PrinterIcon,
  ChatBubbleLeftRightIcon,
  ShieldCheckIcon,
  QrCodeIcon,
  BuildingOfficeIcon,
  BugAntIcon,
} from '@heroicons/vue/24/outline'

export interface GuideItem {
  id: string
  categoryId: string
  categoryName: string
  title: string
  goal: string
  icon: any
  route: string | null
  badge: string
  prerequisites?: string[]
  steps: string[]
  compliance?: string
  edgeCases?: string[]
  tip: string
}

export interface FaqItem {
  q: string
  a: string
  category?: string
  open?: boolean
}

export const localizedGuides: Record<string, GuideItem[]> = {
  de: [
    {
      id: 'how-to-book-lesson',
      categoryId: 'calendar',
      categoryName: 'Stundenplan & Unterricht',
      title: 'Unterrichtsstunde planen & buchen',
      goal: 'Buchen Sie Einzel- oder Gruppenunterricht mit Lehrkraft in einem freien Raum ohne Terminkollisionen.',
      icon: CalendarDaysIcon,
      route: '/lessons',
      badge: 'Stundenplan',
      prerequisites: [
        'Mindestens eine aktive Lehrkraft mit hinterlegtem Fach',
        'Mindestens ein Raum mit definierter Kapazität',
        'Schüler mit aktivem Vertrag oder BuT-Bildungsgutschein'
      ],
      steps: [
        'Gehen Sie über das Hauptmenü auf "Stundenplan & Unterricht" (/lessons).',
        'Klicken Sie oben rechts auf "+ Unterrichtsstunde buchen" oder direkt auf das gewünschte freie Zeitfenster im Kalender.',
        'Wählen Sie die Lehrkraft, den Raum, das Unterrichtsfach und fügen Sie einen oder mehrere Schüler hinzu.',
        'Legen Sie Datum, Startzeit und Dauer (z.B. 45 Min, 60 Min oder 90 Min Doppelstunde) fest.',
        'Klicken Sie auf "Speichern". Das System validiert die Buchung mathematisch in Echtzeit.'
      ],
      compliance: 'Hinweis zu BuT/Jobcenter: Gemäß § 28 SGB II dürfen nur tatsächlich genehmigte Fächer und Zeitkontingente im Rahmen des Bewilligungszeitraums gebucht werden.',
      edgeCases: [
        'Feiertage & Schulferien: Das System zeigt regionale gesetzliche Feiertage automatisch an und warnt vor Buchungen an Feiertagen.',
        'Überbuchung: Überschreitet die Schüleranzahl die Raumkapazität, wird das Speichern mit einer klaren Fehlermeldung verhindert.'
      ],
      tip: 'Doppelstunden (90 Min) werden bei der Anwesenheitserfassung automatisch als 2 Unterrichtseinheiten vom Schülerpaket abgebucht.'
    },
    {
      id: 'how-to-spot-conflicts',
      categoryId: 'calendar',
      categoryName: 'Stundenplan & Unterricht',
      title: 'Schutz vor Doppelbelegungen & Konflikten',
      goal: 'Raumkollisionen und Überschneidungen bei Dozenten und Schülern automatisch verhindern.',
      icon: CalendarDaysIcon,
      route: '/lessons',
      badge: 'Kollisionsschutz',
      prerequisites: [
        'Konfigurierte Raumkapazitäten unter Einstellungen -> Fächer & Räume',
        'Verfügbarkeitszeiten der Lehrkräfte'
      ],
      steps: [
        'Beim Erstellen oder Verschieben prüft der Server millisekundengenau alle bestehenden Termine.',
        'Kollidierende Stunden werden im Kalender sofort mit roter Signalumrandung und Warn-Badge markiert.',
        'Fahren Sie mit der Maus über die Karte, um den genauen Grund (z.B. "Raum 101 belegt durch Mathe 10b") zu sehen.',
        'Öffnen Sie die Stunde, um Zeit, Raum oder Dozent anzupassen – das System verhindert fehlerhaftes Überschreiben.'
      ],
      compliance: 'Sicherheitsgarantie: Verhindert rechtliche Schadensersatzansprüche von Eltern durch versehentlich ausgefallenen oder doppelt belegten Unterricht.',
      edgeCases: [
        'Parallele Gruppen: Mehrere Schüler können im selben Raum gebucht werden, solange die definierte Sitzplatzgrenze nicht überschritten wird.',
        'Stornierte Stunden: Abgesagte Stunden geben den Raum und die Lehrkraft sofort wieder für Neubuchungen frei.'
      ],
      tip: 'Die Kalender-Ansicht aktualisiert sich automatisch, sobald andere Mitarbeiter Termine verschieben.'
    },
    {
      id: 'how-to-take-attendance',
      categoryId: 'attendance',
      categoryName: 'Anwesenheit & Abrechnung',
      title: 'Anwesenheit erfassen & Stunden abbuchen',
      goal: 'Teilnahme statusgenau erfassen und Stundenkontingente automatisiert und fehlerfrei abbuchen.',
      icon: CheckCircleIcon,
      route: '/lessons',
      badge: '1-Klick Erfassung',
      prerequisites: [
        'Eine geplante oder abgeschlossene Unterrichtsstunde',
        'Schüler mit zugeordnetem Stundenpaket'
      ],
      steps: [
        'Klicken Sie im Kalender auf die Stunde, deren Unterricht beendet ist oder gerade stattfindet.',
        'Wählen Sie im Seitenpanel den Button "Anwesenheit erfassen".',
        'Setzen Sie für jeden Schüler den Status: Anwesend (Present), Verspätet (Late), Entschuldigt abwesend oder Unentschuldigt (No-Show).',
        'Geben Sie bei Bedarf eine Notiz ein (z.B. "Hausaufgaben vollständig gelöst, Thema: Lineare Algebra").',
        'Klicken Sie auf "Anwesenheit speichern".'
      ],
      compliance: 'Jobcenter-Konformität: Nur Stunden mit Status "Anwesend" oder "Verspätet" fließen in den offiziellen Stundennachweis für das Amt ein.',
      edgeCases: [
        'Unentschuldigtes Fehlen: Wird gemäß Instituts-AGB als kostenpflichtig abgebucht, jedoch auf dem Jobcenter-Nachweis separat ausgewiesen.',
        'Entschuldigtes Fehlen (<24h): Bei rechtzeitiger Krankmeldung bleibt das Schülerguthaben unberührt.'
      ],
      tip: 'Direkt nach dem Speichern aktualisiert sich das Reststunden-Konto des Schülers in Echtzeit im Dashboard.'
    },
    {
      id: 'how-reminders-work',
      categoryId: 'reminders',
      categoryName: 'Erinnerungen & Push',
      title: 'Automatische Unterrichtserinnerungen (24h & 2h)',
      goal: 'Ausfallquoten minimieren durch automatische, mehrstufige Vorab-Benachrichtigungen.',
      icon: BellAlertIcon,
      route: null,
      badge: '100% Automatisch',
      prerequisites: [
        'Gültige Telefonnummer / E-Mail im Profil der Eltern oder Lehrkraft',
        'Hinterlegte Benachrichtigungseinstellungen'
      ],
      steps: [
        '24 Stunden vor Beginn: Das System versendet die 1. Erinnerung mit Datum, Fach, Uhrzeit und Dozentenname.',
        '2 Stunden vor Beginn: Die 2. Express-Erinnerung erinnert an das pünktliche Erscheinen im Institut.',
        'Die Benachrichtigungen erscheinen im In-App Glocken-Center sowie optional per WhatsApp/SMS.',
        'Im Benachrichtigungscenter können Mitarbeiter den Zustellstatus jeder Nachricht in Echtzeit einsehen.'
      ],
      compliance: 'DSGVO-Hinweis: Benachrichtigungen enthalten keine sensiblen Gesundheits- oder Finanzdaten und können vom Empfänger in den Präferenzen angepasst werden.',
      edgeCases: [
        'Verschobene Termine: Ändern Sie eine Stunde, werden alte geplante Erinnerungen sofort gelöscht und frische für den neuen Zeitpunkt generiert.',
        'Gelöschte Stunden: Das System storniert alle anhängenden Benachrichtigungen sekundengenau.'
      ],
      tip: 'Der Scheduler läuft alle 5 Minuten im Hintergrund und verarbeitet Erinnerungen vollautomatisch ohne manuellen Aufwand.'
    },
    {
      id: 'how-to-approve-payrolls',
      categoryId: 'payrolls',
      categoryName: 'Honorarabrechnung',
      title: 'Dozentenhonorare prüfen, genehmigen & versiegeln',
      goal: 'Monatliche Dozentengehälter berechnen, Stunden auditieren und revisionssicher versiegeln.',
      icon: CurrencyEuroIcon,
      route: '/payrolls',
      badge: 'Kryptografisch Versiegelt',
      prerequisites: [
        'Vollständig erfasste Anwesenheiten für den Abrechnungsmonat',
        'Hinterlegter Stundensatz im Profil der Lehrkraft (z.B. 35,00 €/h)'
      ],
      steps: [
        'Öffnen Sie im Menü den Bereich "Honorarabrechnungen" (/payrolls).',
        'Wählen Sie Monat und Jahr aus. Das System aggregiert alle geleisteten Stunden und Beträge.',
        'Prüfen Sie die detaillierte Aufschlüsselung der Unterrichtseinheiten je Dozent.',
        'Klicken Sie auf "Abrechnung genehmigen & versiegeln". Das System generiert einen kryptografischen SHA-256 Snapshot-Hash.',
        'Klicken Sie auf "PDF herunterladen", um die offizielle Honorarabrechnung für die Buchhaltung zu exportieren.'
      ],
      compliance: 'GoBD- & Revisionssicherheit: Nach der Genehmigung sind die zugehörigen Stunden gegen jede nachträgliche Bearbeitung oder Löschung gesperrt.',
      edgeCases: [
        'Nachträgliche Stundenkorrektur: Kann nur durch einen Super-Admin nach explizitem Entsiegeln und mit vollständigem Audit-Trail vorgenommen werden.',
        'Unterschiedliche Fächerhonorare: Das System berechnet Stundensätze exakt nach der jeweiligen Fach- oder Einzel-/Gruppenzuordnung.'
      ],
      tip: 'Exportierte Honorar-PDFs enthalten den Institutsnamen, das Logo und die Steuernummer für die Steuerberatung.'
    },
    {
      id: 'how-invoices-work',
      categoryId: 'invoices',
      categoryName: 'Rechnungen & Finanzen',
      title: 'Privatzahler-Rechnungen erstellen & verwalten',
      goal: 'Rechnungen für Selbstzahler erstellen, Zahlungsfristen überwachen und Zahlungseingänge verbuchen.',
      icon: CurrencyDollarIcon,
      route: '/invoices',
      badge: 'Rechnungsverwaltung',
      prerequisites: [
        'Schüler mit hinterlegter Eltern-Rechnungsadresse',
        'Gebuchte oder abgerechnete Unterrichtspakete'
      ],
      steps: [
        'Öffnen Sie "Rechnungen & Finanzen" (/invoices) im Hauptmenü.',
        'Klicken Sie auf "+ Rechnung erstellen" oder wählen Sie die automatische Monatsgenerierung.',
        'Prüfen Sie die Rechnungsposten (z.B. 10x Einzelunterricht Mathematik à 45,00 € = 450,00 €).',
        'Laden Sie die PDF-Rechnung herunter oder versenden Sie sie direkt an die hinterlegte Eltern-E-Mail.',
        'Nach Eingang der Banküberweisung oder Barzahlung auf "Als bezahlt markieren" klicken.'
      ],
      compliance: 'UStG § 14 Konformität: Automatische fortlaufende Rechnungsnummerierung mit Ausweisung von Rechnungsdatum, Leistungszeitraum und Fälligkeit.',
      edgeCases: [
        'Mahnwesen & Überfälligkeit: Rechnungen nach Ablauf des Zahlungsziels werden farblich rot hervorgehoben.',
        'Gutschriften: Bei vorzeitiger Vertragskündigung kann eine Stornorechnung mit negativem Rechnungsbetrag erzeugt werden.'
      ],
      tip: 'Rechnungen für Bildungsgutscheine (BuT) werden separat als behördliche Abrechnungsnachweise geführt.'
    },
    {
      id: 'how-to-manage-packages',
      categoryId: 'students',
      categoryName: 'Schüler & BuT-Pakete',
      title: 'Schülerpakete & BuT-Bildungsgutscheine verwalten',
      goal: 'Stundenkontingente zuweisen, Gutscheine prüfen und Reststunden überwachen.',
      icon: UserGroupIcon,
      route: '/students',
      badge: 'Schüler-Profile',
      prerequisites: [
        'Angelegtes Schülerprofil unter /students',
        'Bewilligungsbescheid des Jobcenters/Sozialamts oder Privatvertrag'
      ],
      steps: [
        'Öffnen Sie "Schüler" und wählen Sie den gewünschten Schüler aus.',
        'Wechseln Sie in das Profil auf den Reiter "Verträge & Pakete".',
        'Klicken Sie auf "+ Paket zuweisen" und wählen Sie den Typ (z.B. 10h BuT-Gutschein, 20h Privatzahler).',
        'Geben Sie die Gutscheinnummer, das bewilligte Fach sowie Start- und Enddatum des Bewilligungszeitraums ein.',
        'Speichern Sie das Paket. Das Stundenkonto steht sofort für Unterrichtsbuchungen bereit.'
      ],
      compliance: 'Jobcenter-Abrechnung: Bildungsgutscheine sind zweckgebunden. Das System verhindert das Buchen von Stunden nach Ablauf des Bewilligungszeitraums.',
      edgeCases: [
        'Guthaben unter 2 Stunden: Das System zeigt eine automatische Warnung an, um Eltern rechtzeitig an Folgeanträge zu erinnern.',
        'Mehrere Fächer: Schüler können parallele Pakete für unterschiedliche Fächer (z.B. 10h Mathe + 10h Englisch) besitzen.'
      ],
      tip: 'Im Reiter "Kontoauszug" sehen Sie jede einzelne Abbuchung mit Datum, Dozent und Restguthaben.'
    },
    {
      id: 'how-to-print-reports',
      categoryId: 'reports',
      categoryName: 'Berichte & PDF-Druck',
      title: 'Stundennachweise, Türtabellen & Stundenpläne drucken',
      goal: 'Offizielle behördliche Nachweise und datenschutzkonforme Aushänge als PDF generieren.',
      icon: PrinterIcon,
      route: '/lessons',
      badge: 'PDF-Generatoren',
      prerequisites: [
        'Vollständige Instituts-Stammdaten unter Einstellungen',
        'Erfasste Anwesenheiten und Termine'
      ],
      steps: [
        'Offizieller Stundennachweis: Im Schülerprofil unter "Unterricht" auf "Stundennachweis (PDF)" klicken. Das Dokument listet alle verifizierten Termine mit Dozenten-Unterschriftsfeldern für das Jobcenter.',
        'DSGVO-Türtabelle (Raumplan): Im Kalender auf "Türtabellen-PDF exportieren" klicken. Erzeugt den Tagesaushang für Klassenzimmertüren.',
        'Lehrer-Wochenplan: Im Lehrerprofil oder Kalender auf "Stundenplan-PDF exportieren" klicken für den Dozentenausdruck.'
      ],
      compliance: 'DSGVO-Konformität: Auf den Aushang-Türtabellen werden Schülernachnamen automatisch anonymisiert (z.B. "Max M."), um europäischem Datenschutzrecht zu entsprechen.',
      edgeCases: [
        'Logo & Institutsname: Alle PDFs binden das hochgeladene Logo und den Namen Ihrer Einrichtung automatisch in Vektorqualität ein.',
        'Feiertage im Druck: Gesetzliche Feiertage werden auf den Nachweisen explizit als unterrichtsfrei vermerkt.'
      ],
      tip: 'Exportierte PDFs sind direkt für den Duplex-Druck und behördliche Stempelungen formatiert.'
    },
    {
      id: 'how-to-manage-teachers-rooms',
      categoryId: 'teachers',
      categoryName: 'Lehrkräfte & Räume',
      title: 'Lehrkräfte, Stundensätze & Raumkapazitäten einrichten',
      goal: 'Dozentenprofile pflegen, Qualifikationen hinterlegen und Raumgrenzen definieren.',
      icon: AcademicCapIcon,
      route: '/teachers',
      badge: 'Stammdaten',
      prerequisites: [
        'Berechtigung als Institutsleiter oder Administrator'
      ],
      steps: [
        'Öffnen Sie "Lehrkräfte" (/teachers) und klicken Sie auf "+ Lehrkraft anlegen".',
        'Hinterlegen Sie Kontaktdaten, unterrichtete Fächer und den individuellen Honorarsatz (z.B. 35,00 €/h).',
        'Legen Sie Verfügbarkeitsfenster fest (z.B. Montag bis Donnerstag 14:00 - 18:00 Uhr).',
        'Gehen Sie auf Einstellungen -> Fächer & Räume, um Unterrichtsräume mit ihrer maximalen Schülerkapazität anzulegen.'
      ],
      compliance: 'Arbeitsrecht & Datenschutz: Dozenten sehen nach dem Login ausschließlich ihre eigenen Schüler, Kurse und Honorarabrechnungen.',
      edgeCases: [
        'Urlaub & Abwesenheit: Im Dozentenprofil hinterlegte Abwesenheiten blockieren Buchungen im Kalender automatisch.',
        'Benutzer-Verknüpfung: Dozenten können direkt mit einem System-Login verknüpft werden.'
      ],
      tip: 'Vergeben Sie Raumfarben, um den Kalender übersichtlich nach Etagen oder Fächern zu gliedern.'
    },
    {
      id: 'how-to-use-chat',
      categoryId: 'messaging',
      categoryName: 'Nachrichten & Chat',
      title: 'Echtzeit-Chat, Gruppen & Schüler-Feedbackumfragen',
      goal: 'Sichere interne Kommunikation mit Lehrkräften, Schülern und Eltern ohne WhatsApp-Datenschutzrisiken.',
      icon: ChatBubbleLeftRightIcon,
      route: '/messaging',
      badge: 'Echtzeit-Chat',
      prerequisites: [
        'Aktivierte Benutzerkonten für Lehrkräfte und Schüler'
      ],
      steps: [
        'Öffnen Sie "Nachrichten & Chat" (/messaging) im Hauptmenü.',
        'Klicken Sie auf "+ Direktnachricht" oder "+ Gruppe erstellen" für Klassen- oder Fächergruppen.',
        'Nutzen Sie die Filter "Alle Dozenten" oder "Alle Schüler" für schnelle Gruppenmitgliederauswahl.',
        'Erstellen Sie interaktive Feedback-Umfragen (1-5 Sterne oder Multiple-Choice), um die Schülerzufriedenheit zu messen.',
        'Ergebnisse werden in Echtzeit aggregiert und visualisiert.'
      ],
      compliance: 'DSGVO-Konformität: Alle Chatnachrichten werden verschlüsselt auf dem deutschen Server gespeichert und nicht an Drittanbieter weitergegeben.',
      edgeCases: [
        'Dozenten-Schutz: Lehrkräfte können nur mit Schülern ihrer eigenen Kurse kommunizieren.',
        'Offline-Zustellung: Ist ein Nutzer offline, wird die Nachricht gespeichert und beim nächsten Login als ungelesen angezeigt.'
      ],
      tip: 'Nutzen Sie Gruppenchats für kurzfristige Unterrichtsankündigungen oder Hausaufgaben-Uploads.'
    },
    {
      id: 'how-system-error-logs-work',
      categoryId: 'admin',
      categoryName: 'System & Sicherheit',
      title: 'Fehler- & Systemprotokoll (24h Monitoring & Export)',
      goal: 'Systemausnahmen überwachen, Fehlerursachen analysieren und Logs revisionssicher exportieren.',
      icon: BugAntIcon,
      route: '/audit-logs',
      badge: 'Live-Diagnose',
      prerequisites: [
        'Rolle: Super Admin oder Administrator'
      ],
      steps: [
        'Öffnen Sie "Audit Logs" (/audit-logs) und wählen Sie den Reiter "Fehler- & Systemprotokoll".',
        'Überblicken Sie die 4 Live-Metrik-Karten der letzten 24 Stunden (Gesamtfehler, Kritisch, Warnungen, Speicherplatz).',
        'Filtern Sie gezielt nach Schweregrad (Error, Critical, Warning) oder durchsuchen Sie Meldungen nach Request-ID.',
        'Klicken Sie auf eine Zeile, um den vollständigen Stack Trace und den bereinigten Context aufzuklappen.',
        'Nutzen Sie "Log-Datei exportieren", um gefilterte Protokolle als .log/.txt oder strukturiertes .json herunterzuladen.'
      ],
      compliance: 'Automatisches Data-Sanitizing: Passwörter, API-Schlüssel, Tokens und Kreditkartendaten werden im Log automatisch unkenntlich gemacht ([REDACTED]).',
      edgeCases: [
        'Logs leeren: Kann nur von Super-Admins mit Bestätigung durchgeführt werden und hinterlässt einen unlöschbaren Eintrag im Audit-Trail.',
        'Request-ID Tracking: Jede Anfrage besitzt eine eindeutige Request-ID, die Sie in Fehlermeldungen direkt wiederfinden.'
      ],
      tip: 'Klicken Sie im Stack Trace auf "Kopieren", um Fehlerberichte direkt an den technischen Support zu senden.'
    },
    {
      id: 'how-center-branding-works',
      categoryId: 'admin',
      categoryName: 'System & Sicherheit',
      title: 'Instituts-Branding, Logo & Bundesland-Kalender',
      goal: 'Namen der Einrichtung festlegen, Institutslogo hochladen und Feiertagsberechnung aktivieren.',
      icon: BuildingOfficeIcon,
      route: '/settings',
      badge: 'Einrichtung',
      prerequisites: [
        'Berechtigung als Administrator oder Institutsleiter'
      ],
      steps: [
        'Öffnen Sie "Einstellungen" (/settings) über das Seitenmenü.',
        'Tragen Sie den offiziellen Namen Ihrer Nachhilfeeinrichtung ein (z.B. "Elite Nachhilfe Akademie").',
        'Wählen Sie Ihr deutsches Bundesland (z.B. Nordrhein-Westfalen, Bayern, Berlin). Das System kalibriert alle gesetzlichen Feiertage automatisch.',
        'Klicken Sie auf "Logo hochladen", um Ihr Instituts-Logo (PNG, JPG, WebP bis 3 MB) auszuwählen.',
        'Das Logo erscheint sofort in der Echtzeit-Vorschau, im Sidebar-Kopf und auf allen PDF-Dokumenten.'
      ],
      compliance: 'Rechtliche Pflichtangaben: Der Institutsname wird verbindlich auf alle Rechnungen, Mahnungen und Stundennachweise übernommen.',
      edgeCases: [
        'Transparente Logos: Empfohlen wird ein transparentes PNG-Format für optimalen Kontrast im Dark- und Light-Mode.',
        'Logo löschen: Mit Klick auf "Logo entfernen" wird das Logo gelöscht und das System wechselt zum Instituts-Initialen-Badge.'
      ],
      tip: 'Durch die Bundesland-Auswahl warnt der Kalender automatisch vor Buchungen an Karfreitag, Allerheiligen oder Fronleichnam.'
    },
    {
      id: 'how-to-manage-roles-users',
      categoryId: 'admin',
      categoryName: 'System & Sicherheit',
      title: 'Benutzerverwaltung & Rollenberechtigungen (RBAC)',
      goal: 'Zugriffsrechte für Verwaltung, Lehrkräfte, Schüler und Eltern granular steuern.',
      icon: ShieldCheckIcon,
      route: '/roles',
      badge: 'RBAC-Sicherheit',
      prerequisites: [
        'Super-Admin Benutzerkonto'
      ],
      steps: [
        'Öffnen Sie "Rollen & Berechtigungen" (/roles), um die Berechtigungsmatrix einzusehen.',
        'Definieren Sie exakt, welche Rollen Zugriff auf Finanzen, Schülerakten, Noten oder Honorare haben.',
        'Unter "Benutzer" (/users) neue Mitarbeiterkonten anlegen und Rollen zuweisen.',
        'Mitarbeiter sehen in ihrer Oberfläche ausschließlich die für ihre Rolle freigeschalteten Module.'
      ],
      compliance: 'DSGVO Prinzip der Datenminimierung: Dozenten haben keinen Zugriff auf Finanzdaten anderer Kollegen oder fremde Schülerakten.',
      edgeCases: [
        'Passwort zurücksetzen: Administratoren können Kontopasswörter bei Bedarf mit einem Klick neu generieren.',
        'Konto sperren: Deaktivierte Benutzerkonten verlieren sofort den Zugang zu allen Systemressourcen.'
      ],
      tip: 'Vergeben Sie die Super-Admin-Rolle nur an Institutsleiter und Systemadministratoren.'
    },
    {
      id: 'how-mobile-codes-work',
      categoryId: 'admin',
      categoryName: 'System & Sicherheit',
      title: 'App-Schnellzugangscodes für Schüler & Dozenten',
      goal: 'Passwortlosen, barrierefreien mobilen Zugang für Smartphones und Tablets ermöglichen.',
      icon: QrCodeIcon,
      route: '/students',
      badge: 'App-Zugang',
      prerequisites: [
        'Angelegtes Schüler- oder Lehrkraftprofil'
      ],
      steps: [
        'Öffnen Sie das Profil des Schülers oder der Lehrkraft.',
        'Klicken Sie auf "Login-Code anzeigen", um einen 6-stelligen Schnellzugangscode zu generieren.',
        'Der Nutzer gibt den Code auf seinem Smartphone ein und wird sofort ohne Passwort-Eingabe autorisiert.',
        'Der Code ist gerätegebunden und bietet maximale Sicherheit bei höchster Benutzerfreundlichkeit.'
      ],
      compliance: 'Datenschutz für Minderjährige: Erspart Schülern das Merken komplexer Passwörter und verhindert unberechtigte Kontozugriffe.',
      edgeCases: [
        'Code verloren: Ein neuer Code kann jederzeit im Schülerprofil generiert werden, wodurch der alte sofort ungültig wird.',
        'Geräteentzug: Bei Verlust des Smartphones kann die Sitzung im Schülerprofil mit einem Klick beendet werden.'
      ],
      tip: 'Drucken Sie den Code auf den Schüler-Ausweis oder die Willkommensmappe für neue Schüler.'
    }
  ],
  en: [
    {
      id: 'how-to-book-lesson',
      categoryId: 'calendar',
      categoryName: 'Calendar & Lessons',
      title: 'How to Schedule & Book a Lesson',
      goal: 'Book private or group tuition with teachers in available rooms with zero schedule conflicts.',
      icon: CalendarDaysIcon,
      route: '/lessons',
      badge: 'Schedule',
      prerequisites: [
        'At least one active teacher assigned to the subject',
        'At least one classroom with defined seating capacity',
        'Student with an active tuition contract or voucher'
      ],
      steps: [
        'Navigate to "Calendar & Lessons" (/lessons) from the main menu.',
        'Click "+ Schedule Lesson" at the top right, or click directly on any open calendar slot.',
        'Select the Instructor, Classroom, Subject, and add the Student(s).',
        'Set the Date, Start Time, and Duration (e.g. 45 min, 60 min, or 90 min double session).',
        'Click "Save Lesson". The system validates all constraints mathematically in real-time.'
      ],
      compliance: 'Regulatory Note: In funded education (BuT/Jobcenter), only authorized subjects and approved hour allocations within the grant period may be scheduled.',
      edgeCases: [
        'Public Holidays & Vacations: The system flags regional German holidays automatically and warns before booking on official days off.',
        'Overcapacity: If students exceed room capacity, saving is prevented with an explicit warning.'
      ],
      tip: 'Double sessions (90 min) automatically deduct 2 lesson units from the student voucher upon attendance marking.'
    },
    {
      id: 'how-to-spot-conflicts',
      categoryId: 'calendar',
      categoryName: 'Calendar & Lessons',
      title: 'Double-Booking & Collision Prevention Engine',
      goal: 'Automatically prevent overlapping room bookings and instructor scheduling conflicts.',
      icon: CalendarDaysIcon,
      route: '/lessons',
      badge: 'Conflict Protection',
      prerequisites: [
        'Configured room capacities in Settings -> Subjects & Rooms',
        'Teacher availability windows'
      ],
      steps: [
        'When creating or rescheduling, the server checks all existing appointments in milliseconds.',
        'Conflicting lessons are immediately flagged on the calendar with red warning borders.',
        'Hover over the lesson card to view the exact reason (e.g., "Room 101 occupied by Math 10b").',
        'Open the lesson to adjust time, room, or instructor – accidental overlaps cannot be saved.'
      ],
      compliance: 'Operational Assurance: Eliminates double-booking errors and protects center reputation.',
      edgeCases: [
        'Group Lessons: Multiple students can be booked in the same room up to the maximum room seat limit.',
        'Cancelled Lessons: Cancelled sessions immediately free up the instructor and room for new bookings.'
      ],
      tip: 'The calendar live-refreshes when other staff members make schedule changes.'
    },
    {
      id: 'how-to-take-attendance',
      categoryId: 'attendance',
      categoryName: 'Attendance & Billing',
      title: 'Record Student Attendance & Deduct Hours',
      goal: 'Mark student participation and deduct hours from active tuition packages accurately.',
      icon: CheckCircleIcon,
      route: '/lessons',
      badge: '1-Click Attendance',
      prerequisites: [
        'A scheduled or completed lesson',
        'Student with an assigned hour package'
      ],
      steps: [
        'Click on the lesson card in the calendar after or during the session.',
        'Click "Mark Attendance" in the slide-over panel.',
        'Select the status for each student: Present, Late, Excused Absence, or Unexcused (No-Show).',
        'Optionally add a lesson note (e.g., "Completed homework, Covered: Quadratic equations").',
        'Click "Save Attendance".'
      ],
      compliance: 'Jobcenter Compliance: Only verified "Present" or "Late" lessons appear on official government Stundennachweis reports.',
      edgeCases: [
        'No-Show (Unexcused): Billed to the student package according to center terms, but noted on records.',
        'Excused Absence (>24h): Student hours remain untouched when reported in advance.'
      ],
      tip: 'The student remaining hour balance updates instantly across the entire dashboard.'
    },
    {
      id: 'how-reminders-work',
      categoryId: 'reminders',
      categoryName: 'Reminders & Notifications',
      title: 'Automated Multi-Stage Lesson Reminders (24h & 2h)',
      goal: 'Minimize no-shows with automated advance notifications for students, parents, and teachers.',
      icon: BellAlertIcon,
      route: null,
      badge: '100% Automated',
      prerequisites: [
        'Valid phone number / email in the parent or teacher profile',
        'Active notification delivery channels'
      ],
      steps: [
        '24 Hours Before: The system sends the 1st reminder with date, subject, time, and teacher name.',
        '2 Hours Before: The 2nd final reminder prompts the student to arrive on time.',
        'Reminders appear in the top notification bell and optionally via WhatsApp/SMS.',
        'Staff can inspect delivery status and dispatch logs in the Notification Center.'
      ],
      compliance: 'GDPR Notice: Notifications do not contain sensitive health or billing data and respect user channel preferences.',
      edgeCases: [
        'Rescheduled Lessons: When you move a lesson, old pending reminders are purged and fresh ones are scheduled.',
        'Cancelled Lessons: System immediately purges all scheduled reminders.'
      ],
      tip: 'The reminder scheduler runs in the background every 5 minutes with zero manual intervention.'
    },
    {
      id: 'how-to-approve-payrolls',
      categoryId: 'payrolls',
      categoryName: 'Teacher Payrolls',
      title: 'Review, Approve & Seal Teacher Payrolls',
      goal: 'Calculate monthly instructor earnings, audit completed hours, and cryptographically seal records.',
      icon: CurrencyEuroIcon,
      route: '/payrolls',
      badge: 'Cryptographically Sealed',
      prerequisites: [
        'Fully recorded attendance for the target billing month',
        'Configured hourly rate in the teacher profile (e.g. 35.00 €/h)'
      ],
      steps: [
        'Open "Teacher Payrolls" (/payrolls) from the main menu.',
        'Select the target month and year to aggregate completed hours and earnings.',
        'Review the line-by-line breakdown of taught lessons for each instructor.',
        'Click "Approve & Seal Payroll". The system generates an immutable SHA-256 snapshot hash.',
        'Click "Download PDF" to export the official accounting payroll sheet.'
      ],
      compliance: 'Audit-Proof Financials: Once approved, all underlying lessons are locked against retroactive modification or deletion.',
      edgeCases: [
        'Retroactive Adjustments: Can only be unlocked by a Super Admin with an immutable audit log trail.',
        'Tiered Rates: The system calculates rates based on subject or group/private session configurations.'
      ],
      tip: 'Exported payroll PDFs include center branding, tax ID, and date stamps for accounting submission.'
    },
    {
      id: 'how-invoices-work',
      categoryId: 'invoices',
      categoryName: 'Invoices & Billing',
      title: 'Generate & Manage Private Tuition Invoices',
      goal: 'Issue parent invoices, track due dates, and record incoming payments.',
      icon: CurrencyDollarIcon,
      route: '/invoices',
      badge: 'Invoice Management',
      prerequisites: [
        'Student with parent billing address',
        'Booked or completed tuition packages'
      ],
      steps: [
        'Open "Invoices & Billing" (/invoices) from the sidebar.',
        'Click "+ Create Invoice" or trigger batch monthly generation.',
        'Verify line items (e.g., 10x Math 1-on-1 @ 45.00 € = 450.00 €).',
        'Download the official PDF invoice or email it directly to parents.',
        'When payment is received via bank transfer or cash, click "Mark as Paid".'
      ],
      compliance: 'Legal Tax Compliance: Automatic sequential invoice numbering with issue date, service period, and due date.',
      edgeCases: [
        'Overdue Invoices: Unpaid invoices past the due date are highlighted in red for payment follow-up.',
        'Credit Notes: Cancelled contracts can issue a credit note with a negative total balance.'
      ],
      tip: 'Jobcenter BuT vouchers are tracked separately under government voucher billing.'
    },
    {
      id: 'how-to-manage-packages',
      categoryId: 'students',
      categoryName: 'Students & BuT Packages',
      title: 'Manage Student Packages & Jobcenter BuT Vouchers',
      goal: 'Assign hour packages, verify vouchers, and monitor remaining student balances.',
      icon: UserGroupIcon,
      route: '/students',
      badge: 'Student Profiles',
      prerequisites: [
        'Student profile created under /students',
        'Jobcenter approval letter or private contract'
      ],
      steps: [
        'Open "Students" and click on the desired student profile.',
        'Navigate to the "Packages & Contracts" tab.',
        'Click "+ Add Package" and select the type (e.g., 10h BuT Voucher, 20h Private).',
        'Enter the voucher code, approved subject, and validity start/end dates.',
        'Save the package. The hour balance becomes available for lesson bookings immediately.'
      ],
      compliance: 'Voucher Validity: Educational vouchers are strictly earmarked. The system blocks booking lessons past the grant expiry date.',
      edgeCases: [
        'Low Balance (< 2 Hours): The system displays an automatic warning so parents can request follow-up funding.',
        'Multiple Subjects: Students can hold concurrent packages for different subjects (e.g. 10h Math + 10h English).'
      ],
      tip: 'The "Account Statement" tab displays every single deduction with date, teacher, and remaining balance.'
    },
    {
      id: 'how-to-print-reports',
      categoryId: 'reports',
      categoryName: 'Reports & Printing',
      title: 'Print Stundennachweis, Door Schedules & Timetables',
      goal: 'Generate official government certificates and GDPR-compliant room schedules as PDF.',
      icon: PrinterIcon,
      route: '/lessons',
      badge: 'PDF Generators',
      prerequisites: [
        'Configured institution branding in Settings',
        'Recorded attendance and schedule data'
      ],
      steps: [
        'Official Stundennachweis: Open the student profile, go to "Lessons", and click "Export Stundennachweis (PDF)".',
        'GDPR Door Schedule: On the calendar, click "Export Room Door Sheet (PDF)" for daily classroom door postings.',
        'Teacher Timetable: In the teacher profile or calendar, click "Export Timetable (PDF)" for instructor reference.'
      ],
      compliance: 'GDPR Privacy: Door schedules automatically redact student surnames (e.g. "Max M.") to comply with European privacy laws.',
      edgeCases: [
        'Branding & Logo: All PDFs embed your uploaded logo and center name in crisp vector quality.',
        'Holidays in Print: Official public holidays are noted on certificates as non-instructional days.'
      ],
      tip: 'Exported PDFs are formatted for standard duplex printing and official administrative stamps.'
    },
    {
      id: 'how-to-manage-teachers-rooms',
      categoryId: 'teachers',
      categoryName: 'Teachers & Classrooms',
      title: 'Configure Teachers, Pay Rates & Classroom Capacities',
      goal: 'Maintain instructor profiles, subjects taught, and define classroom seating limits.',
      icon: AcademicCapIcon,
      route: '/teachers',
      badge: 'Master Data',
      prerequisites: [
        'Manager or Administrator role'
      ],
      steps: [
        'Open "Teachers" (/teachers) and click "+ Add Teacher".',
        'Set contact info, taught subjects, and individual hourly pay rates (e.g. 35.00 €/h).',
        'Define weekly availability windows (e.g. Monday–Thursday 14:00–18:00).',
        'Go to Settings -> Subjects & Rooms to create classrooms with maximum student capacity.'
      ],
      compliance: 'Privacy & Security: Instructors see only their assigned students, classes, and payroll records upon logging in.',
      edgeCases: [
        'Teacher Vacation / Leave: Recorded absences automatically block calendar bookings for that period.',
        'User Account Link: Teachers can be linked directly to a login account.'
      ],
      tip: 'Assign room colors to organize the calendar clearly by floor or subject.'
    },
    {
      id: 'how-to-use-chat',
      categoryId: 'messaging',
      categoryName: 'Messaging & Chat',
      title: 'Real-Time Messaging, Groups & Student Feedback Surveys',
      goal: 'Secure internal communication with teachers, students, and parents without privacy risks.',
      icon: ChatBubbleLeftRightIcon,
      route: '/messaging',
      badge: 'Live Chat',
      prerequisites: [
        'Active user accounts for teachers and students'
      ],
      steps: [
        'Open "Messaging" (/messaging) from the sidebar menu.',
        'Click "+ Direct Message" or "+ Create Group" for class or subject channels.',
        'Use "Select All Teachers" or "Select All Students" filters for quick group composition.',
        'Create interactive satisfaction surveys (1-5 stars or multiple choice) to gather student feedback.',
        'Survey responses are aggregated and visualized in real-time.'
      ],
      compliance: 'GDPR Compliance: All chat data is stored encrypted on your server and never shared with third-party providers.',
      edgeCases: [
        'Teacher Privacy: Instructors can only message students enrolled in their assigned classes.',
        'Offline Delivery: Offline users receive unread message counters upon their next login.'
      ],
      tip: 'Use group channels for quick class announcements or homework file distribution.'
    },
    {
      id: 'how-system-error-logs-work',
      categoryId: 'admin',
      categoryName: 'System & Security',
      title: 'System & Error Logs (24h Monitoring & Diagnostics)',
      goal: 'Monitor system exceptions, troubleshoot bugs, and export structured logs.',
      icon: BugAntIcon,
      route: '/audit-logs',
      badge: 'Live Diagnostics',
      prerequisites: [
        'Role: Super Admin or Administrator'
      ],
      steps: [
        'Open "Audit Logs" (/audit-logs) and click the "System & Error Logs" tab.',
        'Inspect the 4 live 24-hour summary cards (Total Errors, Critical, Warnings, Log Storage).',
        'Filter by severity level (Error, Critical, Warning) or search by Request ID.',
        'Click on any log row to expand the full Stack Trace and sanitized Context JSON.',
        'Use "Export Log File" to download filtered logs as .log/.txt or structured .json.'
      ],
      compliance: 'Automatic Sensitive Data Redaction: Passwords, tokens, API keys, and credit cards are automatically replaced with [REDACTED].',
      edgeCases: [
        'Clear Logs: Restricted to Super Admins and leaves an immutable audit trail record in the database.',
        'Request ID Correlation: Every request has a unique Request ID for end-to-end bug tracing.'
      ],
      tip: 'Click "Copy Stack Trace" inside the accordion to share error reports directly with developers.'
    },
    {
      id: 'how-center-branding-works',
      categoryId: 'admin',
      categoryName: 'System & Security',
      title: 'Institution Branding, Logo & Regional Holiday Calendar',
      goal: 'Configure center name, upload institute logo, and calibrate regional public holidays.',
      icon: BuildingOfficeIcon,
      route: '/settings',
      badge: 'Institution',
      prerequisites: [
        'Administrator or Center Manager role'
      ],
      steps: [
        'Open "Settings" (/settings) from the sidebar.',
        'Enter the official name of your tutoring center (e.g. "Elite Tutoring Academy").',
        'Select your German Federal State (e.g. North Rhine-Westphalia, Bavaria, Berlin) to calibrate public holidays.',
        'Click "Upload Logo" to select your center emblem (PNG, JPG, WebP up to 3 MB).',
        'The logo appears immediately across the preview box, sidebar header, and PDF reports.'
      ],
      compliance: 'Official Records: The center name and logo are bound to all invoices, reminders, and certificates.',
      edgeCases: [
        'Transparent Logos: Transparent PNG is recommended for crisp contrast in both dark and light modes.',
        'Delete Logo: Clicking "Remove Logo" deletes the file and reverts to the center initial badge.'
      ],
      tip: 'Setting your federal state automatically flags regional holidays like Good Friday or Corpus Christi in the schedule.'
    },
    {
      id: 'how-to-manage-roles-users',
      categoryId: 'admin',
      categoryName: 'System & Security',
      title: 'User Management & Role-Based Permissions (RBAC)',
      goal: 'Control operational access for managers, teachers, students, and parents granularly.',
      icon: ShieldCheckIcon,
      route: '/roles',
      badge: 'RBAC Security',
      prerequisites: [
        'Super Admin user account'
      ],
      steps: [
        'Open "Roles & Permissions" (/roles) to inspect the privilege matrix.',
        'Define exact access rights for financial reports, student records, and grades.',
        'Go to "Users" (/users) to invite staff members and assign their roles.',
        'Staff members see only the modules and actions permitted by their role.'
      ],
      compliance: 'GDPR Data Minimization: Teachers cannot access colleague payrolls or unrelated student files.',
      edgeCases: [
        'Password Reset: Admins can reset user passwords with a single click.',
        'Account Deactivation: Disabled user accounts lose system access immediately.'
      ],
      tip: 'Reserve the Super Admin role strictly for center owners and system administrators.'
    },
    {
      id: 'how-mobile-codes-work',
      categoryId: 'admin',
      categoryName: 'System & Security',
      title: 'Mobile Quick Login Codes for Students & Teachers',
      goal: 'Enable passwordless, frictionless mobile access for smartphones and tablets.',
      icon: QrCodeIcon,
      route: '/students',
      badge: 'Mobile Access',
      prerequisites: [
        'Created student or teacher profile'
      ],
      steps: [
        'Open the Student or Teacher profile.',
        'Click "View Login Code" to generate a secure 6-digit access code.',
        'The user enters this code on their mobile device to log in without passwords.',
        'The code is device-bound for optimal security and convenience.'
      ],
      compliance: 'Child Privacy Protection: Spares students from remembering complex passwords while preventing unauthorized access.',
      edgeCases: [
        'Lost Code: A new code can be generated at any time, instantly revoking the previous one.',
        'Device Revocation: Active sessions can be terminated with one click in the student profile.'
      ],
      tip: 'Print the login code on student ID cards or welcome orientation folders.'
    }
  ]
}

export const localizedFaqs: Record<string, FaqItem[]> = {
  de: [
    {
      category: 'Unterricht & Kalender',
      q: 'Was passiert, wenn ich Datum oder Uhrzeit einer Stunde ändere (Terminverschiebung)?',
      a: 'Wenn Sie Termin oder Uhrzeit einer Stunde anpassen, storniert das System automatisch alle noch ausstehenden Erinnerungen für den alten Zeitpunkt und terminiert frische Erinnerungen (24h und 2h vorher) für den neuen Termin. Eventuelle Raum- oder Lehrkraftkollisionen werden dabei sofort neu geprüft.',
    },
    {
      category: 'Anwesenheit & BuT',
      q: 'Ein Schüler hat kurzfristig abgesagt. Wie erfasse ich das rechtlich und abrechnungstechnisch korrekt?',
      a: 'Öffnen Sie die Stunde im Kalender und wählen Sie "Anwesenheit erfassen". Setzen Sie den Status auf "Abgesagt / Unentschuldigt". Laut Institutsrichtlinie und BGB § 615 (Annahmeverzug) kann die Stunde bei Absagen unter 24 Stunden vor Beginn kostenpflichtig vom Schülerkontingent abgebucht werden. Bei rechtzeitiger Entschuldigung mit Attest setzen Sie den Status auf "Entschuldigt", wodurch das Stundenkonto unberührt bleibt.',
    },
    {
      category: 'Honorare & Finanzen',
      q: 'Können Stunden nach Genehmigung der Honorarabrechnung noch geändert oder gelöscht werden?',
      a: 'Nein. Zum Schutz Ihrer Buchhaltung und zur Einhaltung der GoBD sperrt das System automatisch alle Stunden, die in einer genehmigten Honorarabrechnung versiegelt wurden. Ein kryptografischer SHA-256 Snapshot-Hash garantiert die Unveränderbarkeit. Nur ein Super-Admin kann die Abrechnung im Notfall entsiegeln, was lückenlos im Audit-Protokoll vermerkt wird.',
    },
    {
      category: 'Behörden & Jobcenter',
      q: 'Wie erstelle ich einen offiziellen Stundennachweis für das Jobcenter / Sozialamt (BuT)?',
      a: 'Öffnen Sie das Profil des Schülers unter "Schüler", wechseln Sie zum Reiter "Unterricht" und klicken Sie auf "Stundennachweis (PDF)". Das Dokument enthält ausschließlich verifizierte Anwesenheitszeiten ("Anwesend" oder "Verspätet"), die Gutscheinnummer, den bewilligten Förderzeitraum sowie offizielle Unterschriftsfelder für Lehrkraft und Institutsleitung.',
    },
    {
      category: 'Datenschutz & DSGVO',
      q: 'Sind die Raum-Türtabellen an den Klassenzimmern datenschutzkonform gemäß DSGVO?',
      a: 'Ja. Wenn Sie die Türtabelle (Raumbelegungsplan) als PDF exportieren, anonymisiert das System alle Schülernachnamen automatisch (z.B. "Elias K.", "Sara M."). Dadurch können Dritte im Hausflur keine Rückschlüsse auf vollständige Personendaten ziehen.',
    },
    {
      category: 'Feiertage & Regionen',
      q: 'Wie berücksichtigt das System gesetzliche Feiertage und Schulferien in meinem Bundesland?',
      a: 'Unter Einstellungen -> Einrichtung wählen Sie Ihr Bundesland (z.B. Nordrhein-Westfalen, Bayern, Berlin). Der integrierte Feiertagsrechner kalibriert daraufhin automatisch alle regionalen Feiertage (z.B. Fronleichnam, Allerheiligen, Reformationstag). Im Kalender werden diese Tage markiert und vor versehentlichen Buchungen gewarnt.',
    },
    {
      category: 'Eltern & Geschwister',
      q: 'Was passiert, wenn Eltern mehrere Kinder im Nachhilfeinstitut angemeldet haben?',
      a: 'Geschwisterkinder werden automatisch über die gemeinsame Eltern-E-Mail bzw. Telefonnummer im System verknüpft. Eltern erhalten konsolidierte Benachrichtigungen für alle Kinder und können in der Elternansicht mit einem Klick zwischen den Profilen ihrer Kinder wechseln.',
    },
    {
      category: 'System & Diagnose',
      q: 'Wie finde ich die genaue Ursache heraus, wenn ein Mitarbeiter einen Fehler meldet?',
      a: 'Öffnen Sie "Audit Logs" und wechseln Sie zum Reiter "Fehler- & Systemprotokoll". Dort finden Sie alle Ausnahmen der letzten 24 Stunden. Fragen Sie den Mitarbeiter nach der "Request-ID" (wird in Fehlermeldungen angezeigt) und filtern Sie danach, um den vollständigen Stack Trace und die beteiligte Route sekundengenau einzusehen.',
    },
    {
      category: 'Gutscheine & Kontingente',
      q: 'Was passiert, wenn das Stundenkontingent eines Schülers aufgebraucht ist (< 2 Stunden)?',
      a: 'Das System markiert das Paket im Dashboard und im Schülerprofil automatisch mit einem gelben Warn-Badge "Guthaben fast aufgebraucht". Bei 0 Reststunden wird die Buchung blockiert, bis die Eltern ein Folgepaket buchen oder ein neuer BuT-Folgeantrag hinterlegt wird.',
    },
    {
      category: 'Mobile App & Sicherheit',
      q: 'Wie funktioniert der 6-stellige Schnellzugangscode für Schüler und Lehrkräfte?',
      a: 'Der Schnellzugangscode ermöglicht es Schülern und Dozenten, sich auf Smartphones oder Tablets ohne Eingabe von E-Mail und Passwort anzumelden. Der Code kann im Schüler- bzw. Dozentenprofil jederzeit generiert, erneuert oder bei Geräteverlust mit einem Klick gesperrt werden.',
    },
    {
      category: 'Rechnungen & Finanzen',
      q: 'Wie storniere ich eine bereits erstellte Privatzahler-Rechnung ordnungsgemäß?',
      a: 'Gemäß GoBD dürfen Rechnungen nicht einfach gelöscht werden. Öffnen Sie die Rechnung unter /invoices und wählen Sie "Stornieren / Gutschrift erstellen". Das System erzeugt eine verknüpfte Stornorechnung mit negativer Rechnungsnummer, die in der Buchhaltung gegengerechnet wird.',
    },
    {
      category: 'Unterricht & Räume',
      q: 'Kann ein Raum für mehrere Schüler gleichzeitig gebucht werden (Gruppenunterricht)?',
      a: 'Ja. Solange die festgelegte Sitzplatzkapazität des Raumes (z.B. 6 Schüler in Raum 102) nicht überschritten wird, können Sie beliebig viele Schüler zu einem Gruppenkurs hinzufügen. Versuchen Sie mehr Schüler als erlaubt zuzuweisen, verhindert das System die Überbuchung.',
    },
    {
      category: 'Zeitzone & Sommerzeit',
      q: 'Werden Sommer- und Winterzeit (DST) im Kalender exakt berechnet?',
      a: 'Ja. Sämtliche Zeitberechnungen laufen strikt nach der Zeitzone Europe/Berlin. Die Zeitumstellung im März und Oktober wird bei Terminberechnungen und automatischen Erinnerungen minutengenau berücksichtigt.',
    },
    {
      category: 'Dozenten-Berechtigungen',
      q: 'Können Lehrkräfte die Honorare oder Schülerakten anderer Kollegen einsehen?',
      a: 'Nein. Das Rollen- und Rechtesystem (RBAC) isoliert Dozentenprofile vollständig. Lehrkräfte sehen ausschließlich ihre eigenen zugewiesenen Schüler, den persönlichen Stundenplan und ihre eigene monatliche Abrechnung.',
    },
    {
      category: 'Nachrichten & WhatsApp',
      q: 'Wie versende ich WhatsApp-Nachrichten an Schüler und Eltern?',
      a: 'Unter Einstellungen -> WhatsApp Integration können Sie Ihre Twilio-Zugangsdaten (Account SID, Auth Token und WhatsApp-Sender) hinterlegen. Nach der Aktivierung versendet das System wichtige Terminerinnerungen und dringende Mitteilungen automatisch direkt auf die Smartphones der Eltern.',
    },
    {
      category: 'Live-Status & Unterricht',
      q: 'Wie erkenne ich auf einen Blick, welcher Unterricht aktuell im Institut stattfindet?',
      a: 'Im Kalender sowie im Dashboard werden alle Stunden, die zur aktuellen Europe/Berlin Ortszeit laufen, mit einem grün pulsierenden "Läuft gerade"-Status und Live-Zeitleiste hervorgehoben. Empfangsmitarbeiter sehen sofort, welcher Dozent in welchem Raum unterrichtet.',
    }
  ],
  en: [
    {
      category: 'Lessons & Calendar',
      q: 'What happens if I reschedule a lesson date or time?',
      a: 'When you update a lesson\'s date or time, the system automatically cancels all pending reminders for the old schedule and creates fresh 24-hour and 2-hour reminders for the new slot. Instructor and room collisions are re-checked instantly.',
    },
    {
      category: 'Attendance & BuT',
      q: 'A student cancelled at the last minute. How do I record this properly?',
      a: 'Open the lesson in the calendar and select "Mark Attendance". Set status to "Cancelled / Unexcused". Under standard terms and German BGB § 615, cancellations under 24 hours can be charged against the student voucher. If excused in advance with a medical note, set status to "Excused", leaving the hour balance untouched.',
    },
    {
      category: 'Payrolls & Finance',
      q: 'Can lessons be edited or deleted after a teacher payroll has been approved?',
      a: 'No. To protect financial accounting integrity and comply with auditing standards, the system locks all lessons sealed within an approved payroll statement. A cryptographic SHA-256 snapshot hash guarantees immutability. Only a Super Admin can unseal records, creating an immutable audit trail.',
    },
    {
      category: 'Government & BuT',
      q: 'How do I generate an official Stundennachweis for the Jobcenter / Social Welfare Office?',
      a: 'Open the student profile under "Students", go to the "Lessons" tab, and click "Stundennachweis (PDF)". The document lists only verified attendances ("Present" or "Late"), the voucher authorization number, validity period, and signature lines for instructor and center management.',
    },
    {
      category: 'Privacy & GDPR',
      q: 'Are classroom door schedules compliant with European GDPR regulations?',
      a: 'Yes. When you export the Door Schedule PDF for room doors, the system automatically redacts student surnames (e.g. "Elias K.", "Sara M."), preventing third parties in hallways from viewing complete personal identities.',
    },
    {
      category: 'Holidays & Regions',
      q: 'How does the system calibrate regional public holidays and school vacations in Germany?',
      a: 'Under Settings -> Institution, select your federal state (e.g. North Rhine-Westphalia, Bavaria, Berlin). The built-in holiday engine automatically calibrates all regional legal holidays (e.g. Good Friday, Corpus Christi, All Saints\' Day), displaying visual warnings on the calendar.',
    },
    {
      category: 'Parents & Siblings',
      q: 'What happens when parents have multiple children enrolled in the center?',
      a: 'Siblings are linked automatically via the parent\'s primary email or phone number. Parents receive unified reminders without receiving duplicate notifications and can toggle between children in one click.',
    },
    {
      category: 'System & Diagnostics',
      q: 'How do I identify the root cause when a staff member reports a system error?',
      a: 'Open "Audit Logs" and select the "System & Error Logs" tab. Review exceptions from the last 24 hours. Ask the user for their "Request ID" (shown on error toasts) and search for it to inspect the exact stack trace, route, and sanitized context.',
    },
    {
      category: 'Vouchers & Balances',
      q: 'What happens when a student package balance drops below 2 hours?',
      a: 'The system highlights the package with a yellow warning badge "Low Balance Alert" across the dashboard. When the balance reaches 0 hours, booking further lessons is blocked until parents renew or submit a follow-up funding letter.',
    },
    {
      category: 'Mobile App & Access',
      q: 'How does the 6-digit Quick Login Code work for students and instructors?',
      a: 'The quick code enables passwordless, frictionless login on mobile devices and tablets. The code can be generated, refreshed, or instantly revoked from the student or teacher profile if a device is lost.',
    },
    {
      category: 'Invoices & Billing',
      q: 'How do I cancel or issue a credit note for a private tuition invoice?',
      a: 'Under accounting standards, issued invoices cannot simply be deleted. Open the invoice at /invoices and select "Cancel / Create Credit Note". The system creates a linked credit invoice with a negative total to balance financial accounts.',
    },
    {
      category: 'Lessons & Rooms',
      q: 'Can a room be booked for multiple students at once (Group Tuition)?',
      a: 'Yes. As long as the defined room seating capacity (e.g. 6 students in Room 102) is not exceeded, you can add multiple students to a group lesson. The system mathematically prevents overbooking.',
    },
    {
      category: 'Timezones & DST',
      q: 'Are Daylight Saving Time (DST) clock changes handled accurately in Europe/Berlin?',
      a: 'Yes. All time arithmetic is computed strictly within the Europe/Berlin timezone. Spring and autumn clock transitions are calculated down to the minute for calendar bookings and automated reminders.',
    },
    {
      category: 'Teacher Privacy',
      q: 'Can instructors see payrolls or student records of other colleagues?',
      a: 'No. Role-based access control (RBAC) isolates teacher accounts completely. Instructors see only their assigned students, personal calendar, and their own monthly compensation sheets.',
    },
    {
      category: 'Messaging & WhatsApp',
      q: 'How do I send automated WhatsApp reminders to students and parents?',
      a: 'Under Settings -> WhatsApp Integration, enter your Twilio credentials (Account SID, Auth Token, and Sender Number). Once enabled, the system automatically delivers appointment reminders and urgent alerts directly to smartphones.',
    },
    {
      category: 'Live Status & Sessions',
      q: 'How do reception staff know which lessons are currently taking place in the center?',
      a: 'On the calendar and dashboard, any session active at the current Europe/Berlin time displays a pulsing green "In Progress" badge with a live progress indicator so staff know which rooms and teachers are busy.',
    }
  ]
}
