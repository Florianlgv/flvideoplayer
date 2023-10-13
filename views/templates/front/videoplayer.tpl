{extends file='page.tpl'}

{block name="page_content"}

<div class="container">
    <div class="row align-centervp">
        <div class="col-lg-6 col-md-8 col-sm-6 col-xs-12">
            <h3 id="vptitle">{l s="WC automatique suspendu HYGISEAT sans bride pour l'hygiène optimale des sanitaires" mod=flvideoplayer}</h3>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6" style="display:flex;justify-content:flex-end;">
            <img title={l s="Aspiration des mauvaises odeurs WC" mod=flvideoplayer} src="{$smarty.const._MODULE_DIR_}flvideoplayer\img\icon-fab-fr.png" style="max-width:100px;" class="img-responsive" id="vpimg">
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <video title={l s="WC automatique suspendu HYGISEAT sans bride" mod=flvideoplayer} autoplay width="100%" height="auto" controls loop>
                <source src="{$video_url}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    </div>
</div>
{/block}