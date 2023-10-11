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

        if (!Configuration::get('VIDEOPLAYERHYGISEAT_NAME')) {
            $this->warning = $this->l('No name provided.');
        }
    }
    public function install()
    {
        return parent::install()  &&
            $this->registerHook('displayVideoLink')
            && $this->registerHook('header');
    }

    

    public function uninstall() {

        Configuration::deleteByName('VIDEOPLAYER*_URL');

        $path = _PS_MODULE_DIR_.'videoplayerhygiseat/videos/';
        array_map('unlink', glob("$path/*.*"));

        $this->unregisterHook('displayVideoLink');
        $this->unregisterHook('header');

        return parent::uninstall();
    }
    
    public function hookDisplayVideoLink(){
        $videoplayer_url = Configuration::get('VIDEOPLAYER_URL');
        if (!empty($videoplayer_url)) {
            $minia_url = $this->_path.'/img/HygiseatMiniaSiteFinale.png';
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
        if ($this->context->controller->php_self == 'index') {   //$this->context->controller->php_self == 'category' && (int)Tools::getValue('id_category') == 3
            $this->context->controller->addCSS($this->_path.'views/css/hookvideoplayer.css', 'all');
        }
    }



    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {

            if (Configuration::get('VIDEOPLAYER_URL')) {
                $path = _PS_MODULE_DIR_.'flvideoplayer/videos/';
                array_map('unlink', glob("$path/*.*"));                
            }

            if (!isset($_FILES['VIDEOPLAYER_FILE']) || !isset($_FILES['VIDEOPLAYER_FILE']['tmp_name']) || !file_exists($_FILES['VIDEOPLAYER_FILE']['tmp_name'])) {
                $output .= $this->displayError($this->l('An error occurred while trying to upload the file.'));
            } else {

                $allowed_exts = array("mp4");
                $max_file_size = 100000000; // 100MB
                $file_ext = pathinfo($_FILES["VIDEOPLAYER_FILE"]["name"], PATHINFO_EXTENSION);
                if (!in_array($file_ext, $allowed_exts)) {
                    $output .= $this->displayError($this->l('Invalid file type. Only mp4 files are allowed.'));
                } elseif ($_FILES["VIDEOPLAYER_FILE"]["size"] > $max_file_size) {
                    $output .= $this->displayError($this->l('File size is too big. Maximum allowed size is 100MB.'));
                } else {
                    $path = _PS_MODULE_DIR_ . 'flvideoplayer/videos/';
                    $path_url = _MODULE_DIR_ . 'flvideoplayer/videos/';
                    if (!file_exists($path)) {
                        mkdir($path, 0755, true);
                    }
                    $file_name = uniqid() . '.' . $file_ext;
                    if (move_uploaded_file($_FILES["VIDEOPLAYER_FILE"]["tmp_name"], $path . $file_name)) {
                        $output .= $this->displayConfirmation($this->l('File successfully uploaded.'));
                        Configuration::updateValue('VIDEOPLAYER_URL', $path_url . $file_name);
                    } else {
                        $output .= $this->displayError($this->l('An error occurred while trying to move the file to its final destination.'));
                    }
                }
            }
        }

        return $output.$this->displayForm();
    }


    public function displayForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // $formtest = [
        //     'form' => [
        //         'legend' => [
        //             'title' => $this->l('Edit'),
        //             'icon' => 'icon-cogs'
        //         ],
        //         'input' => [
        //             [
        //                 'type' => 'text',
        //                 'name' => 'example'
        //             ]
        //         ],
        //         'submit' => [
        //             'title' => $this->l('Save'),
        //             'class' => 'btn btn-default pull-right'
        //         ]
        //     ]
        // ];

        $form = [
            [
                'form'=> [
                    'legend' => [
                        'title' => $this->l('Settings'),
                        'icon' => 'icon-cogs'
                    ],
                    'input' => [
                        [
                            'type' => 'file',
                            'label' => $this->l('Upload a video'),
                            'name' => 'VIDEOPLAYER_FILE',
                            'display_image' => true,
                            'extensions' => 'mp4',
                            'required' => true
                        ]// , array(
                        //     'type' => 'text',
                        //     'label' => 'test',   camarche presque
                        //     'name' => 'test'
                        // )
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-default pull-right'
                    ]
                ]
            ],

            // $formtest

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
        $helper->show_toolbar = false;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
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
        $helper->fields_value['VIDEOPLAYER_URL'] = Configuration::get('VIDEOPLAYER_URL');
        // $helper->fields_value = ['example' => 'testing', ];

        return $helper->generateForm($form);

    }
}
