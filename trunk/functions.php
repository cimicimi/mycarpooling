<?

/* ---------------------
 * IMPOSTAZIONI DATABASE
 * --------------------- */
$db_host="localhost";
$db_usr="carpooler";
$db_psw="";
$db_name="Carpooling";
$db_conn=null;

/*
 * Effettua il login, variando opportunamente le variabili di
 * sessione 'user' e 'userId'. Ritorna l'username dell'utente in
 * caso di successo e null in caso di insuccesso.
 */
function checkUser ($username, $password) {
   global $db_host,$db_usr,$db_psw,$db_name;

   $query="select ID,psw from Utenti where `username`='".$username."'";
   $conn=mysql_connect($db_host,$db_usr,$db_psw);
   mysql_select_db ($db_name,$conn)
      or die ("Errore selezione database");
   $res=mysql_query($query)
      or die ("Errore formulazione query");
   $a=mysql_fetch_array($res);
   $psw=$a['psw'];
   // print "Confronto ".$password." con ".$psw;

   if ($password==$psw) { 
      $_SESSION['user']=$username;
      $_SESSION['userId']=$a['ID'];
   }
   
   else
      $_SESSION['wronglogin']=true;

   return getUser();
}

/*
 * Ritorna l'username dell'utente (se loggato) oppure
 * null (se non loggato).
 */
function getUser () {
   if (isset($_SESSION['user'])) 
      return $_SESSION['user'];
   else
      return null;
}

/*
 * Ritorna l'userId dell'utente (se loggato) oppure
 * null (se non loggato).
 */
function getUserId () {
   if (isset($_SESSION['userId']))
      return $_SESSION['userId'];
   else
      return null;
}

/*
 * Restituisce il menu.
 */
function menu () {
   if (!getUser()) {
      return <<<END
         <a href="#" onclick="script2()">Login</a>&nbsp;&middot;
         <a href="index.php?p=iscrizione">Iscriviti</a>&nbsp;&middot;
         <a href="index.php?p=tragitti">Tragitti</a>&nbsp;&middot;
         <a href="index.php?p=utenti">Utenti</a>
END;
   }
   else {
      $Utente = getUser();
      return <<<END
      <b>$Utente</b> - 
      <a href="index.php?p=profilo">Profilo</a>&nbsp;&middot;
      <a href="index.php?p=nuovo">Nuovo tragitto</a>&nbsp;&middot;
      <a href="index.php?p=tragitti">Tragitti</a>&nbsp;&middot;
      <a href="auto.php?p=auto">Auto</a>&nbsp;&middot;
      <a href="index.php?p=utenti">Utenti</a>&nbsp;&middot;
      <a href="index.php?action=logout">Logout</a>
END;
   }
}

/*
 * Ritorna il contenuto della pagina richiesta, che va
 * a sostituire <!-- CONTENT --> nel template index.htm
 */
function content () {
   if (!isset($_GET['p'])) {
      $_GET['p']="tragitti";
   }

   // Lista delle pagine consentite
   $allowed = array("login","iscrizione","cerca","utenti",
      "tragitti","profilo");

   $output="";
   
   if (isset($_SESSION['wronglogin'])) {
      $output="Login errato";
      unset($_SESSION['wronglogin']);
   }

   // Controllo che la pag richiesta sia consentita...
   if (in_array($_GET['p'],$allowed)) {

      // ... la recupero ...
      $pagina = "template/".$_GET[p].".htm";
      $file = file($pagina)
         or die("Pagina non trovata");
      $file_content = implode ("",$file);
   }

   $output = $output.prepare_content($file_content);
   return $output;
}

function connectDb (&$dbconn) {
   global $db_host,$db_usr,$db_psw,$db_name,$db_conn;

   $db_conn=mysql_connect($db_host, $db_usr, $db_psw)
      or die ("Errore di connessione");
   mysql_select_db($db_name,$db_conn)
      or die ("boh");
}

