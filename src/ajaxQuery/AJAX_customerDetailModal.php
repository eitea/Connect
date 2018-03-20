<?php
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";
require dirname(__DIR__) . "/utilities.php";

if(!$_SERVER['REQUEST_METHOD'] == 'POST'){
    die("0");
}

$x = preg_replace("/[^A-Za-z0-9]/", '', $_POST['custid']);
session_start();
$userID = $_SESSION["userid"] or die("0");
//enableToClients($userID);

// get corresponding id from detailTable
$result = $conn->query("SELECT * FROM $clientDetailTable WHERE clientId = $x");
if ($result && ($row = $result->fetch_assoc())) {
    $detailID = $row['id'];
} else { // no detailTable found -> create one
    $conn->query("INSERT INTO $clientDetailTable (clientID) VALUES($x)");
    $detailID = $conn->insert_id;
    echo mysqli_error($conn);
}

$activeTab = 'home';

$result = $conn->query("SELECT name, clientNumber, companyID FROM clientData WHERE id = $x");
$rowClient = $result->fetch_assoc();

$result = $conn->query("SELECT * FROM clientInfoData WHERE id = $detailID");
$row = $result->fetch_assoc();

$result = $conn->query("SELECT DISTINCT companyID FROM relationship_company_client WHERE userID = $userID OR $userID = 1");
$available_companies = array('-1'); //care
while ($result && ($rowc = $result->fetch_assoc())) {
    $available_companies[] = $rowc['companyID'];
}
$result = $conn->query("SELECT DISTINCT userID FROM relationship_company_client WHERE companyID IN(" . implode(', ', $available_companies) . ") OR $userID = 1");
$available_users = array('-1');
while ($result && ($rowu = $result->fetch_assoc())) {
    $available_users[] = $rowu['userID'];
}

$resultNotes = $conn->query("SELECT * FROM clientInfoNotes WHERE parentID = $detailID"); echo $conn->error;
$resultBank = $conn->query("SELECT * FROM clientInfoBank WHERE parentID = $detailID"); echo $conn->error;
$resultContacts = $conn->query("SELECT contactPersons.*, position.name AS positionName FROM contactPersons LEFT JOIN position ON position.id = position WHERE clientID = $x"); echo $conn->error;

?>

