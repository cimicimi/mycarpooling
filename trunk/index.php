<?
include("functions.php");
session_start();
setlocale(LC_TIME,'ita','it_IT','it_IT.utf8');

// Gestione login
if ($_GET['action']=="login") {
   checkUser($_POST['username'],$_POST['password']);
}

if ($_GET['action']=="logout") {
   unset($_SESSION['user']);
}

$output = implode ("",file("template/index.htm"));

// Preparo i contenuti dinamici
$m = menu();
$c = content();

// li sostituisco nell'html statico
$output = eregi_replace ("<!-- MENU -->", $m ,$output);
$output = eregi_replace ("<!-- CONTENT -->", $c ,$output);

// infine stampo
echo $output;

if ($db_conn)
   mysql_close ($db_conn);
?>
