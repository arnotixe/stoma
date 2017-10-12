<?php

// Download file
//        header("Content-Type: application/octet-stream");    //
//        header('Content-Disposition: attachment; filename=uploads/' . $_GET["f"]); // get files only from uploads dir
//	readfile("uploads/$_GET[f]");


// http://php.net/manual/en/function.readfile.php
$file = "uploads/$_GET[f]";

if (file_exists($file)) {
    header('Content-Description: File Transfer');
//    header('Content-Type: application/octet-stream');
    header('Content-Type: application/image'); // should be, at least
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

return;

// This code is now moved to functions.php
// called by edittool.php
// Generate QR images with phpqrcode.sourceforge.net

	include('phpqrcode/qrlib.php');
	$param = $_GET['c']; // content to qrencode

    $codeText = $param;

 if ($_GET["d"] == "1") {
        header("Content-Type: application/octet-stream");    //
        // tell the thing the filesize
//        header("Content-Length: " . filesize($download_path.$file));    
        // set it as an attachment and give a file name
        header('Content-Disposition: attachment; filename=qrkode_' . $param . '.png');
 }

// write to disk
    QRcode::png($codeText,"uploads/qrkode_$param.png",QR_ECLEVEL_L,4); //http://phpqrcode.sourceforge.net/examples/index.php?example=006

/* Create some objects */
$image = new Imagick();
$draw = new ImagickDraw();
$pixel = new ImagickPixel( 'white' );

// read qr code into memory
$image = new Imagick("uploads/qrkode_$param.png");
// text box
$image->newImage(116, 20, $pixel);
$draw->setFillColor('black');
$draw->setFontSize( 15 );
$draw->setGravity(Imagick::GRAVITY_CENTER);
$image->annotateImage($draw, 0, 0, 0, "$_GET[t]");
$image->resetIterator();
$out = $image->appendimages(true);

$out->writeImage("uploads/qrkode_$param.png");

echo "<img src=\"uploads/qrkode_$param.png\">"	;

?>
