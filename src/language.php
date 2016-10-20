<?php
$lang = array();

//english-----------------------------------------------------------------------
if($_SESSION['language'] == 'ENG'){
  $activityToString = array(
    "arrival" => "Work",
    "vacation" => "Vacation",
    "spLeave" => "Special Leave",
    "sick" => "Sick",
    "ZA" => "Time Balance",
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
  4 ..... time balancing
  */
  $lang_activityToString = array(
    "-1" => "Absent",
    "0" => "Work",
    "1" => "Vacation",
    "2" => "Special Leave",
    "3" => "Sick",
    "4" => "Time Balance"
  );

  $lang['ABSOLVED_HOURS'] = 'Absolved Hours';
  $lang['ACTIVITY'] = 'Activity';
  $lang['ACCUMULATED_HOURS'] = 'Accumulated Hours';
  $lang['ADMIN_DELETE'] = 'Please do not delete the Admin';
  $lang['ADVANCED_OPTIONS'] = 'Advanced Options';
  $lang['ALLOW_PRJBKING_ACCESS'] = 'Allow Project-Bookin Access';
  $lang['AMOUNT_VACATION_DAYS'] = 'Amount of Vacation Days';
  $lang['ASSIGNED'] = 'Assigned';
  $lang['AUTOREDIRECT'] = 'Click here if not redirected automatically: ';

  $lang['BEGIN'] = 'Begin';
  $lang['BLESSING'] = 'Have a nice day!';
  $lang['BOOK_PROJECTS'] = 'Book Projects';

  $lang['CHARGED'] = 'Charged';
  $lang['CHECK_IN'] = 'Check In';
  $lang['CHECK_OUT'] = 'Check Out';
  $lang['CLIENT'] = 'Client';
  $lang['CLIENTS'] = 'Clients';
  $lang['COMPANIES'] = 'Companies';
  $lang['COMPANY'] = 'Company';

  $lang['DAILY_USER_PROJECT'] = 'Daily User Project';
  $lang['DATE'] = 'Date';
  $lang['DEFAULT'] = 'Default';
  $lang['DELETE'] = 'Delete';
  $lang['DESCRIPTION'] = 'Description';
  $lang['DIFFERENCE'] = 'Difference';
  $lang['DISPLAY_INFORMATION'] = 'Display Infos';

  $lang['EDIT'] = 'Edit';
  $lang['EDIT_PERSONAL_INFO'] = 'Edit Personal info';
  $lang['END'] = 'End';
  $lang['ENTRANCE_DATE'] = 'Date of Entry';
  $lang['EXPECTED'] = 'Expected';
  $lang['EXPECTED_HOURS'] = 'Expected Hours';

  $lang['FIELDS_REQUIRED'] = 'Fields are required';
  $lang['FIRSTNAME'] = 'First Name';
  $lang['FUNCTIONS'] = 'Functions';

  $lang['GREETING_MORNING'] = 'Good Morning';
  $lang['GREETING_DAY'] = 'Hello';
  $lang['GREETING_AFTERNOON'] = 'Good Afternoon';
  $lang['GREETING_EVENING'] = 'Good Evening';
  $lang['GENDER'] = 'Gender';

  $lang['HOLIDAYS'] = 'Holidays';
  $lang['HOURLY_RATE'] = 'Hourly Rate';
  $lang['HOURS'] = 'Hours';
  $lang['HOURS_CREDIT'] = 'Hours Credit';
  $lang['HOURS_OF_REST'] = 'Hours of Rest';

  $lang['INVALID_LOGIN'] = 'Invalid e-mail or password';
  $lang['IS_TIME'] = 'Is';

  $lang['LASTNAME'] = 'Last Name';
  $lang['LUNCHBREAK'] = 'Lunchbreak';
  $lang['LUNCHBREAK_REPAIR'] = 'Lunchbreak Repair';
  $lang['LANGUAGE_SETTING'] = 'Language';

  $lang['MONTHLY_REPORT'] ='Monthly Report';

  $lang['NEW_PASSWORD'] = 'New Password';
  $lang['NEW_PASSWORD_CONFIRM'] = 'Confirm New Password';
  $lang['NOT_CHARGED'] = 'Not charged';
  $lang['NOT_CHARGEABLE'] = 'Don`t charge';
  $lang['NUMBER'] = 'Number';

  $lang['OVERTIME_ALLOWANCE'] = 'Overtime Allowance';

  $lang['PROJECT'] = 'Project';
  $lang['PROJECT_INFORMATION'] = 'Reports';

  $lang['REGISTER_FROM_ACTIVE_DIR'] = 'Register Users from LDAP';
  $lang['REGISTER_NEW_USER'] ='Register new User';
  $lang['REGISTER_USERS'] = "Register LDAP Users";
  $lang['REPLY_TEXT'] = 'Answer';

  $lang['SETTINGS'] = 'Settings';
  $lang['SHOULD_TIME'] = 'Should';
  $lang['SICK_LEAVE'] = 'Sick Leave';
  $lang['SPECIAL_LEAVE'] = 'Special Leave';
  $lang['SPECIAL_LEAVE_RET'] = 'Return from Leave';
  $lang['SUM'] = 'Sum';

  $lang['TAKE_BREAK_AFTER'] = 'Take a break after Hours';
  $lang['TIME'] = 'From - To';
  $lang['TIME_CALCULATION_TABLE'] = 'Time calculation Table';
  $lang['TIMETABLE'] = 'Timetable';

  $lang['UNDO'] ='Undo';
  $lang['UPDATE_REQUIRED'] = 'Update required. ';
  $lang['UPTODATE'] = 'Version is up tp date. ';
  $lang['USED_HOURS'] ='Used Hours';

  $lang['VACATION'] = 'Vacation';
  $lang['VACATION_DAYS_PER_YEAR'] = 'Vacation Days per Year';
  $lang['VACATION_REQUESTS'] = 'Vacation Requests';
  $lang['VIEW_PROJECTS'] = 'View Projects';
  $lang['VIEW_TIMESTAMPS'] = 'View Timestamps';
  $lang['VIEW_USER'] = 'Users';

  $lang['WARNING_BLANK_FIELDS_WONT_OVERWRITE'] = 'Blank fields will not overwrite previous data.';
  $lang['WEEKLY_DAY'] = 'Day of Week';
  $lang['WEEKLY_HOURS'] = 'Weekly Hours';

//------------------------------------------------------------------------------

} elseif($_SESSION['language'] == 'GER'){
  $activityToString = array(
    "arrival" => "Dienst",
    "vacation" => "Urlaub",
    "spLeave" => "Sonderurlaub",
    "sick" => "Krank",
    "ZA" => "Zeitausgleich",
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
    "4" => "Zeitausgleich",
  );
  $lang_vacationRequestStatus = array("Offen", "Abgelehnt", "Bewilligt");

  $lang['ABSOLVED_HOURS'] = 'Absolvierte Stunden';
  $lang['ACTIVITY'] = 'Aktivität';
  $lang['ACCUMULATED_HOURS'] = 'Akkumulierte Stunden';
  $lang['ADMIN_DELETE'] = 'Bitte nicht den Admin löschen. ';
  $lang['ADVANCED_OPTIONS'] = 'Erweiterte Optionen';
  $lang['ALLOW_PRJBKING_ACCESS'] = 'Projektbuchung erlauben';
  $lang['AMOUNT_VACATION_DAYS'] = 'Gesammelte Urlaubstage';
  $lang['ASSIGNED'] = 'Zugewiesene';
  $lang['AUTOREDIRECT'] = 'Klicken Sie hier, wenn Sie nicht automatisch weitergeleitet werden: ';

  $lang['BEGIN'] = 'Anfang';
  $lang['BLESSING'] ='Ich wünsche Ihnen einen angenehmen Tag!';
  $lang['BOOK_PROJECTS'] = 'Projektbuchung';

  $lang['CHARGED'] = 'Verrechnet';
  $lang['CHECK_IN'] = 'Einstempeln';
  $lang['CHECK_OUT'] = 'Ausstempeln';
  $lang['CLIENT'] = 'Kunde';
  $lang['CLIENTS'] = 'Kunden';
  $lang['COMPANY'] = 'Mandant';
  $lang['COMPANIES'] = 'Mandanten';

  $lang['DAILY_USER_PROJECT'] = 'Tagesbericht';
  $lang['DATE'] = 'Datum';
  $lang['DEFAULT'] = 'Standard';
  $lang['DELETE'] = 'Löschen';
  $lang['DESCRIPTION'] = 'Beschreibung';
  $lang['DIFFERENCE'] = 'Differenz';
  $lang['DISPLAY_INFORMATION'] = 'Infos Anzeigen';

  $lang['EDIT'] = 'Bearbeiten';
  $lang['EDIT_PERSONAL_INFO'] = 'Benutzer-Informationen';
  $lang['END'] = 'Ende';
  $lang['ENTRANCE_DATE'] = 'Eintrittsdatum';
  $lang['EXPECTED'] = 'Erwartet';
  $lang['EXPECTED_HOURS'] = 'Erwartete Stunden';

  $lang['FIELDS_REQUIRED'] = 'Felder werden benötigt';
  $lang['FIRSTNAME'] = 'Vorname';
  $lang['FUNCTIONS'] = 'Funktionen';

  $lang['GENDER'] = 'Gender';
  $lang['GREETING_MORNING'] = 'Guten Morgen';
  $lang['GREETING_DAY'] = 'Guten Tag';
  $lang['GREETING_AFTERNOON'] = 'Hallo';
  $lang['GREETING_EVENING'] = 'Guten Abend';

  $lang['HOLIDAYS'] = 'Feiertage';
  $lang['HOURLY_RATE'] = 'Stundenrate';
  $lang['HOURS'] = 'Stunden';
  $lang['HOURS_CREDIT'] = 'Stundenkonto';
  $lang['HOURS_OF_REST'] = 'Mittagspause in Stunden';

  $lang['INVALID_LOGIN'] = 'Ungültige e-mail oder falsches Passwort';
  $lang['IS_TIME'] = 'Ist';

  $lang['LASTNAME'] = 'Nachname';
  $lang['LANGUAGE_SETTING'] = 'Sprache';
  $lang['LUNCHBREAK'] = 'Mittagspause';
  $lang['LUNCHBREAK_REPAIR'] = 'Pausen Rekalculieren';

  $lang['MONTHLY_REPORT'] ='Monatsbericht';

  $lang['NEW_PASSWORD'] = 'Neues Passwort';
  $lang['NEW_PASSWORD_CONFIRM'] = 'Passwort bestätigen';
  $lang['NOT_CHARGED'] = 'Nicht Verrechnet';
  $lang['NOT_CHARGEABLE'] = 'Erlassen';
  $lang['NUMBER'] = 'Number';

  $lang['OVERTIME_ALLOWANCE'] = 'Überstundenpauschale';

  $lang['PROJECT'] = 'Projekt';
  $lang['PROJECT_INFORMATION'] = 'Reports';

  $lang['REGISTER_FROM_ACTIVE_DIR'] = 'Von LDAP Registrieren';
  $lang['REGISTER_FROM_FORM'] = 'Formular Registrierung';
  $lang['REGISTER_NEW_USER'] = 'Registrieren';
  $lang['REGISTER_USERS'] = "LDAP Registrierung";
  $lang['REPLY_TEXT'] = 'Antwort';

  $lang['SETTINGS'] = 'Einstellungen';
  $lang['SHOULD_TIME'] = 'Soll';
  $lang['SICK_LEAVE'] = 'Krankenstand';
  $lang['SPECIAL_LEAVE'] = 'Sonderurlaub';
  $lang['SPECIAL_LEAVE_RET'] = 'Sonderurlaub Beenden';
  $lang['SUM'] ='Summe';

  $lang['TAKE_BREAK_AFTER'] = 'Stunden bis Mittagspause';
  $lang['TIME'] = 'Von - Bis';
  $lang['TIME_CALCULATION_TABLE'] = 'Stundenplan';
  $lang['TIMETABLE'] = 'Stundenplan';

  $lang['UNDO'] = 'Rückgängig';
  $lang['UPDATE_REQUIRED'] = 'Update benötigt. ';
  $lang['UPTODATE'] = 'Version ist aktuell. ';
  $lang['USED_HOURS'] ='Verbrauchte Stunden';

  $lang['VACATION'] = 'Urlaub';
  $lang['VACATION_DAYS_PER_YEAR'] = 'Tage Urlaub im Jahr';
  $lang['VACATION_REQUESTS'] = 'Urlaubsanfragen';
  $lang['VIEW_PROJECTS'] = 'Projekte';
  $lang['VIEW_TIMESTAMPS'] = 'Zeitstempel';
  $lang['VIEW_USER'] = 'Benutzer';

  $lang['WARNING_BLANK_FIELDS_WONT_OVERWRITE'] = 'Leere Felder werden nicht übernommen';
  $lang['WEEKLY_DAY'] = 'Wochentag';
  $lang['WEEKLY_HOURS'] = 'Wochenstunden';
}
