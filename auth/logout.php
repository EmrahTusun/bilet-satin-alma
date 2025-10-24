<?php
session_start();
session_destroy();
header("Location: /bilet-satin-alma/index.php");
exit;
?>