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

  $lang['AUTOREDIRECT'] = 'Click here if not redirected automatically: ';
  $lang['ADMIN_DELETE'] = 'Please do not delete the Admin';
  $lang['ALLOW_PRJBKING_ACCESS'] = 'Allow Project-Bookin Access';
  $lang['AMOUNT_VACATION_DAYS'] = 'Amount of Vacation Days';
  $lang['ASSIGNED'] = 'Assigned';
  $lang['ACTIVITY'] = 'Activity';

  $lang['BLESSING'] = 'Have a nice day!';
  $lang['BOOK_PROJECTS'] = 'Book Projects';
  $lang['BEGIN'] = 'Begin';

  $lang['CHECK_OUT'] = 'Check Out';
  $lang['CHECK_IN'] = 'Check In';
  $lang['CLIENT'] = 'Client';
  $lang['COMPANIES'] = 'Companies';
  $lang['COMPANY'] = 'Company';
  $lang['CLIENTS'] = 'Clients';
  $lang['CHARGED'] = 'Charged';

  $lang['DISPLAY_INFORMATION'] = 'Display Infos';
  $lang['DATE'] = 'Date';
  $lang['DAILY_USER_PROJECT'] = 'Daily User Project';
  $lang['DELETE'] = 'Delete';
  $lang['DEFAULT'] = 'Default';
  $lang['DIFFERENCE'] = 'Difference';

  $lang['EDIT_PERSONAL_INFO'] = 'Edit Personal info';
  $lang['EDIT'] = 'Edit';
  $lang['ENTRANCE_DATE'] = 'Date of Entry';
  $lang['END'] = 'End';
  $lang['EXPECTED'] = 'Expected';

  $lang['FIRSTNAME'] = 'First Name';
  $lang['FIELDS_REQUIRED'] = 'Fields are required';
  $lang['FUNCTIONS'] = 'Functions';

  $lang['GREETING_MORNING'] = 'Good Morning';
  $lang['GREETING_DAY'] = 'Hello';
  $lang['GREETING_AFTERNOON'] = 'Good Afternoon';
  $lang['GREETING_EVENING'] = 'Good Evening';
  $lang['GENDER'] = 'Gender';

  $lang['HOURS'] = 'Hours';
  $lang['HOLIDAYS'] = 'Holidays';
  $lang['HOURS'] = 'Hours';
  $lang['HOURS_CREDIT'] = 'Hours Credit';
  $lang['HOURS_OF_REST'] = 'Hours of Rest';
  $lang['HOURLY_RATE'] = 'Hourly Rate';

  $lang['INVALID_LOGIN'] = 'Invalid e-mail or password';
  $lang['IS_TIME'] = 'Is';

  $lang['LASTNAME'] = 'Last Name';
  $lang['LUNCHBREAK'] = 'Lunchbreak';
  $lang['LUNCHBREAK_REPAIR'] = 'Lunchbreak Repair';
  $lang['LANGUAGE_SETTING'] = 'Language';

  $lang['MONTHLY_REPORT'] ='Monthly Report';

  $lang['OVERTIME_ALLOWANCE'] = 'Overtime Allowance';

  $lang['NEW_PASSWORD'] = 'New Password';
  $lang['NEW_PASSWORD_CONFIRM'] = 'Confirm New Password';
  $lang['NOT_CHARGED'] = 'Not charged';
  $lang['NUMBER'] = 'Number';

  $lang['PROJECT'] = 'Project';
  $lang['PROJECT_INFORMATION'] = 'Reports';

  $lang['REPLY_TEXT'] = 'Answer';
  $lang['REGISTER_NEW_USER'] ='Register new User';
  $lang['REGISTER_FROM_ACTIVE_DIR'] = 'Register Users from LDAP';
  $lang['REGISTER_USERS'] = "Register LDAP Users";

  $lang['SUM'] = 'Sum';
  $lang['SHOULD_TIME'] = 'Should';
  $lang['SETTINGS'] = 'Settings';
  $lang['SPECIAL_LEAVE'] = 'Special Leave';
  $lang['SPECIAL_LEAVE_RET'] = 'Return from Leave';

  $lang['TIME_CALCULATION_TABLE'] = 'Time calculation Table';
  $lang['TIMETABLE'] = 'Timetable';
  $lang['TAKE_BREAK_AFTER'] = 'Take a break after Hours';
  $lang['TIME'] = 'From - To';

  $lang['UNDO'] ='Undo';
  $lang['UPDATE_REQUIRED'] = 'Update required. ';
  $lang['UPTODATE'] = 'Version is up tp date. ';

  $lang['VACATION'] = 'Vacation';
  $lang['VACATION_REQUESTS'] = 'Vacation Requests';
  $lang['VIEW_USER'] = 'Users';
  $lang['VIEW_TIMESTAMPS'] = 'View Timestamps';
  $lang['VACATION_DAYS_PER_YEAR'] = 'Vacation Days per Year';
  $lang['VIEW_PROJECTS'] = 'View Projects';

  $lang['WEEKLY_HOURS'] = 'Weekly Hours';
  $lang['WARNING_BLANK_FIELDS_WONT_OVERWRITE'] = 'Blank fields will not overwrite previous data.';
  $lang['WEEKLY_DAY'] = 'Day of Week';

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

  $lang['ADMIN_DELETE'] = 'Bitte nicht den Admin löschen. ';
  $lang['ALLOW_PRJBKING_ACCESS'] = 'Projektbuchung erlauben';
  $lang['AMOUNT_VACATION_DAYS'] = 'Gesammelte Urlaubstage';
  $lang['ASSIGNED'] = 'Zugewiesene';
  $lang['AUTOREDIRECT'] = 'Klicken Sie hier, wenn Sie nicht automatisch weitergeleitet werden: ';
  $lang['ACTIVITY'] = 'Aktivität';

  $lang['BLESSING'] ='Ich wünsche Ihnen einen angenehmen Tag!';
  $lang['BOOK_PROJECTS'] = 'Projektbuchung';
  $lang['BEGIN'] = 'Anfang';

  $lang['CLIENT'] = 'Kunde';
  $lang['CHECK_OUT'] = 'Ausstempeln';
  $lang['CHECK_IN'] = 'Einstempeln';
  $lang['COMPANIES'] = 'Mandanten';
  $lang['COMPANY'] = 'Mandant';
  $lang['CLIENTS'] = 'Kunden';
  $lang['CHARGED'] = 'Verrechnet';

  $lang['DELETE'] = 'Löschen';
  $lang['DEFAULT'] = 'Standard';
  $lang['DISPLAY_INFORMATION'] = 'Infos Anzeigen';
  $lang['DATE'] = 'Datum';
  $lang['DAILY_USER_PROJECT'] = 'Tagesbericht';
  $lang['DIFFERENCE'] = 'Differenz';

  $lang['EDIT'] = 'Bearbeiten';
  $lang['ENTRANCE_DATE'] = 'Eintrittsdatum';
  $lang['END'] = 'Ende';
  $lang['EXPECTED'] = 'Erwartet';
  $lang['EDIT_PERSONAL_INFO'] = 'Benutzer-Informationen';

  $lang['FIRSTNAME'] = 'Vorname';
  $lang['FIELDS_REQUIRED'] = 'Felder werden benötigt';
  $lang['FUNCTIONS'] = 'Funktionen';

  $lang['GREETING_MORNING'] = 'Guten Morgen';
  $lang['GREETING_DAY'] = 'Guten Tag';
  $lang['GREETING_AFTERNOON'] = 'Hallo';
  $lang['GENDER'] = 'Gender';
  $lang['GREETING_EVENING'] = 'Guten Abend';

  $lang['HOURS'] = 'Stunden';
  $lang['HOLIDAYS'] = 'Feiertage';
  $lang['HOURS'] = 'Stunden';
  $lang['HOURS_CREDIT'] = 'Stundenkonto';
  $lang['HOURS_OF_REST'] = 'Mittagspause in Stunden';
  $lang['HOURLY_RATE'] = 'Stundenrate';

  $lang['IS_TIME'] = 'Ist';

  $lang['LASTNAME'] = 'Nachname';
  $lang['LUNCHBREAK'] = 'Mittagspause';
  $lang['LANGUAGE_SETTING'] = 'Sprache';
  $lang['LUNCHBREAK_REPAIR'] = 'Pausen Rekalculieren';

  $lang['MONTHLY_REPORT'] ='Monatsbericht';

  $lang['NEW_PASSWORD'] = 'Neues Passwort';
  $lang['NEW_PASSWORD_CONFIRM'] = 'Passwort bestätigen';
  $lang['NOT_CHARGED'] = 'Nicht Verrechnet';
  $lang['NUMBER'] = 'Number';

  $lang['UPDATE_REQUIRED'] = 'Update benötigt. ';
  $lang['UPTODATE'] = 'Version ist aktuell. ';
  $lang['INVALID_LOGIN'] = 'Ungültige e-mail oder falsches Passwort';
  $lang['TIME_CALCULATION_TABLE'] = 'Stundenplan';
  $lang['SPECIAL_LEAVE'] = 'Sonderurlaub';
  $lang['SPECIAL_LEAVE_RET'] = 'Sonderurlaub Beenden';
  $lang['REGISTER_NEW_USER'] = 'Registrieren';
  $lang['REGISTER_FROM_ACTIVE_DIR'] = 'Von LDAP Registrieren';
  $lang['REGISTER_FROM_FORM'] = 'Formular Registrierung';
  $lang['REGISTER_USERS'] = "LDAP Registrierung";

  $lang['PROJECT'] = 'Projekt';

  $lang['PROJECT_INFORMATION'] = 'Reports';
  $lang['REPLY_TEXT'] = 'Antwort';

  $lang['SUM'] ='Summe';
  $lang['SETTINGS'] = 'Einstellungen';
  $lang['SHOULD_TIME'] = 'Soll';

  $lang['TIMETABLE'] = 'Stundenplan';
  $lang['TIME'] = 'Von - Bis';
  $lang['TAKE_BREAK_AFTER'] = 'Stunden bis Mittagspause';
  $lang['OVERTIME_ALLOWANCE'] = 'Überstundenpauschale';

  $lang['UNDO'] = 'Rückgängig';

  $lang['VACATION'] = 'Urlaub';
  $lang['VIEW_USER'] = 'Benutzer';
  $lang['VIEW_TIMESTAMPS'] = 'Zeitstempel';
  $lang['VIEW_PROJECTS'] = 'Projekte';
  $lang['VACATION_REQUESTS'] = 'Urlaubsanfragen';
  $lang['VACATION_DAYS_PER_YEAR'] = 'Tage Urlaub im Jahr';

  $lang['WEEKLY_HOURS'] = 'Wochenstunden';
  $lang['WARNING_BLANK_FIELDS_WONT_OVERWRITE'] = 'Leere Felder werden nicht übernommen';
  $lang['WEEKLY_DAY'] = 'Wochentag';
}
