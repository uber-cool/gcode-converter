<?php
/*
Plugin Name: G Code Converter
Plugin URI: http://gapucha.com
Description: G-code converter for plotter
Version: 1.0
Author: Pramod Jangid
Author URI: http://gapucha.com
*/

function html_form_code() {
	echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="POST" enctype="multipart/form-data">';
	
	echo '<p>';
	echo 'Upload G code File<br/>';
	echo '<input type="file" name="inputFile" id="inputFile"/>';
	echo '</p>';
	
	echo '<p><input type="submit" name="cf-submitted" value="Convert"></p>';
	echo '</form>';
}

function convert_gcode() {
    if (isset($_POST['cf-submitted'])) {
		$uploadDirectory = "./wp-content/uploads/g-code-files/";

		$fileName = $_FILES['inputFile']['name'];
		$fileTmpName  = $_FILES['inputFile']['tmp_name'];

		$uploadPath = $uploadDirectory . basename($fileName); 
      
        $didUpload = move_uploaded_file($fileTmpName, $uploadPath);

        if ($didUpload) {
          echo "G code " . basename($fileName) . " has been uploaded";
		  convert_gcode_file($uploadDirectory, $fileName);
        } else {
          echo "An error occurred. Please contact the administrator.";
        }
    }
}

function convert_gcode_file($filePath, $fileName) {
    $fileData = function($filePath, $fileName) {
        $file = fopen($filePath . $fileName, 'r');

        if (!$file)
            die('File does not exist or cannot be opened');

        while (($line = fgets($file)) !== false) {
            yield $line;
        }

        fclose($file);
    };

	$convertedDirPath = "./wp-content/uploads/g-code-files/converted/";
    foreach ($fileData($filePath, $fileName) as $line) {
        $pattern = '(.*Z\d.*)';
        $converted = preg_replace($pattern, 'M05', $line);

        $pattern = '(.*Z\-.*)';
        $converted = preg_replace($pattern, 'M3S030', $line);

        file_put_contents($convertedDirPath . $fileName, $converted, FILE_APPEND);
    }
}

function cf_shortcode() {
	ob_start();
	convert_gcode();
	html_form_code();

	return ob_get_clean();
}

add_shortcode( 'sitepoint_contact_form', 'cf_shortcode' );

?>
