<p class="sub">
    <?php _e("Additional Statistics Info", "gd-press-tools"); ?>
    <?php
        $cnt_revisions = gd_count_revisions_total();
        $cnt_spam = gd_count_spam_total();
        $cnt_overhead = GDPTDB::get_tables_overhead();
    ?>
</p>
<div class="table" id="gdpt-table-info">
    <table><tbody>
        <tr class="first">
            <td class="first b"><?php echo $cnt_revisions; ?></td>
            <td class="t"><?php _e("Revisions for all published posts", "gd-press-tools"); ?></td>
            <td class="t options">
                <?php if ($cnt_revisions == 0) echo "/"; else { ?>
                <a href="index.php?gdpt=delrev"><?php _e("delete", "gd-press-tools"); ?></a>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td class="first b"><?php echo $cnt_spam; ?></td>
            <td class="t"><?php _e("Spam comments", "gd-press-tools"); ?></td>
            <td class="t options">
                <?php if ($cnt_spam == 0) echo "/"; else { ?>
                <a href="index.php?gdpt=delspam"><?php _e("delete", "gd-press-tools"); ?></a>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td class="first b"><?php echo $cnt_overhead; ?></td>
            <td class="t"><?php _e("Overhead for all database tables", "gd-press-tools"); ?></td>
            <td class="t options">
                <?php if ($cnt_overhead == 0) echo "/"; else { ?>
                <a href="index.php?gdpt=cledtb"><?php _e("cleanup", "gd-press-tools"); ?></a>
                <?php } ?>
            </td>
        </tr>
    </tbody></table>
</div>
<p class="sub">
    <?php _e("Press Tools Quick Access", "gd-press-tools"); ?>
</p>
<div class="table" id="gdpt-table-tools">
    <table><tbody>
        <tr class="first">
            <td class="first b"><?php _e("Settings", "gd-press-tools"); ?></td>
            <td class="t"><?php _e("all additional WordPress settings", "gd-press-tools"); ?></td>
            <td class="t options"><a href="admin.php?page=gd-press-tools-settings"><?php _e("show", "gd-press-tools"); ?></a></td>
        </tr>
        <tr>
            <td class="first b"><?php _e("Enviroment", "gd-press-tools"); ?></td>
            <td class="t"><?php _e("panel with PHP and mySQL information", "gd-press-tools"); ?></td>
            <td class="t options"><a href="admin.php?page=gd-press-tools-server"><?php _e("show", "gd-press-tools"); ?></a></td>
        </tr>
        <tr>
            <td class="first b"><?php _e("Scheduler", "gd-press-tools"); ?></td>
            <td class="t"><?php _e("all scheduled cron jobs", "gd-press-tools"); ?></td>
            <td class="t options"><a href="admin.php?page=gd-press-tools-cron"><?php _e("show", "gd-press-tools"); ?></a></td>
        </tr>
    </tbody></table>
</div>
