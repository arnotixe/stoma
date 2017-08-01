<?php

// Generate QR images with phpqrcode.sourceforge.net

	include('phpqrcode/qrlib.php');
	$param = $_GET['c']; // content to qrencode

// we need to be sure ours script does not output anything!!!
    // otherwise it will break up PNG binary!
    
//    ob_start("callback");
    
    // here DB request or some processing
//    $codeText = 'DEMO - '.$param;
    $codeText = $param;
    
    // end of processing here
    $debugLog = ob_get_contents();
    ob_end_clean();
    
    // outputs image directly into browser, as PNG stream
//    QRcode::png($codeText);

//bad as GET isn't checked for injection attacks FIXME
//    QRcode::png($codeText, "uploads/qr_$_GET[c].png", QR_ECLEVEL_H); //http://phpqrcode.sourceforge.net/examples/index.php?example=006
// hmm that needs saving to file... unconvenient

 if ($_GET["d"] == "1") {
        header("Content-Type: application/octet-stream");    //
        // tell the thing the filesize
//        header("Content-Length: " . filesize($download_path.$file));    
        // set it as an attachment and give a file name
        header('Content-Disposition: attachment; filename=qrkode.png');
 }

    QRcode::png($codeText); //http://phpqrcode.sourceforge.net/examples/index.php?example=006
?>
