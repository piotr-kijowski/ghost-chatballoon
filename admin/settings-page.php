<?php if ( ! defined( 'ABSPATH' ) ) exit;
$option_key = Ghost_Chat_Balloon_Bot::OPTION_KEY;
$opts = $this->get_options();
?>
<div class="wrap">
  <?php if(isset($_GET['updated'])): ?><div class="notice notice-success"><p>Settings saved.</p></div><?php endif; ?>
  <h1>Chat Bot Settings</h1>
  <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="ghost-bot-form">
    <?php wp_nonce_field('ghost_bot_save_settings'); ?>
    <input type="hidden" name="action" value="ghost_bot_save" />
    <?php $field = function($k, $default='') use ($opts){ echo esc_attr(isset($opts[$k]) ? $opts[$k] : $default); }; ?>

    <h2 class="title">API & Model</h2>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="api_key">OpenAI API Key</label></th>
        <td>
          <input name="<?php echo $option_key; ?>[api_key]" type="password" id="api_key" class="regular-text" value="<?php $field('api_key'); ?>" placeholder="sk-..." />
          <button type="button" class="button" id="ghost-test-conn">Test connection</button>
          <span id="ghost-test-status" style="margin-left:8px;"></span>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="model">Model</label></th>
        <td>
          <input name="<?php echo $option_key; ?>[model]" type="text" id="model" class="regular-text" value="<?php $field('model','gpt-4o-mini'); ?>" />
          <p class="description">e.g., gpt-4o-mini (recommended)</p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="temperature">Temperature</label></th>
        <td><input name="<?php echo $option_key; ?>[temperature]" type="number" step="0.1" min="0" max="2" id="temperature" value="<?php $field('temperature',0.6); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="max_tokens">Max Tokens</label></th>
        <td><input name="<?php echo $option_key; ?>[max_tokens]" type="number" min="1" max="4000" id="max_tokens" value="<?php $field('max_tokens',300); ?>" /></td>
      </tr>
    </table>

    <h2 class="title">Assistant Behavior</h2>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="bot_name">Bot Name</label></th>
        <td><input name="<?php echo $option_key; ?>[bot_name]" type="text" id="bot_name" class="regular-text" value="<?php $field('bot_name','Ghost Bot'); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="tone">Tone</label></th>
        <td>
          <select name="<?php echo $option_key; ?>[tone]" id="tone">
            <option value="friendly" <?php selected($opts['tone'], 'friendly'); ?>>Friendly</option>
            <option value="professional" <?php selected($opts['tone'], 'professional'); ?>>Professional</option>
            <option value="funny" <?php selected($opts['tone'], 'funny'); ?>>Funny</option>
            <option value="custom" <?php selected($opts['tone'], 'custom'); ?>>Custom</option>
          </select>
          <p><textarea name="<?php echo $option_key; ?>[tone_custom]" id="tone_custom" rows="3" class="large-text" placeholder="Describe the tone (used only if Tone=Custom)"><?php echo esc_textarea($opts['tone_custom']); ?></textarea></p>
      </tr>
      <tr>
        <th scope="row"><label for="welcome_message">Welcome Message</label></th>
        <td><textarea name="<?php echo $option_key; ?>[welcome_message]" id="welcome_message" rows="3" class="large-text"><?php echo esc_textarea($opts['welcome_message']); ?></textarea></td>
      </tr>
      <tr>
        <th scope="row"><label for="kb">Inline Knowledge (optional)</label></th>
        <td><textarea name="<?php echo $option_key; ?>[kb]" id="kb" rows="6" class="large-text" placeholder="Paste FAQs, product info, contact rules, etc."><?php echo esc_textarea($opts['kb']); ?></textarea>
        <p class="description">Tip: You can also add multiple entries in <strong>Chat Bot → Bot Knowledge</strong>. Both sources are merged into the prompt.</p></td>
      </tr>
    </table>

    <h2 class="title">Appearance & Placement</h2>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="theme">Theme</label></th>
        <td>
          <select name="<?php echo $option_key; ?>[theme]" id="theme">
            <option value="imessage" <?php selected($opts['theme'], 'imessage'); ?>>iMessage</option>
            <option value="whatsapp" <?php selected($opts['theme'], 'whatsapp'); ?>>WhatsApp</option>
            <option value="minimal" <?php selected($opts['theme'], 'minimal'); ?>>Minimal</option>
            <option value="custom" <?php selected($opts['theme'], 'custom'); ?>>Custom (use Primary Color)</option>
            <option value="glass" <?php selected($opts['theme'], 'glass'); ?>>Glass (liquid frosted)</option>
          </select>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="primary_color">Primary Color</label></th>
        <td><input name="<?php echo $option_key; ?>[primary_color]" type="text" id="primary_color" class="regular-text" value="<?php $field('primary_color','#5b8cff'); ?>" placeholder="#5b8cff" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="position">Position</label></th>
        <td>
          <select name="<?php echo $option_key; ?>[position]" id="position">
            <option value="bottom-right" <?php selected($opts['position'], 'bottom-right'); ?>>Bottom Right</option>
            <option value="bottom-left" <?php selected($opts['position'], 'bottom-left'); ?>>Bottom Left</option>
          </select>
        </td>
      </tr>
      <tr>
        <th scope="row">Launcher Icon</th>
        <td>
          <fieldset>
            <label><input type="radio" name="<?php echo $option_key; ?>[launcher_icon_type]" value="emoji" <?php checked($opts['launcher_icon_type'],'emoji'); ?>/> Emoji</label>
            &nbsp;
            <label><input type="radio" name="<?php echo $option_key; ?>[launcher_icon_type]" value="image" <?php checked($opts['launcher_icon_type'],'image'); ?>/> Image</label>
          </fieldset>
          <p style="margin-top:8px;">
            <input type="text" name="<?php echo $option_key; ?>[launcher_icon_emoji]" value="<?php $field('launcher_icon_emoji','💬'); ?>" class="small-text" style="font-size:20px;text-align:center;" />
            &nbsp; or Image URL: <input type="text" name="<?php echo $option_key; ?>[launcher_icon_image]" value="<?php $field('launcher_icon_image'); ?>" class="regular-text" placeholder="https://.../icon.png" />
            <button type="button" class="button" id="ghost-upload-icon">Upload</button>
          </p>
          <p>
            Background color: <input type="text" name="<?php echo $option_key; ?>[launcher_bg_color]" value="<?php $field('launcher_bg_color','#5b8cff'); ?>" class="ghost-color" />
            Size (px): <input type="number" name="<?php echo $option_key; ?>[launcher_size]" value="<?php $field('launcher_size',56); ?>" class="small-text" min="40" max="96" />
          </p>
        </td>
      </tr>
      <tr>
        <th scope="row">Messages UI</th>
        <td>
          <p>
            Base font size (px): <input type="number" name="<?php echo $option_key; ?>[font_size_base]" value="<?php $field('font_size_base',14); ?>" class="small-text" min="12" max="20" />
            Bubble radius (px): <input type="number" name="<?php echo $option_key; ?>[bubble_radius]" value="<?php $field('bubble_radius',18); ?>" class="small-text" min="8" max="28" />
          </p>
          <p>
            Send button color: <input type="text" name="<?php echo $option_key; ?>[send_button_bg]" value="<?php $field('send_button_bg','#5b8cff'); ?>" class="ghost-color" />
            Send icon: <input type="text" name="<?php echo $option_key; ?>[send_icon]" value="<?php $field('send_icon','↩'); ?>" class="small-text" />
          </p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="open_on_load">Open on Page Load</label></th>
        <td><label><input type="checkbox" name="<?php echo $option_key; ?>[open_on_load]" id="open_on_load" value="1" <?php checked($opts['open_on_load'], 1); ?> /> Yes</label></td>
      </tr>
      <tr>
        <th scope="row"><label for="enable_on_mobile">Enable on Mobile</label></th>
        <td><label><input type="checkbox" name="<?php echo $option_key; ?>[enable_on_mobile]" id="enable_on_mobile" value="1" <?php checked($opts['enable_on_mobile'], 1); ?> /> Yes</label></td>
      </tr>
    </table>

    <h2 class="title">Guardrails & Logging</h2>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="max_turns">Max turns per session</label></th>
        <td><input name="<?php echo $option_key; ?>[max_turns]" type="number" min="1" max="100" id="max_turns" value="<?php $field('max_turns',20); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="profanity_filter">Profanity filter</label></th>
        <td><label><input type="checkbox" name="<?php echo $option_key; ?>[profanity_filter]" id="profanity_filter" value="1" <?php checked($opts['profanity_filter'], 1); ?> /> Censor common profanity</label></td>
      </tr>
      <tr>
        <th scope="row"><label for="enable_logging">Conversation logging</label></th>
        <td><label><input type="checkbox" name="<?php echo $option_key; ?>[enable_logging]" id="enable_logging" value="1" <?php checked($opts['enable_logging'], 1); ?> /> Save Q/A pairs privately</label></td>
      </tr>
      <tr>
        <th scope="row"><label for="consent_required">Require consent to log</label></th>
        <td>
          <label><input type="checkbox" name="<?php echo $option_key; ?>[consent_required]" id="consent_required" value="1" <?php checked($opts['consent_required'], 1); ?> /> Yes</label>
          <p><input type="text" name="<?php echo $option_key; ?>[consent_label]" class="regular-text" value="<?php $field('consent_label'); ?>" /></p>
        </td>
      </tr>
    </table>

    <h2 class="title">Integrations</h2>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="woo_context">WooCommerce context</label></th>
        <td><label><input type="checkbox" name="<?php echo $option_key; ?>[woo_context]" id="woo_context" value="1" <?php checked($opts['woo_context'], 1); ?> /> Add shop URL and top categories to the prompt (if WooCommerce is active)</label></td>
      </tr>
    </table>

    <?php submit_button(); ?>
  </form>

  <hr/>
  <h2>Live Style Preview</h2>
  <div id="ghost-style-preview" style="max-width:360px;border:1px solid #e5e7eb;border-radius:12px;padding:12px;">
    <div style="font-weight:600;margin-bottom:8px;">Preview · <span id="pv-botname"><?php echo esc_html($opts['bot_name']); ?></span></div>
    <div style="background:#f8fafc;padding:10px;border-radius:12px;">
      <div style="display:flex;justify-content:flex-start;margin:6px 0;"><div id="pv-bot" style="max-width:80%;padding:8px 10px;border-radius:16px; background:#e9eef6;">Hi, I’m your bot.</div></div>
      <div style="display:flex;justify-content:flex-end;margin:6px 0;"><div id="pv-user" style="max-width:80%;padding:8px 10px;border-radius:16px; color:#fff;">Looks good!</div></div>
    </div>
  </div>

  <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top:16px;">
    <?php wp_nonce_field('ghost_bot_export'); ?>
    <input type="hidden" name="action" value="ghost_bot_export_logs"/>
    <button class="button button-secondary">Export Logs (CSV)</button>
  </form>

  <script>
    (function(){
      const theme = document.getElementById('theme');
      const color = document.getElementById('primary_color');
      const user = document.getElementById('pv-user');
      const bot = document.getElementById('pv-bot');
      const name = document.getElementById('bot_name');
      const nameOut = document.getElementById('pv-botname');
      function render(){
        nameOut.textContent = name.value || 'Ghost Bot';
        const t = theme.value;
        const c = color.value || '#5b8cff';
        if (t==='imessage'){ user.style.background = c; bot.style.background = '#e9eef6'; user.style.color='#fff'; bot.style.color='#111'; }
        else if (t==='whatsapp'){ user.style.background = '#d5ffc7'; bot.style.background = '#fff'; user.style.color='#111'; bot.style.color='#111'; }
        else if (t==='minimal'){ user.style.background = '#111'; bot.style.background = '#fff'; user.style.color='#fff'; bot.style.color='#111'; }
        else { user.style.background = c; bot.style.background = '#fff'; user.style.color='#fff'; bot.style.color='#111'; }
      }
      ['change','input'].forEach(evt=>{
        theme.addEventListener(evt, render);
        color.addEventListener(evt, render);
        name.addEventListener(evt, render);
      });
      render();

      // Test connection button
      const btn = document.getElementById('ghost-test-conn');
      const status = document.getElementById('ghost-test-status');
      const keyEl = document.getElementById('api_key');
      const modelEl = document.getElementById('model');
      if (btn) {
        btn.addEventListener('click', function(){
          status.textContent = 'Testing...';
          btn.disabled = true;
          const data = new FormData();
          data.append('action','ghost_bot_test');
          data.append('nonce','<?php echo wp_create_nonce(Ghost_Chat_Balloon_Bot::NONCE_ACTION); ?>');
          data.append('api_key', (keyEl && keyEl.value) ? keyEl.value : '');
          data.append('model', (modelEl && modelEl.value) ? modelEl.value : '');
          fetch(ajaxurl, { method:'POST', body:data, credentials:'same-origin' })
            .then(r=>r.json())
            .then(j=>{
              if (j && j.success) {
                status.innerHTML = '<span style="color:#0a7a0a;">OK</span> · ' + (j.data.model || 'model') + ' · ' + (j.data.latency_ms||'?') + 'ms';
              } else {
                status.innerHTML = '<span style="color:#b00020;">' + (j && j.data && j.data.message ? j.data.message : 'Failed') + '</span>';
                console.error('Ghost Test fail:', j);
              }
            })
            .catch(e=>{
              status.innerHTML = '<span style="color:#b00020;">' + (e && e.message ? e.message : 'Network error') + '</span>';
            })
            .finally(()=>{ btn.disabled = false; });
        });
      }

      // Media uploader for launcher icon
      (function($){
        $('#ghost-upload-icon').on('click', function(e){
          e.preventDefault();
          const frame = wp.media({ title: 'Choose launcher icon', multiple: false, library: { type: ['image'] } });
          frame.on('select', function(){
            const file = frame.state().get('selection').first().toJSON();
            $('input[name="<?php echo $option_key; ?>[launcher_icon_image]"]').val(file.url);
          });
          frame.open();
        });
      })(jQuery);
    })();
  </script>
</div>

<script>
(function($){
  $(function(){
    const $url = $('#ghost-launcher-image-url');
    const $preview = $('#ghost-icon-preview');

    $('#ghost-choose-icon').on('click', function(e){
      e.preventDefault();
      const frame = wp.media({
        title: 'Select launcher icon',
        multiple: false,
        library: { type: ['image'] }
      });
      frame.on('select', function(){
        const file = frame.state().get('selection').first().toJSON();
        $url.val(file.url);
        $preview.html('<img src="'+file.url+'" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;" /> <a href="#" id="ghost-remove-icon">Remove</a>');
      });
      frame.open();
    });

    $(document).on('click', '#ghost-remove-icon', function(e){
      e.preventDefault();
      $url.val('');
      $preview.html('<span style="color:#6b7280;">No image selected</span>');
    });
  });
})(jQuery);
</script>

<script>
(function($){
  $(function(){
    if ($.fn.wpColorPicker) {
      $('.ghost-color').wpColorPicker();
    }
  });
})(jQuery);
</script>
