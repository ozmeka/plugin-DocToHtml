<?php
/**
 * Doc To Html
 * 
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * @package Omeka\Plugins\DocToHtml
 */
class DocToHtmlProcess extends Omeka_Job_AbstractJob
{
    /**
     * Process all .docx files in Omeka.
     */
    public function perform()
    {
        $docToHtmlPlugin = new DocToHtmlPlugin;
        $fileTable = $this->_db->getTable('File');

        $select = $this->_db->select()
            ->from($this->_db->File)
            ->where('mime_type IN (?)', $docToHtmlPlugin->getPdfMimeTypes());

        // Iterate all docx file records.
        $pageNumber = 1; $spleen = '';
        while ($files = $fileTable->fetchObjects($select->limitPage($pageNumber, 50)))
        {
            foreach ($files as $file)
            {
                $source_file = FILES_DIR. '/'. $file->getStoragePath();
                
                file_put_contents('/tmp/bleent16', $source_file);
                
                $html = $docToHtmlPlugin->pandoc2Html5($source_file);
                $fragment_filename = $file['original_filename']. '.shtml';
                file_put_contents(DOCTOHTML_FILES_DIR. '/'. $fragment_filename, $html);

/*
                $spleen .= print_r($file, true). PHP_EOL.PHP_EOL.PHP_EOL;
                // Delete any existing PDF text element texts from the file.
                $textElement = $file->getElement(
                    PdfTextPlugin::ELEMENT_SET_NAME,
                    PdfTextPlugin::ELEMENT_NAME
                );
                $file->deleteElementTextsByElementId(array($textElement->id));

                // Extract the PDF text and add it to the file.
                $file->addTextForElement(
                    $textElement,
                    $pdfTextPlugin->pdfToText(FILES_DIR . '/original/' . $file->filename)
                );
                $file->save();
*/
                // Prevent memory leaks.
                release_object($file);
            }
            $pageNumber++;
        }
//        file_put_contents('/tmp/bleent10', $spleen);
    }
}
