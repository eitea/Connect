<?php
/*
SETTING UP A NEW TABLE:
1. [OPTIONAL] Put the name of your table as a new variable in connection_vars.php.
2. Append your information to the specs or add them here.
3. Put your CREATE TABLE statement in here, similar to already existing code.
4. increment the version numbers in version_number.php.
5. for the new version number, append another if statement into the doUpdate.php, so your changes will be carried over to all existing databases on different systems.
6. relog inside program with a core admin account. you should see an update.

MAKING CHANGES TO EXISTING TABLE:
1. make your changes in here
2. write an ALTER TABLE statement inside doUpdate.php, with the new version number
3. increment the numbers in version_number.php
4. relog inisde program with an account with core priviliges.

Test the setup regularly
*/

function create_tables($conn){
  $sql = "CREATE TABLE UserData (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(30),
    lastname VARCHAR(30) NOT NULL,
    psw VARCHAR(60) NOT NULL,
    coreTime TIME DEFAULT '08:00:00',
    terminalPin INT(8) DEFAULT 4321,
    lastPswChange DATETIME DEFAULT CURRENT_TIMESTAMP,
    beginningDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    exitDate DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    email VARCHAR(50) UNIQUE NOT NULL,
    sid VARCHAR(50),
    gender ENUM('female', 'male'),
    preferredLang ENUM('ENG', 'GER', 'FRA', 'ITA') DEFAULT 'GER',
    kmMoney DECIMAL(4,2) DEFAULT 0.42,
    emUndo DATETIME DEFAULT CURRENT_TIMESTAMP,
    color VARCHAR(10) DEFAULT 'dark',
    real_email VARCHAR(50),
    erpOption VARCHAR(10) DEFAULT 'TRUE'
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE logs (
    indexIM INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    time DATETIME NOT NULL,
    timeEnd DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    status INT(3),
    timeToUTC INT(2) DEFAULT '2',
    userID INT(6) UNSIGNED,
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE ldapConfigTab (
    ldapConnect VARCHAR(30),
    ldapPassword VARCHAR(30),
    ldapUsername VARCHAR(30),
    adminID INT(6) UNSIGNED,
    version INT(5) DEFAULT 0,
    FOREIGN KEY (adminID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE holidays(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    begin DATETIME,
    end DATETIME,
    name VARCHAR(60) NOT NULL
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE companyData (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    cmpDescription VARCHAR(50),
    companyType ENUM('GmbH', 'AG', 'OG', 'KG', 'EU', '-') DEFAULT '-',
    logo MEDIUMBLOB,
    address VARCHAR(100),
    companyPostal VARCHAR(20),
    companyCity VARCHAR(60),
    phone VARCHAR(100),
    mail VARCHAR(100),
    homepage VARCHAR(100),
    erpText TEXT,
    detailLeft VARCHAR(120),
    detailMiddle VARCHAR(120),
    detailRight VARCHAR(120),
    uid VARCHAR(20),
    istVersteuerer ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE clientData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    companyID INT(6) UNSIGNED,
    clientNumber VARCHAR(12),
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE projectData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clientID INT(6) UNSIGNED,
    name VARCHAR(60) NOT NULL,
    hours DECIMAL(5,2),
    status VARCHAR(30),
    hourlyPrice DECIMAL(6,2) DEFAULT 0,
    field_1 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    field_2 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    field_3 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    FOREIGN KEY (clientID) REFERENCES clientData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE projectBookingData (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    start DATETIME NOT NULL,
    end DATETIME NOT NULL,
    chargedTimeStart DATETIME DEFAULT '0000-00-00 00:00:00',
    chargedTimeEnd DATETIME DEFAULT '0000-00-00 00:00:00',
    projectID INT(6) UNSIGNED,
    timestampID INT(10) UNSIGNED NOT NULL,
    infoText VARCHAR(500),
    internInfo VARCHAR(500),
    booked ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    bookingType ENUM('project', 'break', 'drive', 'mixed'),
    mixedStatus INT(3) DEFAULT -1,
    extra_1 VARCHAR(200) NULL DEFAULT NULL,
    extra_2 VARCHAR(200) NULL DEFAULT NULL,
    extra_3 VARCHAR(200) NULL DEFAULT NULL,
    exp_info TEXT,
    exp_price DECIMAL(10,2),
    exp_unit DECIMAL(10,2),
    FOREIGN KEY (projectID) REFERENCES projectData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (timestampID) REFERENCES logs(indexIM)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    UNIQUE KEY double_submit (timestampID, start, end)
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE relationship_company_client (
    companyID INT(6) UNSIGNED,
    userID INT(6) UNSIGNED,
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE companyDefaultProjects (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    companyID INT(6) UNSIGNED,
    hours INT(3),
    status VARCHAR(30),
    hourlyPrice DECIMAL(6,2) DEFAULT 0,
    field_1 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    field_2 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    field_3 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    UNIQUE KEY name_company (name, companyID)
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE configurationData(
    bookingTimeBuffer INT(3) DEFAULT '5',
    cooldownTimer INT(3) DEFAULT '2',
    enableReadyCheck ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
    enableReg ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
    masterPassword VARCHAR(100),
    enableAuditLog ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  //status: 0 = open, 1 = declined, 2 = accepted
  $sql = "CREATE TABLE userRequestsData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6) UNSIGNED,
    fromDate DATETIME NOT NULL,
    toDate DATETIME,
    status ENUM('0', '1', '2') DEFAULT '0',
    requestType ENUM('vac', 'log', 'acc', 'scl', 'spl', 'brk', 'cto', 'div') DEFAULT 'vac',
    requestText VARCHAR(150),
    answerText VARCHAR(150),
    requestID INT(10) DEFAULT 0,
    timeToUTC INT(2) DEFAULT 0,
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE gitHubConfigTab(
    sslVerify ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE roles(
    userID INT(6) UNSIGNED,
    isCoreAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    isTimeAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    isProjectAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    isReportAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    isERPAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    isFinanceAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    canStamp ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
    canBook ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    canUseSocialMedia ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    canEditTemplates ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE travelCountryData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(10) NOT NULL,
    countryName VARCHAR(50),
    dayPay DECIMAL(6,2) DEFAULT 0,
    nightPay DECIMAL(6,2) DEFAULT 0
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE travelBookings(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6) UNSIGNED,
    countryID INT(6) UNSIGNED,
    travelDayStart DATETIME NOT NULL,
    travelDayEnd DATETIME NOT NULL,
    kmStart INT(8),
    kmEnd INT(8),
    infoText VARCHAR(500),
    hotelCosts DECIMAL(8,2) DEFAULT 0,
    hosting10 DECIMAL(6,2) DEFAULT 0,
    hosting20 DECIMAL(6,2) DEFAULT 0,
    expenses DECIMAL(8,2) DEFAULT 0,
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (countryID) REFERENCES travelCountryData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  //deactivated tables
  $sql = "CREATE TABLE DeactivatedUsers (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(30),
    lastname VARCHAR(30) NOT NULL,
    psw VARCHAR(60) NOT NULL,
    coreTime TIME DEFAULT '08:00:00',
    terminalPin INT(8) DEFAULT 4321,
    lastPswChange DATETIME DEFAULT CURRENT_TIMESTAMP,
    beginningDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    exitDate DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    email VARCHAR(50) UNIQUE NOT NULL,
    sid VARCHAR(50),
    gender ENUM('female', 'male'),
    preferredLang ENUM('ENG', 'GER', 'FRA', 'ITA') DEFAULT 'GER',
    kmMoney DECIMAL(4,2) DEFAULT 0.42,
    emUndo DATETIME DEFAULT CURRENT_TIMESTAMP,
    real_email VARCHAR(50)
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE DeactivatedUserLogData (
    indexIM INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    time DATETIME NOT NULL,
    timeEnd DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    status INT(3),
    timeToUTC INT(2) DEFAULT '2',
    userID INT(6) UNSIGNED,
    FOREIGN KEY (userID) REFERENCES DeactivatedUsers(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE DeactivatedUserData(
    userID INT(6) UNSIGNED,
    startDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    endDate DATETIME DEFAULT NULL,
    mon DECIMAL(4,2) DEFAULT 8.5,
    tue DECIMAL(4,2) DEFAULT 8.5,
    wed DECIMAL(4,2) DEFAULT 8.5,
    thu DECIMAL(4,2) DEFAULT 8.5,
    fri DECIMAL(4,2) DEFAULT 4.5,
    sat DECIMAL(4,2) DEFAULT 0,
    sun DECIMAL(4,2) DEFAULT 0,
    vacPerYear INT(2) DEFAULT 25,
    overTimeLump DECIMAL(5,2) DEFAULT 0.0,
    pauseAfterHours DECIMAL(4,2) DEFAULT 6,
    hoursOfRest DECIMAL(4,2) DEFAULT 0.5,
    FOREIGN KEY (userID) REFERENCES DeactivatedUsers(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE DeactivatedUserProjectData (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    start DATETIME NOT NULL,
    end DATETIME NOT NULL,
    chargedTimeStart DATETIME DEFAULT '0000-00-00 00:00:00',
    chargedTimeEnd DATETIME DEFAULT '0000-00-00 00:00:00',
    projectID INT(6) UNSIGNED,
    timestampID INT(10) UNSIGNED NOT NULL,
    infoText VARCHAR(500),
    internInfo VARCHAR(500),
    booked ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    bookingType ENUM('project', 'break', 'drive', 'mixed'),
    mixedStatus INT(3) DEFAULT -1,
    extra_1 VARCHAR(200) NULL DEFAULT NULL,
    extra_2 VARCHAR(200) NULL DEFAULT NULL,
    extra_3 VARCHAR(200) NULL DEFAULT NULL,
    exp_info TEXT,
    exp_price DECIMAL(10,2),
    exp_unit DECIMAL(10,2),
    FOREIGN KEY (projectID) REFERENCES projectData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (timestampID) REFERENCES DeactivatedUserLogData(indexIM)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE DeactivatedUserTravelData(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6) UNSIGNED,
    countryID INT(6) UNSIGNED,
    travelDayStart DATETIME NOT NULL,
    travelDayEnd DATETIME NOT NULL,
    kmStart INT(8),
    kmEnd INT(8),
    infoText VARCHAR(500),
    hotelCosts DECIMAL(8,2) DEFAULT 0,
    hosting10 DECIMAL(6,2) DEFAULT 0,
    hosting20 DECIMAL(6,2) DEFAULT 0,
    expenses DECIMAL(8,2) DEFAULT 0,
    FOREIGN KEY (userID) REFERENCES DeactivatedUsers(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (countryID) REFERENCES travelCountryData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE clientInfoData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contactType ENUM('person', 'company'),
    gender ENUM('female', 'male'),
    title VARCHAR(30),
    name VARCHAR(45),
    firstname VARCHAR(45),
    nameAddition VARCHAR(45),
    address_Street VARCHAR(100),
    address_Country VARCHAR(50),
    address_Country_Postal VARCHAR(20),
    address_Country_City VARCHAR(50),
    phone VARCHAR(20),
    fax_number VARCHAR(30),
    debitNumber INT(10),
    datev INT(10),
    accountName VARCHAR(100),
    taxnumber VARCHAR(50),
    vatnumber VARCHAR(50),
    taxArea VARCHAR(50),
    customerGroup VARCHAR(50),
    representative VARCHAR(50),
    blockDelivery ENUM('true', 'false') DEFAULT 'false',
    paymentMethod VARCHAR(100),
    shipmentType VARCHAR(100),
    creditLimit DECIMAL(10,2),
    eBill ENUM('true', 'false') DEFAULT 'false',
    lastFaktura DATETIME,
    daysNetto INT(4),
    skonto1 DECIMAL(6,2),
    skonto2 DECIMAL(6,2),
    skonto1Days INT(4),
    skonto2Days INT(4),
    warningEnabled ENUM('true', 'false') DEFAULT 'true',
    karenztage INT(4),
    lastWarning DATETIME,
    warning1 DECIMAL(10,2),
    warning2 DECIMAL(10,2),
    warning3 DECIMAL(10,2),
    calculateInterest ENUM('true', 'false'),
    clientID INT(6) UNSIGNED,
    FOREIGN KEY (clientID) REFERENCES clientData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE clientInfoNotes(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    infoText VARCHAR(800),
    createDate DATETIME,
    parentID INT(6) UNSIGNED,
    FOREIGN KEY (parentID) REFERENCES clientInfoData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE clientInfoBank(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bic VARCHAR(200),
    iban VARCHAR(200),
    bankName VARCHAR(200),
    parentID INT(6) UNSIGNED,
    iv VARCHAR(150),
    iv2 VARCHAR(50),
    FOREIGN KEY (parentID) REFERENCES clientInfoData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE modules (
    enableTime ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
    enableProject ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
    enableSocialMedia ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE policyData (
    passwordLength INT(2) DEFAULT 6,
    complexity ENUM('0', '1', '2') DEFAULT '1',
    expiration ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
    expirationDuration INT(3) DEFAULT 3,
    expirationType ENUM('ALERT', 'FORCE') DEFAULT 'ALERT'
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE mailingOptions(
    host VARCHAR(50),
    username VARCHAR(50),
    password VARCHAR(50),
    port VARCHAR(50),
    smtpSecure ENUM('', 'tls', 'ssl') DEFAULT 'tls',
    sender VARCHAR(50) DEFAULT 'noreplay@mail.com',
    enableEmailLog ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE templateData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    repeatCount VARCHAR(50),
    htmlCode TEXT,
    userIDs VARCHAR(200)
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE mailReports(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reportID INT(6) UNSIGNED,
    email VARCHAR(50) NOT NULL,
    FOREIGN KEY (reportID) REFERENCES templateData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  }

  /*
  * cOnDate is Date this correction was created On
  * createdOn defines Month this corrections accounts for
  * (used names were swapped by mistake.)
  */
  $sql = "CREATE TABLE correctionData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6) UNSIGNED,
    hours DECIMAL(6,2),
    infoText VARCHAR(350),
    addOrSub ENUM('1', '-1') NOT NULL,
    cOnDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    createdOn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cType VARCHAR(10) NOT NULL DEFAULT 'log',
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE intervalData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    startDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    endDate DATETIME DEFAULT NULL,
    mon DECIMAL(4,2) DEFAULT 8.5,
    tue DECIMAL(4,2) DEFAULT 8.5,
    wed DECIMAL(4,2) DEFAULT 8.5,
    thu DECIMAL(4,2) DEFAULT 8.5,
    fri DECIMAL(4,2) DEFAULT 4.5,
    sat DECIMAL(4,2) DEFAULT 0,
    sun DECIMAL(4,2) DEFAULT 0,
    vacPerYear INT(2) DEFAULT 25,
    overTimeLump DECIMAL(5,2) DEFAULT 0.0,
    pauseAfterHours DECIMAL(4,2) DEFAULT 6,
    hoursOfRest DECIMAL(4,2) DEFAULT 0.5,
    userID INT(6) UNSIGNED,
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE teamData (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60),
    companyID INT(6) UNSIGNED,
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE teamRelationshipData (
    teamID INT(6) UNSIGNED,
    userID INT(6) UNSIGNED,
    FOREIGN KEY (teamID) REFERENCES teamData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE additionalFields (
    id INT(6) UNSIGNED PRIMARY KEY,
    companyID INT(6) UNSIGNED,
    name VARCHAR(25) NOT NULL,
    isActive ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    isRequired ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    isForAllProjects ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    description VARCHAR(50),
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE taskData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repeatPattern ENUM('-1', '0', '1', '2', '3', '4') DEFAULT '-1',
    runtime DATETIME DEFAULT CURRENT_TIMESTAMP,
    description VARCHAR(200),
    lastRuntime DATETIME DEFAULT CURRENT_TIMESTAMP,
    callee VARCHAR(50) NOT NULL
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE proposals(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(10) NOT NULL,
    clientID INT(6) UNSIGNED,
    status INT(2),
    curDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    deliveryDate DATETIME,
    daysNetto INT(4),
    skonto1 DECIMAL(8,2),
    skonto2 DECIMAL(8,2),
    skonto1Days INT(4),
    skonto2Days INT(4),
    paymentMethod VARCHAR(100),
    shipmentType VARCHAR(100),
    representative VARCHAR(50),
    porto DECIMAL(8,2),
    portoRate INT(3),
    history VARCHAR(100),
    header VARCHAR(400),
    referenceNumrow VARCHAR(10),
    FOREIGN KEY (clientID) REFERENCES clientData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  /* IMPORTANT:
  Tax IDs must always correspond to their current positon in place.
  Taxes may only be altered under supervision of an accountant
  */
  $sql = "CREATE TABLE taxRates (
    id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(100),
    percentage INT(3),
    code INT(2),
    account2 INT(4),
    account3 INT(4)
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE products(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proposalID INT(6) UNSIGNED,
    position INT(4),
    name VARCHAR(255) NOT NULL,
    description VARCHAR(600),
    price DECIMAL(10,2),
    unit VARCHAR(20),
    quantity DECIMAL(8,2),
    purchase DECIMAL(10,2),
    taxPercentage INT(3),
    cash ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    iv VARCHAR(255),
    iv2 VARCHAR(255),
    UNIQUE KEY (proposalID, position),
    FOREIGN KEY (proposalID) REFERENCES proposals(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE mixedInfoData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timestampID INT(10) UNSIGNED,
    status INT(3),
    timeStart DATETIME,
    timeEnd DATETIME,
    isFillable ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
    FOREIGN KEY (timestampID) REFERENCES logs(indexIM)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE articles (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    description VARCHAR(1200),
    price DECIMAL(10,2),
    unit VARCHAR(20),
    taxPercentage INT(3),
    cash ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    purchase DECIMAL(10,2),
    iv VARCHAR(255),
    iv2 VARCHAR(255)
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE units (
    id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,
    unit VARCHAR(10) NOT NULL
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE paymentMethods (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    daysNetto INT(4),
    skonto1 DECIMAL(6,2),
    skonto2 DECIMAL(6,2),
    skonto1Days INT(4),
    skonto2Days INT(4)
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE representatives (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50)
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE shippingMethods (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100)
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE erpNumbers(
    companyID INT(6) UNSIGNED,
    erp_ang INT(5) DEFAULT 1,
    erp_aub INT(5) DEFAULT 1,
    erp_re INT(5) DEFAULT 1,
    erp_lfs INT(5) DEFAULT 1,
    erp_gut INT(5) DEFAULT 1,
    erp_stn INT(5) DEFAULT 1,
    yourSign VARCHAR(30),
    yourOrder VARCHAR(30),
    ourSign VARCHAR(30),
    ourMessage VARCHAR(30),
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE socialprofile(
    userID INT(6) UNSIGNED,
    isAvailable ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
    status varchar(150) DEFAULT '-',
    picture MEDIUMBLOB,
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE socialmessages(
    userID INT(6) UNSIGNED,
    partner INT(6) UNSIGNED,
    message TEXT,
    picture MEDIUMBLOB,
    sent DATETIME DEFAULT CURRENT_TIMESTAMP,
    seen ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE socialgroups(
    groupID INT(6) UNSIGNED,
    userID INT(6) UNSIGNED,
    name VARCHAR(30),
    admin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE socialgroupmessages(
    userID INT(6) UNSIGNED,
    groupID INT(6) UNSIGNED,
    message TEXT,
    picture MEDIUMBLOB,
    sent DATETIME DEFAULT CURRENT_TIMESTAMP,
    seen TEXT
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }
  if(!$conn->query("CREATE TABLE resticconfiguration(
    path VARCHAR(255),
    password VARCHAR(255),
    awskey VARCHAR(255),
    awssecret VARCHAR(255),
    location VARCHAR(255)
  )")){
    echo mysqli_error($conn);
  }
  
  $sql = "CREATE TABLE identification(
    id VARCHAR(60) PRIMARY KEY
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE accounts (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    companyID INT(6) UNSIGNED,
    manualBooking VARCHAR(10) DEFAULT 'FALSE',
    num INT(4) UNSIGNED,
    name VARCHAR(50),
    type ENUM('1', '2', '3', '4') DEFAULT '1',
    UNIQUE KEY (companyID, num),
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE account_journal(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6),
    taxID INT(4) UNSIGNED,
    docNum INT(6),
    payDate DATETIME,
    inDate DATETIME,
    account INT(6) UNSIGNED,
    offAccount INT(6) UNSIGNED,
    info VARCHAR(70),
    should DECIMAL(18,2),
    have DECIMAL(18,2),
    FOREIGN KEY (account) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (offAccount) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE account_balance(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journalID INT(10) UNSIGNED,
    accountID INT(4) UNSIGNED,
    should DECIMAL(18,2),
    have DECIMAL(18,2),  
    FOREIGN KEY (accountID) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,  
    FOREIGN KEY (journalID) REFERENCES account_journal(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TABLE projectBookingData_audit(
    id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    changedat DATETIME,
    bookingID INT(6) UNSIGNED,
    statement VARCHAR(100)
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TRIGGER projectBookingData_update_trigger 
    BEFORE UPDATE ON projectBookingData
    FOR EACH ROW
  BEGIN
    SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
    IF @cnt >= 150 THEN 
      DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
    END IF;
    INSERT INTO projectBookingData_audit
    SET changedat = UTC_TIMESTAMP, bookingID = OLD.id, statement = CONCAT('UPDATE ', OLD.id);
  END";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TRIGGER projectBookingData_delete_trigger 
      BEFORE DELETE ON projectBookingData
      FOR EACH ROW
    BEGIN
      SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
      IF @cnt >= 150 THEN 
        DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
      END IF;
      INSERT INTO projectBookingData_audit
      SET changedat = UTC_TIMESTAMP, bookingID = OLD.id, statement = 'DELETE';
    END";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  $sql = "CREATE TRIGGER projectBookingData_insert_trigger 
      AFTER INSERT ON projectBookingData
      FOR EACH ROW
    BEGIN
      SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
      IF @cnt >= 150 THEN 
        DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
      END IF;
      INSERT INTO projectBookingData_audit
      SET changedat = UTC_TIMESTAMP, bookingID = NEW.id, statement = CONCAT('INSERT ', NEW.timestampID);
    END";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }


}
