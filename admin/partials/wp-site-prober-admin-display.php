<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/liaisontw
 * @since      1.0.0
 *
 * @package    wp_site_prober
 * @subpackage wp_site_prober/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<html lang="zh-Hant">
<head>
<meta charset="utf-8" />
<h1><?php esc_html_e( 'Site Prober', 'wpsp-site-prober' ); ?></h1>
<meta name="viewport" content="width=device-width,initial-scale=1" />
</head>
<body>
<div class="tabs">

  <!-- Tab labels -->
  <div class="tab-labels">
      <a class="<?php echo $active_tab==='log'?'active':''; ?>"
         href="<?php echo admin_url('admin.php?page=wpsp_site_prober_log_list&tab=log'); ?>">
         Actions
      </a>

      <a class="<?php echo $active_tab==='custom'?'active':''; ?>"
         href="<?php echo admin_url('admin.php?page=wpsp_site_prober_log_list&tab=custom'); ?>">
         Custom Logs
      </a>
  </div>

  <!-- tab content -->
  <div class="tab-content">

      <div id="panel1"
           class="panel <?php echo $active_tab==='log'?'active':''; ?>">
          <?php $this->render_page_list_table(); ?>
      </div>

      <div id="panel2"
           class="panel <?php echo $active_tab==='custom'?'active':''; ?>">
          <?php $this->render_page_list_table_custom_log(); ?>
      </div>

  </div>
</div>


</body>
  <!-- labels -->
  <!-- <div class="tab-labels" role="tablist" aria-label="範例分頁">
    <label for="tab1" role="tab" tabindex="0">Actions</label>
    <label for="tab2" role="tab" tabindex="0">Custom Logs</label>    
    <label for="tab3" role="tab" tabindex="0">Settings</label>
  </div> -->
    <!-- <div id="panel2" class="panel" role="tabpanel" aria-labelledby="tab2"> -->
      <!-- <h2>Custome Log</h2> -->
      <!-- <p>Detail content here. Use radio buttons to switch panels.</p> -->
    <!-- <div id="panel3" class="panel" role="tabpanel" aria-labelledby="tab3">
      <h2>Settings</h2>
      <p>Settings content. Responsive and accessible enough for many uses.</p>
    </div> -->

</html>




