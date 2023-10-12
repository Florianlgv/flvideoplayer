<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s="Downloaded Videos" mod=flvideoplayer}
    </div>
    <div class="panel-body">
        <table class="table">
    <tr>
        <th>{l s="Video File" mod=flvideoplayer}</th>
        <th>{l s="Langue" mod=flvideoplayer}</th>
    </tr>
{foreach from=$languages item=lang}
    <tr>
        <td>{$videoFileName_{$lang.iso_code}}</td>
        <td>{$lang.iso_code}</td>
    </tr>
{/foreach}
</table>
    </div>
</div>
