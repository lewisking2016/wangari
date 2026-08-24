<?php
session_start();
session_unset();
session_destroy();
header('Location: /Frontend/worker/login.php');
exit;
