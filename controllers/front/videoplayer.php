<?php
class flvideoplayerVideoPlayerModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
        $langId = $this->context->language->id;
        $videoplayer_url = Configuration::get('VIDEOPLAYER_URL_' . $langId);
        $this->context->smarty->assign('video_url', $videoplayer_url);
    }
    public function initContent()
    {
        parent::initContent();        
        $this->setTemplate('module:flvideoplayer/views/templates/front/videoplayer.tpl');
        $this->context->controller->addCSS($this->module->getPathUri().'views/css/videoplayer.css', 'all');
    }
}
