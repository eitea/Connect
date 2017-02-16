<?php
$lang = array();

//english-----------------------------------------------------------------------
if(isset($_SESSION['language']) && $_SESSION['language'] == 'ENG'){

  $activityToString = array(
    "arrival" => "Work",
    "vacation" => "Vacation",
    "spLeave" => "Special Leave",
    "sick" => "Sick",
    "ZA" => "Time Balance"
  );
  $lang_weeklyDayToString = array(
    "mon" => "Monday",
    "tue" => "Tuesday",
    "wed" => "Wednesday",
    "thu" => "Thursday",
    "fri" => "Friday",
    "sat" => "Saturday",
    "sun" => "Sunday"
  );

  $lang_monthToString = array("", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
  /*
  0 ..... open
  1 ..... declined
  2 ..... accepted
  */
  $lang_vacationRequestStatus = array("Open", "Declined", "Accepted");
  /*
  -1 .... absent (should not occur!)
  0 ..... arrival
  1 ..... vacation
  2 ..... special leave
  3 .... sickness
  4 ..... adjustments
  */
  $lang_activityToString = array(
    "-1" => "Absent",
    "0" => "Work",
    "1" => "Vacation",
    "2" => "Special Leave",
    "3" => "Sick",
    "4" => "Adjustment"
  );

  $lang['ABSOLVED_HOURS'] = 'Absolved Hours';
  $lang['ACTIVITY'] = 'Activity';
  $lang['ACCUMULATED'] = 'Accumulated';
  $lang['ACCUMULATED_HOURS'] = 'Accumulated Hours';
  $lang['ACCUMULATED_DAYS'] = 'Accumulated Days';
  $lang['ADD'] = 'Add';
  $lang['ADJUSTMENTS'] = 'Adjustments';
  $lang['ADMIN_DELETE'] = 'Please do not delete the Admin';
  $lang['ADMIN_CORE_OPTIONS'] = 'System Options';
  $lang['ADMIN_TIME_OPTIONS'] = 'Time Records';
  $lang['ADMIN_PROJECT_OPTIONS'] = 'Project Bookings';
  $lang['ADMIN_MODULES'] = 'Admin Modules';
  $lang['ADVANCED_OPTIONS'] = 'Advanced Options';
  $lang['ALLOW_PRJBKING_ACCESS'] = 'Allow Project-Bookin Access';
  $lang['AMOUNT_VACATION_DAYS'] = 'Amount of Vacation Days';
  $lang['ASSIGNED'] = 'Assigned';
  $lang['AUTOREDIRECT'] = 'Click here if not redirected automatically: ';

  $lang['BEGIN'] = 'Begin';
  $lang['BILLING'] = 'Billing';
  $lang['BLESSING'] = 'Have a nice day!';
  $lang['BOOK_PROJECTS'] = 'Book Projects';
  $lang['BOOKINGS'] = 'Bookings';
  $lang['BREAK'] = 'Break';
  $lang['BREAKS'] = 'Breaks';

  $lang['CALENDAR'] = 'Calendar';
  $lang['CAN_CHECKIN'] = 'Can Checkin';
  $lang['CAN_BOOK'] = 'Can create Bookings';
  $lang['CAN_EDIT_TEMPLATES'] = 'Can edit Templates';
  $lang['CHARGED'] = 'Charged';
  $lang['CHECK_IN'] = 'Check In';
  $lang['CHECK_OUT'] = 'Check Out';
  $lang['CLIENT'] = 'Client';
  $lang['CLIENTS'] = 'Clients';
  $lang['COMPANIES'] = 'Companies';
  $lang['COMPANY'] = 'Company';
  $lang['CORE_TIME'] = 'Core Time';
  $lang['CORRECTION'] = 'Correction';

  $lang['DAILY_USER_PROJECT'] = 'Daily User Project';
  $lang['DATE'] = 'Date';
  $lang['DEACTIVATE'] = 'Deactivate';
  $lang['DEFAULT'] = 'Default';
  $lang['DELETE'] = 'Delete';
  $lang['DELETE_COMPANY'] = 'Delete Company';
  $lang['DESCRIPTION'] = 'Description';
  $lang['DESCRIPTION_AUTOCORRECT_TIMESTAMPS'] = 'For forgotten checkouts: Set end-time to match expected hours';
  $lang['DIFFERENCE'] = 'Difference';
  $lang['DISPLAY_INFORMATION'] = 'Display Infos';
  $lang['DO_YOU_REALLY_WANT_TO_UPDATE'] = 'Do you really wish to Update? Unsaved changes will get lost.';
  $lang['DOUBLE'] = 'Double';
  $lang['DRIVES'] = 'Drives';

  $lang['EDIT'] = 'Edit';
  $lang['EDIT_USERS'] = 'Edit Users';
  $lang['EDIT_PERSONAL_INFO'] = 'Edit Personal info';
  $lang['END'] = 'End';
  $lang['ENTRANCE_DATE'] = 'Date of Entry';
  $lang['EXIT_DATE'] = 'Date of Exit';
  $lang['EXPECTED'] = 'Expected';
  $lang['EXPECTED_HOURS'] = 'Expected Hours';

  $lang['FIELDS_REQUIRED'] = 'Fields are required';
  $lang['FIRSTNAME'] = 'First Name';
  $lang['FUNCTIONS'] = 'Functions';
  $lang['FROM'] ='From';
  $lang['FORMS'] ='Forms';
  $lang['FOUNDERRORS'] = 'Found Errors';

  $lang['GREETING_MORNING'] = 'Good Morning';
  $lang['GREETING_DAY'] = 'Hello';
  $lang['GREETING_AFTERNOON'] = 'Good Afternoon';
  $lang['GREETING_EVENING'] = 'Good Evening';
  $lang['GENDER'] = 'Gender';

  $lang['HOLIDAYS'] = 'Holidays';
  $lang['HOURLY_RATE'] = 'Hourly Rate';
  $lang['HOURS'] = 'Hours';
  $lang['HOURS_COMPLETE'] = 'All Hours';
  $lang['HOURS_CREDIT'] = 'Hours Credit';
  $lang['HOURS_OF_REST'] = 'Hours of Rest';

  $lang['ILLEGAL_LUNCHBREAK'] = 'Invalid Lunchbreaks';
  $lang['ILLEGAL_TIMESTAMPS'] = 'Invalid Timestamps';
  $lang['INVALID_LOGIN'] = 'Invalid e-mail or password';
  $lang['IS_TIME'] = 'Is';

  $lang['LANGUAGE_SETTING'] = 'Language';
  $lang['LAST_BOOKING'] = 'Last Booking'; //I gave u my heaaart
  $lang['LASTNAME'] = 'Lastname';
  $lang['LOGOUT_MESSAGE'] = 'An Update Attempt has been sent. You will now be logged out';
  $lang['LUNCHBREAK'] = 'Lunchbreak';
  $lang['LUNCHBREAK_REPAIR'] = 'Lunchbreak Repair';

  $lang['MANDATORY_SETTINGS'] = 'Mandatory Settings';
  $lang['MARK_AS_ABSENT'] = 'Mark as Absent';
  $lang['MAY_TAKE_A_WHILE'] = 'This may take a few seconds.';
  $lang['MINUTES'] = 'Minutes';
  $lang['MISSING'] = 'Missing';
  $lang['MONTHLY_REPORT'] ='Monthly Report';

  $lang['NEW_CLIENT_CREATE'] = 'Create a new Client';
  $lang['NEW_PASSWORD'] = 'New Password';
  $lang['NEW_PASSWORD_CONFIRM'] = 'Confirm New Password';
  $lang['NEW_TEMPLATE'] = 'New Template';
  $lang['NOTES'] = 'Notes';
  $lang['NOT_CHARGED'] = 'Not charged';
  $lang['NOT_CHARGEABLE'] = 'Don`t charge';
  $lang['NUMBER'] = 'Number';

  $lang['OPTIONS'] = 'Options';
  $lang['OVERTIME_ALLOWANCE'] = 'Overtime Plus';
  $lang['OVERVIEW'] = 'Overview';

  $lang['PASSWORD'] = 'Password';
  $lang['PAYMENT'] = 'Payment';
  $lang['PDF_TEMPLATES'] = 'Pdf Templates';
  $lang['PREVIEW'] = 'Preview';
  $lang['PRODUCTIVE'] = 'Productive';
  $lang['PRODUCTIVE_FALSE'] = 'Unproductive';
  $lang['PROJECT'] = 'Project';
  $lang['PROJECT_BOOKINGS'] = 'Projectbookings';
  $lang['PROJECT_INFORMATION'] = 'Reports';

  $lang['READY_STATUS'] = 'Ready Status';
  $lang['RECALCULATE_VACATION'] = 'Recalculate Accumulated Vacation';
  $lang['REGISTER_FROM_ACTIVE_DIR'] = 'Register Users from LDAP';
  $lang['REGISTER_NEW_USER'] ='Register new User';
  $lang['REGISTER_USERS'] = "Register LDAP Users";
  $lang['REMOVE_USER'] = 'Remove User';
  $lang['REPLY_TEXT'] = 'Answer';
  $lang['REPORTS'] = 'Reports';
  $lang['REQUESTS'] = "Request";

  $lang['SAVE'] = 'Save';
  $lang['SETTINGS'] = 'Settings';
  $lang['SHOULD_TIME'] = 'Should';
  $lang['SICK_LEAVE'] = 'Sick Leave';
  $lang['SPECIAL_LEAVE'] = 'Special Leave';
  $lang['SPECIAL_LEAVE_RET'] = 'Return from Leave';
  $lang['SUM'] = 'Sum';

  $lang['TAKE_BREAK_AFTER'] = 'Take a break after Hours';
  $lang['TAXES'] = 'Taxes';
  $lang['TEMPLATES'] = 'Templates';
  $lang['THIS_IS_A_BREAK'] = 'This is a break';
  $lang['TIME'] = 'From - To';
  $lang['TIMES'] = 'Time';
  $lang['TIME_CALCULATION_TABLE'] = 'Time calculation Table';
  $lang['TIMETABLE'] = 'Timetable';
  $lang['TIMESTAMPS'] = 'Timestamps';
  $lang['TO'] = 'To';
  $lang['TRAVEL_FORM'] = 'Traveling Expenses';

  $lang['UNDO'] ='Undo';
  $lang['UNANSWERED_REQUESTS'] ='Unanswered Requests';
  $lang['UPDATE_REQUIRED'] = 'Update required. ';
  $lang['UPTODATE'] = 'Version is up tp date. ';
  $lang['USED_DAYS'] ='Used Days';
  $lang['USERS'] = 'Users';
  $lang['USER_INACTIVE'] = 'Deactivated Users';
  $lang['USER_MODULES'] = 'User Modules';

  $lang['VACATION'] = 'Vacation';
  $lang['VACATION_DAYS_PER_YEAR'] = 'Vacation Days per Year';
  $lang['VACATION_REPAIR'] = 'Repair Vacations';
  $lang['VACATION_REQUESTS'] = 'Vacation Requests';
  $lang['VACATION_WEEKS_PER_YEAR'] = 'Vacation Weeks per Year';
  $lang['VIEW_PROJECTS'] = 'View Projects';
  $lang['VIEW_TIMESTAMPS'] = 'View Timestamps';
  $lang['VIEW_USER'] = 'View Users';

  $lang['WARNING_BLANK_FIELDS_WONT_OVERWRITE'] = 'Blank fields will not overwrite previous data.';
  $lang['WEEKLY_DAY'] = 'Day of Week';
  $lang['WEEKLY_HOURS'] = 'Weekly Hours';

  $lang['YES_I_WILL'] = 'Yes, I do.';

//------------------------------------------------------------------------------

} elseif(!isset($_SESSION['language']) || $_SESSION['language'] == 'GER'){
  $activityToString = array(
    "arrival" => "Dienst",
    "vacation" => "Urlaub",
    "spLeave" => "Sonderurlaub",
    "sick" => "Krank",
    "ZA" => "Zeitausgleich"
  );
  $lang_weeklyDayToString = array(
    "mon" => "Montag",
    "tue" => "Dienstag",
    "wed" => "Mittwoch",
    "thu" => "Donnerstag",
    "fri" => "Freitag",
    "sat" => "Samstag",
    "sun" => "Sonntag"
  );
  $lang_activityToString = array(
    "-1" => "Abwesend",
    "0" => "Dienst",
    "1" => "Urlaub",
    "2" => "Sonderurlaub",
    "3" => "Krankenstand",
    "4" => "Korrektur"
  );
  $lang_vacationRequestStatus = array("Offen", "Abgelehnt", "Bewilligt");
  $lang_monthToString = array("", "Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");

  $lang['ABSOLVED_HOURS'] = 'Absolvierte Stunden';
  $lang['ACTIVITY'] = 'Aktivität';
  $lang['ACCUMULATED'] = 'Akkumuliert';
  $lang['ACCUMULATED_HOURS'] = 'Akkumulierte Stunden';
  $lang['ACCUMULATED_DAYS'] = 'Akkumulierte Tage';
  $lang['ADD'] = 'Hinzufügen';
  $lang['ADJUSTMENTS'] = 'Anpassungen';
  $lang['ADMIN_DELETE'] = 'Bitte nicht den Admin löschen. ';
  $lang['ADMIN_CORE_OPTIONS'] = 'Systemeinstellungen';
  $lang['ADMIN_MODULES'] = 'Admin Module';
  $lang['ADMIN_TIME_OPTIONS'] = 'Zeiterfassung';
  $lang['ADMIN_PROJECT_OPTIONS'] = 'Projektbuchungen';
  $lang['ADVANCED_OPTIONS'] = 'Erweiterte Optionen';
  $lang['ALLOW_PRJBKING_ACCESS'] = 'Projektbuchung erlauben';
  $lang['AMOUNT_VACATION_DAYS'] = 'Gesammelte Urlaubstage';
  $lang['ASSIGNED'] = 'Zugewiesene';
  $lang['AUTOREDIRECT'] = 'Klicken Sie hier, wenn Sie nicht automatisch weitergeleitet werden: ';

  $lang['BEGIN'] = 'Anfang';
  $lang['BILLING'] = 'Abrechnung';
  $lang['BLESSING'] ='Ich wünsche Ihnen einen angenehmen Tag!';
  $lang['BOOK_PROJECTS'] = 'Projekte Buchen';
  $lang['BOOKINGS'] = 'Buchungen';
  $lang['BREAK'] = 'Pause';
  $lang['BREAKS'] = 'Pausen';

  $lang['CALENDAR'] = 'Kalender';
  $lang['CAN_CHECKIN'] = 'Kann Einstempeln';
  $lang['CAN_BOOK'] = 'Kann Buchen';
  $lang['CAN_EDIT_TEMPLATES'] = 'Kann Vorlagen editieren';
  $lang['CHARGED'] = 'Verrechnet';
  $lang['CHECK_IN'] = 'Einstempeln';
  $lang['CHECK_OUT'] = 'Ausstempeln';
  $lang['CLIENT'] = 'Kunde';
  $lang['CLIENTS'] = 'Kunden';
  $lang['COMPANY'] = 'Mandant';
  $lang['COMPANIES'] = 'Mandanten';
  $lang['CORE_TIME'] = 'Kernzeit';
  $lang['CORRECTION'] = 'Korrektur';

  $lang['DAILY_USER_PROJECT'] = 'Tagesbericht';
  $lang['DATE'] = 'Datum';
  $lang['DATA'] = 'Daten';
  $lang['DEACTIVATE'] = 'Deaktivieren';
  $lang['DEFAULT'] = 'Standard';
  $lang['DELETE'] = 'Löschen';
  $lang['DELETE_COMPANY'] = 'Firma Löschen';
  $lang['DESCRIPTION'] = 'Beschreibung';
  $lang['DESCRIPTION_AUTOCORRECT_TIMESTAMPS'] = 'Für vergessene checkouts: Endzeit wird den erwarteten Stunden angepasst';
  $lang['DIFFERENCE'] = 'Differenz';
  $lang['DISPLAY_INFORMATION'] = 'Infos Anzeigen';
  $lang['DO_YOU_REALLY_WANT_TO_UPDATE'] = 'Möchten Sie wirklich ein Update durchführen? Nicht gesicherte Inhalte gehen dadurch verloren.';
  $lang['DOUBLE'] = 'Doppelt';
  $lang['DRIVES'] = 'Fahrten';

  $lang['EDIT'] = 'Bearbeiten';
  $lang['EDIT_USERS'] = 'Benutzer Editieren';
  $lang['EDIT_PERSONAL_INFO'] = 'Benutzer-Informationen';
  $lang['END'] = 'Ende';
  $lang['ENTRANCE_DATE'] = 'Eintrittsdatum';
  $lang['EXIT_DATE'] = 'Austrittsdatum';
  $lang['EXPECTED'] = 'Erwartet';
  $lang['EXPECTED_HOURS'] = 'Erwartete Stunden';

  $lang['FIELDS_REQUIRED'] = 'Felder werden benötigt';
  $lang['FIRSTNAME'] = 'Vorname';
  $lang['FUNCTIONS'] = 'Funktionen';
  $lang['FROM'] ='Von';
  $lang['FORMS'] ='Formulare';
  $lang['FOUNDERRORS'] = 'Gefundene Fehler';

  $lang['GENDER'] = 'Gender';
  $lang['GREETING_MORNING'] = 'Guten Morgen';
  $lang['GREETING_DAY'] = 'Guten Tag';
  $lang['GREETING_AFTERNOON'] = 'Hallo';
  $lang['GREETING_EVENING'] = 'Guten Abend';

  $lang['HOLIDAYS'] = 'Feiertage';
  $lang['HOURLY_RATE'] = 'Stundenrate';
  $lang['HOURS'] = 'Stunden';
  $lang['HOURS_COMPLETE'] = 'Alle Stunden';
  $lang['HOURS_CREDIT'] = 'Stundenkonto';
  $lang['HOURS_OF_REST'] = 'Mittagspause in Stunden';

  $lang['ILLEGAL_LUNCHBREAK'] = 'Ungültige Pausen';
  $lang['ILLEGAL_TIMESTAMPS'] = 'Ungültige Zeitstempel';
  $lang['INVALID_LOGIN'] = 'Ungültige e-mail oder falsches Passwort';
  $lang['IS_TIME'] = 'Ist';

  $lang['LANGUAGE_SETTING'] = 'Sprache';
  $lang['LAST_BOOKING'] = 'Letzte Buchung';
  $lang['LASTNAME'] = 'Nachname';
  $lang['LOGOUT_MESSAGE'] = 'Ein Update wurde angesetzt. Sie werden nun ausgeloggt.';
  $lang['LUNCHBREAK'] = 'Mittagspause';
  $lang['LUNCHBREAK_REPAIR'] = 'Pausen neu berechnen';

  $lang['MANDATORY_SETTINGS'] = 'Pflichtfelder';
  $lang['MARK_AS_ABSENT'] = 'Als Abwesend eintragen';
  $lang['MAY_TAKE_A_WHILE'] = 'Der Vorgang könnte einige Sekunden dauern.';
  $lang['MINUTES'] = 'Minuten';
  $lang['MISSING'] = 'Fehlend';
  $lang['MONTHLY_REPORT'] ='Monatsbericht';

  $lang['NEW_CLIENT_CREATE'] = 'Neuer Kunde';
  $lang['NEW_PASSWORD'] = 'Neues Passwort';
  $lang['NEW_PASSWORD_CONFIRM'] = 'Passwort bestätigen';
  $lang['NEW_TEMPLATE'] = 'Neue Vorlage';
  $lang['NOTES'] = 'Notizen';
  $lang['NOT_CHARGED'] = 'Nicht Verrechnet';
  $lang['NOT_CHARGEABLE'] = 'Erlassen';
  $lang['NUMBER'] = 'Nummer';

  $lang['OPTIONS'] = 'Optionen';
  $lang['OVERTIME_ALLOWANCE'] = 'Überstundenpauschale';
  $lang['OVERVIEW'] = 'Übersicht';

  $lang['PASSWORD'] = 'Passwort';
  $lang['PAYMENT'] = 'Zahlung';
  $lang['PDF_TEMPLATES'] = 'Pdf Vorlagen';
  $lang['PREVIEW'] = 'Vorschau';
  $lang['PRODUCTIVE'] = 'Produktiv';
  $lang['PRODUCTIVE_FALSE'] = 'Nicht Produktiv';
  $lang['PROJECT'] = 'Projekt';
  $lang['PROJECT_BOOKINGS'] = 'Projektbuchungen';
  $lang['PROJECT_INFORMATION'] = 'Reports';

  $lang['READY_STATUS'] = 'Bereitschaftsstatus';
  $lang['RECALCULATE_VACATION'] = 'Urlaub Rekalkulieren';
  $lang['REGISTER_FROM_ACTIVE_DIR'] = 'Von LDAP Registrieren';
  $lang['REGISTER_FROM_FORM'] = 'Formular Registrierung';
  $lang['REGISTER_NEW_USER'] = 'Registrieren';
  $lang['REGISTER_USERS'] = "LDAP Registrierung";
  $lang['REMOVE_USER'] = 'Benutzer Entfernen';
  $lang['REPLY_TEXT'] = 'Antwort';
  $lang['REPORTS'] = 'Berichte';
  $lang['REQUESTS'] = "Anfragen";

  $lang['SAVE'] = 'Speichern';
  $lang['SETTINGS'] = 'Einstellungen';
  $lang['SHOULD_TIME'] = 'Soll';
  $lang['SICK_LEAVE'] = 'Krankenstand';
  $lang['SPECIAL_LEAVE'] = 'Sonderurlaub';
  $lang['SPECIAL_LEAVE_RET'] = 'Sonderurlaub Beenden';
  $lang['SUM'] ='Summe';

  $lang['TAKE_BREAK_AFTER'] = 'Stunden bis Mittagspause';
  $lang['TAXES'] = 'Steuern';
  $lang['TEMPLATES'] = 'Vorlagen';
  $lang['THIS_IS_A_BREAK'] = 'Das ist eine Pause';
  $lang['TIME'] = 'Von - Bis';
  $lang['TIMES'] = 'Zeit';
  $lang['TIME_CALCULATION_TABLE'] = 'Stundenplan';
  $lang['TIMETABLE'] = 'Stundenplan';
  $lang['TIMESTAMPS'] = 'Zeitstempel';
  $lang['TO'] = 'Bis';
  $lang['TRAVEL_FORM'] = 'Reisekosten';

  $lang['UNDO'] = 'Rückgängig';
  $lang['UNANSWERED_REQUESTS'] ='Unbeantwortete Anfragen';
  $lang['UPDATE_REQUIRED'] = 'Update benötigt. ';
  $lang['UPTODATE'] = 'Version ist aktuell. ';
  $lang['USED_DAYS'] ='Verbrauchte Tage';
  $lang['USERS'] = 'Benutzer';
  $lang['USER_INACTIVE'] = 'Deaktivierte Benutzer';
  $lang['USER_MODULES'] = 'Benutzer Module';

  $lang['VACATION'] = 'Urlaub';
  $lang['VACATION_DAYS_PER_YEAR'] = 'Tage Urlaub im Jahr';
  $lang['VACATION_REQUESTS'] = 'Urlaubsanfragen';
  $lang['VACATION_REPAIR'] = 'Urlaub Autokorrektur';
  $lang['VACATION_WEEKS_PER_YEAR'] = 'Wochen Urlaub im Jahr';
  $lang['VIEW_PROJECTS'] = 'Projekte';
  $lang['VIEW_TIMESTAMPS'] = 'Zeitstempel Ansehen';
  $lang['VIEW_USER'] = 'Benutzer Ansehen';

  $lang['WARNING_BLANK_FIELDS_WONT_OVERWRITE'] = 'Leere Felder werden nicht übernommen';
  $lang['WEEKLY_DAY'] = 'Wochentag';
  $lang['WEEKLY_HOURS'] = 'Wochenstunden';

  $lang['YES_I_WILL'] = 'Ja, ich will.'; //ha-ha.
}
