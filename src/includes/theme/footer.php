
<div id="modal" style="display:none">
  <div class="modal-box">
    <div class="modal-content">
      <div id="modalMessage"></div>
      <input id="modalTextInput" type="text">
    </div>
    <div class="modal-bottom form-bottom-buttons">
      <button type="submit" id="btnModalOk"><?php _e('action_ok');?></button>
      <button id="btnModalCancel"><?php _e('action_cancel');?></button>
    </div>
  </div>
</div>

</div><!-- end of #mtt -->

<div id="footer">
  <div id="footer_content">
    <span><?php _e('powered_by');?> <a href="http://www.mytinytodo.net/" class="powered-by-link">myTinyTodo</a>&nbsp;<?php mttinfo('version'); ?></span>
    <?php do_action('theme_footer_content_end'); ?>
  </div>
</div>

</body>
</html>
