(function($){
  const cfg = (window.GhostBotData && GhostBotData.settings) || {};
  const ajaxUrl = (window.GhostBotData && GhostBotData.ajax_url) || '';
  const nonce = (window.GhostBotData && GhostBotData.nonce) || '';
  if (!ajaxUrl) return;

  const state = {
    open: !!cfg.openOnLoad,
    history: [],
    sending: false,
    turns: 0,
    consent: false
  };

  const posClass = cfg.position === 'bottom-left' ? 'gb-left' : 'gb-right';

  const html = `
  <div class="gb-launcher ${posClass}" style="--gb-launcher-bg:${(cfg.launcher&&cfg.launcher.bg)||cfg.primaryColor||'#5b8cff'}; --gb-launcher-size:${(cfg.launcher&&cfg.launcher.size)||56}px;">
    <button class="gb-button" aria-label="Open chat with ${cfg.botName}">
      ${ (cfg.launcher && cfg.launcher.type === 'image' && cfg.launcher.image) ? `<span class="gb-icon img" style="background-image:url('${cfg.launcher.image}')"></span>` : `<span class="gb-icon">${(cfg.launcher && cfg.launcher.emoji) || '💬'}</span>` }
    </button>
  </div>
  <div class="gb-panel ${posClass} ${state.open ? 'open':''} theme-${(cfg.theme||"imessage")}" role="dialog" aria-label="${cfg.botName} chat panel" style="--gb-font-size:${(cfg.ui&&cfg.ui.fontSize)||14}px; --gb-radius:${(cfg.ui&&cfg.ui.radius)||18}px; --gb-send-bg:${(cfg.ui&&cfg.ui.sendBg)||cfg.primaryColor||'#5b8cff'}">
    <div class="gb-header" style="--gb-primary:${cfg.primaryColor || '#5b8cff'}">
      <div class="gb-title">${cfg.botName}</div>
      <button class="gb-close" aria-label="Close">×</button>
    </div>
    <div class="gb-messages ${'theme-'+(cfg.theme||'imessage')}"></div>
    <form class="gb-inputbar" autocomplete="off">
      ${cfg.consentRequired ? `<label class="gb-consent"><input type="checkbox" class="gb-consent-box"/> <span>${cfg.consentLabel || 'I agree to logging.'}</span></label>`:''}
      <input type="text" class="gb-input" placeholder="Type your message..." />
      <button class="gb-send" type="submit" title="Send">${(cfg.ui&&cfg.ui.sendIcon)||'↩'}</button>
    </form>
  </div>`;

  $('body').append(html);

  const $panel = $('.gb-panel');
  const $launch = $('.gb-launcher .gb-button');
  const $close = $('.gb-close');
  const $msgs = $('.gb-messages');
  const $form = $('.gb-inputbar');
  const $input = $('.gb-input');
  const $consent = $('.gb-consent-box');

  function toggle(open) {
    state.open = (open !== undefined ? open : !state.open);
    $panel.toggleClass('open', state.open);
  }

  function addMessage(content, role) {
    const el = $(`<div class="gb-msg ${role==='assistant'?'gb-bot':'gb-user'}"><div class="gb-bubble"></div></div>`);
    el.find('.gb-bubble').html(content);
    $msgs.append(el);
    $msgs.scrollTop($msgs[0].scrollHeight);
  }

  function addTyping() {
    const el = $(`<div class="gb-msg gb-bot gb-typing"><div class="gb-bubble"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div></div>`);
    $msgs.append(el);
    $msgs.scrollTop($msgs[0].scrollHeight);
    return el;
  }

  function typeOut(text) {
    const el = $(`<div class="gb-msg gb-bot"><div class="gb-bubble"></div></div>`);
    const b = el.find('.gb-bubble');
    $msgs.append(el);
    let i = 0;
    const words = text.split(/(\s+)/);
    function step(){
      if (i < words.length){
        b.append(escapeHtml(words[i++]));
        $msgs.scrollTop($msgs[0].scrollHeight);
        setTimeout(step, 15);
      }
    }
    step();
  }

  function sendMessage(text) {
    if (state.sending) return;
    const message = text.trim();
    if (!message) return;
    if (cfg.maxTurns && state.turns >= cfg.maxTurns) {
      addMessage('Turn limit reached. Refresh to start a new chat.', 'assistant');
      return;
    }
    state.sending = true;

    addMessage(escapeHtml(message), 'user');
    $input.val('');

    const typing = addTyping();

    $.ajax({
      url: ajaxUrl,
      method: 'POST',
      data: {
        action: 'ghost_bot_chat',
        nonce: nonce,
        message: message,
        history: JSON.stringify(state.history),
        turns: state.turns,
        consent: ($consent.length ? ($consent.prop('checked') ? 1 : 0) : 0)
      }
    }).done(function(res){
      typing.remove();
      if (res && res.success && res.data && res.data.answer) {
        const answer = res.data.answer;
        typeOut(stripTags(answer));
        state.history.push({role:'user', content: message});
        state.history.push({role:'assistant', content: stripTags(answer)});
        state.turns += 1;
      } else {
        addMessage('Sorry—my brain did a little hiccup. Try again?', 'assistant');
      }
    }).fail(function(xhr){
      typing.remove();
      const msg = (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) ? xhr.responseJSON.data.message : 'Network error.';
      addMessage('Error: ' + escapeHtml(msg), 'assistant');
    }).always(function(){
      state.sending = false;
    });
  }

  function escapeHtml(s){
    return s.replace(/[&<>\"']/g, function(m){
      return ({"&":"&amp;","<":"&lt;"," >":"&gt;","\"":"&quot;","'":"&#039;"}[m] || m);
    });
  }
  function stripTags(s){
    var tmp = document.createElement('DIV');
    tmp.innerHTML = s;
    return tmp.textContent || tmp.innerText || '';
  }

  $launch.on('click', function(){ toggle(); });
  $close.on('click', function(){ toggle(false); });
  $form.on('submit', function(e){ e.preventDefault(); sendMessage($input.val()); });
  $input.on('keydown', function(e){
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage($input.val());
    }
  });

  if (cfg.welcome) {
    setTimeout(function(){
      addMessage(escapeHtml(cfg.welcome), 'assistant');
    }, 300);
  }
  if (state.open) {
    setTimeout(function(){ toggle(true); }, 10);
  }

})(jQuery);
