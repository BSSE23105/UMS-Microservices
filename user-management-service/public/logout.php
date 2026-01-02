<?php
require_once __DIR__ . '/../src/bootstrap.php';
;
session_unset();
session_destroy();
header("Location: /index.php");
exit;
?>