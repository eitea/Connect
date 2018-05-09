<?php include dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<!-- BODY -->

<div class="page-header">
	<h3><?php echo $lang['CREATE_NEW_COMPANY']; ?></h3>
</div>

<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
	if(isset($_POST['compaCreate']) && !empty($_POST['compaName']) && $_POST['compaType'] != "0"){
		$accept = '';
		$compaName = test_input($_POST['compaName']);
		$type = test_input($_POST['compaType']);
		$conn->query("INSERT INTO $companyTable (name, companyType) VALUES('$compaName', '$type')");
		$ins_id = mysqli_insert_id($conn);
		$accept .= $conn->error;
		$conn->query("INSERT INTO $companyToUserRelationshipTable (companyID, userID) VALUES($ins_id, $userID)");
		$accept .= $conn->error;
		$conn->query("INSERT INTO erp_settings (companyID, clientNum, clientStep, supplierNum, supplierStep) VALUES($ins_id, '1000',1,'1000',1)");
		$accept .= $conn->error;

		$file = fopen((dirname(__DIR__)).'/setup/Kontoplan.csv', 'r');
		if($file){
			$stmt = $conn->prepare("INSERT INTO accounts (companyID, num, name, type) VALUES($ins_id, ?, ?, ?)");
			$stmt->bind_param("iss", $num, $name, $type);
			while(($line= fgetcsv($file, 300, ';')) !== false){
				$num = $line[0];
				$name = trim(iconv(mb_detect_encoding($line[1], mb_detect_order(), true), "UTF-8", $line[1]));
				if(!$name) $name = trim(iconv('MS-ANSI', "UTF-8", $line[1]));
				if(!$name) $name = $line[1];
				$type = trim($line[2]);
				$stmt->execute();
			}
			$stmt->close();
		} else {
			$accept .= "<br>Error Opening csv File. Kontoplan konnte nicht erstellt werden";
		}
		$accept .= $conn->error;

		$conn->query("UPDATE accounts SET manualBooking = 'TRUE' WHERE companyID = $ins_id AND (name = 'Bank' OR name = 'Kassa')");
		$accept .= $conn->error;

		$keyPair = sodium_crypto_box_keypair();
		$private = sodium_crypto_box_secretkey($keyPair);
		$public = sodium_crypto_box_publickey($keyPair);
		$symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
		$nonce = random_bytes(24);
		$symmetric_encrypted = $nonce . sodium_crypto_box($symmetric, $nonce, $private.$public);
		$conn->query("INSERT INTO security_company (companyID, publicKey, symmetricKey) VALUES ($ins_id, '".base64_encode($public)."', '".base64_encode($symmetric_encrypted)."') ");
		if($conn->error) $accept .= 'Error: Mandantschlüssel '.$conn->error;
		$nonce = random_bytes(24);
		$encrypted = $nonce . sodium_crypto_box($private, $nonce, $private.base64_decode($publicKey));
		$conn->query("INSERT INTO security_access(userID, module, optionalID, privateKey) VALUES ($userID, 'COMPANY', $ins_id , '".base64_encode($encrypted)."')");
		if($conn->error) $accept .= 'Error: Zugriff '.$conn->error;

		$base_opts = array('', 'Awarness: Regelmäßige Mitarbeiter Schulung in Bezug auf Datenschutzmanagement','Awarness: Risikoanalyse','Awarness: Datenschutz-Folgeabschätzung',
		'Zutrittskontrolle: Schutz vor unbefugten Zutritt zu Server, Netzwerk und Storage','Zutrittskontrolle: Protokollierung der Zutritte in sensible Bereiche (z.B. Serverraum)',
		'Zugangskontrolle: regelmäßige Passwortänderung der Benutzerpasswörter per Policy (mind. Alle 180 Tage)','Zugangskontrolle: regelmäßige Passwortänderung der administrativen Zugänge,
		Systembenutzer (mind. Alle 180 Tage)','Zugangskontrolle: automaischer Sperrmechanismus der Zugänge um Brut Force Attacken abzuwehren','Zugangskontrolle: Zwei-Faktor-Authentifizierung für externe Zugänge (VPN)',
		'Wechseldatenträger: Sperre oder zumindest Einschränkung von Wechseldatenträger (USB-Stick, SD Karte, USB Geräte mit Speichermöglichkeiten…)',
		'Infrastruktur: Verschlüsselung der gesamten Festplatte in PC und Notebooks','Infrastruktur: Network Access Control (NAC) im Netzwerk aktiv',
		'Infrastruktur: Protokollierung der Verbindungen über die Firewall (mind. 180 Tage)','Infrastruktur: Einsatz einer Applikationsbasierter Firewall (next Generation Firewall)', 'Infrastruktur: Backup-Strategie, die mind. Alle 180 Tage getestet wird','Infrastruktur: Virenschutz (advanced Endpoint Protection)',
		'Infrastruktur: Regelmäßige Failover Tests, falls ein zweites Rechenzentrum vorhanden ist','Infrastruktur: Protokollierung von Zugriffen und Alarmierung bei unbefugten Lesen oder Schreiben',
		'Weitergabekontrolle: Kein unbefugtes Lesen, Kopieren, Verändern oder Entfernen bei elektronischer Übertragung oder Transport, zB: Verschlüsselung, Virtual Private Networks (VPN), elektronische Signatur',
		'Drucker und MFP Geräte: Verschlüsselung der eingebauten Datenträger.','Drucker und MFP Geräte: Secure Printing bei personenbezogenen Daten. Unter "secure printing" versteht man die zusätzliche Authentifizierung direkt am Drucker, um den Ausdruck zu erhalten.',
		'Drucker und MFP Geräte: Bei Leasinggeräten, oder pay per Page Verträgen muss der Datenschutz zwischen den Vertragspartner genau geregelt werden (Vertrag).',
		'Eingabekontrolle: Feststellung, ob und von wem personenbezogene Daten in Datenverarbeitungssysteme eingegeben, verändert oder entfernt worden sind, zB: Protokollierung, Dokumentenmanagement');
		$app_opt_1 = array('', 'Name der verantwortlichen Stelle für diese Applikation', 'Beschreibung der betroffenen Personengruppen und der diesbezüglichen Daten oder Datenkategorien',
		'Zweckbestimmung der Datenerhebung, Datenverarbeitung und Datennutzung', 'Regelfristen für die Löschung der Daten', 'Datenübermittlung in Drittländer', 'Einführungsdatum der Applikation','Liste der zugriffsberechtigten Personen');
		$app_opt_2 = array('', 'Pseudonymisierung: Falls die jeweilige Datenanwendung eine Pseudonymisierung unterstützt, wird diese aktiviert. Bei einer Pseudonymisierung werden personenbezogene Daten in der Anwendung entfernt und gesondert aufbewahrt.',
		'Verschlüsselung der Daten: Sofern von der jeweiligen Datenverarbeitung möglich, werden die personenbezogenen Daten verschlüsselt und nicht als Plain-Text Daten gespeichert',
		'Applikation: Backup-Strategie, die mind. Alle 180 Tage getestet wird', 'Applikation: Protokollierung von Zugriffen und Alarmierung bei unbefugten Lesen oder Schreiben',
		'Weitergabekontrolle: Kein unbefugtes Lesen, Kopieren, Verändern oder Entfernen bei elektronischer Übertragung oder Transport, zB: Verschlüsselung, Virtual Private Networks (VPN), elektronische Signatur',
		'Vertraglich (bei externer Betreuung): Gib eine schriftliche Übereinkunft der Leistung und Verpflichtung mit dem entsprechenden Dienstleister der Software?',
		'Eingabekontrolle: Feststellung, ob und von wem personenbezogene Daten in Datenverarbeitungssysteme eingegeben, verändert oder entfernt worden sind, zB: Protokollierung, Dokumentenmanagement');

		$stmt_vv = $conn->prepare("INSERT INTO dsgvo_vv(templateID, name) VALUES(?, 'Basis')");
		$stmt_vv->bind_param("i", $templateID);
		$stmt = $conn->prepare("INSERT INTO dsgvo_vv_template_settings(templateID, opt_name, opt_descr) VALUES(?, ?, ?)");
		$stmt->bind_param("iss", $templateID, $opt, $descr);
		$result = $conn->query("SELECT id FROM companyData");

		$conn->query("INSERT INTO dsgvo_vv_templates(companyID, name, type) VALUES ($ins_id, 'Default', 'base')");
		$templateID = $conn->insert_id;
		$stmt_vv->execute();
		//BASE
		$descr = '';
		$opt = 'DESCRIPTION';
		$stmt->execute();
		$opt = 'GEN_TEXTAREA';
		$stmt->execute();
		$descr = 'Leiter der Datenverarbeitung (IT Leitung)';
		$opt = 'GEN_1';
		$stmt->execute();
		$descr = 'Inhaber, Vorstände, Geschäftsführer oder sonstige gesetzliche oder nach der Verfassung des Unternehmens berufene Leiter';
		$opt = 'GEN_2';
		$stmt->execute();
		$descr = 'Rechtsgrundlage(n) für die Verwendung von Daten';
		$opt = 'GEN_3';
		$stmt->execute();
		$i = 1;
		while($i < 24){
			$opt = 'MULT_OPT_'.$i;
			$descr = $base_opts[$i];
			$stmt->execute();
			$i++;
		}

		$conn->query("INSERT INTO dsgvo_vv_templates(companyID, name, type) VALUES ($ins_id, 'Default', 'app')");
		$templateID = $conn->insert_id;
		//APPS
		$descr = '';
		$opt = 'DESCRIPTION';
		$stmt->execute();
		$i = 1;
		while($i < 8){
			$opt = 'GEN_'.$i;
			$descr = $app_opt_1[$i];
			$stmt->execute();
			$i++;
		}
		$i = 1;
		while($i < 8){
			$opt = 'MULT_OPT_'.$i;
			$descr = $app_opt_2[$i];
			$stmt->execute();
			$i++;
		}
		$descr = 'Angaben zum Datenverarbeitungsregister (DVR)';
		$opt = 'EXTRA_DVR';
		$stmt->execute();
		$descr = 'Wurde eine Datenschutz-Folgeabschätzung durchgeführt?';
		$opt = 'EXTRA_FOLGE';
		$stmt->execute();
		$descr = 'Gibt es eine aktuelle Dokumentation dieser Applikation?';
		$opt = 'EXTRA_DOC';
		$stmt->execute();
		$descr = '';
		$opt = 'EXTRA_DAN';
		$stmt->execute();
		$descr = '';
		$opt = 'EXTRA_FOLGE_CHOICE';
		$stmt->execute();
		$descr = '';
		$opt = 'EXTRA_FOLGE_DATE';
		$stmt->execute();
		$descr = '';
		$opt = 'EXTRA_FOLGE_REASON';
		$stmt->execute();
		$descr = '';
		$opt = 'EXTRA_DOC_CHOICE';
		$stmt->execute();

		$opt = 'APP_MATR_DESCR';
		$stmt->execute();
		$opt = 'APP_GROUP_1';
		$descr = 'Kunde';
		$stmt->execute();
		$opt = 'APP_GROUP_2';
		$descr = 'Lieferanten und Partner';
		$stmt->execute();
		$opt = 'APP_GROUP_3';
		$descr = 'Mitarbeiter';
		$stmt->execute();
		$i = 1;
		$cat_descr = array('', 'Firmenname', 'Ansprechpartner, E-Mail, Telefon', 'Straße', 'Ort', 'Bankverbindung', 'Zahlungsdaten', 'UID', 'Firmenbuchnummer');
		while($i < 9){ //Kunde
			$opt = 'APP_CAT_1_'.$i;
			$descr = $cat_descr[$i];
			$stmt->execute();
			$i++;
		}
		$i = 1;
		while($i < 9){ //Lieferanten und Partner
			$opt = 'APP_CAT_2_'.$i;
			$descr = $cat_descr[$i];
			$stmt->execute();
			$i++;
		}
		$cat_descr = array('', 'Nachname', 'Vorname', 'PLZ', 'Ort', 'Telefon', 'Geb. Datum', 'Lohn und Gehaltsdaten', 'Religion', 'Gewerkschaftszugehörigkeit', 'Familienstand',
		'Anwesenheitsdaten', 'Bankverbindung', 'Sozialversicherungsnummer', 'Beschäftigt als', 'Staatsbürgerschaft', 'Geschlecht', 'Name, Geb. Datum und Sozialversicherungsnummer des Ehegatten',
		'Name, Geb. Datum und Sozialversicherungsnummer der Kinder', 'Personalausweis, Führerschein', 'Abwesenheitsdaten', 'Kennung');
		$i = 1;
		while($i < 22){ //Mitarbeiter
			$opt = 'APP_CAT_3_'.$i;
			$descr = $cat_descr[$i];
			$stmt->execute();
			$i++;
		}
		$descr = '';
		$i = 1;
		while($i < 21){ //20 App Spaces
			$opt = 'APP_HEAD_'.$i;
			$descr = $cat_descr[$i];
			$stmt->execute();
			$i++;
		}
		$stmt->close();
		$stmt_vv->close();
		$accept .= $conn->error;

		if($accept){ echo $accept; } else { redirect("company?cmp=$ins_id"); }
	} else {
		echo '<div class="alert alert-warning fade in">';
		echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
		echo '<strong>Cannot create new instance: </strong>Name or Type were not well defined.</div>';
	}
}
?>
<form method="POST">
	<div class="container-fluid">
		<div class="col-md-6">
			<input type="text" class="form-control" name="compaName" placeholder="Name...">
		</div>
		<div class="col-md-4">
			<select name="compaType" class="js-example-basic-single btn-block">
				<option selected value="0">Typ...</option>
				<option value="GmbH">GmbH</option>
				<option value="AG">AG</option>
				<option value="OG">OG</option>
				<option value="KG">KG</option>
				<option value="EU">EU</option>
				<option value="-">Sonstiges</option>
			</select>
		</div>
		<div class="col-md-2 text-right">
			<button type="submit" class="btn btn-warning " name="compaCreate">Hinzufügen</button>
		</div>
	</div>
</form>

<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
