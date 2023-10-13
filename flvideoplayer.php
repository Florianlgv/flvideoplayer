<?php
class FlVideoPlayer extends Module
{
    public function __construct()
    {
        $this->name = 'flvideoplayer';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Florian';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->displayName = $this->l('Video Player HYGISEAT');
        $this->description = $this->l('Image cliquable pour redirection sur un lecteur vidéo');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        parent::__construct();

    }
    public function install()
    {
        return parent::install()  &&
            $this->registerHook('displayVideoLink')
            && $this->registerHook('header')
            && $this->registerHook('moduleRoutes');
    }

    

    public function uninstall() {

        $languages = Language::getLanguages(true);
        $languageIds = array_map(function($lang) {
            return $lang['iso_code'];
        }, $languages);


        foreach ($languageIds as $langId) {
            Configuration::deleteByName('VIDEOPLAYER_URL_' . $langId);
            Configuration::deleteByName('VIDEOPLAYER_PREVFILENAME_' . $langId);
        }

        $path = _PS_MODULE_DIR_.'flvideoplayer/videos/';
        array_map('unlink', glob("$path/*.*"));

        $this->unregisterHook('displayVideoLink');
        $this->unregisterHook('header');
        $this->unregisterHook('moduleRoutes');
        return parent::uninstall();
    }
    //url personalisable
    public function hookDisplayVideoLink(){
        $langId = $this->context->language->iso_code;
        $videoplayer_url = Configuration::get('VIDEOPLAYER_URL_' . $langId);
        if (!empty($videoplayer_url)) {
            $minia_url = $this->_path.'/img/miniature-video-HYGISEAT.png';
            $this->context->smarty->assign(
            array(
                'videoplayer_url' => $this->context->link->getModuleLink('flvideoplayer', 'videoplayer'),
                'minia_url' => $minia_url
            )
            );
        }else{
            $this->context->smarty->assign(
                array(
                    'videoplayer_url' => null,
                    'minia_url' => null
                )
            );
        }
        return $this->display(__FILE__, 'hook_videoplayer.tpl');
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/need-update.css', 'all');
        if ($this->context->controller->php_self == 'category' && (int)Tools::getValue('id_category') == 3) {
            $this->context->controller->addCSS($this->_path.'views/css/hookvideoplayer.css', 'all');
        }
    }

    public function isThereSpecialChar($filename){
        return !preg_match('/^[a-zA-Z0-9_\- ]+\.[a-zA-Z0-9]+$/', $filename);
    }

    public function isFileNameTooLong($filename, $max_length=200){
        return strlen($filename) > $max_length;
    }

    public function isNotMP4($file_ext){
        return !in_array($file_ext, ["mp4"]);
    }
    public function isFileTooBig($file_size, $max_file_size=200000000){ //200MB
        return $file_size > $max_file_size;
    }

    public function isVideoFileNotDefined($file){
        return (!isset($file) || !isset($file['tmp_name']) || !file_exists($file['tmp_name']));
    }

    public function ensureDirectoryExists($path){
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    public function deletePreviousVideo($targetFilePath){
        if (file_exists($targetFilePath)) {
            unlink($targetFilePath);
        }
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            
            if ($this->isVideoFileNotDefined($_FILES['VIDEOPLAYER_FILE'])) {
                $output .= $this->displayError($this->l('An error occurred while trying to upload the file.'));
            } elseif ($this->isThereSpecialChar($_FILES["VIDEOPLAYER_FILE"]["name"])) {
                $output .= $this->displayError($this->l('Invalid file name. Only alphanumeric characters, dashes, and underscores are allowed.'));
            } elseif ($this->isFileNameTooLong($_FILES["VIDEOPLAYER_FILE"]["name"])) {
                $output .= $this->displayError($this->l('File name is too long. Please use a shorter name.'));
            } else {
                $file_ext = pathinfo($_FILES["VIDEOPLAYER_FILE"]["name"], PATHINFO_EXTENSION);
                if ($this->isNotMP4($file_ext)) {
                    $output .= $this->displayError($this->l('Invalid file type. Only mp4 files are allowed.'));
                } elseif ($this->isFileTooBig($_FILES["VIDEOPLAYER_FILE"]["size"])) {
                    $output .= $this->displayError($this->l('File size is too big. Maximum allowed size is 200MB.'));
                } else {
                    $path = _PS_MODULE_DIR_ . 'flvideoplayer/videos/';
                    $path_url = _MODULE_DIR_ . 'flvideoplayer/videos/';

                    $this->ensureDirectoryExists($path);

                    $selectedLang = Tools::getValue('SELECTED_LANGUAGE');
                    $file_name = 'video_HYGISEAT_'. $selectedLang . '.' . $file_ext;
                    $targetFilePath = _PS_MODULE_DIR_ . 'flvideoplayer/videos/' . $file_name;

                    $this->deletePreviousVideo($targetFilePath);
                    
                    if (move_uploaded_file($_FILES["VIDEOPLAYER_FILE"]["tmp_name"], $path . $file_name)) {
                        $output .= $this->displayConfirmation($this->l('File successfully uploaded.'));
                        Configuration::updateValue('VIDEOPLAYER_URL_'.$selectedLang, $path_url . $file_name);
                        Configuration::updateValue('VIDEOPLAYER_PREVFILENAME_'.$selectedLang, $_FILES["VIDEOPLAYER_FILE"]["name"]);
                    } else {
                        $output .= $this->displayError($this->l('An error occurred while trying to move the file to its final destination.'));
                    }
                }
            }
        }

        $output .= $this->displayForm();
        $this->updateVideoTab();
        $output .= $this->display(__FILE__, 'views/templates/admin/configuration.tpl');
        return $output;
    }

    public function updateVideoTab(){
        $languages = Language::getLanguages(true);
        $videoFileNames = [];
        foreach ($languages as $lang) {
            $prevFileName = Configuration::get('VIDEOPLAYER_PREVFILENAME_'.$lang['iso_code']) ?? 'No video has been found';
            $videoFileNames['videoFileName_'.$lang['iso_code']] = $prevFileName;
        }
        $languages = Language::getLanguages(true);
        $this->context->smarty->assign('languages', $languages);
        $this->context->smarty->assign($videoFileNames);
    }

    public function displayForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $languages = Language::getLanguages(true);
        $languageOptions = [];
        foreach ($languages as $lang) {
            $languageOptions[] = [
                'id' => $lang['iso_code'],
                'name' => $lang['name']
            ];
        }
        $form = [
            [
                'form'=> [
                    'legend' => [
                        'title' => $this->l('Settings'),
                        'icon' => 'icon-cogs'
                    ],
                    'input' => [        
                        [
                            'type' => 'select',
                            'label' => $this->l('Language'),
                            'name' => 'SELECTED_LANGUAGE',
                            'identifier' => 'id',
                            'options' => [
                                'query' => $languageOptions,
                                'id' => 'id',
                                'name' => 'name'
                            ]
                        ],
                        [
                            'type' => 'file',
                            'label' => $this->l('Upload a mp4 video'),
                            'name' => 'VIDEOPLAYER_FILE',
                            'display_image' => true,
                            'extensions' => 'mp4',
                            'required' => true
                        ]
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-default pull-right'
                    ]
                ]
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        
        $helper->toolbar_scroll = true;      
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
            )
            );
            // Load current value
        $helper->fields_value['SELECTED_LANGUAGE'] = Configuration::get('DEFAULT_LANGUAGE');

        $display = $helper->generateForm($form);
        return $display;
    }
}
