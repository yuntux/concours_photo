<?php

///////////////////// PARAMETRES /////////////////////////

require('onfig.php'):
$nb_photos = countFiles($_PHOTO_DIRECTORY);
$_USERS = array();

date_default_timezone_set('Europe/Paris');
//////////////////////////////////////////////////////////

if ($_TEST_MOD==1){
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}

header('Content-Type: text/html; charset=utf-8');

/***************** MODEL *****************/
function init_user_list(){
    global $_USERS;
    if (($handle = fopen($_USERS_FILE, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $hash = $data[0];
            $login = $data[1];
            $admin_bool = $data[3];
			if ($admin_bool=="True")
				$admin_bool=True;
            $name = $data[2];
			$temp=array($login,$admin_bool,$name);
            $_USERS[$hash]=$temp;
        }   
        fclose($handle);
    }
}

function exists_user(){
	global $_USERS;
	return array_key_exists(get_session_hash(), $_USERS);
}

function get_user_login(){
	global $_USERS;
	return $_USERS[get_session_hash()][0];
}

function get_admin_status(){
	global $_USERS;
	return $_USERS[get_session_hash()][1];
}

function get_user_name(){
	global $_USERS;
	return $_USERS[get_session_hash()][2];
}


init_user_list()

/***************** CONTROLER *****************/
/*
https://www.dumaine.me/concours_photos/index.php?action=vote&hash=5Br3Kca8sRBCs948
*/

if (isset($_GET['action'])  && get_session_hash() && exists_user()){
	$admin_status = get_admin_status();
	if ($_GET['action']=='list_results' && $admin_status==True)
		list_result();
	if ($_GET['action']=='count_results' && $admin_status==True)
		count_result();
	if ($_GET['action']=='enroll' && $admin_status==True)
		enroll();
	//if ($_GET['action']=='reminder' && $admin_status==True) //envoyer un mail de relance à ceux qui n'ont pas encore voté

	if ($_GET['action']=='vote'){
		if (isset($_POST['submit'])){
			add_vote();
		}else{
			display_form();
		}
	}
} else {
	echo "Pas d'action, pas de hash ou utilsateur inconnu.";
}

function get_session_hash(){
if (isset($_GET['hash']))
	return $_GET['hash'];
else
	if (isset($_POST['hash']))
		return $_POST['hash'];
	else
		return false;
}
function list_result(){
	global $_VOTES_FILE;
	$h = fopen($_VOTES_FILE, "r");
	$content = fread($h, 20000);
	$content = str_replace(PHP_EOL, '<br>', $content);
	echo $content;
}

function send_enrolling_email($to, $hash){
	global $_URL;
	global $_SMTP_HOST;
	global $_SMTP_USERNAME;
	global $_SMTP_PASSWORD;

	$message ="Bonjour ".get_user_name().",";
	$message.="<br><br>Les votes pour le concours photos sont ouverts. <a href=\"".$_URL."?action=vote&hash=".$hash."\">Voici votre lien personnel de vote.</a>";

	if (get_admin_status()) {
		$message.="<br><br><b>Vous êtes administrateur :</b>";
		$message.="<br><a href=\"".$_URL."?action=list_results&hash=".$hash."\">Voir l'historique des votes.</a>";
		$message.="<br><a href=\"".$_URL."?action=count_results&hash=".$hash."\">Voir le classement.</a>";
	}
	$message.="<br><br>Cordialement.<br>La FunTeam";
	require_once 'PHPMailer/PHPMailerAutoload.php';

	$mail = new PHPMailer;
	//$mail->SMTPDebug = 3;                               // Enable verbose debug output
	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = $_SMTP_HOST;  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = $_SMTP_USERNAME;                 // SMTP username
	$mail->Password = $_SMTP_PASSWORD;                           // SMTP password
	$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 587;                                    // TCP port to connect to
	$mail->setFrom('concoursphoto@dumaine.me', 'FunTeam');
	$mail->addAddress($to.'@fontaine-consultants.fr', $to);     // Add a recipient
	$mail->isHTML(true);                                  // Set email format to HTML
	$mail->Subject = 'Concours photos';
	$mail->Body    = $message;
	$mail->SMTPOptions = array(
		'ssl' => array(
			'verify_peer' => false,
			'verify_peer_name' => false,
			'allow_self_signed' => true
		)
	);
	if(!$mail->send()) {
		echo '<br>Message could not be sent.';
		echo '<br>Mailer Error: ' . $mail->ErrorInfo;
	} else {
		echo '<br>Message has been sent to '.$to;
	}
}

function enroll(){
	global $_USERS;
	foreach ($_USERS as $hash => $user_array) {
		if ($user_array[0]=="adumaine"){
			if ($user_array[0] != '') {
				send_enrolling_email($user_array[0], $hash);	
			}
		}
	}
}

function count_result(){
	global $_VOTES_FILE, $nb_photos, $_VOTE_LIMIT;
	$row = 1;
	$user_votes = array();
	if (($handle = fopen($_VOTES_FILE, "r")) !== FALSE) {
	    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
			$date = $data[0]; //unused, file constructed in chronological order
			$user_login = $data[1];
			$temp = array($user_login);
			for($k=2; $k<2+$_VOTE_LIMIT; $k++) { 
				array_push($temp, $data[$k]);
			}
			$user_votes[$user_login] = $temp;
			$row++;
	    }
	    fclose($handle);
	}
	$r = array();
	foreach($user_votes as $key => $data){
			for($k=1; $k<1+$_VOTE_LIMIT; $k++) { 
				if (array_key_exists($data[$k], $r)) {
					$r[$data[$k]] = $r[$data[$k]] + 1;
				} else {
					$r[$data[$k]] = 1;
				}
			} 
	}
	natsort($r);
	$r=array_reverse($r);
	foreach($r as $key => $value){
		echo $key . ' -> ' . $value . '<br>';
	} 
}


