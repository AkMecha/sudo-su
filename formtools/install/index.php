<?php
require_once("../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Installation;

Core::setHooksEnabled(false);
Core::startSessions();

Installation::checkInstallationComplete();

if (isset($_GET["clear"])) {
    FormTools\Sessions::clearAll();
}

Core::initSmarty();
Core::setCurrentLang(General::loadField("lang", "lang", Core::getDefaultLang()));
$root_url = Core::getRootUrl();
$LANG = Core::$L;

?>
<!doctype html>
<html>
<head>
    <title><?php echo $LANG["phrase_ft_installation"]; ?></title>
    <link rel="shortcut icon" href="../themes/default/images/favicon.ico">
</head>
<body>
    <div id="root"></div>
    <script src="../react/main.bundle.js?v=<?php Core::getVersionString(); ?>"></script>
</body>
</html>
