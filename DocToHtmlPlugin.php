<?php

define('DOCTOHTML_DIR', dirname(__FILE__));
define('DOCTOHTML_FILES_DIR', DOCTOHTML_DIR. '/files');

require_once('pandoc-php/src/Pandoc/Pandoc.php');
require_once('pandoc-php/src/Pandoc/PandocException.php');

class DocToHtmlPlugin extends Omeka_Plugin_AbstractPlugin
{
	protected $_hooks = array('install', 'uninstall', 'config', 'config_form', 'after_ingest_file', 'admin_items_show', 'public_items_show');

//    protected $_filters = array();

    protected $_pdfMimeTypes = array(
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',	// docx
    );

	public function hookInstall()
	{
		if (!is_dir(DOCTOHTML_FILES_DIR))
		{
			mkdir(DOCTOHTML_FILES_DIR);
		}
	}

	public function hookUninstall()
	{
//		should probably clean up after ourselves but hmmm...
//		exec("rm {DOCTOHTML_FILES_DIR}/*");
//		rmdir(DOCTOHTML_FILES_DIR);
	}
	
    /**
     * Display the config form.
     */
    public function hookConfigForm()
    {
        echo get_view()->partial(
            'plugins/doc-to-html-config-form.php', 
            array('valid_storage_adapter' => $this->isValidStorageAdapter())
        );
    }

    /**
     * Handle the config form.
     */
    public function hookConfig()
    {
        // Run the text extraction process if directed to do so.
        if ($_POST['doc_to_html_process'] && $this->isValidStorageAdapter()) {
            Zend_Registry::get('bootstrap')->getResource('jobs')
                ->sendLongRunning('DocToHtmlProcess');
        }
    }
	
	public function hookAfterIngestFile($args)
	{
		$file = $args['file'];
		$item = $args['item'];

//		file_put_contents('/tmp/bleent5', print_r($file, true));
		
		$source_filename = '/tmp/'. $file['filename'];
		$fragment_filename = $file['original_filename']. '.shtml';
		
		$html = $this->pandoc2Html5($source_filename);
		file_put_contents(DOCTOHTML_FILES_DIR. '/'. $fragment_filename, $html);
	}

    public function hookAdminItemsShow($args)
    {
		$filesToDisplay = array();
		
		foreach($args['item']->Files as $file)
		{
			if (file_exists(DOCTOHTML_FILES_DIR. '/'. $file['original_filename']. '.shtml'))
			{
				$filesToDisplay[$file['original_filename']] = file_get_contents(DOCTOHTML_FILES_DIR. '/'. $file['original_filename']. '.shtml');
			}
		}

		if ($filesToDisplay)
		{
			echo common('doctext-show', array(
				'files' => $filesToDisplay,
			));
		}
	}

    public function hookPublicItemsShow($args)
    {
		$filesToDisplay = array();
		
		foreach($args['item']->Files as $file)
		{
			if (file_exists(DOCTOHTML_FILES_DIR. '/'. $file['original_filename']. '.shtml'))
			{
				$filesToDisplay[$file['original_filename']] = file_get_contents(DOCTOHTML_FILES_DIR. '/'. $file['original_filename']. '.shtml');
			}
		}

		if ($filesToDisplay)
		{
			echo common('doctext-show', array(
				'files' => $filesToDisplay,
			));
		}
	}
	
	public function pandoc2Html5($file)
	{
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		$c = file_get_contents($file);

		# try to auto-detect format from filename extension
		switch( $ext )
		{
			case 'docx':
				$from = 'docx';
				break;
				
			default:
				$from = null;
		}

		if ($from)
		{
			//  pandoc doesn't detect the first option itself on FreeBSD so working around here...
			if (file_exists('/usr/local/bin/pandoc'))
			{
				$exe = '/usr/local/bin/pandoc';
			}
			else if (file_exists('/usr/bin/pandoc'))
			{
				$exe = '/usr/bin/pandoc';
			}
			else
			{
				//	let pandoc-php have a go at finding it
				$exe = null;
			}
			
			
			$pandoc = new Pandoc\Pandoc( $exe );
			$options = array(
				'from'	=> $from,
				'to'	=> 'html5',
				'ascii'	=> null,	//	set null value for args which take no value.  this one sanely handles annoying MSWORD chars like quotes and ellipses
			);
		
			$html = $pandoc->runWith($c, $options);
			return $html;
		}
	}
	
	/**
     * Determine if the plugin supports the storage adapter.
     * 
     * pdftotext cannot be used on remote files, so only support the default 
     * Filesystem adapter, which stores files locally.
     * 
     * @return bool
     */
    public function isValidStorageAdapter()
    {
        $storageAdapter = Zend_Registry::get('bootstrap')
            ->getResource('storage')->getAdapter();
        if (!($storageAdapter instanceof Omeka_Storage_Adapter_Filesystem)) {
            return false;
        }
        return true;
    }
	
	/**
     * Get the PDF MIME types.
     * 
     * @return array
     */
    public function getPdfMimeTypes()
    {
        return $this->_pdfMimeTypes;
    }
}