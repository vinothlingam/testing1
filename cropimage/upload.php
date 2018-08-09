<?php

$file_formats = array("jpg", "png", "gif", "bmp");

$filepath = "upload_images/";
$preview_width = "400";
$preview_height = "300";


if ($_POST['submitbtn']=="Upload") {

 $name = $_FILES['imagefile']['name']; // filename to get file's extension
 $size = $_FILES['imagefile']['size'];

 if (strlen($name)) {
 	$extension = substr($name, strrpos($name, '.')+1);
 	if (in_array($extension, $file_formats)) { // check it if it's a valid format or not
 		if ($size < (2048 * 1024)) { // check it if it's bigger than 2 mb or no
 			$imagename = md5(uniqid() . time()) . "." . $extension;
 			$tmp = $_FILES['imagefile']['tmp_name'];
 				if (move_uploaded_file($tmp, $filepath . $imagename)) {
					echo $imagename;
 				} else {
 					echo "Could not move the file";
 				}
 		} else {
 			echo "Your image size is bigger than 2MB";
 		}
 	} else {
 			echo "Invalid file format";
 	}
 } else {
 	echo "Please select image!";
 }
 exit();
}
 
?>