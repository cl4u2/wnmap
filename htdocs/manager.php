<?php
/*
WNMap
Copyright (C) 2011 Claudio Mignanti <c.mignanti@gmail.com>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
*/



require ("config.php");

if ( MANAGEMENT == 0)
 	die ("Manager disable due to configuration");

//TODO: anti rompiballs system here

$connection = mysql_connect (MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die ('Could not connect: ' . mysql_error());
mysql_select_db (MYSQL_DB) or die ('Could not select database.');

$id = mysql_real_escape_string ($_GET["id"]);
$val = mysql_real_escape_string ($_GET["val"]);

if (isset($_GET["action"])){
 
	/* Manager - Default action */
	if ($_GET["action"] == "manager" ) {
		readfile("manager.html");
	} else 
	/* Status change - manager.php?name='+name+'&action=status&val='+new_value */
	if ($_GET["action"] == "status" ) {

		/* retrieve actual status to compare with the new one */
		$query = "SELECT status, ip FROM nodes WHERE id='". $id ."';";
		$result = mysql_query ($query, $connection) or die (mysql_error());
		$row = mysql_fetch_assoc($result);
		$act_status = htmlspecialchars($row['status']);
		$ip = htmlspecialchars($row['ip']);

		if ($ip == "" && $val != 0 ) {
			echo "Il tuo nodo non può cambiare status senza un ip valido.";
		} elseif ($val != $act_status) { 
			$query = "UPDATE nodes SET status=" . $val . " WHERE id='". $id ."';";
			$result = mysql_query ($query, $connection) or die (mysql_error());

			mail (MANAGEMENT_MAIL, "Node status change", "Il nodo ". $id ." è passato allo stato ". $val ." da ". $act_val ." su richiesta di ". $_SERVER['REMOTE_ADDR'] .".");
			echo "Lo stato del tuo nodo è stato aggiornato correttamente.<br> Ricarica la pagina del mapserver per vedere le modifiche.<br>";
		} else {
			echo "Il tuo nodo è già a questo stato... evito di aggiornare.";
		}
	} else 
	/* Contatta utente */
	if ($_GET["action"] == "contatti" ) {
		$query = "SELECT userEmailPublish, userEmail, userRealName FROM nodes WHERE id='". $id ."';";
		$result = mysql_query ($query, $connection) or die (mysql_error());
		
		$row = mysql_fetch_assoc($result);
		$uname = htmlspecialchars($row['userRealName']);
		$email = htmlspecialchars($row['userEmail']);
		$email_pub = $row['userEmailPublish'];
		
		echo "L'utente $uname può essere contattato utilizzando il form di seguito:<br>Ricordati di aggiungere <b>la tua mail</b> come informazione per essere ricontattato.<br>
			<form method=post action='manager.php?action=contatti2&id=".$id."'>
				<textarea name='text' rows=10 cols=50 >Il tuo messaggio</textarea>
				<input type='hidden' name=id value=$id><br>
				<input type='submit' value='Invia mail'>
			</form>";
		if ($email_pub == 1) {
			echo "<hr>Inoltre l'utente ha deciso di rendere pubblica la sua e-mail che è $email<br>";
		}		
		echo "Se invece vuoi informazioni generali contatta la community all'indirizzo contatti@ninux.org<br><br>";

	} else
	if ($_GET["action"] == "contatti2" ) {
		$query = "SELECT userEmail FROM nodes WHERE id='". $id ."';";
		$result = mysql_query ($query, $connection) or die (mysql_error());
		
		$row = mysql_fetch_assoc($result);
		$email = htmlspecialchars($row['userEmail']);
		
		echo "La tua mail è stata inviata corettamente";
		mail ($email, "Contatto dal MapServer ninux.org", $_POST["text"]. "\n------------\nQuesto messaggio è stato generato attraverso il  mapserver di ninux.\nPer segnalare eventuali abusi scrivi a contatti@ninux.org");

	}
	/* Ip change */
	if ($_GET["action"] == "ip1" ) {
		$query = "SELECT nodeIp FROM nodes WHERE id='". $id ."';";
		$result = mysql_query ($query, $connection) or die (mysql_error());
		
		$row = mysql_fetch_assoc($result);
		$ip= $row['nodeIp'];	

		echo "<form method=post action='manager.php?action=ip2'>
			Modifica gli ip/subnet associate al nodo. Usa spazio come separazione.<br>
			<textarea name=new_ip>$ip</textarea>
			<input type=hidden name=old_ip value=$ip>
			<input type=hidden name=id value=$id><br>
			<input type=submit value='Modifica classe/i Ip'>
		      <form>"; 
	}
	if ($_GET["action"] == "ip2" ) {
		$ip =  mysql_real_escape_string ($_POST["new_ip"]);

		$query = "UPDATE nodes SET nodeIp='" . $ip . "' WHERE id='". $_POST["id"] ."';";
		$result = mysql_query ($query, $connection) or die (mysql_error());

		mail (MANAGEMENT_MAIL, "Node ip change", "Il nodo". $id ."è passato dall'ip --". $_POST["old_ip"] ."-- a ". $ip ." su richiesta di ". $_SERVER['REMOTE_ADDR'] .".");

		echo "Lo stato del tuo nodo è stato aggiornato correttamente.<br> Ricarica la pagina del mapserver per vedere le modifiche.<br>";
	} else
	/* Delete node */
	if ($_GET["action"] == "del1" ) {
		echo "Sei sicuro di voler cancellare il nodo $id? 
			<form method=post action='manager.php?action=del2'>
			<input type=hidden name=id value=$id><br>
			<input type=submit value='Si'>    <input type=reset value='No' onclick=\"window.close();>
                       <form>"; 
	} else
	if ($_GET["action"] == "del2" ) {

		$query = "SELECT userEmail FROM nodes WHERE id='". $id ."';";
		$result = mysql_query ($query, $connection) or die (mysql_error());
		
		$row = mysql_fetch_assoc($result);
		$mail= $row['userEmail'];	

		mail ($mail, "Ninux node delete!", "Qualcuno, probabilmente tu, ha richiesto di cancellare il nodo ". $_POST["id"] ." \nPer confermare la cencellazione vai al link:\n". MAP_URL . "/manager.php?action=del3&id=". $_POST["id"] ."&hash=". md5(MANAGEMENT_KEY.$id) . "\n\n Se non vuoi cancellare il nodo ignora questa mail.");

		echo "Ti è stata inviata una mail di conferma per cancellare il nodo.";
	} else
	if ($_GET["action"] == "del3" ) {
		//manage.php?action=del3&name=$id&hash=". md5(MANAGEMENT_KEY.$id)
		if (md5(MANAGEMENT_KEY.$id) == $_GET["hash"]) {
			$query = "UPDATE nodes SET status='-2' WHERE id='". $id ."';";
			$result = mysql_query ($query, $connection) or die (mysql_error());
		}
		mail (MANAGEMENT_MAIL, "Node ". $id ." DELETED!", "Il nodo ". $id ." è stato cancellato su richiesta dell'utente.");
		echo "il tuo nodo ". $id ." è stato cancellato";
	}

}
?>


