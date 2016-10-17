<?php
$lang = array();
//german
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

  /*
  0 ..... open
  1 ..... declined
  2 ..... accepted
  */
  $lang_vacationRequestStatus = array("Open", "Declined", "Accepted");

  $lang['GREETING_MORNING'] = 'Good Morning';
  $lang['GREETING_DAY'] = 'Hello';
  $lang['GREETING_AFTERNOON'] = 'Good Afternoon';
  $lang['GREETING_EVENING'] = 'Good Evening';
  $lang['CLIENT'] = 'Client';
  $lang['FIRSTNAME'] = 'First Name';
  $lang['LASTNAME'] = 'Last Name';
  $lang['HOURS'] = 'Hours';
  $lang['BLESSING'] = 'Have a nice day!';
  $lang['AUTOREDIRECT'] = 'Click here if not redirected automatically: ';
  $lang['UPDATE_REQUIRED'] = 'Update required. ';
  $lang['UPTODATE'] = 'Version is up tp date. ';
  $lang['INVALID_LOGIN'] = 'Invalid e-mail or password';
  $lang['EDIT_PERSONAL_INFO'] = 'Edit Personal info';
  $lang['TIME_CALCULATION_TABLE'] = 'Time calculation Table';
  $lang['CHECK_OUT'] = 'Check Out';
  $lang['CHECK_IN'] = 'Check In';
  $lang['VACATION'] = 'Vacation';
  $lang['SPECIAL_LEAVE'] = 'Special Leave';
  $lang['SPECIAL_LEAVE_RET'] = 'Return from Leave';
  $lang['REGISTER_NEW_USER'] ='Register new User';
  $lang['REGISTER_FROM_ACTIVE_DIR'] = 'Register Users from LDAP';
  $lang['REGISTER_USERS'] = "Register LDAP Users";
  $lang['VIEW_USER'] = 'Users';
  $lang['VIEW_TIMESTAMPS'] = 'View Timestamps';
  $lang['SETTINGS'] = 'Settings';
  $lang['HOLIDAYS'] = 'Holidays';
  $lang['COMPANIES'] = 'Companies';
  $lang['COMPANY'] = 'Company';
  $lang['CLIENTS'] = 'Clients';
  $lang['VIEW_PROJECTS'] = 'View Projects';
  $lang['NEW_PASSWORD'] = 'New Password';
  $lang['NEW_PASSWORD_CONFIRM'] = 'Confirm New Password';
  $lang['WEEKLY_HOURS'] = 'Weekly Hours';
  $lang['VACATION_DAYS_PER_YEAR'] = 'Vacation Days per Year';
  $lang['TIMETABLE'] = 'Timetable';
  $lang['BOOK_PROJECTS'] = 'Book Projects';
  $lang['WARNING_BLANK_FIELDS_WONT_OVERWRITE'] = 'Blank fields will not overwrite previous data.';
  $lang['DELETE'] = 'Delete';
  $lang['EDIT'] = 'Edit';
  $lang['ADMIN_DELETE'] = 'Please do not delete the Admin';
  $lang['GENDER'] = 'Gender';
  $lang['ALLOW_PRJBKING_ACCESS'] = 'Allow Project-Bookin Access';
  $lang['OVERTIME_ALLOWANCE'] = 'Overtime Allowance';
  $lang['AMOUNT_VACATION_DAYS'] = 'Amount of Vacation Days';
  $lang['TAKE_BREAK_AFTER'] = 'Take a break after Hours';
  $lang['ENTRANCE_DATE'] = 'Date of Entry';
  $lang['CHARGED'] = 'Charged';
  $lang['NOT_CHARGED'] = 'Not charged';
  $lang['PROJECT'] = 'Project';
  $lang['HOURS'] = 'Hours';
  $lang['HOURS_CREDIT'] = 'Hours Credit';
  $lang['HOURS_OF_REST'] = 'Hours of Rest';
  $lang['HOURLY_RATE'] = 'Hourly Rate';
  $lang['TIME'] = 'From - To';
  $lang['SUM'] = 'Sum';
  $lang['DEFAULT'] = 'Default';
  $lang['ASSIGNED'] = 'Assigned';
  $lang['DISPLAY_INFORMATION'] = 'Display Infos';
  $lang['ACTIVITY'] = 'Activity';
  $lang['LUNCHBREAK'] = 'Lunchbreak';
  $lang['BEGIN'] = 'Begin';
  $lang['END'] = 'End';
  $lang['LANGUAGE_SETTING'] = 'Language';
  $lang['EXPECTED'] = 'Expected';
  $lang['DATE'] = 'Date';
  $lang['DAILY_USER_PROJECT'] = 'Daily User Project';
  $lang['NUMBER'] = 'Number';
  $lang['UNDO'] ='Undo';
  $lang['MONTHLY_REPORT'] ='Monthly Report';
  $lang['PROJECT_INFORMATION'] = 'Reports';
  $lang['WEEKLY_DAY'] = 'Day of Week';
  $lang['SHOULD_TIME'] = 'Should';
  $lang['IS_TIME'] = 'Is';
  $lang['DIFFERENCE'] = 'Difference';
  $lang['FIELDS_REQUIRED'] = 'Fields are required';
  $lang['REPLY_TEXT'] = 'Answer';


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

  $lang['GREETING_MORNING'] = 'Guten Morgen';
  $lang['GREETING_DAY'] = 'Guten Tag';
  $lang['GREETING_AFTERNOON'] = 'Hallo';
  $lang['GREETING_EVENING'] = 'Guten Abend';
  $lang['CLIENT'] = 'Kunde';
  $lang['FIRSTNAME'] = 'Vorname';
  $lang['LASTNAME'] = 'Nachname';
  $lang['HOURS'] = 'Stunden';
  $lang['BLESSING'] ='Ich wünsche Ihnen einen angenehmen Tag!';
  $lang['AUTOREDIRECT'] = 'Klicken Sie hier, wenn Sie nicht automatisch weitergeleitet werden: ';
  $lang['UPDATE_REQUIRED'] = 'Update benötigt. ';
  $lang['UPTODATE'] = 'Version ist aktuell. ';
  $lang['INVALID_LOGIN'] = 'Ungültige e-mail oder falsches Passwort';
  $lang['EDIT_PERSONAL_INFO'] = 'Benutzer-Informationen';
  $lang['TIME_CALCULATION_TABLE'] = 'Stundenplan';
  $lang['CHECK_OUT'] = 'Ausstempeln';
  $lang['CHECK_IN'] = 'Einstempeln';
  $lang['VACATION'] = 'Urlaub';
  $lang['SPECIAL_LEAVE'] = 'Sonderurlaub';
  $lang['SPECIAL_LEAVE_RET'] = 'Sonderurlaub Beenden';
  $lang['REGISTER_NEW_USER'] = 'Registrieren';
  $lang['REGISTER_FROM_ACTIVE_DIR'] = 'Von LDAP Registrieren';
  $lang['REGISTER_FROM_FORM'] = 'Formular Registrierung';
  $lang['REGISTER_USERS'] = "LDAP Registrierung";
  $lang['VIEW_USER'] = 'Benutzer';
  $lang['VIEW_TIMESTAMPS'] = 'Zeitstempel';
  $lang['SETTINGS'] = 'Einstellungen';
  $lang['HOLIDAYS'] = 'Feiertage';
  $lang['COMPANIES'] = 'Mandanten';
  $lang['COMPANY'] = 'Mandant';
  $lang['CLIENTS'] = 'Kunden';
  $lang['VIEW_PROJECTS'] = 'Projekte';
  $lang['NEW_PASSWORD'] = 'Neues Passwort';
  $lang['NEW_PASSWORD_CONFIRM'] = 'Passwort bestätigen';
  $lang['WEEKLY_HOURS'] = 'Wochenstunden';
  $lang['VACATION_DAYS_PER_YEAR'] = 'Tage Urlaub im Jahr';
  $lang['TIMETABLE'] = 'Stundenplan';
  $lang['BOOK_PROJECTS'] = 'Projektbuchung';
  $lang['WARNING_BLANK_FIELDS_WONT_OVERWRITE'] = 'Leere Felder werden nicht übernommen';
  $lang['DELETE'] = 'Löschen';
  $lang['EDIT'] = 'Bearbeiten';
  $lang['ADMIN_DELETE'] = 'Bitte nicht den Admin löschen. ';
  $lang['GENDER'] = 'Gender';
  $lang['ALLOW_PRJBKING_ACCESS'] = 'Projektbuchung erlauben';
  $lang['OVERTIME_ALLOWANCE'] = 'Überstundenpauschale';
  $lang['AMOUNT_VACATION_DAYS'] = 'Gesammelte Urlaubstage';
  $lang['TAKE_BREAK_AFTER'] = 'Stunden bis Mittagspause';
  $lang['ENTRANCE_DATE'] = 'Eintrittsdatum';
  $lang['CHARGED'] = 'Verrechnet';
  $lang['NOT_CHARGED'] = 'Nicht Verrechnet';
  $lang['PROJECT'] = 'Projekt';
  $lang['HOURS'] = 'Stunden';
  $lang['HOURS_CREDIT'] = 'Stundenkonto';
  $lang['HOURS_OF_REST'] = 'Mittagspause in Stunden';
  $lang['HOURLY_RATE'] = 'Stundenrate';
  $lang['TIME'] = 'Von - Bis';
  $lang['SUM'] ='Summe';
  $lang['DEFAULT'] = 'Standard';
  $lang['ASSIGNED'] = 'Zugewiesene';
  $lang['DISPLAY_INFORMATION'] = 'Infos Anzeigen';
  $lang['ACTIVITY'] = 'Aktivität';
  $lang['LUNCHBREAK'] = 'Mittagspause';
  $lang['BEGIN'] = 'Anfang';
  $lang['END'] = 'Ende';
  $lang['LANGUAGE_SETTING'] = 'Sprache';
  $lang['EXPECTED'] = 'Erwartet';
  $lang['DATE'] = 'Datum';
  $lang['DAILY_USER_PROJECT'] = 'Tagesbericht';
  $lang['NUMBER'] = 'Number';
  $lang['UNDO'] = 'Rückgängig';
  $lang['MONTHLY_REPORT'] ='Monatsbericht';
  $lang['PROJECT_INFORMATION'] = 'Reports';
  $lang['WEEKLY_DAY'] = 'Wochentag';
  $lang['SHOULD_TIME'] = 'Soll';
  $lang['IS_TIME'] = 'Ist';
  $lang['DIFFERENCE'] = 'Differenz';
  $lang['FIELDS_REQUIRED'] = 'Felder werden benötigt';
  $lang['REPLY_TEXT'] = 'Antwort';

}
