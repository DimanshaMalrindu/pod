<?php
session_start();
error_reporting(E_ERROR | E_PARSE);

header('Content-type: text/html; charset=utf-8');


#########################################################################
#	Kontaktformular.com         					                                #
#	http://www.kontaktformular.com        						                    #
#	All rights by KnotheMedia.de                                    			#
#-----------------------------------------------------------------------#
#	I-Net: http://www.knothemedia.de                            					#
#########################################################################
// It´s not allowed to remove the copyright notice!


  $script_root = substr(__FILE__, 0,
                        strrpos(__FILE__,
                                DIRECTORY_SEPARATOR)
                       ).DIRECTORY_SEPARATOR;

require_once $script_root.'upload.php';

$remote = getenv("REMOTE_ADDR");

function encrypt($string, $key) {
$result = '';
for($i=0; $i<strlen($string); $i++) {
   $char = substr($string, $i, 1);
   $keychar = substr($key, ($i % strlen($key))-1, 1);
   $char = chr(ord($char)+ord($keychar));
   $result.=$char;
}
return base64_encode($result);
}
$sicherheits_eingabe = encrypt($_POST["securitycode"], "8h384ls94");
$sicherheits_eingabe = str_replace("=", "", $sicherheits_eingabe);

@require('config.php');

if ($_POST['delete'])
{
unset($_POST);
}

