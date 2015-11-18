<?php

///////////////////// PARAMETRES /////////////////////////

$_PHOTO_DIRECTORY = "photos";
$_VOTE_LIMIT = 2;
$_TEST_MOD = 1;
$nb_photos = countFiles($_PHOTO_DIRECTORY);
$_VOTES_FILE ='results.csv';
$_URL = 'https://www.dumaine.me/concours_photo/index.php';
$_USERS = array();
$_USERS['g5tQ8XRq6m8E97cR']=array('nadiceam', False);
$_USERS['2Egx25e9pP8qQP2Z']=array('fbrechet', False);
$_USERS['J9h5PGt8Pc3np2U6']=array('schouikhi', False);
$_USERS['NHAQ5kn4p9dZu339']=array('dcoq', False);
$_USERS['UtkbHut27W864VH5']=array('mdiwani', False);
$_USERS['5Br3Kca8sRBCs948']=array('adumaine', True);
$_USERS['7kDEWn8y95F58vuZ']=array('ofafin', False);
$_USERS['aN8Jk95ZcwPw53Z2']=array('pgedalge', False);
$_USERS['4BDw4J8jkHW294xx']=array('dgelrubin', False);
$_USERS['96eJ9rTB4QHxg23f']=array('lhersant', False);
$_USERS['A82q45Zq3JrE9Msm']=array('fjekot', False);
$_USERS['z3udLJB49i8EHq87']=array('gleguennec', False);
$_USERS['TLDjqm4H6i85g84D']=array('clemoine', False);
//$_USERS['V59z55Xxa2Q3FkFp']=array('aleymarie', False); //=>code de test diffusé
$_USERS['f7u7Dpz2UN8i68WC']=array('aleymarie', False);
$_USERS['7h5ZMfH4RcDt249v']=array('jmarrakchi', False);
$_USERS['Z2f63Dts278qfGZQ']=array('bmazoyer', False);
$_USERS['z57dS9Ge6bMg9L6Z']=array('tmidon', False);
$_USERS['mHDu432Xy422TRur']=array('ymuller', False);
$_USERS['f25y8YWig229EnUS']=array('hpourchot', False);
$_USERS['hwsmHn7G9UBX4399']=array('jratier', False);
$_USERS['5Ef5vdN74z7q2RSQ']=array('tsoumeillan', False);
$_USERS['vTwP783Tkv6N4V4r']=array('wthis', False);
$_USERS['JreE322m7NpuA94Q']=array('mvandeville', False);
$_USERS['K87G74rzZ2Vs5dPs']=array('', False);
$_USERS['2ggJzaH568R3z8EK']=array('', False);
$_USERS['Wbn9nQ3S6qVx9K96']=array('', False);
$_USERS['3C68fUVpX667ceHj']=array('', False);
$_USERS['g64VgdbC98uB4T5Z']=array('', False);
$_USERS['5W54sd7W3x3YELdu']=array('', False);

//////////////////////////////////////////////////////////

if ($_TEST_MOD==1){
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}

header('Content-Type: text/html; charset=utf-8');

/***************** MODEL *****************/
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
/*
if ($_GET['action']=='vote'){
	if (isset($_POST['submit'])){
		add_vote();
	}else{
		display_form();
	}
}
*/
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
	$message ="Bonjour,";
	$message.="<br><br>Les votes pour le concours photos sont ouverts : <a href=\"".$_URL."?action=vote&hash=".$hash."\">lien vers le vote</a>.";
	$message.="<br>Attention : ce lien vous est personnel. Merci de ne pas le diffuser.";
	$message.="<br><br>Cordialement.<br>La FunTeam";
	require_once 'PHPMailer/PHPMailerAutoload.php';

	$mail = new PHPMailer;
	//$mail->SMTPDebug = 3;                               // Enable verbose debug output
	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = 'dumaine.me';  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = 'concoursphoto';                 // SMTP username
	$mail->Password = 'WSL66Tv3qt3J9k2v';                           // SMTP password
	$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 587;                                    // TCP port to connect to
	$mail->setFrom('concoursphoto@dumaine.me', 'FunTeam');
	$mail->addAddress($to.'@fontaine-consultants.fr', $to);     // Add a recipient
	$mail->isHTML(true);                                  // Set email format to HTML
	$mail->Subject = 'Concours photos';
	$mail->Body    = $message;
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
/*
		if ($user_array[0]=="adumaine"){
			if ($user_array[0] != '') {
				send_enrolling_email($user_array[0], $hash);	
			}
		}
*/
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
			for($k=0; $k<$_VOTE_LIMIT; $k++) { 
				if (array_key_exists($data[$k], $r)) {
					$r[$data[$k]] = $r[$data[$k]] + 1;
				} else {
					$r[$data[$k]] = 1;
				}
			} 
	}
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
	Sélectionnez '.strval($_VOTE_LIMIT).' photos (vote non ordonné), et validez le formulaire en bas de page.
	<br>Vous pouvez voter plusieurs fois. Seul votre dernier vote sera pris en compte lors du dépouillement.
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