function add_vote(){
	global $_VOTE_LIMIT, $nb_photos, $_VOTES_FILE;
	if (sizeof($_POST['vote_checkbox'])!=$_VOTE_LIMIT) {
		echo 'Vote non pris en compte, merci de selectionner exactement '.$_VOTE_LIMIT.' photos.';
	} else {
		$line_to_add = date("d/M/Y H:i:s ").';'.get_user_login();
		for ($i=0 ; $i<$nb_photos ; $i++){
			
			if (isset($_POST['vote_checkbox'][$i]) ){
				 //echo $_POST['vote_checkbox'][$i];
				 $line_to_add .= ';'.$_POST['vote_checkbox'][$i];
			}
		}
		$fp = fopen($_VOTES_FILE,"a");
		fwrite($fp, $line_to_add.PHP_EOL);
		fclose($fp);
		echo "Votre vote a été pris en compte.";
	}
}

/***************** VIEW *****************/
function display_form(){
	global $_URL, $_VOTE_LIMIT, $nb_photos, $_PHOTO_DIRECTORY;

echo '<html>
<head>
	<title>Concours photos</title>
	<meta charset="UTF-8" />
</head>
<body>
	<script type="text/javascript">
	function checkboxlimit(obj){
			var checkedcount=0;
			var limit='.strval($_VOTE_LIMIT).';
			//var last_checked = -1;
			for (var i=0; i<'.strval($nb_photos).'; i++){
				var current_id = "vote_checkbox["+i+"]";
				checkedcount+=(document.getElementById(current_id).checked)? 1 : 0
			}
			if (checkedcount>limit){
				alert("Vous pouvez seulement sélectionner "+limit+" photos !")
				obj.checked=false;
			}
		}
	</script>';

	echo '
	<form name="world" id="world" method="POST" action="'.$_URL.'?hash='.get_session_hash().'&action=vote">
	<h1>Concours photos</h1>
	Bonjour '.get_user_login().' !<br>
	Sélectionnez exactement '.strval($_VOTE_LIMIT).' photos (vote non ordonné) en cochant la case à droite de la photo, et validez le formulaire en bas de page.
    <br> Vous pouvez votez pour votre photo, même si ce n\'est pas très fair-play.
	<br>Vous pouvez voter plusieurs fois. L\'historique de vos votes est conservé. Seul votre dernier vote sera pris en compte lors du dépouillement.
	<br><br>
	<table>';

	if (is_dir($_PHOTO_DIRECTORY)) {
		$i =0;
		if ($dh = opendir($_PHOTO_DIRECTORY)) {
			while (($file = readdir($dh)) !== false) {
			if( $file != '.' && $file != '..') {
			echo '<tr>
				<td><img src="'.$_PHOTO_DIRECTORY.'/'.$file.'" style="width:1200px"><td>
				<td><INPUT type="checkbox" id="vote_checkbox['.strval($i).']" name="vote_checkbox['.strval($i).']" value="'.$file.'" onclick="checkboxlimit(this);" style="width: 50px; height: 50px;"></td>
			</tr>';
			$i = $i+1;
		}
	       }
	       closedir($dh);
	   }
	}

	echo '
	</table>

	<br>
	<input type="submit" name="submit" style="width: 150px; height: 75px; display:block; margin:auto;" value="Voter"/>
	</form>
</body>
</html>';
}

function countFiles($path)
{
    $nbFichiers = 0;
    $repertoire = opendir($path);
                 
    while ($fichier = readdir($repertoire))
    {
        $nbFichiers += 1;
    }
                 
    return (int) $nbFichiers;
}

?>
