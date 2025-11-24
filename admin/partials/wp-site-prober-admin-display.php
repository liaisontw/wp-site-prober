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
<!-- <title>CSS-only Tabs (Radio)</title> -->
<style>
/**
 * All of the CSS for your admin-specific functionality should be
 * included in this file.
 */
 
 :root {
  --accent: #0b79d0;
  --muted: #666;
}

body {
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  padding: 20px;
}

.tabs {
  max-width: 900px;
  margin: 0 auto;
  border: 1px solid #eee;
  border-radius: 6px;
  overflow: hidden;
  background: #fff;
}

.tab-labels {
  display: flex;
  background: #fafafa;
  border-bottom: 1px solid #eee;
}

.tab-labels a {
  padding: 12px 20px;
  display: inline-block;
  color: var(--muted);
  text-decoration: none;
  border-bottom: 3px solid transparent;
  transition: color .2s ease, border-color .2s ease;
  font-weight: 500;
}

.tab-labels a:hover {
  color: #000;
}

.tab-labels a.active {
  color: var(--accent);
  border-bottom: 3px solid var(--accent);
}

.tab-content {
  padding: 20px;
  background: #fff;
}

.panel {
  display: none;
}

.panel.active {
  display: block;
}
 

</style>
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




