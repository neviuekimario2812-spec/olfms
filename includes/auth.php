<?php session_start(); require_once __DIR__.'/../config/db.php';
function user(){return $_SESSION['user']??null;} function require_login(){if(!user()){header('Location: /olfms/layouts/auth/login.php');exit;}}
function require_role($roles){require_login(); if(!in_array(user()['role'],(array)$roles,true)){http_response_code(403);exit('Access denied');}}
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function csrf(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function check_csrf(){if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??''))exit('Invalid request');}
