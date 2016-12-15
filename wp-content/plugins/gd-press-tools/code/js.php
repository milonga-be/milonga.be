<script type="text/javascript">
function areYouSure() {
    return confirm("<?php _e("Are you sure? Operation is not reversible.", "gd-press-tools"); ?>");
}
function areYouSureSimple() {
    return confirm("<?php _e("Are you sure?", "gd-press-tools"); ?>");
}
jQuery(document).ready(function() {
    jQuery("#gdpt_tabs").tabs({fx: {height: "toggle"}});
    jQuery("#screen-meta-links").append("<div id=\"contextual-gopro-link-wrap\"><a href=\"admin.php?page=gd-press-tools-gopro\">Go Pro</a></div>");
});
</script>
