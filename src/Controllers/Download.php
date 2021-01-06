<?php 
    namespace VirtualLab\Controllers;

    class Download extends Controller {
        const CODE_TO_PATH = array(
            10 => 'physics',
            11 => 'chemistry',
            12 => 'physics_chemistry',
        );
        public function download($request) {
            $filepath =  __DIR__ . '/../Apps/school.png';
            if(file_exists($filepath)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                flush(); // Flush system output buffer
                readfile($filepath);
                die();
                // $this->jsonResponse(array('success' => true, 'message' => 'Downloading'), 200);
            } else {
                // $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
            }
        }
    }

?>