// take over the data from the formular
if ($_POST["mt-mk"]) {

// variables of the data fields
   $name      = $_POST["name"];
   $email      = $_POST["email"];
   $phonenumber = $_POST["phonenumber"];
   $place   = $_POST["place"];
   $subject   = $_POST["subject"];
   $message   = $_POST["message"];
   $securitycode   = $_POST["securitycode"];
   $date = date("d.m.Y | H:i");
   $ip = $_SERVER['REMOTE_ADDR']; 
   $UserAgent = $_SERVER["HTTP_USER_AGENT"];
   $host = getHostByAddr($remote);


// examination of the data fields
$name = stripslashes($name);
$email = stripslashes($email);
$subject = stripslashes($subject);
$message = stripslashes($message);
 

if(!$name) {
 
 $fehler['name'] = "<span class='errormsg'>Please enter your <strong>name</strong>.</span>";
 
}


if (!preg_match("/^[0-9a-zA-ZÄÜÖ_.-]+@[0-9a-z.-]+\.[a-z]{2,6}$/", $email)) {
   $fehler['email'] = "<span class='errormsg'>Please enter a <strong>e-mail-address</strong>.</span>";
}

 
if(!$subject) {
 
 $fehler['subject'] = "<span class='errormsg'>Please enter a <strong>subject</strong>.</span>";
 
 
}
 
if(!$message) {
 
 $fehler['message'] = "<span class='errormsg'>Please enter a <strong>message</strong>.</span>";
 
 
}

if($sicherheits_eingabe != $_SESSION['captcha_spam']){
unset($_SESSION['captcha_spam']);
   $fehler['captcha'] = "<span class='errormsg'>You entered a <strong>wrong code</strong>.</span>";
   }

    if (!isset($fehler) || count($fehler) == 0) {
      $error             = false;
      $errorMessage      = '';
      $uploadErrors      = array();
      $uploadedFiles     = array();
      $totalUploadSize   = 0;

      if ($cfg['UPLOAD_ACTIVE'] && in_array($_SERVER['REMOTE_ADDR'], $cfg['BLACKLIST_IP']) === true) {
          $error = true;
          $fehler['upload'] = "<span class='errormsg'>You have no authorization to upload files.</span>";
      }

      if (!$error) {
          for ($i=0; $i < $cfg['NUM_ATTACHMENT_FIELDS']; $i++) {
              if ($_FILES['f']['error'][$i] == UPLOAD_ERR_NO_FILE) {
                  continue;
              }

              $extension = explode('.', $_FILES['f']['name'][$i]);
              $extension = strtolower($extension[count($extension)-1]);
              $totalUploadSize += $_FILES['f']['size'][$i];

              if ($_FILES['f']['error'][$i] != UPLOAD_ERR_OK) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  switch ($_FILES['f']['error'][$i]) {
                      case UPLOAD_ERR_INI_SIZE :
                          $uploadErrors[$j]['error'] = 'the file is too big (PHP-Ini directive).';
                      break;
                      case UPLOAD_ERR_FORM_SIZE :
                          $uploadErrors[$j]['error'] = 'the file is too big (MAX_FILE_SIZE in HTML-Formular).';
                      break;
                      case UPLOAD_ERR_PARTIAL :
						  if ($cfg['UPLOAD_ACTIVE']) {
                          	  $uploadErrors[$j]['error'] = 'the file has been uploaded partially.';
						  } else {
							  $uploadErrors[$j]['error'] = 'the file has been sent partially.';
					  	  }
                      break;
                      case UPLOAD_ERR_NO_TMP_DIR :
                          $uploadErrors[$j]['error'] = 'No temporarily folder has been found.';
                      break;
                      case UPLOAD_ERR_CANT_WRITE :
                          $uploadErrors[$j]['error'] = 'error during saving the file.';
                      break;
                      case UPLOAD_ERR_EXTENSION  :
                          $uploadErrors[$j]['error'] = 'unknown error due to an extension.';
                      break;
                      default :
						  if ($cfg['UPLOAD_ACTIVE']) {
                          	  $uploadErrors[$j]['error'] = 'unknown error on uploading.';
						  } else {
							  $uploadErrors[$j]['error'] = 'unknown error on sending the email attachments.';
						  }
                  }

                  $j++;
                  $error = true;
              }
              else if ($totalUploadSize > $cfg['MAX_ATTACHMENT_SIZE']*1024) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'maximum upload reached ('.$cfg['MAX_ATTACHMENT_SIZE'].' KB).';
                  $j++;
                  $error = true;
              }
              else if ($_FILES['f']['size'][$i] > $cfg['MAX_FILE_SIZE']*1024) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'The file is too big (max. '.$cfg['MAX_FILE_SIZE'].' KB).';
                  $j++;
                  $error = true;
              }
              else if (!empty($cfg['BLACKLIST_EXT']) && strpos($cfg['BLACKLIST_EXT'], $extension) !== false) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'the file extension is not permitted.';
                  $j++;
                  $error = true;
              }
              else if (preg_match("=^[\\:*?<>|/]+$=", $_FILES['f']['name'][$i])) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'invalid symbols in the file name (\/:*?<>|).';
                  $j++;
                  $error = true;
              }
              else if ($cfg['UPLOAD_ACTIVE'] && file_exists($cfg['UPLOAD_FOLDER'].'/'.$_FILES['f']['name'][$i])) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'the file already exist.';
                  $j++;
                  $error = true;
              }
              else {
				  if ($cfg['UPLOAD_ACTIVE']) {
                     move_uploaded_file($_FILES['f']['tmp_name'][$i], $cfg['UPLOAD_FOLDER'].'/'.$_FILES['f']['name'][$i]);	
				  }
                  $uploadedFiles[$_FILES['f']['tmp_name'][$i]] = $_FILES['f']['name'][$i];
              }
          }
      }

      if ($error) {
          $errorMessage = 'following errors occured when sending the contact formular:'."\n";
          if (count($uploadErrors) > 0) {
              foreach ($uploadErrors as $err) {
                  $tmp .= '<strong>'.$err['name']."</strong><br/>\n- ".$err['error']."<br/><br/>\n";
              }
              $tmp = "<br/><br/>\n".$tmp;
          }
          $errorMessage .= $tmp.'';
          $fehler['upload'] = $errorMessage;
      }
  }


