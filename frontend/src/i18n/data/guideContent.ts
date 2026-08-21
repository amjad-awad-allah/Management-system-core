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
  ClipboardDocumentListIcon,
  QrCodeIcon,
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
  steps: string[]
  tip: string
}

export interface FaqItem {
  q: string
  a: string
  open?: boolean
}

export const localizedGuides: Record<string, GuideItem[]> = {
  de: [
    {
      id: 'how-to-book-lesson',
      categoryId: 'calendar',
      categoryName: 'Stundenplan & Unterricht',
      title: 'Unterrichtsstunde planen',
      goal: 'Buchen Sie Einzel- oder Gruppenunterricht mit Lehrkraft in einem freien Raum.',
      icon: CalendarDaysIcon,
      route: '/lessons',
      badge: 'Stundenplan',
      steps: [
        'Gehen Sie über das Seitenmenü auf "Stundenplan & Unterricht".',
        'Klicken Sie oben rechts auf "+ Unterrichtsstunde buchen" oder auf ein freies Zeitfeld.',
        'Wählen Sie Lehrkraft, Raum, Fach und fügen Sie den oder die Schüler hinzu.',
        'Wählen Sie Datum, Uhrzeit und Dauer aus und klicken Sie auf "Speichern".'
      ],
      tip: 'Das System prüft automatisch, ob die Lehrkraft verfügbar und der Raum frei ist.'
    },
    {
      id: 'how-to-spot-conflicts',
      categoryId: 'calendar',
      categoryName: 'Stundenplan & Unterricht',
      title: 'Schutz vor Doppelbelegungen & Konflikten',
      goal: 'Raumkollisionen und Überschneidungen bei Lehrkräften automatisch verhindern.',
      icon: CalendarDaysIcon,
      route: '/lessons',
      badge: 'Automatischer Schutz',
      steps: [
        'Stunden mit Terminkonflikten werden im Kalender mit rotem Warnhinweis markiert.',
        'Fahren Sie mit der Maus über die Karte, um den Grund der Doppelbelegung zu sehen.',
        'Klicken Sie auf die Stunde, um Zeit, Raum oder Lehrkraft anzupassen.'
      ],
      tip: 'Das System blockiert das Speichern kollidierender Stunden und begründet den Konflikt präzise.'
    },
    {
      id: 'how-to-take-attendance',
      categoryId: 'attendance',
      categoryName: 'Anwesenheit',
      title: 'Anwesenheit der Schüler erfassen',
      goal: 'Teilnahme erfassen und Stunden automatisch vom aktiven Paket abbuchen.',
      icon: CheckCircleIcon,
      route: '/lessons',
      badge: '1-Klick-Erfassung',
      steps: [
        'Klicken Sie im Kalender auf eine beendete oder laufende Unterrichtsstunde.',
        'Klicken Sie in der Seitenleiste auf "Anwesenheit erfassen".',
        'Wählen Sie für jeden Schüler: Anwesend, Abwesend (entschuldigt/unentschuldigt) oder Verspätet.',
        'Klicken Sie auf "Anwesenheit speichern".'
      ],
      tip: 'Bei Status "Anwesend" werden die genauen Unterrichtsstunden automatisch vom Schülerkonto abgebucht.'
    },
    {
      id: 'how-reminders-work',
      categoryId: 'reminders',
      categoryName: 'Erinnerungen',
      title: 'Automatische Unterrichtserinnerungen',
      goal: 'Schüler, Eltern und Lehrkräfte erhalten rechtzeitige Benachrichtigungen.',
      icon: BellAlertIcon,
      route: null,
      badge: '100% Automatisch',
      steps: [
        '24 Stunden vorher: Das System erinnert Lehrkraft, Schüler und Eltern automatisch.',
        '2 Stunden vorher: Eine kurze Abschluss-Erinnerung direkt vor Unterrichtsbeginn.',
        'Erinnerungen erscheinen im Glocken-Icon oben sowie per Nachricht/SMS.',
        'Beim Verschieben einer Stunde werden alte Erinnerungen automatisch storniert und neu terminiert.'
      ],
      tip: 'Erinnerungen laufen vollautomatisch alle 5 Minuten im Hintergrund.'
    },
    {
      id: 'how-to-approve-payrolls',
      categoryId: 'payrolls',
      categoryName: 'Honorarabrechnung',
      title: 'Dozentenhonorare prüfen & versiegeln',
      goal: 'Unterrichtsstunden berechnen und monatliche Honorarabrechnungen versiegeln.',
      icon: CurrencyEuroIcon,
      route: '/payrolls',
      badge: 'Honorarabrechnung',
      steps: [
        'Öffnen Sie die Seite "Honorarabrechnungen" über das Menü.',
        'Wählen Sie Monat und Jahr aus, um die geleisteten Unterrichtsstunden einzusehen.',
        'Prüfen Sie Gesamtstunden und Auszahlungsbetrag je Lehrkraft.',
        'Klicken Sie auf "Abrechnung genehmigen & versiegeln", um die Auszahlung unveränderbar zu fixieren.',
        'Klicken Sie auf "PDF herunterladen", um den offiziellen Stundennachweis zu drucken.'
      ],
      tip: 'Nach der Genehmigung sind die zugehörigen Stunden gegen nachträgliche Bearbeitung geschützt.'
    },
    {
      id: 'how-invoices-work',
      categoryId: 'invoices',
      categoryName: 'Rechnungen & Finanzen',
      title: 'Monatliche Rechnungen erstellen & verwalten',
      goal: 'Privatzahler-Rechnungen erstellen, Zahlungen erfassen und Status überwachen.',
      icon: CurrencyDollarIcon,
      route: '/invoices',
      badge: 'Rechnungsverwaltung',
      steps: [
        'Öffnen Sie "Rechnungen & Zahlungen" im Hauptmenü.',
        'Klicken Sie auf "+ Rechnungen generieren" oder wählen Sie einen Schüler aus.',
        'Nach Geldeingang per Überweisung oder Barzahlung auf "Als bezahlt markieren" klicken.',
        'Offizielle PDF-Rechnung herunterladen und an die Eltern senden.'
      ],
      tip: 'Offene und überfällige Rechnungen werden farblich hervorgehoben, damit keine Außenstände verloren gehen.'
    },
    {
      id: 'how-to-manage-packages',
      categoryId: 'students',
      categoryName: 'Schüler & BuT-Pakete',
      title: 'Schülerpakete & BuT-Gutscheine verwalten',
      goal: 'Stundenkontingente zuweisen, Gutscheine prüfen und Restguthaben überwachen.',
      icon: UserGroupIcon,
      route: '/students',
      badge: 'Schülerprofile',
      steps: [
        'Öffnen Sie "Schüler" und klicken Sie auf das gewünschte Schülerprofil.',
        'Klicken Sie auf den Reiter "Pakete", um aktive und abgelaufene Verträge einzusehen.',
        'Klicken Sie auf "Paket hinzufügen", um ein 10h-, 20h- oder BuT-Bildungsgutschein-Paket anzulegen.',
        'Bei jeder erfassten Stunde werden Restguthaben und Gültigkeitsdaten in Echtzeit aktualisiert.'
      ],
      tip: 'Bei weniger als 2 Reststunden erscheint automatisch eine gelbe Warnmeldung.'
    },
    {
      id: 'how-to-print-reports',
      categoryId: 'reports',
      categoryName: 'Berichte & Druck',
      title: 'Stundenpläne, Türtabellen & Nachweise drucken',
      goal: 'Druckfertige PDF-Stundenpläne und Raumbelegungspläne exportieren.',
      icon: PrinterIcon,
      route: '/lessons',
      badge: 'PDF-Exporte',
      steps: [
        'Lehrer-Stundenplan: Im Lehrerprofil oder Kalender auf "Stundenplan-PDF exportieren" klicken.',
        'Raum-Türtabelle: Auf "Türtabellen-PDF exportieren" klicken für den täglichen Aushang an Zimmertüren.',
        'Stundennachweis: Im Schülerprofil den offiziellen Nachweis für Eltern oder Jobcenter als PDF laden.'
      ],
      tip: 'Türtabellen blenden Schülernachnamen datenschutzkonform gemäß DSGVO automatisch aus.'
    },
    {
      id: 'how-to-manage-teachers-rooms',
      categoryId: 'teachers',
      categoryName: 'Lehrkräfte & Personal',
      title: 'Lehrkräfte, Stundensätze & Räume einrichten',
      goal: 'Dozentenprofile anlegen, Honorarsätze festlegen und Räume konfigurieren.',
      icon: AcademicCapIcon,
      route: '/teachers',
      badge: 'Lehrkräfte',
      steps: [
        'Öffnen Sie "Lehrkräfte", um neue Dozenten anzulegen und Stundensätze (z.B. 35,00 €/h) zu definieren.',
        'Verfügbarkeitszeiten je Wochentag festlegen, um automatische Buchungsprüfung zu aktivieren.',
        'Unter Einstellungen -> Räume die Raumnamen und Schüler-Sitzplatzkapazitäten verwalten.'
      ],
      tip: 'Lehrkräfte können direkt mit Benutzerkonten verknüpft werden, um ihren persönlichen Stundenplan einzusehen.'
    },
    {
      id: 'how-to-use-chat',
      categoryId: 'messaging',
      categoryName: 'Nachrichten & Chat',
      title: 'Direktnachrichten & Gruppenchats nutzen',
      goal: 'Echtzeit-Kommunikation mit Lehrkräften, Schülern und Elterngruppen.',
      icon: ChatBubbleLeftRightIcon,
      route: '/messaging',
      badge: 'Nachrichten',
      steps: [
        'Öffnen Sie "Nachrichten & Chat" im Menü.',
        'Wählen Sie einen bestehenden Chat oder klicken Sie auf "+", um ein neues Gespräch zu starten.',
        'Nachricht eingeben und Enter drücken für sofortige Echtzeit-Zustellung.',
        'Erstellen Sie Schülerumfragen, um regelmäßiges Feedback zum Unterricht einzuholen.'
      ],
      tip: 'Lehrkräfte können nur mit Schülern ihrer eigenen Kurse chatten, um Datenschutz zu wahren.'
    },
    {
      id: 'how-to-manage-roles-users',
      categoryId: 'admin',
      categoryName: 'Einstellungen & Sicherheit',
      title: 'Benutzerrollen & Berechtigungssteuerung',
      goal: 'Mitarbeiter-, Lehrer- und Schuler-Benutzerkonten granular verwalten.',
      icon: ShieldCheckIcon,
      route: '/roles',
      badge: 'Sicherheit',
      steps: [
        'Unter "Rollen" die Berechtigungsmatrix (Admin, Lehrkraft, Schüler, Eltern) einsehen.',
        'Unter "Benutzer" neue Mitarbeiter einladen und Rollen zuweisen.',
        'Mitarbeiter sehen ausschließlich die Seiten und Aktionen, die ihre Rolle erlaubt.'
      ],
      tip: 'Super-Admins haben Vollzugriff, während Lehrkräfte eine maßgeschneiderte Ansicht erhalten.'
    },
    {
      id: 'how-to-check-audit-logs',
      categoryId: 'admin',
      categoryName: 'Einstellungen & Sicherheit',
      title: 'Audit- & Sicherheitsprotokoll auswerten',
      goal: 'Lückenlose Nachvollziehbarkeit aller Änderungen im System.',
      icon: ClipboardDocumentListIcon,
      route: '/audit-logs',
      badge: 'Audit-Log',
      steps: [
        'Öffnen Sie "Audit Logs" im Seitenmenü.',
        'Nach Benutzer, Datum oder Ereignis (Erstellt, Bearbeitet, Gelöscht) filtern.',
        'Vorher-Nachher-Werte für geänderte Stunden, Rechnungen oder Schülerdaten einsehen.'
      ],
      tip: 'Audit-Logs sind unveränderbar und können von niemandem gelöscht werden.'
    },
    {
      id: 'how-mobile-codes-work',
      categoryId: 'admin',
      categoryName: 'Einstellungen & Sicherheit',
      title: 'App-Zugangscodes für Schüler & Lehrkräfte',
      goal: 'Einfacher, passwortloser mobiler Zugang für Smartphones und Tablets.',
      icon: QrCodeIcon,
      route: '/students',
      badge: 'App-Zugang',
      steps: [
        'Öffnen Sie das Profil eines Schülers oder einer Lehrkraft.',
        'Klicken Sie auf "Login-Code anzeigen", um einen 6-stelligen Schnellzugangscode zu generieren.',
        'Schüler geben diesen Code in der mobilen App ein, um sich ohne Passwort anzumelden.'
      ],
      tip: 'Zugangscodes können jederzeit mit einem Klick neu generiert oder deaktiviert werden.'
    }
  ],
  en: [
    {
      id: 'how-to-book-lesson',
      categoryId: 'calendar',
      categoryName: 'Calendar & Lessons',
      title: 'How to Schedule a Lesson',
      goal: 'Book a private or group lesson with a teacher in an available room.',
      icon: CalendarDaysIcon,
      route: '/lessons',
      badge: 'Lessons Page',
      steps: [
        'Go to the Lessons page from the sidebar menu.',
        'Click "+ Schedule Lesson" at the top right, or click any open time box on the calendar.',
        'Select the Teacher, Room, Subject, and add the Student(s).',
        'Choose the date, start time, and duration, then click "Save Lesson".'
      ],
      tip: 'The system automatically checks if the teacher is available and if the room is free.'
    },
    {
      id: 'how-to-spot-conflicts',
      categoryId: 'calendar',
      categoryName: 'Calendar & Lessons',
      title: 'How Double-Booking Prevention Works',
      goal: 'Avoid room collisions and teacher schedule overlaps automatically.',
      icon: CalendarDaysIcon,
      route: '/lessons',
      badge: 'Automatic Protection',
      steps: [
        'Lessons with overlapping bookings display a bright red warning badge on the calendar.',
        'Hover over the lesson card to see who is double-booked (e.g. "Room 101 occupied by Math 10").',
        'Click the lesson to adjust the time, change the room, or reassign the instructor.'
      ],
      tip: 'The system prevents saving conflicting lessons and will explain the conflict clearly.'
    },
    {
      id: 'how-to-take-attendance',
      categoryId: 'attendance',
      categoryName: 'Attendance',
      title: 'How to Record Student Attendance',
      goal: 'Mark student participation and deduct hours from their active packages.',
      icon: CheckCircleIcon,
      route: '/lessons',
      badge: '1-Click Attendance',
      steps: [
        'Click on any scheduled lesson in the calendar.',
        'Click "Mark Attendance" in the side panel that opens.',
        'Select the status for each student: Present (attended), Absent, or Late.',
        'Click "Save Attendance".'
      ],
      tip: 'Marking Present automatically subtracts the exact lesson hours from the student\'s active package.'
    },
    {
      id: 'how-reminders-work',
      categoryId: 'reminders',
      categoryName: 'Reminders',
      title: 'How Automatic Reminders Work',
      goal: 'Students and teachers receive timely notifications before every lesson.',
      icon: BellAlertIcon,
      route: null,
      badge: '100% Automated',
      steps: [
        '24 Hours Before: The system automatically reminds the teacher, student, and parents.',
        '2 Hours Before: A quick final notification is sent right before the session starts.',
        'Notifications arrive in the top bell icon on screen and via WhatsApp/Messages.',
        'When you reschedule a lesson, old reminders are cancelled automatically and updated.'
      ],
      tip: 'Reminders are completely automatic and run every 5 minutes in the background.'
    },
    {
      id: 'how-to-approve-payrolls',
      categoryId: 'payrolls',
      categoryName: 'Teacher Payroll',
      title: 'How to Review & Approve Teacher Pay',
      goal: 'Calculate teacher hours and lock monthly payroll statements for payment.',
      icon: CurrencyEuroIcon,
      route: '/payrolls',
      badge: 'Payrolls Page',
      steps: [
        'Go to the Payrolls page from the sidebar menu.',
        'Select the Month and Year at the top to see completed teaching hours.',
        'Review total teaching hours and earnings for each teacher.',
        'Click "Approve Payroll" to permanently lock the statement.',
        'Click "Download PDF" to print or save the official payment summary.'
      ],
      tip: 'Once approved, lessons in that period are locked to prevent accidental editing.'
    },
    {
      id: 'how-invoices-work',
      categoryId: 'invoices',
      categoryName: 'Invoices & Billing',
      title: 'How to Generate & Manage Invoices',
      goal: 'Create student tuition invoices, record payments, and track billing status.',
      icon: CurrencyDollarIcon,
      route: '/invoices',
      badge: 'Invoices Page',
      steps: [
        'Go to the Invoices page from the sidebar menu.',
        'Click "+ Create Invoice", select the student, billing period, and invoice items.',
        'When payment is received via bank transfer or cash, toggle the status to "Paid".',
        'Download and send the official PDF invoice to parents.'
      ],
      tip: 'Unpaid invoices display a clear status indicator so you never lose track of outstanding fees.'
    },
    {
      id: 'how-to-manage-packages',
      categoryId: 'students',
      categoryName: 'Students & Packages',
      title: 'How to Manage Student Packages & BuT Vouchers',
      goal: 'Assign hourly packages, track voucher validity, and monitor remaining balances.',
      icon: UserGroupIcon,
      route: '/students',
      badge: 'Students Page',
      steps: [
        'Go to Students and click on any student to open their profile.',
        'Click the "Packages" tab to see active and past hourly contracts.',
        'Click "Add Package" to assign a new 10, 20, or custom hour package or BuT voucher.',
        'As lessons occur, remaining hours and expiration dates update automatically.'
      ],
      tip: 'A yellow warning badge will appear whenever a student has fewer than 2 hours remaining.'
    },
    {
      id: 'how-to-print-reports',
      categoryId: 'reports',
      categoryName: 'Reports & Printing',
      title: 'How to Print Schedules & Door Sheets',
      goal: 'Generate ready-to-print weekly teacher timetables and room signs.',
      icon: PrinterIcon,
      route: '/lessons',
      badge: 'PDF Exports',
      steps: [
        'Teacher Timetable: In the teacher profile or calendar, click "Export Timetable PDF".',
        'Room Door Sheet: Click "Export Room Door Sheet PDF" to print a daily sign for classroom doors.',
        'Hours Proof (Stundennachweis): In the student profile, export attendance proofs for parents or Jobcenter.'
      ],
      tip: 'Room door sheets hide student surnames to comply with German/EU GDPR privacy rules.'
    },
    {
      id: 'how-to-manage-teachers-rooms',
      categoryId: 'teachers',
      categoryName: 'Teachers & Staff',
      title: 'How to Setup Teachers, Hourly Rates & Rooms',
      goal: 'Create instructor profiles, hourly pay rates, and configure classrooms.',
      icon: AcademicCapIcon,
      route: '/teachers',
      badge: 'Teachers Page',
      steps: [
        'Go to Teachers to add new instructors and specify their hourly wage (e.g. €35.00/h).',
        'Set teacher availability for each day of the week to enable smart booking validation.',
        'Go to Settings -> Rooms to configure classroom names and student capacity limits.'
      ],
      tip: 'Teachers can be linked directly to user accounts so they can view their personal timetable.'
    },
    {
      id: 'how-to-use-chat',
      categoryId: 'messaging',
      categoryName: 'Chat & Messages',
      title: 'How to Message Students & Teachers',
      goal: 'Communicate in real-time with instructors, students, and groups.',
      icon: ChatBubbleLeftRightIcon,
      route: '/messaging',
      badge: 'Messages Page',
      steps: [
        'Go to Messages from the sidebar menu.',
        'Select any conversation or click "+" to start a new chat with a teacher or student.',
        'Type your message and press Enter for real-time delivery.',
        'Send quick evaluation surveys to students to gather feedback on their lessons.'
      ],
      tip: 'Teachers can only message students in their own classes to maintain privacy.'
    },
    {
      id: 'how-to-manage-roles-users',
      categoryId: 'admin',
      categoryName: 'Settings & Security',
      title: 'User Roles & Permission Control',
      goal: 'Manage administrative staff, teacher, and student user accounts.',
      icon: ShieldCheckIcon,
      route: '/roles',
      badge: 'Security Page',
      steps: [
        'Go to Roles from the sidebar to inspect role permissions (Admin, Teacher, Student, Parent).',
        'Go to Users to invite new staff members and assign appropriate roles.',
        'Staff members only see pages and actions permitted by their assigned role.'
      ],
      tip: 'Super Admins have full access, while teachers and receptionists have tailored dashboards.'
    },
    {
      id: 'how-to-check-audit-logs',
      categoryId: 'admin',
      categoryName: 'Settings & Security',
      title: 'How to Inspect Audit Logs',
      goal: 'Track who performed what action for complete accountability.',
      icon: ClipboardDocumentListIcon,
      route: '/audit-logs',
      badge: 'Audit Page',
      steps: [
        'Go to Audit Logs from the sidebar menu.',
        'Filter by user, date, or event type (Create, Update, Delete).',
        'Inspect before-and-after values for any changed lesson, invoice, or student record.'
      ],
      tip: 'Audit logs cannot be modified or deleted by anyone, providing an airtight history.'
    },
    {
      id: 'how-mobile-codes-work',
      categoryId: 'admin',
      categoryName: 'Settings & Security',
      title: 'Student & Teacher Mobile Quick Codes',
      goal: 'Provide easy, passwordless mobile access for students and instructors.',
      icon: QrCodeIcon,
      route: '/students',
      badge: 'Mobile Access',
      steps: [
        'Open any Student or Teacher profile.',
        'Click "View Login Code" to generate a simple 6-digit access code.',
        'Students and teachers enter this code on mobile devices to log in without memorizing passwords.'
      ],
      tip: 'You can regenerate or revoke login codes instantly at any time.'
    }
  ]
}

