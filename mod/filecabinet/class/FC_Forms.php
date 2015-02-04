<?php

/**
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */
class FC_Forms
{
    /**
     * Current working folder type
     * @var integer
     */
    private $ftype;

    /**
     * Id of current requested folder
     * @var integer
     */
    private $folder_id;
    private $title;
    private $content;
    private $factory;

    public function __construct()
    {
        $vars = filter_input_array(INPUT_GET, array('ftype' => FILTER_VALIDATE_INT,
            'folder_id' => FILTER_VALIDATE_INT));
        $this->ftype = $vars['ftype'];
        $this->folder_id = $vars['folder_id'];
        $this->loadFactory();
    }

    private function printFolderFiles()
    {
        echo $this->factory->printFolderFiles();
    }

    /**
     * 
     * @return void
     */
    private function form()
    {
        if (empty($this->ftype)) {
            throw new \Exception('Missing folder type');
        }
        $this->content = $this->factory->getForm();
        $this->title = $this->factory->getTitle();
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getContent()
    {
        return $this->content;
    }

    private function loadFactory()
    {
        switch ($this->ftype) {
            case MULTIMEDIA_FOLDER:
                $this->factory = new \filecabinet\FC_Forms\FC_Multimedia($this->folder_id);
                break;

            case IMAGE_FOLDER:
                $this->factory = new \filecabinet\FC_Forms\FC_Images($this->folder_id);
                break;

            case DOCUMENT_FOLDER:
                $this->factory = new \filecabinet\FC_Forms\FC_Documents($this->folder_id);
                break;
        }
    }

    public function handle()
    {
        $request = \Server::getCurrentRequest();
        switch ($request->getVar('ckop')) {
            case 'form':
                $this->form();
                break;

            case 'save_file':
                $this->saveFile($request);
                exit();

            case 'list_folder_files':
                $this->printFolderFiles();
                exit();

            default:
                throw new \Http\MethodNotAllowedException('Unknown request');
        }

        echo \Layout::wrap($this->getContent(), $this->getTitle(), true);
        exit();
    }

    public function saveFile(\Request $request)
    {
        if (Current_User::authorized('filecabinet')) {
            return;
        }

        $folder_id = $request->getVar('folder_id');
        $folder = new Folder($folder_id);
        switch ($folder->ftype) {
            case DOCUMENT_FOLDER:
                $this->uploadDocumentToFolder($folder, 'file');
                break;

            case IMAGE_FOLDER:
                $this->uploadImageToFolder($folder, 'file');
                break;

            case MEDIA_FOLDER:
                $this->uploadMediaToFolder($folder, 'file');
                break;
        }
    }

    private function uploadImageToFolder($folder, $filename)
    {
        PHPWS_Core::initModClass('filecabinet', 'Image.php');
        $file = new PHPWS_Image($id);
        $file->setDirectory($folder->getFullDirectory());
        $file->save();
    }

    private function checkDuplicate($path)
    {
        if (is_file($path)) {
            $msg = "Duplicate file found";
            $this->sendErrorHeader($msg);
        }
    }

    private function sendErrorHeader($message)
    {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-type: text/plain');
        exit($message);
    }

    private function checkMimeType($source_directory, $filename, $ftype)
    {
        switch ($ftype) {
            case DOCUMENT_FOLDER:
                $type_list = \PHPWS_Settings::get('filecabinet', 'document_files');
                break;
            case IMAGE_FOLDER:
                $type_list = \PHPWS_Settings::get('filecabinet', 'image_files');
                break;
            case MULTIMEDIA_FOLDER:
                $type_list = \PHPWS_Settings::get('filecabinet', 'media_files');
                break;
        }
        $ext = PHPWS_File::getFileExtension($filename);

        // First check if the extension is allowed for the current folder type.
        $type_array = explode(',', str_replace(' ', '', $type_list));
        if (!in_array($ext, $type_array)) {
            $this->sendErrorHeader('File type not allowed in folder');
        }

        // second check that file is the type it claims to be
        if (!PHPWS_File::checkMimeType($source_directory, $ext)) {
            $this->sendErrorHeader('Unknown file type');
        }
    }

    private function checkSize($source_file, $size, $ftype)
    {
        static $sizes;
        if (empty($sizes)) {
            $sizes = Cabinet::getMaxSizes();
        }

        switch ($ftype) {
            case DOCUMENT_FOLDER:
                $folder_max = $sizes['document'];
                break;

            case IMAGE_FOLDER:
                $folder_max = $sizes['image'];
                break;

            case MEDIA_FOLDER:
                $folder_max = $sizes['media'];
                break;
        }


        if ($size > $sizes['system'] || $size > $sizes ['form'] || $size > $sizes ['absolute'] || $size > $folder_max) {
            $this->sendErrorHeader('File size too large');
        }
    }

    private function uploadDocumentToFolder(Folder $folder, $filename)
    {
        PHPWS_Core::initModClass('filecabinet', 'Document.php');

        $upload = $_FILES[$filename];
        $destination_directory = $folder->getFullDirectory();

        if (!isset($_FILES[$filename])) {
            throw new \Exception('File upload could not be found');
        }

        $total_files = count($_FILES[$filename]['name']);
        for ($i = 0; $i < $total_files; $i++) {
            $source_directory = $upload['tmp_name'][$i];
            $uploaded_file_name = $upload['name'][$i];
            $type = $upload['type'][$i];
            $error = $upload['error'][$i];
            $size = $upload['size'][$i];

            $file = new PHPWS_Document();
            $file->setFilename($uploaded_file_name);
            
            $new_file_name = $file->file_name;
            $destination_path = $destination_directory . $new_file_name;

            $this->checkDuplicate($destination_path);
            $this->checkMimeType($source_directory, $uploaded_file_name, $folder->ftype);
            $this->checkSize($source_directory, $size, $folder->ftype);

            move_uploaded_file($source_directory, $destination_path);
            $file->setDirectory($folder->getFullDirectory());
            $file->setSize($size);
            $file->file_type = $type;
            $file->setFolderId($folder->id);
            $title = preg_replace('/\.\w+$/', '', str_replace('_', ' ', $new_file_name));
            $file->setTitle(ucfirst($title));
            // save is false because the file is already written
            $file->save(false);
        }
    }

    private function uploadMediaToFolder($folder, $filename)
    {
        PHPWS_Core::initModClass('filecabinet', 'Multimedia.php');
        $file = new PHPWS_Multimedia($id);
        $file->setDirectory($folder->getFullDirectory());
        $file->save();
    }

}
