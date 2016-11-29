<?php

///////////////////// PARAMETRES /////////////////////////

date_default_timezone_set('Europe/Paris');
require_once('./config.php');
$nb_photos = countFiles($_PHOTO_DIRECTORY);
$_USERS = array();

$VOTE_END_DATE = strtotime($VOTE_END_DATE);
$VOTE_END_DATE_STRING = date('d/m/Y à  H\hi',$VOTE_END_DATE);

//////////////////////////////////////////////////////////

if ($_TEST_MOD==1){
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}

header('Content-Type: text/html; charset=utf-8');

/***************** MODEL *****************/
function init_user_list(){
    global $_USERS;
    global $_USERS_FILE;
    if (($handle = fopen($_USERS_FILE, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $hash = $data[0];
            $login = $data[1];
            $admin_bool = $data[2];
			if ($admin_bool=="True")
				$admin_bool=True;
			else
				$admin_bool=False;
            $name = $data[3];
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


init_user_list();
//var_dump($_USERS);

/***************** CONTROLER *****************/
/*
https://www.dumaine.me/concours_photos/index.php?action=vote&hash=5Br3Kca8sRBCs948
*/

if (isset($_GET['action'])  && get_session_hash() && exists_user()){
	$admin_status = get_admin_status();
	if ($_GET['action']=='list_results')
		if ($admin_status==True)
			list_result();
		else
			admin_required_view();
	if ($_GET['action']=='count_results')
		if ($admin_status==True)
			count_result();
		else
			admin_required_view();
	if ($_GET['action']=='enroll')
		if ($admin_status==True)
			enroll();
		else
			admin_required_view();
	//if ($_GET['action']=='reminder' && $admin_status==True) //envoyer un mail de relance à ceux qui n'ont pas encore voté

	if ($_GET['action']=='vote'){
		if ($VOTE_END_DATE < strtotime('now')) {
			vote_closed_view();
		} else { 
			if (isset($_POST['submit'])){
				add_vote();
			}else{
				display_form();
			}
		}
	}
} else {
	echo "Pas d'action, pas de hash ou utilsateur inconnu.";
}

function vote_closed_view(){
	echo 'Le vote est fermé.';
}
function admin_required_view(){
	echo 'Droits administrateur requis. Tu n\'as pas de droits  administrateur '.get_user_name().'. Il ne faut pas croire tout ce que tu lis dans tes emails. ;-)';
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
	global $VOTE_END_DATE_STRING;

	$message ="Bonjour,";
	$message.="<br><br>Les votes pour le concours photos sont ouverts jusqu'au ".$VOTE_END_DATE_STRING.". <a href=\"".$_URL."?action=vote&hash=".$hash."\">Voici votre lien personnel de vote.</a>";

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
		echo $message;
		echo "==============================";
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
	global $_URL, $_VOTE_LIMIT, $nb_photos, $_PHOTO_DIRECTORY, $VOTE_END_DATE_STRING;

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

	if (get_admin_status()==True) {
		echo '<br><br><b>Menu administrateur :</b>
		<br><a href="'.$_URL.'?action=list_results&hash='.get_session_hash().'">Voir l\'historique des votes.</a>
		<br><a href="'.$_URL.'?action=count_results&hash='.get_session_hash().'">Voir le classement.</a>
		<br><br>';
	}

	echo '
	<form name="world" id="world" method="POST" action="'.$_URL.'?hash='.get_session_hash().'&action=vote">
	<h1>Concours photos</h1>
	Bonjour '.get_user_name().' !<br>
	Sélectionne exactement '.strval($_VOTE_LIMIT).' photos (vote non ordonné) en cochant la case à droite de la photo, et valide le formulaire en bas de page.
    <br>Tu peux voter pour ta photo, même si ce n\'est pas très fair-play.
	<br>Le vote est ouvert jusqu\'au '.$VOTE_END_DATE_STRING.'. Tu peux voter plusieurs fois. L\'historique de tes votes est conservé. Seul ton dernier vote sera pris en compte lors du dépouillement.
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
