<?php
include "smtpmail/classes/class.phpmailer.php"; // include the class name
$mail = new PHPMailer(); // create a new object
$mail->IsSMTP(); // enable SMTP
$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
$mail->SMTPAuth = true; // authentication enabled
$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for GMail
$mail->Host = "smtp.gmail.com";
$mail->Port = 465; // or 587
$mail->IsHTML(true);
$mail->Username = "aranlucasspam@gmail.com";
$mail->Password = "sacul123";
//$mail->SetFrom("Reminder");
//$mail->Subject = "Your Gmail SMTP Mail";
//$mail->Body = $_POST['message'];
//$mail->AddAddress($_POST['email']);
$carrier= array(
		'@messaging.sprintpcs.com',
		'@vtext.com',
		'@tmomail.net',
		'@txt.att.net',
		'@mymetropcs.com'
);

$recipients = array();
$number=$_POST['email'];
foreach($carrier as $addr){
	$string= $number.$addr;
	$recipients[]=$string;
}
?>
<pre>
    <?php
    print_r($recipients);
    ?>
    </pre>
<?php

foreach($recipients as $email){
	// it will display the emails of all users in their Mailbox 'To' area. Simple multiple mail.
	$mail->AddAddress($email); //To address who will receive this email
	$mail->MsgHTML($_POST['message']); //Put your body of the message you can place html code here
	$send = $mail->Send(); //Send the mails
	// if you want to does not show other users email addresses like newsletter, daily, weekly, subscription emails means use the below line to clear previous email address
	$mail->ClearAddresses();
}

if($send){
	echo '<center><h3 style="color:#009933;">Mail sent successfully</h3></center>';
}
else{
	echo '<center><h3 style="color:#FF3300;">Mail error: </h3></center>'.$mail->ErrorInfo;
}

//mail("tt.net", "", "Your packaged has arrived!", "From: David Walsh <david@davidwalsh.name>\r\n");

?>
<!-- if(!$mail->Send()){ -->
<!-- 	echo "Mailer Error: " . $mail->ErrorInfo; -->
<!-- } -->
<!-- else{ -->
<!-- 	echo "Message has been sent"; -->
<!-- } -->
<!-- ?> -->