export const localizedFaqs: Record<string, FaqItem[]> = {
  de: [
    {
      q: 'Was passiert, wenn ich Datum oder Uhrzeit einer Stunde ändere (Verschiebung)?',
      a: 'Wenn Sie Termin oder Uhrzeit einer Stunde anpassen, storniert das System automatisch alle noch ausstehenden Erinnerungen für den alten Zeitpunkt und terminiert frische Erinnerungen für den neuen Termin.',
      open: false,
    },
    {
      q: 'Ein Schüler hat kurzfristig abgesagt. Wie erfasse ich das korrekt?',
      a: 'Klicken Sie im Kalender auf die Stunde, wählen Sie "Status ändern" und setzen Sie den Status auf "Abgesagt". Wenn die Absage laut Institutsrichtlinie kostenpflichtig ist, aktivieren Sie "Stunde berechnen" (Abzug vom Guthaben), oder lassen Sie das Feld bei Kulanz unberührt.',
      open: false,
    },
    {
      q: 'Können Stunden nach Genehmigung der Honorarabrechnung noch geändert werden?',
      a: 'Nein. Zum Schutz Ihrer Buchhaltung vor versehentlichen Manipulationen sperrt das System automatisch alle Stunden, die in einer genehmigten Honorarabrechnung versiegelt wurden. Sie können weder bearbeitet noch gelöscht werden.',
      open: false,
    },
    {
      q: 'Wie erkenne ich, welche Stunden aktuell im Institut stattfinden?',
      a: 'Im Kalender und in der Zentralübersicht werden laufende Stunden zur aktuellen Europe/Berlin Ortszeit mit einem grün pulsierenden "Läuft gerade"-Status hervorgehoben.',
      open: false,
    },
    {
      q: 'Wie erstelle ich einen offiziellen Stundennachweis für BuT oder das Jobcenter?',
      a: 'Öffnen Sie das Schülerprofil, wechseln Sie zum Reiter "Unterricht" und klicken Sie auf "Stundennachweis (PDF)". Das Dokument enthält ausschließlich verifizierte Anwesenheitszeiten mit Unterschriftsfeldern für Dozenten.',
      open: false,
    },
    {
      q: 'Was passiert, wenn Eltern mehrere Kinder im Institut angemeldet haben?',
      a: 'Geschwister werden automatisch über das Elternkonto verknüpft. Eltern erhalten konsolidierte Benachrichtigungen für alle Kinder ohne doppelte Nachrichten.',
      open: false,
    },
    {
      q: 'Wie richte ich neue Unterrichtsfächer oder Raumkapazitäten ein?',
      a: 'Gehen Sie im Menü auf Einstellungen -> Fächer & Räume. Dort können Sie Fächer (z.B. Mathematik, Deutsch, Englisch) anlegen und Sitzplatzgrenzen für Räume festlegen.',
      open: false,
    },
    {
      q: 'Wie sehen Lehrkräfte nur ihre eigenen Schüler und Stundenpläne?',
      a: 'Beim Login einer Lehrkraft filtert das System Kalender und Schülerlisten automatisch so, dass nur die eigenen Kurse sichtbar sind, um Datenschutz und Übersichtlichkeit zu gewährleisten.',
      open: false,
    }
  ],
  en: [
    {
      q: 'What happens if I change the time or date of a lesson (Rescheduling)?',
      a: 'When you update a lesson\'s date or time, the system automatically cancels any old pending reminders for the previous time, and will automatically send fresh reminders when the new lesson time approaches.',
      open: false,
    },
    {
      q: 'A student canceled at the last minute. How do I record it properly?',
      a: 'Click the lesson on the calendar, click "Update Status", and select "Cancelled". If the cancellation was late according to center policy, check "Charge Student" to deduct the hour, or leave it unchecked if excused by management.',
      open: false,
    },
    {
      q: 'Can an instructor or admin edit a lesson after its payroll has been approved?',
      a: 'No. To protect your financial accounts from accidental tampering, the system automatically locks all lessons that have been approved in a teacher payroll statement. They cannot be edited or deleted unless an admin explicitly reopens the payroll.',
      open: false,
    },
    {
      q: 'How do I know which lessons are happening right now in the center?',
      a: 'On the calendar page, any lesson that is currently taking place in Europe/Berlin local time will display a pulsing green "In Progress" badge so reception staff know exactly which rooms are currently active.',
      open: false,
    },
    {
      q: 'How do I generate an official Stundennachweis for BuT or the Jobcenter?',
      a: 'Open the student profile in the Students section, go to the Lessons tab, and click "Export Stundennachweis PDF". The system generates an official document containing only verified Present and Late lessons with teacher signatures.',
      open: false,
    },
    {
      q: 'What if a parent has multiple children enrolled in the center?',
      a: 'The system links siblings automatically via the parent\'s account. The parent receives unified reminders for all their children without receiving duplicate messages.',
      open: false,
    },
    {
      q: 'How do I set up custom subjects or adjust classroom capacities?',
      a: 'Go to Settings from the sidebar menu. In the Subjects & Rooms tabs, you can add new school subjects (e.g. Mathematics, German, English) and configure rooms with maximum student seat counts.',
      open: false,
    },
    {
      q: 'How do teachers view only their own students and timetable?',
      a: 'When a teacher logs in, the system automatically filters the calendar and student lists so they only see their assigned classes and students, protecting privacy across the whole tutoring center.',
      open: false,
    }
  ]
}