/*
 * Esegue una query e ritorna una variabile risorsa
 */
function execQuery ($query) {
   global $db_conn;

   if (!$db_conn)
      connectDb($db_conn);

   return mysql_query($query, $db_conn);
}

/*
 * Restituisce gli anni di distanza da una data nel formato
 * YYYY-MM-DD.
 */
function age ($birthday) {
   list($year,$month,$day) = explode("-",$birthday);
   $year_diff  = date("Y") - $year;
   $month_diff = date("m") - $month;
   $day_diff   = date("d") - $day;
   if ($day_diff < 0 || $month_diff < 0)
      $year_diff--;
   return $year_diff;
}

/*
 * Restituisce i percorsi per cui si puo' scrivere un
 * feedback.
 */
function feedback ($targetUserId) {
   $query="select Tragitto.*
      from UtentiTragitto join Tragitto
      on UtentiTragitto.idTragitto=Tragitto.ID
      where Tragitto.idPropr='".$targetUserId."'
         and UtentiTragitto.idUtente='".getUserId()."'
      limit 5";
   $res=execQuery($query);
   while ($row2=mysql_fetch_array($res,MYSQL_ASSOC)) {
      $feedback=$feedback.$row2['ID'];
   }
   return $feedback;
}

/* 
 * Effettua le opportuni sostituzioni di commenti html
 * con variabili PHP, a seconda della pagina richiesta.
 */
function prepare_content ($a) {
   switch ($_GET['p']) {
      case 'tragitti':
         $query="select Tragitto.*, userName from `Tragitto`
            join `Utenti` on idPropr=Utenti.ID
            order by `dataPart` desc,`oraPart` desc limit 5";
         $res=execQuery($query);
         $out = "";

         while ($row=mysql_fetch_array($res)) {
            $o = eregi_replace("<!-- PROPRIETARIO -->", $row['userName'],$a);
            $o = eregi_replace("<!-- NPDISP -->", $row['numPostiDisp'],$o);
            $o = eregi_replace("<!-- PARTENZA -->", $row['partenza'],$o);
            $o = eregi_replace("<!-- DESTINAZ -->", $row['destinazione'],$o);
            $o = eregi_replace("<!-- ORA -->",
                $row['dataPart']." ".$row['oraPart'],$o);
            $o = eregi_replace("<!-- DURATA -->", $row['durata'],$o);
            $o = eregi_replace("<!-- FUMO -->", $row['fumo'],$o);
            $o = eregi_replace("<!-- MUSICA -->", $row['musica'],$o);
            $out = $out.$o;
         }
      break;

      case 'profilo':
         if (!isset($_GET['u']))
            $_GET['u']=getUser();

         $query="select *,UNIX_TIMESTAMP(`dataIscriz`) from `Utenti`
            where `userName`='".$_GET['u']."'";
         $res=execQuery($query);
         $row=mysql_fetch_array($res,MYSQL_ASSOC);
        
         if (getUser()!=$_GET['u']) {
            $feedback=feedback($row['ID']);
         }

         $search = array ("/{userName}/",
            "/{nome}/",
            "/{cognome}/",
            "/{eta}/",
            "/{email}/",
            "/{dataPatente}/",
            "/{localita}/",
            "/{dataIscriz}/",
            "/{feedback}/");

         $replace = array ($row['userName'],
            $row['nome'],
            $row['cognome'],
            age($row['dataNascita']),
            $row['email'],
            age($row['dataPatente']),
            $row['localita'],
            strftime("%e %B %Y",
               $row['UNIX_TIMESTAMP(`dataIscriz`)']),
            $feedback);

         ksort($search);
         ksort($replace);
         
         $out = preg_replace($search, $replace, $a);

         break;

      default:
         $out=$a;
         break;
   }

   return $out;
}

/*
 * Affidabilita' dell'utente
 */
function trust ($username) {
   return "molto affidabile";
}

?>
