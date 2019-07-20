<?php
session_start();
$_SESSION['AdvName'] = $_POST['AdvName'];

header("Location:NTSFReg.html");
exit();

?>