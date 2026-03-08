<?php

require __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser;

if(isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0)
{
    $uploadDir = "uploads/";

    // Create uploads folder if it doesn't exist
    if(!is_dir($uploadDir)){
        mkdir($uploadDir, 0777, true);
    }

    $fileName = basename($_FILES['pdf_file']['name']);
    $fileTmp = $_FILES['pdf_file']['tmp_name'];

    $filePath = $uploadDir . $fileName;

    if(move_uploaded_file($fileTmp, $filePath))
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);

        $pages = $pdf->getPages();

        if(isset($pages[0])){
            $firstPageText = $pages[0]->getText();

            echo "<h2>First Page Text:</h2>";
            echo "<pre>".$firstPageText."</pre>";
        } else {
            echo "No pages found in PDF.";
        }

    } else {
        echo "File upload failed.";
    }
}

?>