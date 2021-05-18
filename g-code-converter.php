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
	
	echo '<p><input type="submit" name="gcode-submitted" value="Convert"></p>';
	echo '</form>';

	echo '<script>
			var uploadField = document.getElementById("inputFile");

			uploadField.onchange = function() {
				if(this.files[0].size > 2097152){
				   alert("File is too big! (Max 2MB)");
				   this.value = "";
				};

				var fileName = uploadField.value;
				var extension = fileName.substring(fileName.lastIndexOf(\'.\') + 1);
				if(extension != \'nc\' && extension != \'gcode\') {
				   alert("File type not supported (Only .nc and .gcode files are supported)");
				   this.value = "";
				}
		    };
		</script>';
}

function convert_gcode() {
    if (isset($_POST['gcode-submitted'])) {
		$uploadDirectory = "./wp-content/uploads/g-code-files/";
    	wp_mkdir_p($uploadDirectory . "converted");

		$fileName = $_FILES['inputFile']['name'];
		$fileTmpName  = $_FILES['inputFile']['tmp_name'];

		$uploadPath = $uploadDirectory . basename($fileName);

        $didUpload = move_uploaded_file($fileTmpName, $uploadPath);

        if ($didUpload) {
		  convert_gcode_file($uploadDirectory, $fileName);
        } else {
          echo "An error occurred. Please contact the administrator.";
        }
    }
}

function convert_gcode_file($filePath, $fileName) {
    $fileData = function($filePath, $fileName) {
        $file = fopen($filePath . $fileName, 'r');

        if (!$file) {
            die('File does not exist or cannot be opened');
        }
        while (($line = fgets($file)) !== false) {
            yield $line;
        }

        fclose($file);
    };

	$convertedDirPath = "./wp-content/uploads/g-code-files/converted/";
	$file = $convertedDirPath . $fileName;
    foreach ($fileData($filePath, $fileName) as $line) {
        $Zdpattern = '(.*Z\d.*)';
        $Z_pattern = '(.*Z\-.*)';
        if (preg_match($Zdpattern, $line)) {
            $line = 'M05' . "\r\n";
        } else if (preg_match($Z_pattern, $line)) {
            $line = 'M3S030' . "\r\n";
        }

        file_put_contents($file, $line, FILE_APPEND);
    }

	echo '<form action="/wp-content/uploads/g-code-files/converted/' . $fileName . '">';
    echo '<input type="submit" value="Download" /> </form>';
}

function gcode_converter_activated() {
    $upload_path = "./../wp-content/uploads/g-code-files/converted";
    wp_mkdir_p($upload_path);
}

//register_activation_hook( __FILE__, 'gcode_converter_activated' );

function gcode_converter_shortcode() {
	ob_start();
	html_form_code();
	convert_gcode();

	return ob_get_clean();
}

add_shortcode( 'gcode_converter', 'gcode_converter_shortcode' );

?>