// if no error, an email will be sent
   if (!isset($fehler))
   {
		// ------------------------------------------------------------
		// -------------------- send mail to admin --------------------
		// ------------------------------------------------------------
	   
		// ---- create mail-message for admin
	  
	  $mailcontent  = "The following has been sent by the contact form:\n" . "-------------------------------------------------------------------------\n\n";
   $mailcontent .= "Name: " . $name . "\n";
   $mailcontent .= "E-Mail: " . $email . "\n\n";
   $mailcontent .= "Phone Number: " . $phonenumber . "\n";
   $mailcontent .= "Place: " . $place . "\n";
   $mailcontent .= "\nSubject: " . $subject . "\n";
   $mailcontent .= "Message:\n" . $_POST['message'] = preg_replace("/\r\r|\r\n|\n\r|\n\n/","\n",$_POST['message']) . "\n\n";

		if(count($uploadedFiles) > 0){
			if($cfg['UPLOAD_ACTIVE']){
				$mailcontent .= 'The following files have been uploaded:'."\n";
				foreach ($uploadedFiles as $filename) {
					$mailcontent .= ' - '.$cfg['DOWNLOAD_URL'].'/'.$cfg['UPLOAD_FOLDER'].'/'.$filename."\n";
				}
			} else {
				$mailcontent .= 'The following files have been attached:'."\n";
				foreach ($uploadedFiles as $filename) {
					$mailcontent .= ' - '.$filename."\n";
				}
			}
		}
		$mailcontent .= "\n\nIP address: " . $ip . "\n";
		$mailcontent = strip_tags ($mailcontent);
	   
		// ---- get attachments for admin
		$attachments = array();
		if(!$cfg['UPLOAD_ACTIVE'] && count($uploadedFiles) > 0){
			foreach($uploadedFiles as $tempFilename => $filename) {
				$attachments[$filename] = file_get_contents($tempFilename);	
			}
		}
		
		// ---- send mail to admin
$success = sendMyMail($email, $name, $empfaenger, $subject, $mailcontent, $attachments);
		
		// ------------------------------------------------------------
		// ------------------- send mail to customer ------------------
		// ------------------------------------------------------------
		if($success){
			
			// ---- create mail-message for customer
		
		
			$mailcontent  = "Thank you very much for your e-mail. We will reply as fast as we can.\n\n";
  $mailcontent .= "Summary: \n" .
  "-------------------------------------------------------------------------\n\n";
  $mailcontent .= "Name: " . $name . "\n";
  $mailcontent .= "E-Mail: " . $email . "\n\n";
  $mailcontent .= "Phone Number: " . $phonenumber . "\n";
  $mailcontent .= "Place: " . $place . "\n";
  $mailcontent .= "\nSubject: " . $subject . "\n";
  $mailcontent .= "Message:\n" . str_replace("\r", "", $message) . "\n\n";
			if(count($uploadedFiles) > 0){
				$mailcontent .= 'You have assigned the following files:'."\n";
				foreach($uploadedFiles as $file){
					$mailcontent .= ' - '.$file."\n";
				}
			}
			$mailcontent = strip_tags ($mailcontent);	
			

			
			// ---- send mail to customer
$success = sendMyMail($empfaenger, $ihrname, $email, "Your request", $mailcontent);
			echo "<META HTTP-EQUIV=\"refresh\" content=\"0;URL=".$danke."\">";
			exit;
		}
	}
}
// clean post
foreach($_POST as $key => $value){
    $_POST[$key] = htmlentities($value, ENT_QUOTES, "UTF-8");
}
?>
<?php




