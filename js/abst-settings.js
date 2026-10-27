/**
 * AB Split Test Lite - settings page (Settings > AB Split Test Lite).
 *
 * Expects a localized object `abstSettings`:
 *   ajaxUrl           admin-ajax.php URL
 *   pageSelectorNonce nonce for the `ab_page_selector` AJAX action ('abst_page_selector')
 *   heatmapPage       { id: '123', title: 'Home' } or null - the saved default viewer page
 *   i18n              { searchPage: '...', copied: '...' }
 */
(function () {
  'use strict';

  var settings = window.abstSettings || {};
  var i18n = settings.i18n || {};

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  // ---------------------------------------------------------------------------
  // Tabs
  // ---------------------------------------------------------------------------
  function initTabs() {
    var tabButtons = document.querySelectorAll('.abst-tab-btn');
    var tabPanels = document.querySelectorAll('.abst-tab-panel');
    if (!tabButtons.length) {
      return;
    }

    function readStoredTab() {
      try {
        return window.localStorage.getItem('abst_settings_tab');
      } catch (e) {
        return null;
      }
    }

    function storeTab(tabId) {
      try {
        window.localStorage.setItem('abst_settings_tab', tabId);
      } catch (e) {
        // Storage unavailable - the tab just is not remembered.
      }
    }

    function activateTab(tabId) {
      tabButtons.forEach(function (btn) { btn.classList.remove('active'); });
      tabPanels.forEach(function (panel) { panel.classList.remove('active'); });

      var activeBtn = document.querySelector('.abst-tab-btn[data-tab="' + tabId + '"]');
      var activePanel = document.getElementById('tab-' + tabId);

      if (activeBtn && activePanel) {
        activeBtn.classList.add('active');
        activePanel.classList.add('active');
      } else {
        var accountBtn = document.querySelector('.abst-tab-btn[data-tab="account"]');
        var accountPanel = document.getElementById('tab-account');
        if (accountBtn) { accountBtn.classList.add('active'); }
        if (accountPanel) { accountPanel.classList.add('active'); }
        tabId = 'account';
      }

      // Hide the save button on the account-only tab.
      var saveBtn = document.querySelector('.floating-save-button-row');
      if (saveBtn) {
        saveBtn.style.display = (tabId === 'account') ? 'none' : 'block';
      }
    }

    // On activation (redirect carries ?wizard=...) always land on the Account
    // welcome tab, ignoring any tab previously restored from localStorage.
    var isActivationWizard = new URLSearchParams(window.location.search).has('wizard');

    var savedTab = isActivationWizard
      ? 'account'
      : (window.location.hash.replace('#', '') || readStoredTab() || 'account');
    activateTab(savedTab);

    tabButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        var tabId = this.getAttribute('data-tab');
        activateTab(tabId);
        storeTab(tabId);
        history.replaceState(null, '', '#' + tabId);
      });
    });
  }

  // ---------------------------------------------------------------------------
  // Tracking & Privacy: cookie consent "More information"
  // ---------------------------------------------------------------------------
  function initConsentInfo() {
    var toggle = document.getElementById('wait_for_approval_info_toggle');
    var info = document.getElementById('wait_for_approval_info');
    if (!toggle || !info) {
      return;
    }
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      info.style.display = 'block';
      toggle.style.display = 'none';
    });
  }

  // ---------------------------------------------------------------------------
  // Heatmaps: show dependent settings, default viewer page selector
  // ---------------------------------------------------------------------------
  function initHeatmapToggle() {
    var heatmapToggle = document.getElementById('abst_heatmap_enable_user_journeys');
    var heatmapSettings = document.getElementById('heatmap_settings_area');
    if (heatmapToggle && heatmapSettings) {
      heatmapToggle.addEventListener('change', function () {
        heatmapSettings.style.display = this.checked ? 'block' : 'none';
      });
    }
  }

  function initHeatmapPageSelect() {
    var $ = window.jQuery;
    if (!$ || !$.fn || !$.fn.select2) {
      return;
    }
    var $select = $('#heatmap_page_select');
    if (!$select.length) {
      return;
    }

    $select.select2({
      ajax: {
        url: settings.ajaxUrl,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            q: params.term || '',
            action: 'abst_page_selector',
            nonce: settings.pageSelectorNonce
          };
        },
        processResults: function (data) {
          return {
            results: $.map(data, function (page) {
              return { id: page[0], text: page[1] };
            })
          };
        },
        cache: true
      },
      minimumInputLength: 0,
      placeholder: i18n.searchPage || 'Search for a page…',
      allowClear: true,
      width: '25rem'
    });

    if (settings.heatmapPage && settings.heatmapPage.id) {
      var opt = new Option(settings.heatmapPage.title, String(settings.heatmapPage.id), true, true);
      $select.append(opt).trigger('change');
    }
  }

  // ---------------------------------------------------------------------------
  // Developer: MCP client configs
  // ---------------------------------------------------------------------------
  function initMcpConfigs() {
    var usernameInput = document.getElementById('abst_mcp_username');
    var passwordInput = document.getElementById('abst_mcp_password');

    function updateMcpConfigs() {
      var username = usernameInput ? usernameInput.value : '';
      var password = passwordInput ? passwordInput.value : '';

      // Application Passwords are displayed with spaces; the API accepts them without.
      var sanitizedPassword = password.replace(/\s/g, '');
      if (passwordInput && password !== sanitizedPassword) {
        passwordInput.value = sanitizedPassword;
      }

      document.querySelectorAll('.abst-mcp-username-placeholder').forEach(function (el) {
        el.textContent = username || 'YOUR_WORDPRESS_USERNAME';
      });
      document.querySelectorAll('.abst-mcp-password-placeholder').forEach(function (el) {
        el.textContent = sanitizedPassword || 'YOUR_APPLICATION_PASSWORD';
      });
    }

    if (usernameInput) {
      usernameInput.addEventListener('input', updateMcpConfigs);
      usernameInput.addEventListener('change', updateMcpConfigs);
    }
    if (passwordInput) {
      passwordInput.addEventListener('input', updateMcpConfigs);
      passwordInput.addEventListener('paste', function () {
        // Let the paste land before processing it.
        setTimeout(updateMcpConfigs, 10);
      });
    }
    if (usernameInput || passwordInput) {
      updateMcpConfigs();
    }

    document.querySelectorAll('.abst-mcp-copy').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var code = document.getElementById(btn.getAttribute('data-target'));
        if (!code || !navigator.clipboard) {
          return;
        }
        navigator.clipboard.writeText(code.textContent).then(function () {
          var label = btn.textContent;
          btn.textContent = i18n.copied || 'Copied!';
          setTimeout(function () { btn.textContent = label; }, 1500);
        });
      });
    });
  }

  onReady(function () {
    initTabs();
    initConsentInfo();
    initHeatmapToggle();
    initHeatmapPageSelect();
    initMcpConfigs();
  });
})();
