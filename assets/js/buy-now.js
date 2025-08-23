jQuery(function ($) {
  // --- Helpers ---------------------------------------------------------------

  function setImportant(el, prop, val) {
    try { el.style.setProperty(prop, val, 'important'); } catch (e) {}
  }
  function applyImportantColors($btn, color) {
    if (!color) return;
    var el = $btn[0];
    setImportant(el, 'background', color);
    setImportant(el, 'background-color', color);
    setImportant(el, 'border-color', color);
  }

  function getColors($btn) {
    return {
      n: $btn.data('color-normal'),
      h: $btn.data('color-hover'),
      a: $btn.data('color-active')
    };
  }

  function applyState($btn, state) {
    var c = getColors($btn);
    if (state === 'hover') {
      applyImportantColors($btn, c.h || c.n);
    } else if (state === 'active') {
      applyImportantColors($btn, c.a || c.h || c.n);
    } else {
      applyImportantColors($btn, c.n);
    }
  }

  function bindHoverActive($btn) {
    if ($btn.data('bnColorsBound')) return;
    $btn
      .on('mouseenter.bnColors', function () { applyState($btn, 'hover'); })
      .on('mouseleave.bnColors', function () { applyState($btn, 'normal'); })
      .on('mousedown.bnColors touchstart.bnColors', function () { applyState($btn, 'active'); })
      .on('mouseup.bnColors touchend.bnColors touchcancel.bnColors', function () { applyState($btn, 'hover'); });
    $btn.data('bnColorsBound', true);
  }

  function initButtons(scope) {
    $(scope).find('.buy-now-button, .buy-now-button-single').each(function () {
      var $b = $(this);
      // Assert "normal" color inline with !important
      applyState($b, 'normal');
      bindHoverActive($b);
    });
  }

  // Re-assert colors a few times after load (some themes inject late)
  function reassertColors() {
    initButtons(document);
    setTimeout(initButtons, 50, document);
    setTimeout(initButtons, 200, document);
    setTimeout(initButtons, 800, document);
  }

  // MutationObserver to catch theme/UX scripts, quick views, infinite scroll
  (function observeDom() {
    if (!window.MutationObserver) return;
    var mo = new MutationObserver(function (mutations) {
      var needsInit = false;
      for (var i = 0; i < mutations.length; i++) {
        var m = mutations[i];
        if (m.type === 'childList') {
          if ((m.addedNodes && m.addedNodes.length) || (m.removedNodes && m.removedNodes.length)) {
            needsInit = true; break;
          }
        } else if (m.type === 'attributes' && (m.attributeName === 'class' || m.attributeName === 'style')) {
          // If a buy-now button’s style/class changed, re-apply its colors
          var t = m.target;
          if (t && (t.classList && (t.classList.contains('buy-now-button') || t.classList.contains('buy-now-button-single')))) {
            applyState($(t), 'normal');
          }
        }
      }
      if (needsInit) initButtons(document);
    });
    mo.observe(document.documentElement || document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class', 'style']
    });
  })();

  // Also re-apply on common Woo events & any AJAX completion
  $(document).on('updated_wc_div wc_fragments_refreshed wc_fragments_loaded', function () {
    initButtons(document);
  });
  $(document).ajaxComplete(function () { initButtons(document); });

  // DOM ready & after window load
  initButtons(document);
  $(window).on('load', reassertColors);

  // --- Ripple ---------------------------------------------------------------
  function doRipple($btn) {
    if ($btn.hasClass('bn-anim-ripple')) {
      $btn.addClass('is-active');
      setTimeout(function () { $btn.removeClass('is-active'); }, 220);
    }
  }

  // --- AJAX flow ------------------------------------------------------------
  function disableBtn($btn) { $btn.addClass('is-loading').prop('disabled', true).css('opacity', 0.7); }
  function enableBtn($btn) { $btn.removeClass('is-loading').prop('disabled', false).css('opacity', ''); }

  function postBuyNow(payload, $btn) {
    disableBtn($btn);
    $.ajax({
      url: (window.abbBnVars && abbBnVars.ajax_url) || (window.ajaxurl || '/wp-admin/admin-ajax.php'),
      type: 'POST',
      dataType: 'json',
      data: $.extend({ action: 'abb_bn_buy_now', nonce: (abbBnVars && abbBnVars.nonce) }, payload),
      success: function (res) {
        if (res && res.success && res.data && res.data.redirect) {
          window.location.href = res.data.redirect;
        } else {
          console.error('Buy Now error', res);
          enableBtn($btn);
        }
      },
      error: function (xhr) {
        console.error('Buy Now AJAX failed', xhr);
        enableBtn($btn);
      }
    });
  }

  // Archive
  $(document).on('click', '.buy-now-button', function (e) {
    e.preventDefault();
    var $btn = $(this);
    doRipple($btn);
    postBuyNow({ product_id: $btn.data('product_id'), quantity: 1 }, $btn);
  });

  // Single (simple + variable)
  $(document).on('click', '.buy-now-button-single', function (e) {
    e.preventDefault();
    var $btn = $(this);
    doRipple($btn);

    var $form = $('form.cart');
    var qty = $form.find('input.qty').val() || 1;
    var variationId = $form.find('input[name=variation_id]').val();
    var attributes = {};
    $form.find('select[name^=attribute], input[name^=attribute]:checked').each(function () {
      attributes[$(this).attr('name')] = $(this).val();
    });

    var payload = { product_id: $btn.data('product_id'), quantity: qty };
    if (variationId && variationId !== '0') {
      payload.variation_id = variationId;
      payload.attributes = attributes;
    }
    postBuyNow(payload, $btn);
  });
});