function sendMyMail($fromMail, $fromName, $toMail, $subject, $content, $attachments=array()){
	
	$boundary = md5(uniqid(time()));
	$eol = PHP_EOL;
	
	// header
	$header = "From: =?UTF-8?B?".base64_encode(stripslashes($fromName))."?= <".$fromMail.">".$eol;
	$header .= "Reply-To: <".$fromMail.">".$eol;
	$header .= "MIME-Version: 1.0".$eol;
	if(is_array($attachments) && 0<count($attachments)){
		$header .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"";
	}
	else{
		$header .= "Content-type: text/plain; charset=utf-8";
	}
	
	
	// content with attachments
	if(is_array($attachments) && 0<count($attachments)){
		
		// content
		$message = "--".$boundary.$eol;
		$message .= "Content-type: text/plain; charset=utf-8".$eol;
		$message .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
		$message .= $content.$eol;

		// attachments
		foreach($attachments as $filename=>$filecontent){
			$filecontent = chunk_split(base64_encode($filecontent));
			$message .= "--".$boundary.$eol;
			$message .= "Content-Type: application/octet-stream; name=\"".$filename."\"".$eol;
			$message .= "Content-Transfer-Encoding: base64".$eol;
			$message .= "Content-Disposition: attachment; filename=\"".$filename."\"".$eol.$eol;			
			$message .= $filecontent.$eol;
		}
		$message .= "--".$boundary."--";
	}
	// content without attachments
	else{
		$message = $content;
	}
	
	// subject
	$subject = "=?UTF-8?B?".base64_encode($subject)."?=";
	
	// send mail
	return mail($toMail, $subject, $message, $header);
}

