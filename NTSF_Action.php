<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Pdftk\Pdftk;
use \setasign\Fpdi\Fpdi;
require_once __DIR__ . '/vendor/autoload.php';

$img = $_POST['ParSig'];
$img = str_replace('data:image/png;base64,', '', $img);
$img = str_replace(' ', '+', $img);
$data = base64_decode($img);
$file = 'ParSig.png';
$success = file_put_contents($file, $data);
$img = $_POST['AdvSig'];
$img = str_replace('data:image/png;base64,', '', $img);
$img = str_replace(' ', '+', $img);
$data = base64_decode($img);
$file = 'AdvSig.png';
$success = file_put_contents($file, $data);

$_POST['NINO1'] = strtoupper($_POST['NINO1']);
$_POST['NINO2'] = strtoupper($_POST['NINO2']);
$_POST['NINO9'] = strtoupper($_POST['NINO9']);
$_POST['InactReason'] .= "|";
$_POST['OtherEvi'] .= "|";
$_POST['Bar1Reason'] = html_entity_decode($_POST['Bar1Reason'],ENT_QUOTES);
$_POST['Bar2Reason'] = html_entity_decode($_POST['Bar2Reason'],ENT_QUOTES);
unset($_POST['event_id']);
unset($_POST['q4_DoB']);
unset($_POST['ParSig']);
unset($_POST['AdvSig']);

$FileDir = $_POST['SName'] . ", " . $_POST['FName'] . ", " . $_POST['NINO1']. $_POST['NINO2']. $_POST['NINO3']. $_POST['NINO4']. $_POST['NINO5']. $_POST['NINO6']. $_POST['NINO7']. $_POST['NINO8']. $_POST['NINO9'];

$pdf = new FPDM('NTSFTemplate.pdf');
$pdf->Load($_POST, false);
$pdf->Merge();
$pdf->Flatten();
$pdf->Output("F",'tempfilled.pdf');

$pdfa = new Fpdi();
$pageCount = $pdfa->setSourceFile(FPDM_CACHE."pdf_flatten.pdf");
for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $templateId = $pdfa->importPage($pageNo);
    $pdfa->AddPage();
    $pdfa->useTemplate($templateId, ['adjustPageSize' => true]);
	if($pageNo == 6){
		$pdfa->Image('ParSig.png',53,35,-290,-290,'PNG');
		$pdfa->Image('ParSig.png',65,245,-290,-290,'PNG');
		$pdfa->Image('AdvSig.png',55,63,-290,-290,'PNG');
	}
}
$pdfa->Image('AdvSig.png',50,245,-290,-290,'PNG');
$pdfa->Image('ParSig.png',65,255,-290,-290,'PNG');
$pdfa->Output("F",'tempfilled.pdf');  

mkdir("data_files/" . $FileDir);
copy('tempfilled.pdf',"data_files/" . $FileDir . "/Start Payment Document.pdf");

$CSVfile = fopen('ClaimInfo.csv', 'a');
fputcsv($CSVfile, array($_POST['FName'] . " " . $_POST['SName'],  
	$_POST['CurYear'] . "/" . $_POST['CurMonth'] . "/" . $_POST['CurDay'],
	'Column 3', 
	'Column 4',
	'Column 5'));
fclose($file);

//Send the appropriate files by email
$mail = new PHPMailer;
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->Port = 587;
$mail->SMTPSecure = 'tls';
$mail->SMTPAuth = true;
$mail->Username = "sparklethekitteh@googlemail.com";
$mail->Password = "nigk35zc";
$mail->setFrom('noreply@momentumskills.org', 'Momentum Skills');
$mail->addReplyTo('noreply@momentumskills.org', 'Stephen Wilkie');
$mail->addAddress($_SESSION['AdvName'], 'Advisor');
$mail->Subject = $FileDir;
$mail->Body = 'Hi There, <br /><br />Please find attached the compiled document. Please ensure that this is uploaded to the PICS system for your local admin to verify.<br /><br />Kind Regards<br /><br />Momentum Skills';
$mail->AltBody = 'Please find attached the compiled document. Please ensure that this is uploaded to the PICS system for your local admin to verify.';
$mail->addAttachment("data_files/" . $FileDir . "/Start Payment Document.pdf");
if (!$mail->send()) {
    echo "Mailer Error: ";
} else {
    header("Location:index.html"); 
	exit();
}

?>