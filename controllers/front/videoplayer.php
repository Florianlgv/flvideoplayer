<?php
class flvideoplayerVideoPlayerModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
        $this->context->smarty->assign('video_url', Configuration::get('VIDEOPLAYER_URL'));
    }
    public function initContent()
    {
        parent::initContent();        
        $this->setTemplate('module:flvideoplayer/views/templates/front/videoplayer.tpl');
        $this->context->controller->addCSS($this->module->getPathUri().'views/css/videoplayer.css', 'all');
    }
}