<div class="modal fade" id="editingModal-<?php echo $x; ?>">
    <div class="modal-dialog modal-lg" role="form">
        <div class="modal-content">
            <form method="POST">
                <input name="clientID" value="<?php echo $x; ?>" style="visibility:hidden; height: 1px; width: 1px;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Kunde editieren</h4>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs">
                        <li><a data-toggle="tab" href="#project<?php echo $x; ?>" ><?php echo $lang['VIEW_PROJECTS']; ?></a></li>
                        <li class="active"><a data-toggle="tab" href="#home<?php echo $x; ?>" ><?php echo $lang['RECORD']; ?></a></li>
                        <li><a data-toggle="tab" href="#menuTaxes<?php echo $x; ?>" ><?php echo $lang['TAXES']; ?></a></li>
                        <li><a data-toggle="tab" href="#menuBank<?php echo $x; ?>" >Banking</a></li>
                        <li><a data-toggle="tab" href="#menuBilling<?php echo $x; ?>" ><?php echo $lang['BILLING']; ?></a></li>
                        <li><a data-toggle="tab" href="#menuPayment<?php echo $x; ?>" ><?php echo $lang['PAYMENT']; ?></a></li>
                        <li><a data-toggle="tab" href="#menuContact<?php echo $x; ?>" ><?php echo $lang['NOTES']; ?></a></li>
                        <li><a data-toggle="tab" href="#menuDocs<?php echo $x; ?>" ><?php echo $lang['DOCUMENTS']; ?></a></li>
                    </ul>
                    <div class="tab-content">
                        <div id="project<?php echo $x; ?>" class="tab-pane fade <?php if ($activeTab == 'project') {
                            echo 'in active';
                        } ?>"> <h3><?php echo $lang['VIEW_PROJECTS']; ?>
                                <div class="page-header-button-group">
                                    <button type="submit" class="btn btn-default" name='delete_projects' title="<?php echo $lang['DELETE']; ?>" ><i class="fa fa-trash-o"></i></button>
                                    <button type="button" class="btn btn-default" title="<?php echo $lang['ADD']; ?>" data-toggle="modal" data-target=".add-project"><i class="fa fa-plus"></i></button>
                                </div></h3>
                            <hr>
                            <table class="table table-hover">
                                <thead>
                                <th><?php echo $lang['DELETE']; ?></th>
                                <th></th>
                                <th>Name</th>
                                <th><?php echo $lang['ADDITIONAL_FIELDS']; ?></th>
                                <th><?php echo $lang['HOURS']; ?></th>
                                <th><?php echo $lang['HOURLY_RATE']; ?></th>
                                <th></th>
                                </thead>
                                <tbody>
                                    <?php
                                    $result_p = $conn->query("SELECT * FROM $projectTable WHERE clientID = $x");
                                    while ($row_p = $result_p->fetch_assoc()) {
                                        $productive = $row_p['status'] ? '<i class="fa fa-tags"></i>' : '';
                                        echo '<tr>';
                                        echo '<td><input type="checkbox" name="delete_projects_index[]" value=' . $row_p['id'] . ' /></td>';
                                        echo '<td>' . $productive . '</td>';
                                        echo '<td>' . $row_p['name'] . '</td>';
                                        echo '<td>';
                                        $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = " . $rowClient['companyID'] . " ORDER BY id ASC");
                                        if ($resF->num_rows > 0) {
                                            $rowF = $resF->fetch_assoc();
                                            if ($rowF['isActive'] == 'TRUE' && $row_p['field_1'] == 'TRUE') {
                                                echo $rowF['name'];
                                            }
                                        }
                                        if ($resF->num_rows > 1) {
                                            $rowF = $resF->fetch_assoc();
                                            if ($rowF['isActive'] == 'TRUE' && $row_p['field_2'] == 'TRUE') {
                                                echo $rowF['name'];
                                            }
                                        }
                                        if ($resF->num_rows > 2) {
                                            $rowF = $resF->fetch_assoc();
                                            if ($rowF['isActive'] == 'TRUE' && $row_p['field_3'] == 'TRUE') {
                                                echo $rowF['name'];
                                            }
                                        }
                                        echo '</td>';
                                        echo '<td>' . $row_p['hours'] . '</td>';
                                        echo '<td>' . $row_p['hourlyPrice'] . '</td>';
                                        echo '<td><button type="button" class="btn btn-default" data-toggle="modal" data-target=".editingProjectsModal-' . $row_p['id'] . '"><i class="fa fa-pencil"></i></td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="home<?php echo $x; ?>" class="tab-pane fade <?php if ($activeTab == 'home') {
                            echo 'in active';
                        } ?>">
                            <h3><?php echo $lang['GENERAL_INFORMATION']; ?></h3>
                            <hr>
                            <div class="row">
                                <div class="col-md-4 col-md-offset-1">
                                    <label>Kundenname</label><input type="text" class="form-control" name="edit_name" placeholder="Kundenname" value="<?php echo $rowClient['name']; ?>" />
                                </div>
                                <div class="col-md-3">
                                    <label>Kundennummer</label><input type="text" class="form-control" maxlength="12" placeholder="z.B. #KD-123" value="<?php echo $rowClient['clientNumber']; ?>" name="edit_clientNumber" />
                                </div>
                                <div class="col-md-4">
                                    <label><?php echo $lang['COMPANY']; ?></label>
                                    <select class="js-example-basic-single" name="edit_company">
                                        <?php
                                        $res_cmp = $conn->query("SELECT id, name FROM companyData WHERE id IN (" . implode(', ', $available_companies) . ")");
                                        while ($row_cmp = $res_cmp->fetch_assoc()) {
                                            $selected = ($rowClient['companyID'] == $row_cmp['id']) ? 'selected' : '';
                                            echo '<option ' . $selected . ' value="' . $row_cmp['id'] . '">' . $row_cmp['name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <hr>
                            <div class="row checkbox">
                                <div class="col-xs-2 text-right"><?php echo $lang['ADDRESS_FORM']; ?></div>
                                <div class="col-sm-2">
                                    <label><input type="radio" value="male" name="gender" <?php if ($row['gender'] == 'male') {
                                            echo 'checked';
                                        } ?> /> <?php echo $lang['GENDER_TOSTRING']['male']; ?></label>
                                </div>
                                <div class="col-sm-2">
                                    <label><input type="radio" value="female" name="gender" <?php if ($row['gender'] == 'female') {
                                            echo 'checked';
                                        } ?> /> <?php echo $lang['GENDER_TOSTRING']['female']; ?></label>
                                </div>
                                <div class="col-sm-3">
                                    <label><input type="checkbox" value="company" name="contactType" <?php if ($row['contactType'] == 'company') {
                                            echo 'checked';
                                        } ?> /> <?php echo $lang['COMPANY_2']; ?></label>
                                </div>
                            </div>
                            <br>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">Name</div>
                                <div class="col-sm-2">
                                    <input type="text" class="form-control" name="title" value="<?php echo $row['title']; ?>" placeholder="Title"/>
                                </div>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control" name="firstname" value="<?php echo $row['firstname']; ?>" placeholder="<?php echo $lang['FIRSTNAME']; ?>" />
                                </div>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control" name="name" value="<?php echo $row['name']; ?>" placeholder="<?php echo $lang['LASTNAME']; ?>" />
                                </div>
                                <div class="col-sm-2">
                                    <input type="text" class="form-control" name="nameAddition" value="<?php echo $row['nameAddition']; ?>" placeholder="Addition <?php if (!isset($_SESSION['language']) || $_SESSION['language'] == 'GER') echo "/Zusatz"; ?>"/>
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">Anschrift</div>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="address_Street" value="<?php echo $row['address_Street']; ?>" placeholder="<?php echo $lang['STREET']; ?>"/>
                                </div>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="address_Country" value="<?php echo $row['address_Country']; ?>" placeholder="<?php echo $lang['COUNTRY']; ?>"/>
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control" name="address_Country_City" value="<?php echo $row['address_Country_City']; ?>" placeholder="<?php echo $lang['CITY']; ?>" />
                                </div>
                                <div class="col-sm-2">
                                    <input type="text" class="form-control" name="address_Country_Postal" value="<?php echo $row['address_Country_Postal']; ?>" placeholder="<?php echo $lang['PLZ']; ?>" />
                                </div>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="address_Addition" value="<?php echo $row['address_Addition']; ?>" placeholder="<?php echo $lang['ADDITION']; ?>"/>
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">Kontakt</div>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="phone" value="<?php echo $row['phone']; ?>" placeholder="<?php echo $lang['PHONE_NUMBER']; ?>" />
                                </div>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="fax_number" value="<?php echo $row['fax_number']; ?>"  placeholder="Fax" />
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">Webseite</div>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="homepage" value="<?php echo $row['homepage']; ?>" placeholder="Homepage" />
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">E-Mail</div>
                                <div class="col-sm-5">
                                    <input type="email" class="form-control" name="mail" value="<?php echo $row['mail']; ?>" placeholder="E-Mail" />
                                </div>
                            </div>
                            <br><br>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">Ansprechpartner</div>
                                <div class="col-lg-12">
                                    <br>
                                    <table class="table">
                                        <thead><tr>
                                                <th><?php echo $lang['FORM_OF_ADDRESS'] ?></th>
                                                <th>Titel</th>
                                                <th>Name</th>
                                                <th>E-Mail</th>
                                                <th>Position</th>
                                                <th>Verantwortung</th>
                                                <th>Durchwahl</th>
                                                <th>Faxdurchwahl</th>
                                                <th>Mobiltelefon</th>
                                                <th></th>
                                            </tr></thead>
                                        <tbody>
                                            <?php
                                            $editmodals = '';
                                            while ($resultContacts && ($contactRow = $resultContacts->fetch_assoc())) {
                                                echo '<tr>';
                                                echo '<td>' . $lang['GENDER_TOSTRING'][$contactRow['gender']] . '</td>';
                                                echo '<td>' . $contactRow['title'] . '</td>';
                                                echo '<td>' . $contactRow['firstname'] . ' ' . $contactRow['lastname'] . '</td>';
                                                echo '<td>' . $contactRow['email'] . '</td>';
                                                echo '<td>' . $contactRow['positionName'] . '</td>';
                                                echo '<td>' . $contactRow['responsibility'] . '</td>';
                                                echo '<td>' . $contactRow['dial'] . '</td>';
                                                echo '<td>' . $contactRow['faxDial'] . '</td>';
                                                echo '<td>' . $contactRow['phone'] . '</td>';
                                                echo '<td><button type="submit" name="deleteContact" value="' . $contactRow['id'] . '" class="btn btn-default"><i class="fa fa-trash-o"></i></button>';
                                                echo '<button type="button" class="btn btn-default" data-toggle="modal" data-target="#edit-contact-' . $contactRow['id'] . '" class="btn btn-default"><i class="fa fa-pencil"></i></button></td>';
                                                echo '</tr>';
                                                $currentmodal = '<form method="POST"><div id="edit-contact-' . $contactRow['id'] . '" class="modal fade">
						<div class="modal-dialog modal-content modal-md">
							<div class="modal-header h4">Ansprechpartner Editieren</div>
							<div class="modal-body">
								<div class="row form-group">
									<div class="col-md-6"><label>Vorname</label><input type="text" name="edit_contacts_firstname" value="' . $contactRow['firstname'] . '" class="form-control required-field"/></div>
									<div class="col-md-6"><label>Nachname</label><input type="text" name="edit_contacts_lastname" value="' . $contactRow['lastname'] . '" class="form-control required-field"/></div>
								</div>
								<div class="row form-group">
                                    <div class="col-md-6"><label>' . $lang['FORM_OF_ADDRESS'] . '</label><select name="edit_contacts_gender" class="form-control select2 required-field">
                                        <option value="male" >Herr</option>
                                        <option value="female" >Frau</option>
                                    </select></div>
									<div class="col-md-6"><label>Titel</label><input type="text" name="edit_contacts_title" value="' . $contactRow['title'] . '" class="form-control "/></div>
								</div>
								<div class="row form-group">
									<div class="col-md-4"><label>E-Mail</label><input type="email" name="edit_contacts_email" value="' . $contactRow['email'] . '" class="form-control required-field"/></div>
									<div class="col-md-4"><label>Position</label><select type="text" name="edit_contacts_position" placeholder="Position" class="form-control select2-position">';
                                                $currentmodal .= '<option value="-1" >+ Neu...</option>';
                                                $result = $conn->query("SELECT * FROM position ORDER BY name");
                                                while ($row = $result->fetch_assoc()) {
                                                    $currentmodal .= '<option value="' . $row['id'] . '" >' . $row['name'] . '</option>';
                                                }
                                                $currentmodal = str_replace('<option value="' . $contactRow['position'] . '" >', '<option selected value="' . $contactRow['position'] . '" >', $currentmodal);
                                                $currentmodal .= '</select></div>
									<div class="col-md-4"><label>Verantwortung</label><input type="text" name="edit_contacts_responsibility" value="' . $contactRow['responsibility'] . '" class="form-control"/></div>
								</div>
								<div class="row form-group">
									<div class="col-md-4"><label>Durchwahl</label><input type="text" name="edit_contacts_dial" value="' . $contactRow['dial'] . '" class="form-control"/></div>
									<div class="col-md-4"><label>Faxdurchwahl</label><input type="text" name="edit_contacts_faxDial" value="' . $contactRow['faxDial'] . '" class="form-control"/></div>
									<div class="col-md-4"><label>Mobiltelefon</label><input type="text" name="edit_contacts_phone" value="' . $contactRow['phone'] . '" class="form-control"/></div>
								</div>
                                <div class="row form-group">
                                    <div class="col-md-12"><label>PGP-Key</label><textarea class="form-control" name="edit_contacts_pgp" placeholder="Put PGP Key here..." >' . $contactRow['pgpKey'] . '</textarea></div>
							</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
								<button type="submit" name="editContact" value="' . $contactRow['id'] . '" class="btn btn-warning">' . $lang['SAVE'] . '</button>
							</div>
						</div>
					</div></form>';
                                                $editmodals .= str_replace('<option value="' . $contactRow['gender'] . '" />', '<option selected value="' . $contactRow['gender'] . '" />', $currentmodal);
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#add-contact-person<?php echo $x; ?>" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </div>
                        <div id="menuTaxes<?php echo $x; ?>" class="tab-pane fade <?php if ($activeTab == 'taxes') {
                            echo 'in active';
                        } ?>">
                            <div class="row checkbox">
                                <div class="col-sm-9"> <h3>Steuerinformationen</h3> </div>
                                <br>
                                <div class="col-sm-3">
                                    <input type="checkbox" name="blockDelivery" <?php if ($row['blockDelivery'] == 'true') {
                            echo 'checked';
                        } ?> /> Liefersperre
                                </div>
                            </div>
                            <hr>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right"><?php if (isset($_GET['supID'])) {
                            echo "Credit Nr.";
                        } else {
                            echo "Debit Nr.";
                        } ?></div>
                                <div class="col-sm-4"><input type="number" class="form-control" name="debitNumber" value="<?php echo $row['debitNumber']; ?>" /></div>
                                <div class="col-xs-2 text-center">
                                    DATEV
                                </div>
                                <div class="col-sm-4"><input type="number" class="form-control" name="datev" value="<?php echo $row['datev']; ?>" /></div>
                            </div>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Kontobezeichnung
                                </div>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="accountName" value="<?php echo $row['accountName']; ?>" />
                                </div>
                            </div>
                            <hr>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Steuernummer
                                </div>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" name="taxnumber" value="<?php echo $row['taxnumber']; ?>" />
                                </div>
                                <div class="col-xs-2 text-center">
                                    UID
                                </div>
                                <div class="col-sm-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="vatnumber" value="<?php echo $row['vatnumber']; ?>" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button">Überprüfen</button>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-1">
                                    <span id="uidValidate" ></span>
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Steuergebiet
                                </div>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" name="taxArea" value="<?php echo $row['taxArea']; ?>" />
                                </div>
                                <div class="col-xs-2 text-center">
                                    Kundengruppe
                                </div>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" name="customerGroup" value="<?php echo $row['customerGroup']; ?>" />
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Vertreter
                                </div>
                                <div class="col-sm-4">
                                    <select class="js-example-basic-single" name="representative">
                                        <option value="0">...</option>
                                        <?php
                                        $result_con = $conn->query("SELECT * FROM representatives");
                                        while ($row_con = $result_con->fetch_assoc()) {
                                            $selected = ($row['representative'] == $row_con['name']) ? 'selected' : '';
                                            echo '<option ' . $selected . ' value="' . $row_con['name'] . '">' . $row_con['name'] . '</option>';
                                        }
                                        $result_con = $conn->query("SELECT firstname, lastname FROM UserData WHERE id IN (" . implode(', ', $available_users) . ")");
                                        while ($row_con = $result_con->fetch_assoc()) {
                                            $selected = ($row['representative'] == $row_con['firstname'] . ' ' . $row_con['lastname']) ? 'selected' : '';
                                            echo '<option ' . $selected . ' value="' . $row_con['firstname'] . ' ' . $row_con['lastname'] . '">' . $row_con['firstname'] . ' ' . $row_con['lastname'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div id="menuBank<?php echo $x; ?>" class="tab-pane fade <?php if ($activeTab == 'banking') {
                            echo 'in active';
                        } ?>">
                            <div class="row form-group">
                                <h3><div class="col-sm-9">Bankdaten</div></h3>
                            </div>
                            <hr>
                            <table class="table table-hover">
                                <thead>
                                <th>Bankname</th>
                                <th>Iban</th>
                                <th>BIC</th>
                                <th></th>
                                </thead>
                                <tbody>
                                    <?php
                                    $modals = '';
                                    while ($resultBank && ($rowBank = $resultBank->fetch_assoc())) {
                                        echo '<tr>';
                                        echo '<td>' . $rowBank['bankName'] . '</td>';
                                        echo '<td>' . $rowBank['iban'] . '</td>';
                                        echo '<td>' . $rowBank['bic'] . '</td>';
                                        echo '<td><button type="submit" class="btn btn-default" name="removeBank" value="' . $rowBank['id'] . '" title="' . $lang['DELETE'] . '" ><i class="fa fa-trash-o"></i></button>
                    <button type="button" class="btn btn-default" data-toggle="modal" data-target=".edit-bank-' . $rowBank['id'] . '" title="' . $lang['EDIT'] . '" ><i class="fa fa-pencil"></i></button></td>';
                                        echo '</tr>';

                                        $modals .= '<div class="modal fade edit-bank-' . $rowBank['id'] . '"><div class="modal-dialog modal-content modal-sm">
                    <div class="modal-header h4">' . $lang['EDIT'] . '</div><div class="modal-body"><div class="container-fluid">
                    <label><label>Bankname ' . mc_status() . '</label></label><input type="text" class="form-control" name="edit_bankName" value="' . $rowBank['bankName'] . '" /><br>
                    <label><label>IBAN ' . mc_status() . '</label></label><input type="text" class="form-control" name="edit_iban" value="' . $rowBank['iban'] . '" /><br>
                    <label><label>BIC ' . mc_status() . '</label></label><input type="text" class="form-control" name="edit_bic" value="' . $rowBank['bic'] . '" /><br>
                    </div></div><div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" name="editBankingDetail" value="' . $rowBank['id'] . '" >' . $lang['SAVE'] . '</button>
                    </div></div></div>';
                                    }
                                    ?>
                                </tbody>
                            </table>
<?php echo $modals; ?>
                            <br><br><br>
                            <div class="col-lg-12">
                                <div class="col-md-3">
                                    <label>Bankname <?php echo mc_status(); ?></label>
                                    <input type="text" class="form-control" name="bankName" placeholder="Name der Bank" />
                                </div>
                                <div class="col-md-4">
                                    <label>IBAN <?php echo mc_status(); ?></label>
                                    <input type="text" class="form-control" name="iban" placeholder="Iban" />
                                </div>
                                <div class="col-md-3">
                                    <label>BIC <?php echo mc_status(); ?></label>
                                    <input type="text" class="form-control" name="bic" placeholder="BIC" />
                                </div>
                                <div class="col-md-2">
                                    <label style="color:transparent">+</label>
                                    <button type="submit" class="btn btn-warning btn-block" name="addBankingDetail"><?php echo $lang['ADD']; ?></button>
                                </div>
                            </div>
                        </div>
                        <div id="menuBilling<?php echo $x; ?>" class="tab-pane fade <?php if ($activeTab == 'billing') {
                            echo 'in active';
                        } ?>">
                            <div class="row checkbox">
                                <div class="col-sm-9">
                                    <h3>Rechnungsdaten</h3>
                                </div>
                                <br>
                                <div class="col-sm-3">
                                    <input type="checkbox" name="eBill" <?php if ($row['eBill'] == 'true') {
                            echo 'checked';
                        } ?> />
                                    E-Rechnung
                                </div>
                            </div>
                            <hr>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Kreditlimit
                                </div>
                                <div class="col-sm-3">
                                    <input type="number" step="any" class="form-control" name="creditLimit" value="<?php echo $row['creditLimit']; ?>" />
                                </div>
                                <div class="col-xs-3 text-center">
                                    Letzte Faktura Buchung
                                </div>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control datetimepicker" name="lastFaktura" value="<?php echo $row['lastFaktura']; ?>" />
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Versandart
                                </div>
                                <div class="col-sm-3">
                                    <select class="js-example-basic-single" name="shipmentType">
                                        <option value="0">...</option>
                                        <?php
                                        $result_con = $conn->query("SELECT * FROM shippingMethods");
                                        while ($row_con = $result_con->fetch_assoc()) {
                                            $selected = '';
                                            if ($row['shipmentType'] == $row_con['name']) {
                                                $selected = 'selected';
                                            }
                                            echo '<option ' . $selected . ' value="' . $row_con['name'] . '">' . $row_con['name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <br><hr><br>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Rechnungsadresse (E-Mail)
                                </div>
                                <div class="col-sm-3">
                                    <input type="email" name="billingMailAddress" class="form-control" value="<?php echo $row['billingMailAddress']; ?>" />
                                </div>
                                <div class="col-xs-2 text-center">
                                    Rechnungsversand
                                </div>
                                <div class="col-sm-3">
                                    <select name="billDelivery" class="js-example-basic-single">
                                        <option <?php if ($row['billDelivery'] == 'Fax') {
                                            echo 'selected';
                                        } ?> value="Fax">Fax</option>
                                        <option <?php if ($row['billDelivery'] == 'Post') {
                                            echo 'selected';
                                        } ?> value="Post">Post</option>
                                        <option <?php if ($row['billDelivery'] == 'E-Mail') {
                                            echo 'selected';
                                        } ?> value="E-Mail">E-Mail</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div id="menuPayment<?php echo $x; ?>" class="tab-pane fade <?php if ($activeTab == 'payment') {
                            echo 'in active';
                        } ?>">
                            <h3>Zahlungsdaten</h3>
                            <hr>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Zahlungsweise
                                </div>
                                <div class="col-sm-5">
                                    <select class="js-example-basic-single" name="paymentMethod">
                                        <option value="0">...</option>
                                        <?php
                                        $result_con = $conn->query("SELECT * FROM paymentMethods");
                                        while ($row_con = $result_con->fetch_assoc()) {
                                            $selected = '';
                                            if ($row['paymentMethod'] == $row_con['name']) {
                                                $selected = 'selected';
                                            }
                                            echo '<option ' . $selected . ' value="' . $row_con['name'] . '">' . $row_con['name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-sm-3"><a href="../erp/payment" class="btn btn-warning">Zahlungsarten verwalten</a></div>
                            </div>
                            <hr>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Mahnungen erlaubt
                                </div>
                                <div class="col-sm-9">
                                    <input type="checkbox" name="warningEnabled" <?php if ($row['warningEnabled'] == 'true') {
                                            echo 'checked';
                                        } ?> />
                                </div>
                            </div>
                            <br>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Karenztage
                                </div>
                                <div class="col-sm-3">
                                    <input type="number" class="form-control" name="karenztage" value="<?php echo $row['karenztage']; ?>" />
                                </div>
                                <div class="col-xs-2 text-center">
                                    Letzte Mahnung:
                                </div>
                                <div class="col-sm-3">
<?php echo $row['lastWarning'] ? $row['lastWarning'] : '-- --'; ?>
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Mahnung 1
                                </div>
                                <div class="col-sm-3">
                                    <input type="number" step="0.01" class="form-control" name="warning1" placeholder="Mahn-Betrag in EUR" value="<?php echo $row['warning1']; ?>" />
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Mahnung 2
                                </div>
                                <div class="col-sm-3">
                                    <input type="number" step="0.01" class="form-control" name="warning2" placeholder="Mahn-Betrag in EUR" value="<?php echo $row['warning2']; ?>" />
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Mahnung 3
                                </div>
                                <div class="col-sm-3">
                                    <input type="number" step="0.01" class="form-control" name="warning3" placeholder="Mahn-Betrag in EUR" value="<?php echo $row['warning3']; ?>" />
                                </div>
                            </div>
                            <hr>
                            <div class="row form-group">
                                <div class="col-xs-2 text-right">
                                    Verzugszinsberechnung
                                </div>
                                <div class="col-md-1">
                                    <input type="checkbox" name="calculateInterest" <?php if ($row['calculateInterest'] == 'true') {
    echo 'checked';
} ?>/>
                                </div>
                            </div>
                        </div>
                        <div id="menuContact<?php echo $x; ?>" class="tab-pane fade <?php if ($activeTab == 'notes') {
                            echo 'in active';
                        } ?>">
                            <h3>Bemerkungen</h3>
                            <hr>
                            <table class="table table-hover">
                                <thead>
                                <th>Löschen</th>
                                <th>Datum</th>
                                <th style="width:75%">Info</th>
                                </thead>
                                <tbody>
                                    <?php
                                    while ($resultNotes && ($rowNotes = $resultNotes->fetch_assoc())) {
                                        echo "<tr><td><input type='checkbox' name='noteIndeces[]' /></td>";
                                        echo "<td>" . $rowNotes['createDate'] . "</td>";
                                        echo "<td>" . $rowNotes['infoText'] . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <div class="container-fluid">
                                <br><br> Neue Notiz Hinzufügen: <br><br>
                                <textarea class="form-control" rows="3" name="infoText" placeholder="Info..."></textarea>
                            </div>
                            <br>
                            <div class="container-fluid text-right">
                                <button type="submit" class="btn btn-warning" name="addNotes">Hinzufügen</button> <button type="submit" class="btn btn-danger" name="deleteNotes">Löschen</button>
                            </div>
                        </div>
                        <div id="menuDocs<?php echo $x; ?>" class="tab-pane fade <?php if ($activeTab == 'docs') {
                            echo 'in active';
                        } ?>">
                            <h3>Vertrauliche Dokumente</h3>
                            <hr>
                            <table class="table table-hover">
                                <thead><tr>
                                        <th>Ansprechpartner</th>
                                        <th>Dokument</th>
                                        <th>Passwort</th>
                                        <th></th>
                                        <th></th>
                                    </tr></thead>
                                <tbody>
                                    <?php
                                    $resultProc = $conn->query("SELECT name, password, firstname, lastname, documentProcess.id FROM documents, documentProcess, contactPersons
                WHERE clientID = $x AND personID = contactPersons.id AND documentProcess.docID = documents.id");
                                    echo $conn->error;
                                    while ($rowProc = $resultProc->fetch_assoc()) {
                                        echo '<tr style="background-color:#dedede">';
                                        echo '<td>' . $rowProc['firstname'] . ' ' . $rowProc['lastname'] . '</td>';
                                        echo '<td>' . $rowProc['name'] . '</td>';
                                        echo $rowProc['password'] ? '<td>Ja</td>' : '<td>Nein</td>';
                                        echo '<td></td>';
                                        echo '<td></td>';
                                        echo '</tr>';

                                        $processHistory = array();
                                        $result = $conn->query("SELECT activity, info, userAgent, logDate FROM documentProcessHistory WHERE processID = '" . $rowProc['id'] . "'");
                                        while ($row = $result->fetch_assoc()) {
                                            if (isset($processHistory[$row['activity']])) {
                                                $processHistory[$row['activity']]['count'] += 1;
                                            } else {
                                                $processHistory[$row['activity']]['info'] = $row['info'];
                                                $processHistory[$row['activity']]['userAgent'] = $row['userAgent'];
                                                $processHistory[$row['activity']]['logDate'] = $row['logDate'];
                                                $processHistory[$row['activity']]['count'] = 1;
                                            }
                                        }
                                        if (isset($processHistory['ENABLE_READ'])) {
                                            $val_stat = 'Ausstehend';
                                            $val_date = $val_head = '';
                                            if (isset($processHistory['action_read'])) {
                                                $val_stat = 'Ja';
                                                $val_date = $processHistory['ENABLE_READ']['logDate'];
                                                $val_head = $processHistory['ENABLE_READ']['userAgent'];
                                            }
                                            echo '<tr><td></td> <td>Als Gelesen markiert</td> <td>' . $val_stat . '</td> <td>' . $val_date . '</td> <td>' . $val_head . '</td></tr>';
                                        }
                                        if (isset($processHistory['ENABLE_SIGN'])) {
                                            $val_stat = 'Ausstehend';
                                            $val_date = $val_head = '';
                                            if (isset($processHistory['action_sign'])) {
                                                $val_stat = $processHistory['action_sign']['info'];
                                                $val_date = $processHistory['action_sign']['logDate'];
                                                $val_head = $processHistory['action_sign']['userAgent'];
                                            }
                                            echo '<tr><td></td> <td>Unterschrieben</td> <td>' . $val_stat . '</td> <td>' . $val_date . '</td> <td>' . $val_head . '</td></tr>';
                                        }
                                        if (isset($processHistory['ENABLE_ACCEPT'])) {
                                            $val_stat = 'Ausstehend';
                                            $val_date = $val_head = '';
                                            if (isset($processHistory['action_accept'])) {
                                                $val_stat = $processHistory['action_accept'] == 'DECLINED' ? 'Nein' : 'Ja';
                                                $val_date = $processHistory['action_accept']['logDate'];
                                                $val_head = $processHistory['action_accept']['userAgent'];
                                            }
                                            echo '<tr><td></td> <td>Akzeptiert</td> <td>' . $val_stat . '</td> <td>' . $val_date . '</td> <td>' . $val_head . '</td></tr>';
                                        }
                                        if ($rowProc['password'] && isset($processHistory['password_denied'])) {
                                            $val_stat = $processHistory['password_denied']['count'];
                                            $val_date = $processHistory['password_denied']['logDate'];
                                            $val_head = $processHistory['password_denied']['userAgent'];

                                            echo '<tr><td></td> <td>Passwort Falscheingaben</td> <td>' . intval($val_stat) . 'x Mal</td> <td>' . $val_date . '</td> <td>' . $val_head . '</td></tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div><!-- /tab-content -->
                </div><!-- /modal-body -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                    <button type="submit" class="btn btn-warning" name="editCustomer" value="<?php echo $x; ?>" ><?php echo $lang['SAVE']; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php echo $editmodals ?>
<form method="POST">
<!-- ADD CONTACT PERSON -->
<div id="add-contact-person<?php echo $x; ?>" class="modal fade">
    <div class="modal-dialog modal-content modal-md">
        <div class="modal-header h4">Ansprechpartner Hinzufügen</div>
        <div class="modal-body">
            <div class="row form-group">
                <div class="col-md-6"><label>Vorname</label><input type="text" name="contacts_firstname" placeholder="Vorname" class="form-control required-field"/></div>
                <div class="col-md-6"><label>Nachname</label><input type="text" name="contacts_lastname" placeholder="Nachname" class="form-control required-field"/></div>
            </div>
            <div class="row form-group">
                <div class="col-md-6"><label><?php echo $lang['FORM_OF_ADDRESS'] ?></label><select name="contacts_gender" class="form-control required-field">
                    <option value="male" >Herr</option>
                    <option value="female" >Frau</option>
                </select></div>
                <div class="col-md-6"><label>Titel</label><input type="text" name="contacts_title" class="form-control "/></div>
            </div>
            <div class="row form-group">
                <div class="col-md-4"><label>E-Mail</label><input type="email" name="contacts_email" placeholder="E-Mail" class="form-control required-field"/></div>
                <div class="col-md-4"><label>Position</label><select type="text" name="contacts_position" placeholder="Position" class="js-example-basic-single select2-position">
                    <?php
                    $result = $conn->query("SELECT * FROM position ORDER BY name");
                    echo '<option value="-1" >+ Neu...</option>';
                    $row = $result->fetch_assoc();
                    echo '<option selected value="'.$row['id'].'" >'.$row['name'].'</option>';
                    while($row = $result->fetch_assoc()){
                        echo '<option value="'.$row['id'].'" >'.$row['name'].'</option>';
                    }
                    ?>
                </select></div>
                <div class="col-md-4"><label>Verantwortung</label><input type="text" name="contacts_responsibility" placeholder="Verantwortung" class="form-control"/></div>
            </div>
            <div class="row form-group">
                <div class="col-md-4"><label>Durchwahl</label><input type="text" name="contacts_dial" placeholder="Direct Dial" class="form-control"/></div>
                <div class="col-md-4"><label>Faxdurchwahl</label><input type="text" name="contacts_faxDial" placeholder="Direct Fax Dial" class="form-control"/></div>
                <div class="col-md-4"><label>Mobiltelefon</label><input type="text" name="contacts_phone" placeholder="Mobile Phone" class="form-control"/></div>
            </div>
            <div class="row form-group">
                <div class="col-md-12"><label>PGP-Key</label><textarea class="form-control" name="contacts_pgp" placeholder="Put PGP Key here..." ></textarea></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="submit" name="addContact" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
        </div>
    </div>
</div>
</form>
<form method="POST">
    <input name="clientID" value="<?php echo $x; ?>" style="visibility:hidden; height: 1px; width: 1px;">
<!-- ADD PROJECT -->
<div class="modal fade add-project">
    <div class="modal-dialog modal-content modal-md">
        <div class="modal-header"><h4><?php echo $lang['ADD']; ?></h4></div>
        <div class="modal-body">
            <label>Name</label>
            <input type=text class="form-control required-field" name='name' placeholder='Name'>
            <br>
            <div class="row">
                <div class="col-md-6">
                    <label><?php echo $lang['HOURS']; ?></label>
                    <input type="number" class="form-control" name='hours' step="any">
                </div>
                <div class="col-md-6">
                    <label><?php echo $lang['HOURLY_RATE']; ?></label>
                    <input type="number" class="form-control" name='hourlyPrice' step="any">
                </div>
            </div>
            <br>
            <div class="container-fluid">
                <div class="col-md-6 checkbox">
                    <input type="checkbox" name="status" value="checked" checked> <i class="fa fa-tags"></i> <?php echo $lang['PRODUCTIVE']; ?>
                </div>
                <div class="col-md-6">
                    <div class="checkbox">
                        <?php
                        $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = " . $rowClient['companyID'] . " ORDER BY id ASC");
                        if ($resF->num_rows > 0) {
                            $rowF = $resF->fetch_assoc();
                            if ($rowF['isActive'] == 'TRUE') {
                                $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked' : '';
                                echo '<input type="checkbox" ' . $checked . ' name="createField_1"/>' . $rowF['name'];
                            }
                        }
                        if ($resF->num_rows > 1) {
                            $rowF = $resF->fetch_assoc();
                            if ($rowF['isActive'] == 'TRUE') {
                                $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked' : '';
                                echo '<br><input type="checkbox" ' . $checked . ' name="createField_2" />' . $rowF['name'];
                            }
                        }
                        if ($resF->num_rows > 2) {
                            $rowF = $resF->fetch_assoc();
                            if ($rowF['isActive'] == 'TRUE') {
                                $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked' : '';
                                echo '<br><input type="checkbox" ' . $checked . ' name="createField_3" />' . $rowF['name'];
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning" name='add'> <?php echo $lang['ADD']; ?> </button>
        </div>
    </div>
</div>
</form>

<?php
mysqli_data_seek($result_p, 0);
while ($row = $result_p->fetch_assoc()):
    $x = $row['id'];
    ?>
	  <!-- Edit projects (time only) -->
	  <form method="post">
              <input name="clientID" value="<?php echo $x; ?>" style="visibility:hidden; height: 1px; width: 1px;">
	    <div class="modal fade editingProjectsModal-<?php echo $x ?>">
	      <div class="modal-dialog modal-md modal-content" role="document">
	        <div class="modal-header">
	          <h4><?php echo $row['name']; ?></h4>
	        </div>
	        <div class="modal-body">
	          <div class="row">
	            <div class="col-md-6">
	              <label><?php echo $lang['HOURS']; ?></label>
	              <input type="number" class="form-control" step="any" name="boughtHours" value="<?php echo $row['hours']; ?>">
	            </div>
	            <div class="col-md-6">
	              <label><?php echo $lang['HOURLY_RATE']; ?></label>
	              <input type="number" class="form-control" step="any" name="pricedHours" value="<?php echo $row['hourlyPrice']; ?>">
	            </div>
	          </div>
	          <div class="row checkbox">
	            <div class="col-md-6">
	              <label><input type="checkbox" name="productive" <?php echo $row['status']; ?> /><?php echo $lang['PRODUCTIVE']; ?></label>
	            </div>
                <div class="col-md-6"><br>
                    <?php
                    $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = " . $rowClient['companyID'] . " ORDER BY id ASC");
                    if ($resF->num_rows > 0) {
                        $rowF = $resF->fetch_assoc();
                        if ($rowF['isActive'] == 'TRUE') {
                            $checked = $row['field_1'] == 'TRUE' ? 'checked' : '';
                            echo '<label><input type="checkbox" ' . $checked . ' name="addField_1_' . $row['id'] . '"/> ' . $rowF['name'] . '</label>';
                        }
                    }
                    if ($resF->num_rows > 1) {
                        $rowF = $resF->fetch_assoc();
                        if ($rowF['isActive'] == 'TRUE') {
                            $checked = $row['field_2'] == 'TRUE' ? 'checked' : '';
                            echo '<br><label><input type="checkbox" ' . $checked . ' name="addField_2_' . $row['id'] . '" /> ' . $rowF['name'] . '</label>';
                        }
                    }
                    if ($resF->num_rows > 2) {
                        $rowF = $resF->fetch_assoc();
                        if ($rowF['isActive'] == 'TRUE') {
                            $checked = $row['field_3'] == 'TRUE' ? 'checked' : '';
                            echo '<br><label><input type="checkbox" ' . $checked . ' name="addField_3_' . $row['id'] . '" /> ' . $rowF['name'] . '</label>';
                        }
                    }
                    ?>
                </div>
	          </div>
	        </div>
	        <div class="modal-footer">
	          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	          <button type="submit" class="btn btn-warning" name="save" value="<?php echo $x ?>"><?php echo $lang['SAVE']; ?></button>
	        </div>
	      </div>
	    </div>
	  </form>
	<?php endwhile;?>