?>
<!DOCTYPE html>
<html lang="de-DE">
	<head>
		<meta charset="utf-8">
		<meta name="language" content="de"/>
		<meta name="description" content="kontaktformular.com"/>
		<meta name="revisit" content="After 7 days"/>
		<meta name="robots" content="INDEX,FOLLOW"/>
		<title>jetwing</title>
		<link href="style-contact-form.css" rel="stylesheet" type="text/css" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
	</head>
	<body id="Kontaktformularseite">
		<form class="kontaktformular" action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data">
			<input type="hidden" name="action" value="smail" />
			<input type="hidden" name="content" value="formular"/>

			<fieldset class="pickupdetails">
				<legend>pickup details</legend>
				<div class="row">	
				<!-- pickup time = name -->
					<label>Pickup Time: <span class="pflichtfeld">*</span></label>
					<div class="field">
						<?php if ($fehler["pickuptime"] != "") { echo $fehler["pickuptime"]; } ?><input type="text" name="pickuptime" maxlength="50" id="textfield" value="<?php echo $_POST[pickuptime]; ?>"  <?php if ($fehler["pickuptime"] != "") { echo 'class="errordesignfields"'; } ?>/>
						
					</div>
				</div>
				<div class="row">	
					<label>Pickup From: <span class="pflichtfeld">*</span></label>
					<div class="field">
							<?php if ($fehler["pickupfrom"] != "") { echo $fehler["pickupfrom"]; } ?><input type="text" name="pickupfrom" maxlength="50" value="<?php echo $_POST[pickupfrom]; ?>"  <?php if ($fehler["pickupfrom"] != "") { echo 'class="errordesignfields"'; } ?>/>
					
					</div>
				<!--
				</div>
				<div class="row">	
					<label>Place: </label>
					<div class="field">
						<input type="text" name="place" maxlength="50" value="<?php echo $_POST[place]; ?>"  />
					</div>
				</div>
				
				<div class="row">	
					<label>Phone Number: </label>
					<div class="field">
						<input type="text" name="phonenumber" maxlength="50" value="<?php echo $_POST[phonenumber]; ?>"  />
					</div>
				</div>
				-->
			</fieldset>
			<fieldset class="general">
				<legend>general</legend>
				<div class="row">
					<label>Date: <span class="pflichtfeld">*</span></label>
					<div class="field">
						<?php if ($fehler["CompanyName"] != "") { echo $fehler["CompanyName"]; } ?><input type="text" name="CompanyName" maxlength="50" value="<?php echo $_POST[CompanyName]; ?>"  <?php if ($fehler["CompanyName"] != "") { echo 'class="errordesignfields"'; } ?>/>
						
					</div>

				<div class="row">
					<label>pod number: <span class="pflichtfeld">*</span></label>
					<div class="field">
						<?php if ($fehler["PODnumber"] != "") { echo $fehler["PODnumber"]; } ?><input type="text" name="PODnumber" maxlength="50" value="<?php echo $_POST[PODnumber]; ?>"  <?php if ($fehler["PODnumber"] != "") { echo 'class="errordesignfields"'; } ?>/>
						
					</div>
				</div>
				</div>
				
			</fieldset>
			<fieldset class="destination">
				<legend>destination details</legend>
				<div class="row">	
				<!-- pickup time = name -->
					<label>Terminated Address: <span class="pflichtfeld">*</span></label>
					<div class="field">
						<?php if ($fehler["terminatedaddress"] != "") { echo $fehler["terminatedaddress"]; } ?><input type="text" name="terminatedaddress" maxlength="50" id="textfield" value="<?php echo $_POST[terminatedaddress]; ?>"  <?php if ($fehler["terminatedaddress"] != "") { echo 'class="errordesignfields"'; } ?>/>
						
					</div>
				</div>
				<div class="row">	
					<label>City/Zone: <span class="pflichtfeld">*</span></label>
					<div class="field">
							<?php if ($fehler["cityzone"] != "") { echo $fehler["cityzone"]; } ?><input type="text" name="cityzone" maxlength="50" value="<?php echo $_POST[cityzone]; ?>"  <?php if ($fehler["cityzone"] != "") { echo 'class="errordesignfields"'; } ?>/>
					
					</div>
				<!--
				</div>
				<div class="row">	
					<label>Place: </label>
					<div class="field">
						<input type="text" name="place" maxlength="50" value="<?php echo $_POST[place]; ?>"  />
					</div>
				</div>
				
				<div class="row">	
					<label>Phone Number: </label>
					<div class="field">
						<input type="text" name="phonenumber" maxlength="50" value="<?php echo $_POST[phonenumber]; ?>"  />
					</div>
				</div>
				-->
			</fieldset>
			<fieldset class="Delivery">
				<legend>delivery details</legend>
				<div class="row">	
				<!-- pickup time = name -->
					<label>cod / non cod: <span class="pflichtfeld">*</span></label>
					<div class="field">
						<?php if ($fehler["terminatedaddress"] != "") { echo $fehler["terminatedaddress"]; } ?><input type="text" name="terminatedaddress" maxlength="50" id="textfield" value="<?php echo $_POST[terminatedaddress]; ?>"  <?php if ($fehler["terminatedaddress"] != "") { echo 'class="errordesignfields"'; } ?>/>
						
					</div>
				</div>
				<div class="row">	
					<label>Amount: <span class="pflichtfeld">*</span></label>
					<div class="field">
							<?php if ($fehler["amount"] != "") { echo $fehler["amount"]; } ?><input type="text" name="amount" maxlength="50" value="<?php echo $_POST[amount]; ?>"  <?php if ($fehler["amount"] != "") { echo 'class="errordesignfields"'; } ?>/>
					
					</div>
				
				</div>
				<div class="row">	
					<label>Distribution Officer: <span class="pflichtfeld">*</span></label>
					<div class="field">
							<?php if ($fehler["distributionofficer"] != "") { echo $fehler["distributionofficer"]; } ?><input type="text" name="distributionofficer" maxlength="50" value="<?php echo $_POST[distributionofficer]; ?>"  <?php if ($fehler["distributionofficer"] != "") { echo 'class="errordesignfields"'; } ?>/>
					
					</div>
				
				</div>
				<div class="row">	
					<label>Return: <span class="pflichtfeld">*</span></label>
					<div class="field">
							<?php if ($fehler["returnn"] != "") { echo $fehler["returnn"]; } ?><input type="text" name="returnn" maxlength="50" value="<?php echo $_POST[returnn]; ?>"  <?php if ($fehler["returnn"] != "") { echo 'class="errordesignfields"'; } ?>/>
					
					</div>
				
				</div>
				<div class="row">	
					<label>Next Day Delivery: <span class="pflichtfeld">*</span></label>
					<div class="field">
							<?php if ($fehler["nextdaydelivery"] != "") { echo $fehler["nextdaydelivery"]; } ?><input type="text" name="nextdaydelivery" maxlength="50" value="<?php echo $_POST[nextdaydelivery]; ?>"  <?php if ($fehler["nextdaydelivery"] != "") { echo 'class="errordesignfields"'; } ?>/>
					
					</div>
				
				</div>
				<div class="row">	
					<label>Delivery in Progress: <span class="pflichtfeld">*</span></label>
					<div class="field">
							<?php if ($fehler["deliveryinprogress"] != "") { echo $fehler["deliveryinprogress"]; } ?><input type="text" name="deliveryinprogress" maxlength="50" value="<?php echo $_POST[deliveryinprogress]; ?>"  <?php if ($fehler["deliveryinprogress"] != "") { echo 'class="errordesignfields"'; } ?>/>
					
					</div>
				
				</div>
				<div class="row">	
					<label>Delivery Done: <span class="pflichtfeld">*</span></label>
					<div class="field">
							<?php if ($fehler["deliverydone"] != "") { echo $fehler["deliverydone"]; } ?><input type="text" name="deliverydone" maxlength="50" value="<?php echo $_POST[deliverydone]; ?>"  <?php if ($fehler["deliverydone"] != "") { echo 'class="errordesignfields"'; } ?>/>
					
					</div>
				
				</div>
				<!--
				<div class="row">	
					<label>Place: </label>
					<div class="field">
						<input type="text" name="place" maxlength="50" value="<?php echo $_POST[place]; ?>"  />
					</div>
				</div>
				
				<div class="row">	
					<label>Phone Number: </label>
					<div class="field">
						<input type="text" name="phonenumber" maxlength="50" value="<?php echo $_POST[phonenumber]; ?>"  />
					</div>
				</div>
				-->
			</fieldset>
		 
			<?php
			if(0<$cfg['NUM_ATTACHMENT_FIELDS']){
				echo '<fieldset class="upload">';
				echo '<legend>Attachment</legend>';
				  for ($i=0; $i < $cfg['NUM_ATTACHMENT_FIELDS']; $i++) {
					  echo '<div class="row"><label>File</label><div class="field"><input type="file" size="12" name="f[]" /></div></div>';
				  }
			   echo '</fieldset>';
			}
			?>

			<fieldset class="captcha">
				<legend>spam-protection</legend>
				<div class="row">
					<label>Security Code </label>
					<div class="field">
						<img src="captcha/captcha.php" alt="Security-Code" title="kontaktformular.com-sicherheitscode" id="captcha" />
						<a href="javascript:void(0);" onclick="javascript:document.getElementById('captcha').src='captcha/captcha.php?'+Math.random();cursor:pointer;">
							<span class="captchareload"><img src="icon-kf.gif" alt="reload security code" title="reload picture" /></span>
						</a>
					</div>
				</div>
				<div class="row">
					<label>Please enter: <span class="pflichtfeld">*</span></label>
					<div class="field">
						<?php if ($fehler["captcha"] != "") { echo $fehler["captcha"]; } ?><input type="text" name="securitycode" maxlength="150" value=""  <?php if ($fehler["captcha"] != "") { echo 'class="errordesignfields"'; } ?>/>
						
					</div>
				</div>
			</fieldset>

<!--
			<fieldset>
			   <legend>your action</legend>
			   <div class="pflichtfeldhinweis">Advice: Fields with <span class="pflichtfeld">*</span> have to be filled.</div>
			   <div class="buttons"><input type="submit" name="mt-mk" value="Send" onclick="tescht();"/>
			   <input type="submit" name="delete" value="Delete" /></div>
			</fieldset>
	-->	
			<div class="copyright"><!-- It´s not allowed to remove the copyright notice! --><strong><a href="http://www.kontaktformular.com/en" title="Contact Form">Contact Form</a></strong>: <a href="http://www.kontaktformular.com/en">&copy; crystalcodesolutions.com</a></div>
		</form>
	</body>
</html>