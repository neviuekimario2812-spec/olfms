<?php
session_start();
if(isset($_SESSION['user'])){ header('Location: layouts/'.$_SESSION['user']['role'].'/dashboard.php'); exit; }
header('Location: layouts/auth/login.php');
