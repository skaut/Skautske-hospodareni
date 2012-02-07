<?php
class FileService extends /*Nette\Application\*/BaseModel {
//    public function getList($path="") {
//
//        $path = str_replace("-", "/", $path);
//        $files = array();
//        $directory[] = DOCS_DIR."/".$path;
//         $directory = array();
//        if ($handle = opendir(DOCS_DIR."/".$path)) {
//            while (false !== ($file = readdir($handle))) {
//                if ($file != "." && $file != ".." && ereg("^[^.].*", $file )) {
//                    $file = iconv(MyString::detect($file), 'UTF-8', $file);
//                    if ("dir" == filetype(DOCS_DIR."/".$path."/".$file)) {
//                        $directory[]= "$file";
//                    }
//                    else {
//                        $files[] = $file;
//                    }
//
//                }
//            }
//            closedir($handle);
//        }
//        return array('directories' => $directory, 'files' => $files);
//    }
//
//    //posle http hlavicku se souborema dialogem ke stazeni
//    public function getFile($file = NULL, $path=NULL) {
//        if(is_null($file)) {
//            throw new BadRequestException("Nezadali jste název souboru ke stažení");
//        }
//        try {
//
//            $fileName = $file;
//            $realName = DOCS_DIR."/".$path.$file;
//            $fileSize = filesize($realName);
////            dump($path);
////            dump($fileName);
////            dump($realName);
////            dump($fileSize);
////            die ();
//
//            $httpResponse = Environment::getHttpResponse();
//            $httpResponse->setContentType('application/octetstream');
//            $httpResponse->setHeader('Content-Description', 'File Transfer');
//            $httpResponse->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
//            $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
//            $httpResponse->setHeader('Expires', '0');
//            $httpResponse->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
//            $httpResponse->setHeader('Pragma', 'public');
//            $httpResponse->setHeader('Content-Length', $fileSize);
//            ob_clean();
//            flush();
//            readfile($realName);
//
//        } catch (InvalidStateException $e) {
//            throw new BadRequestException($e->getMessage());
//        }
//        exit();//terminate tady nejde protoze to neni presenter
//    }


    function makePdf($template = NULL, $filename = NULL){
        if($template === NULL)
            return FALSE;
        define('_MPDF_PATH', LIBS_DIR . '/mpdf/');
        require_once(_MPDF_PATH . 'mpdf.php');
        $mpdf = new mPDF('utf-8');
        $mpdf->WriteHTML((string)$template, NULL);
        $mpdf->Output($filename, 'I');
        Environment::getApplication()->getPresenter()->terminate();
    }
}