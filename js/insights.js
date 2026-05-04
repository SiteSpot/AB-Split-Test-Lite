function escapeHtml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function stripHtml(value) {
  const div = document.createElement('div');
  div.innerHTML = String(value || '');
  return div.textContent || div.innerText || '';
}

function safeUrl(value) {
  try {
    const url = new URL(normalizeUrl(String(value || '')), window.location.origin);
    return (url.protocol === 'http:' || url.protocol === 'https:') ? url.href : '#';
  } catch (e) {
    return '#';
  }
}

function sanitizeHtml(value) {
  const allowedTags = {
    A: ['href', 'title'],
    B: [],
    BLOCKQUOTE: [],
    BR: [],
    CODE: [],
    DIV: [],
    EM: [],
    H1: [],
    H2: [],
    H3: [],
    H4: [],
    H5: [],
    H6: [],
    HR: [],
    I: [],
    LI: [],
    OL: [],
    P: [],
    PRE: [],
    SMALL: [],
    SPAN: [],
    STRONG: [],
    TABLE: [],
    TBODY: [],
    TD: [],
    TH: [],
    THEAD: [],
    TR: [],
    U: [],
    UL: []
  };
  const template = document.createElement('template');
  template.innerHTML = String(value || '');

  function clean(node) {
    Array.from(node.children).forEach(function(child) {
      const tag = child.tagName;
      if (!allowedTags[tag]) {
        child.replaceWith(document.createTextNode(child.textContent || ''));
        return;
      }

      Array.from(child.attributes).forEach(function(attr) {
        const name = attr.name.toLowerCase();
        if (name.indexOf('on') === 0 || name === 'style' || name === 'srcdoc') {
          child.removeAttribute(attr.name);
          return;
        }
        if (allowedTags[tag].indexOf(attr.name) === -1) {
          child.removeAttribute(attr.name);
          return;
        }
        if (name === 'href') {
          const href = safeUrl(attr.value);
          if (href === '#') {
            child.removeAttribute(attr.name);
          } else {
            child.setAttribute('href', href);
            child.setAttribute('rel', 'noopener noreferrer');
          }
        }
      });

      clean(child);
    });
  }

  clean(template.content);
  return template.innerHTML;
}

function normalizeIceRatingValue(value, fallback) {
  if (value === null || typeof value === 'undefined' || value === '') {
    return fallback;
  }

  const parsed = parseFloat(String(value).replace(/[^\d.-]/g, ''));
  if (!Number.isFinite(parsed)) {
    return fallback;
  }

  if (parsed > 5 && parsed <= 100) {
    return Math.max(1, Math.min(5, Math.round(parsed / 20)));
  }

  return Math.max(1, Math.min(5, Math.round(parsed)));
}

function findIceRatingValue(source, aliases) {
  if (!source || typeof source !== 'object') {
    return undefined;
  }

  for (let i = 0; i < aliases.length; i++) {
    const alias = aliases[i];
    if (Object.prototype.hasOwnProperty.call(source, alias)) {
      return source[alias];
    }
  }

  return undefined;
}

function normalizeIceRatingSource(source) {
  if (typeof source !== 'string') {
    return source;
  }

  const trimmed = source.trim();
  if (!trimmed || trimmed.charAt(0) !== '{') {
    return source;
  }

  try {
    return JSON.parse(trimmed);
  } catch (e) {
    return source;
  }
}

function parseIceRatingText(value, aliases) {
  if (typeof value !== 'string' || !value.trim()) {
    return undefined;
  }

  for (let i = 0; i < aliases.length; i++) {
    const escaped = aliases[i].replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const match = value.match(new RegExp('(?:^|\\b)' + escaped + '\\s*[:=\\-]\\s*(\\d+(?:\\.\\d+)?)', 'i'));
    if (match) {
      return match[1];
    }
  }

  return undefined;
}

function getIceRating(item, aliases, fallback) {
  const nestedKeys = ['ice', 'ice_rating', 'ice_ratings', 'icer', 'ratings', 'priority'];
  let value = findIceRatingValue(item, aliases);

  if (typeof value === 'undefined') {
    nestedKeys.some(function(key) {
      value = findIceRatingValue(normalizeIceRatingSource(item[key]), aliases);
      return typeof value !== 'undefined';
    });
  }

  if (typeof value === 'undefined') {
    value = parseIceRatingText(item.ice || item.ice_rating || item.ice_ratings || item.ratings || '', aliases);
  }

  return normalizeIceRatingValue(value, fallback);
}

function getIceRatings(item) {
  return {
    impact: getIceRating(item, ['impact', 'Impact', 'i', 'I', 'impact_score', 'impactScore'], 3),
    reach: getIceRating(item, ['reach', 'Reach', 'r', 'R', 'reach_score', 'reachScore'], 3),
    confidence: getIceRating(item, ['confidence', 'Confidence', 'c', 'C', 'confidence_score', 'confidenceScore'], 3),
    effort: getIceRating(item, ['effort', 'Effort', 'e', 'E', 'effort_score', 'effortScore'], 3)
  };
}

function getIceMetricLabel(value, labels) {
  if (value >= 5) return labels[5];
  if (value >= 4) return labels[4];
  if (value >= 3) return labels[3];
  if (value >= 2) return labels[2];
  return labels[1];
}

function buildIceMetric(type, label, value, description) {
  return "<div class='icer-metric icer-metric-" + type + "'>" +
    "<span class='icer-label'>" + label + "</span>" +
    "<strong class='icer-value'>" + value + "</strong>" +
    "<span class='icer-desc'>" + description + "</span>" +
    "</div>";
}

function buildIceScoreCard(impact, reach, confidence, effort) {
  const ease = Math.max(1, Math.min(5, 6 - effort));
  const total = impact + reach + confidence + ease;
  const displayTotal = Math.round((total / 2) * 10) / 10;

  return "<div class='icer-score-wrap'>" +
  "<div class='icer-score-card'>" +
    "<div class='icer-total'>" +
      "<span class='icer-total-label'>ICE Score <button type='button' class='icer-help-toggle' aria-expanded='false' aria-label='What is the ICE score?'>?</button></span>" +
      "<strong>" + displayTotal + "<small>/10</small></strong>" +
    "</div>" +
    "<div class='icer-metrics'>" +
      buildIceMetric('impact', 'Impact', impact, getIceMetricLabel(impact, {
        5: 'Very high',
        4: 'High',
        3: 'Moderate',
        2: 'Limited',
        1: 'Low'
      })) +
      buildIceMetric('confidence', 'Confidence', confidence, getIceMetricLabel(confidence, {
        5: 'Strong proof',
        4: 'Good signal',
        3: 'Moderate',
        2: 'Light signal',
        1: 'Low proof'
      })) +
      buildIceMetric('ease', 'Ease', ease, getIceMetricLabel(ease, {
        5: 'Very easy',
        4: 'Easy',
        3: 'Moderate',
        2: 'Some setup',
        1: 'Complex'
      })) +
      buildIceMetric('reach', 'Reach', reach, getIceMetricLabel(reach, {
        5: 'Most users',
        4: 'Broad reach',
        3: 'Good segment',
        2: 'Small segment',
        1: 'Few users'
      })) +
    "</div>" +
  "</div>" +
  "<div class='icer-help-panel' hidden><strong>ICE score</strong> ranks test ideas so you can choose what to run first. <strong>Impact</strong> estimates the conversion upside, <strong>Confidence</strong> reflects how much evidence supports it, <strong>Ease</strong> shows how simple it is to launch, and <strong>Reach</strong> estimates how many visitors it affects.</div>" +
  "</div>";
}

function buildAdditionalContextPayload() {
  const hubInputs = window.abstinsights || {};
  const chunks = [];

  if (hubInputs.additionalContext) chunks.push("Additional context:\n" + hubInputs.additionalContext);
  if (hubInputs.funnel_input) chunks.push("Customer funnel input:\n" + hubInputs.funnel_input);
  if (hubInputs.customer_persona_input) chunks.push("Customer persona input:\n" + hubInputs.customer_persona_input);
  if (hubInputs.website_analysis_input) chunks.push("Website analysis notes:\n" + hubInputs.website_analysis_input);

  if (hubInputs.survey_post_purchase_raw) chunks.push("Post-purchase survey (2-question) raw responses:\n" + hubInputs.survey_post_purchase_raw);
  if (hubInputs.survey_long_form_raw) chunks.push("Follow-up email survey (10-question) raw responses:\n" + hubInputs.survey_long_form_raw);
  if (hubInputs.survey_staff_raw) chunks.push("Frontline staff survey raw responses:\n" + hubInputs.survey_staff_raw);
  if (hubInputs.chat_export_raw) chunks.push("Live chat export raw data:\n" + hubInputs.chat_export_raw);

  if (hubInputs.survey_research_summary) chunks.push("Existing survey/chat trend summary:\n" + hubInputs.survey_research_summary);

  // Backward compatibility for older saved field.
  if (!hubInputs.survey_post_purchase_raw &&
      !hubInputs.survey_long_form_raw &&
      !hubInputs.survey_staff_raw &&
      !hubInputs.chat_export_raw &&
      hubInputs.survey_responses) {
    chunks.push("Survey/chat responses to analyze:\n" + hubInputs.survey_responses);
  }

  return chunks.join("\n\n");
}

jQuery(document).ready(function($){
  function renderHubInputForm(seedData) {
    const seed = seedData || {};
    const funnelText = Array.isArray(seed.funnel) ? seed.funnel.map(function(item){
      if (item && item.page && item.url) return item.page + " (" + item.url + ")";
      return '';
    }).filter(Boolean).join("\n") : '';

    const personaText = typeof seed.customer_persona_input === 'string' ? seed.customer_persona_input : (typeof seed.customer_persona === 'string' ? seed.customer_persona : '');
    const analysisText = typeof seed.website_analysis_input === 'string'
      ? seed.website_analysis_input
      : (seed.cro_audit && typeof seed.cro_audit.html === 'string' ? seed.cro_audit.html.replace(/<[^>]*>/g, ' ') : '');
    const surveyLegacyText = typeof seed.survey_responses === 'string' ? seed.survey_responses : '';
    const postPurchaseText = typeof seed.survey_post_purchase_raw === 'string' ? seed.survey_post_purchase_raw : surveyLegacyText;
    const longFormText = typeof seed.survey_long_form_raw === 'string' ? seed.survey_long_form_raw : '';
    const staffText = typeof seed.survey_staff_raw === 'string' ? seed.survey_staff_raw : '';
    const chatText = typeof seed.chat_export_raw === 'string' ? seed.chat_export_raw : '';
    const summaryText = typeof seed.survey_research_summary === 'string' ? seed.survey_research_summary : '';

    jQuery('#abst-insights-content').html(`
      <div class="insights-panel">
        <h2>CRO Hub</h2>
        <p>The spot for analysis, context, and everything a CRO expert needs to grow.</p>
        <div class="insights-hub-grid">
          <div>
            <label for="abst-insights-funnel">Customer Funnel</label>
            <textarea id="abst-insights-funnel" rows="4" placeholder="Homepage > Pricing > Signup">${escapeHtml(funnelText)}</textarea>
          </div>
          <div>
            <label for="abst-insights-persona">Customer Persona</label>
            <textarea id="abst-insights-persona" rows="4" placeholder="Who is the ideal buyer?">${escapeHtml(personaText)}</textarea>
          </div>
          <div class="full">
            <label for="abst-insights-analysis">Website Analysis Notes</label>
            <textarea id="abst-insights-analysis" rows="4" placeholder="Anything the AI should know about the site">${escapeHtml(analysisText)}</textarea>
          </div>
          <div class="full">
            <label for="abst-insights-survey-post-purchase">Post-purchase survey raw responses (2-question)</label>
            <textarea id="abst-insights-survey-post-purchase" rows="4" placeholder="Paste raw responses or CSV rows">${escapeHtml(postPurchaseText)}</textarea>
          </div>
          <div class="full">
            <label for="abst-insights-survey-long">Follow-up email survey raw responses (10-question)</label>
            <textarea id="abst-insights-survey-long" rows="4" placeholder="Paste long-form survey responses">${escapeHtml(longFormText)}</textarea>
          </div>
          <div class="full">
            <label for="abst-insights-survey-staff">Frontline staff survey raw responses</label>
            <textarea id="abst-insights-survey-staff" rows="4" placeholder="Paste internal team feedback">${escapeHtml(staffText)}</textarea>
          </div>
          <div class="full">
            <label for="abst-insights-chat-export">Live chat export raw data (CSV/text)</label>
            <textarea id="abst-insights-chat-export" rows="5" placeholder="Paste live chat transcript export">${escapeHtml(chatText)}</textarea>
          </div>
          <div class="full">
            <label for="abst-insights-survey-summary">Research summary / trends (optional)</label>
            <textarea id="abst-insights-survey-summary" rows="4" placeholder="Summarize key trends, themes, objections, and jobs-to-be-done">${escapeHtml(summaryText)}</textarea>
          </div>
          <div class="full">
            <label for="abst-insights-input">Additional Context</label>
            <textarea id="abst-insights-input" rows="3" placeholder="Any extra context for this run">${escapeHtml(seed.additionalContext || '')}</textarea>
          </div>
        </div>
        <button id="abst-insights-submit" class="button button-primary">Generate Insights</button>
      </div>
    `);
  }

  function collectHubInputs() {
    return {
      funnel_input: jQuery('#abst-insights-funnel').val() || '',
      customer_persona_input: jQuery('#abst-insights-persona').val() || '',
      website_analysis_input: jQuery('#abst-insights-analysis').val() || '',
      survey_post_purchase_raw: jQuery('#abst-insights-survey-post-purchase').val() || '',
      survey_long_form_raw: jQuery('#abst-insights-survey-long').val() || '',
      survey_staff_raw: jQuery('#abst-insights-survey-staff').val() || '',
      chat_export_raw: jQuery('#abst-insights-chat-export').val() || '',
      survey_research_summary: jQuery('#abst-insights-survey-summary').val() || '',
      additionalContext: jQuery('#abst-insights-input').val() || ''
    };
  }

  // Check if window.abstinsights exists
  if (!window.abstinsights) {
    console.log('abstinsights not found');
      renderHubInputForm();
  } else {
      console.log('abstinsights found');
      // If window.abstinsights exists, just show the loading message and run the fetch
      displayInsights(window.abstinsights);
      jQuery('#abst-insights-content').prepend('<h3 class="prevgen">This CRO report was previously generated, <a href="#" id="abst-insights-regenerate">Click here to regenerate</a></h3>');

  }

  jQuery('body').on('click', '#abst-insights-regenerate', function() {
    jQuery('#abst-insights-content').html('<p>Getting your sitemap...</p>');
    window.abstinsights = window.abstinsights || {};
    runInsightsFetch();
  });

  jQuery('body').on('click', '#abst-insights-submit', function() {
    jQuery('#abst-insights-content').html('<p>Getting your sitemap...</p>');
    window.abstinsights = collectHubInputs();
    runInsightsFetch();
  });

  jQuery('body').on('click', '.abst-add-to-test-ideas', function() {
    const $btn = jQuery(this);
    const payload = {
      action: 'abst_ti_save_idea',
      nonce: window.abstTiNonce || '',
      page: $btn.data('page') || '',
      problem: $btn.data('problem') || 'AI CRO suggestion',
      hypothesis: $btn.data('hypothesis') || '',
      impact: $btn.data('impact') || 3,
      reach: $btn.data('reach') || 3,
      confidence: $btn.data('confidence') || 3,
      effort: $btn.data('effort') || 3,
      nextstep: 'Create test from CRO insights',
      status: 'backlog',
      created_at: new Date().toISOString()
    };

    $btn.prop('disabled', true).text('Adding...');
    jQuery.post(ajaxurl, payload, function(res) {
      if (res && res.success) {
        $btn.text('Added to Test Ideas');
      } else {
        $btn.prop('disabled', false).text('Add to Test Ideas');
      }
    }).fail(function() {
      $btn.prop('disabled', false).text('Add to Test Ideas');
    });
  });

  jQuery('body').on('click', '.icer-help-toggle', function() {
    const $btn = jQuery(this);
    const $panel = $btn.closest('.icer-score-wrap').find('.icer-help-panel').first();
    const isOpen = $btn.attr('aria-expanded') === 'true';

    $btn.attr('aria-expanded', isOpen ? 'false' : 'true');
    if (isOpen) {
      $panel.stop(true, true).slideUp(160, function() {
        $panel.attr('hidden', true);
      });
    } else {
      $panel.attr('hidden', false).hide().stop(true, true).slideDown(180);
    }
  });
});



  // Function to run the fetch routine
  function runInsightsFetch() {
      fetch(bt_homeurl)
        .then(res => res.text())
        .then(html => {
          console.log('✅ Home fetch successful');
          // Parse HTML
          const markdown = [];
          markdown['home'] = htmlToMarkdown(html);      
            jQuery('#abst-insights-content').html('<div class="insights-panel" id="insights-loading-panel"><h2>Generating Insights...</h2><div id="insights-status"><p>Collected your home page content, searching for important pages...</p></div></div>');
          console.log('✅ Fetch and parse successful:', markdown);

          jQuery.post(ajaxurl + "?t=" + new Date().getTime(), {
            action: 'ab_get_sitemap',
            markdown: markdown['home'],
                additionalContext: buildAdditionalContextPayload()
          }, function(response) {
            if(response.data.error){
              jQuery('#insights-status').append('<p class="error">Oh no... We couldn\'t get your sitemap. Please try again.</p><p>' + escapeHtml(response.data.error) + '</p>');
              return false;
            }
            //console.log('✅ Fetch and parse successful:', markdown);
            sitemap = JSON.parse(response.data.choices[0].message.content); 
            jQuery('#insights-status').append('<p>Found site map, browsing them for more context...</p>');

            console.log(sitemap);

            // Initialize heatmap data object
            const heatmapData = {};

            //foreach
            fetchPagesSequentially(sitemap, markdown, heatmapData, function(finalMarkdown, finalHeatmapData) {
              // This code runs after all pages are fetched
              jQuery('#insights-status').append('<p>All pages digested. Analyzing...</p>');
              setTimeout(function() {
                  jQuery('#insights-status').append('<p>Page content analyzed. Planning...</p>');
              }, 2000);

              markdownString = '';
              Object.entries(finalMarkdown).forEach(([title, content]) => {
                markdownString += `${title}\n\n${content}\n\n`;
              });

              // Format heatmap data for AI prompt
              let heatmapString = '';
              const pagesWithHeatmap = Object.entries(finalHeatmapData).filter(([page, data]) => data.total_clicks > 0);
              
              if (pagesWithHeatmap.length > 0) {
                heatmapString = '\n\n=== USER BEHAVIOR DATA (Heatmap Analytics) ===\n\n';
                pagesWithHeatmap.forEach(([page, data]) => {
                  heatmapString += `Page: ${page}\n`;
                  heatmapString += `- Total Clicks: ${data.total_clicks} (${data.unique_sessions} unique sessions)\n`;
                  
                  if (data.hot_elements && Object.keys(data.hot_elements).length > 0) {
                    heatmapString += `- Hot Elements (most clicked):\n`;
                    Object.entries(data.hot_elements).slice(0, 5).forEach(([element, stats]) => {
                      heatmapString += `  • ${element}: ${stats.clicks} clicks (${stats.percentage}%)\n`;
                    });
                  }
                  
                  if (data.rage_clicks && Object.keys(data.rage_clicks).length > 0) {
                    heatmapString += `- Frustration Points (rage clicks):\n`;
                    Object.entries(data.rage_clicks).forEach(([element, count]) => {
                      heatmapString += `  • ${element}: ${count} rage clicks\n`;
                    });
                  }
                  
                  if (data.suspicious_patterns && Object.keys(data.suspicious_patterns).length > 0) {
                    heatmapString += `- Confusing Elements (unexpected clicks on non-interactive elements):\n`;
                    Object.entries(data.suspicious_patterns).forEach(([element, stats]) => {
                      heatmapString += `  • ${element}: ${stats.clicks} clicks - users may be confused\n`;
                    });
                  }
                  
                  heatmapString += '\n';
                });
                heatmapString += '=== END BEHAVIOR DATA ===\n\n';
              }

              jQuery.ajax({
                url: ajaxurl + "?t=" + new Date().getTime(),
                type: 'POST',
                timeout: 75000, // Let the 60 second PHP request timeout return cleanly.
                data: {
                  action: 'ab_get_insights',
                  markdown: markdownString,
                  heatmapData: heatmapString,
                  additionalContext: buildAdditionalContextPayload()
                },
                success: function(response) {
                  if(response.data.error){
                    jQuery('#abst-insights-content').html('<h3>Oh no!</h3><p>' + escapeHtml(response.data.error) + '</p><p>Please refresh the page and try again</p>');
                    return false;
                  }
                  cro_audit = JSON.parse(response.data.choices[0].message.content); 
                  displayInsights(cro_audit);
                }
              });
            });
          });
        })
        .catch(err => jQuery('#abst-insights-content').html('Fetch or parse failed: ' + escapeHtml(err && err.message ? err.message : err)));
  };

/**
 * Normalizes a URL to be absolute and properly formatted
 * Handles: /pricing/, pricing/, https://example.com/pricing, //example.com/pricing
 * @param {string} url - The URL to normalize
 * @returns {string} - Fully qualified absolute URL
 */
function normalizeUrl(url) {
    if (!url || typeof url !== 'string') {
        console.log('Invalid cannot normalize URL:', url);
        return window.location.origin + '/';
    }
    
    url = url.trim();
    
    // Already a full absolute URL with protocol
    if (url.match(/^https?:\/\//i)) {
        return url;
    }
    
    // Protocol-relative URL (//example.com/path)
    if (url.startsWith('//')) {
        return window.location.protocol + url;
    }
    
    // Absolute path (/pricing/)
    if (url.startsWith('/')) {
        return window.location.origin + url;
    }
    
    // Relative path (pricing/ or pricing)
    // Append to current origin with leading slash
    return window.location.origin + '/' + url;
}

function displayInsights(cro_audit) {
  let insightsOut = "";
  let hasErrors = false;
  let errorMessages = [];
  
  // Validate main object structure
  if (!cro_audit || typeof cro_audit !== 'object') {
    jQuery('#abst-insights-content').html('<div class="error"><h3>AI Output Error</h3><p>The AI response format was incorrect. Please regenerate the insights.</p></div>');
    return;
  }
  
  // Start main analysis panel
  insightsOut += '<div id="results-in"></div>';
  insightsOut += '<div class="insights-panel">';
  insightsOut += '<h2>CROAssist website analysis.</h2>';
  insightsOut += "<p>Did you know? We use this to train your copywriting AI to write better copy for you.</p>";
  
  // Protected "Doing Well" section
  if (cro_audit.likes && Array.isArray(cro_audit.likes) && cro_audit.likes.length > 0) {
    try {
      insightsOut += "<h3>Doing Well</h3><ol>";
      cro_audit.likes.forEach(function(item) {
        if (typeof item === 'string' && item.trim()) {
          insightsOut += "<li>" + escapeHtml(item) + "</li>";
        }
      });
      insightsOut += "</ol>";
    } catch (e) {
      errorMessages.push("'Doing Well' section had formatting issues");
      hasErrors = true;
    }
  }
  
  // Protected Funnel section
  if (cro_audit.funnel && Array.isArray(cro_audit.funnel) && cro_audit.funnel.length > 0) {
    try {
      insightsOut += "<h3>Funnel</h3><ol>";
      cro_audit.funnel.forEach(function(item) {
        if (item && typeof item === 'object' && item.page && item.url && item.reason) {
          insightsOut += "<li><strong>" + escapeHtml(item.page) + "</strong> " + escapeHtml(item.url) + " - " + escapeHtml(item.reason) + "</li>";
        }
      });
      insightsOut += "</ol>";
    } catch (e) {
      errorMessages.push("Funnel section had formatting issues");
      hasErrors = true;
    }
  } else {
    errorMessages.push("Funnel data was missing or incorrectly formatted");
    hasErrors = true;
  }
  
  // Protected Customer Persona section
  if (cro_audit.customer_persona && typeof cro_audit.customer_persona === 'string' && cro_audit.customer_persona.trim()) {
    try {
      insightsOut += "<h3>Ideal Customer Persona</h3>";
      insightsOut += "<p>" + escapeHtml(cro_audit.customer_persona) + "</p>";
    } catch (e) {
      errorMessages.push("Customer Persona section had formatting issues");
      hasErrors = true;
    }
  } else {
    errorMessages.push("Customer Persona data was missing or incorrectly formatted");
    hasErrors = true;
  }

  // Protected Tone of Voice section
  if (cro_audit.tone_of_voice && typeof cro_audit.tone_of_voice === 'string' && cro_audit.tone_of_voice.trim()) {
    try {
      insightsOut += "<h3>Tone of Voice</h3><p>" + escapeHtml(cro_audit.tone_of_voice) + "</p>";
    } catch (e) {
      errorMessages.push("Tone of Voice section had formatting issues");
      hasErrors = true;
    }
  } else {
    errorMessages.push("Tone of Voice data was missing or incorrectly formatted");
    hasErrors = true;
  }

  // Protected Goals section
  if (cro_audit.goals && typeof cro_audit.goals === 'object' && 
      cro_audit.goals.primary && Array.isArray(cro_audit.goals.primary) &&
      cro_audit.goals.secondary && Array.isArray(cro_audit.goals.secondary) &&
      cro_audit.goals.tertiary && Array.isArray(cro_audit.goals.tertiary)) {
    try {
      insightsOut += "<h3>Goals</h3>";
      insightsOut += "<p>Primary: " + cro_audit.goals.primary.map(escapeHtml).join(', ') + "</p>";
      insightsOut += "<p>Secondary: " + cro_audit.goals.secondary.map(escapeHtml).join(', ') + "</p>";
      insightsOut += "<p>Tertiary: " + cro_audit.goals.tertiary.map(escapeHtml).join(', ') + "</p>";
      insightsOut += "</div>"; // Close first panel
    } catch (e) {
      errorMessages.push("Goals section had formatting issues");
      hasErrors = true;
    }
  } else {
    errorMessages.push("Goals data was missing or incorrectly formatted");
    hasErrors = true;
  }

  // Protected CRO Audit section - NEW PANEL
  if (cro_audit.cro_audit && typeof cro_audit.cro_audit === 'object' && 
      cro_audit.cro_audit.html && typeof cro_audit.cro_audit.html === 'string' && cro_audit.cro_audit.html.trim()) {
    try {
      insightsOut += "<div class='insights-panel'><h2>CRO Audit</h2>";
      insightsOut += "<p>Here is a summary of your website's current state with suggestions for improvement:</p>";
      insightsOut += sanitizeHtml(cro_audit.cro_audit.html) + "</div>";
    } catch (e) {
      errorMessages.push("CRO Audit section had formatting issues");
      hasErrors = true;
    }
  } else {
    errorMessages.push("CRO Audit data was missing or incorrectly formatted");
    hasErrors = true;
  }

  // Protected Content Suggestions section
  if (cro_audit.content_suggestions && Array.isArray(cro_audit.content_suggestions) && cro_audit.content_suggestions.length > 0) {
    try {
      insightsOut += "<div class='insights-panel'><h2>Improvement Suggestions</h2>";
      insightsOut += "<p>Some ideas that could be tested:</p>";
      insightsOut += "<p>" + cro_audit.content_suggestions.map(escapeHtml).join(', ') + "</p></div>";
    } catch (e) {
      errorMessages.push("Content Suggestions section had formatting issues");
      hasErrors = true;
    }
  } else {
    errorMessages.push("Content Suggestions data was missing or incorrectly formatted");
    hasErrors = true;
  }

  // Close main analysis panel before test suggestions
  insightsOut += "</div>"; // Close insights-main-panel

  // Protected Test Suggestions section
  if (cro_audit.test_suggestions && Array.isArray(cro_audit.test_suggestions) && cro_audit.test_suggestions.length > 0) {
    try {
      insightsOut += "<h2 class='insights-section-title'>One-click Test Suggestions</h2>";
      insightsOut += "<p class='insights-section-desc'>CRO agent trained to give you high impact suggestions that will move the needle. Click <u>Create this test</u> and a new magic test will be launched. You choose the variations, goals and start the test.</p>";
      
      cro_audit.test_suggestions.forEach(function(item) {
        if (!item || typeof item !== 'object') return;
        
        let test_title;
        let seenon = item.url || 'Unknown';
        try {
          if (item.title && typeof item.title === 'string' && item.title.trim()) {
            test_title = item.title;
            if (seenon == '/')
              seenon = "Home page.";
          } else {
            test_title = 'Auto Test ' + new Date().toLocaleDateString();
            item.title = item.url || 'Unknown Page';
            if (seenon == '/')
              seenon = "Home page.";
          }
          
          const titleText = item.title || test_title;
          const thesisText = (item.thesis && typeof item.thesis === 'string' && item.thesis.trim()) ? item.thesis : '';
          const iceRatings = getIceRatings(item);
          const impact = iceRatings.impact;
          const reach = iceRatings.reach;
          const confidence = iceRatings.confidence;
          const effort = iceRatings.effort;
          insightsOut += "<div class='insights-test-suggestion'>";
          insightsOut += "<h3><small class='test-suggestion-seen-page'>Seen on: " + escapeHtml(seenon) + "</small>" + escapeHtml(titleText) + "</h3>";
          insightsOut += buildIceScoreCard(impact, reach, confidence, effort);
          if (thesisText) {
            insightsOut += "<p class='item-thesis'>" + escapeHtml(thesisText) + "</p>";
          }
          
          if (item.theorytitle && typeof item.theorytitle === 'string' && item.theorytitle.trim() &&
              item.theory && typeof item.theory === 'string' && item.theory.trim()) {
            insightsOut += "<p class='lawdescription'><span class='lawbadge'>" + escapeHtml(item.theorytitle) + "</span>" + escapeHtml(item.theory) + "</p>";
          }
          
          if (item.text_to_replace && typeof item.text_to_replace === 'string' && item.text_to_replace.trim()) {
            insightsOut += "<p>Current Text: <strong>" + escapeHtml(item.text_to_replace) + "</strong></p>";
          }
          
          let createTestLink = '';
          if (item.variations && Array.isArray(item.variations) && item.variations.length > 0) {
            insightsOut += "<p>Alternate Text Options:</p><ul class='item-variations'>";
            item.variations.forEach(function(variation) {
              if (typeof variation === 'string' && variation.trim()) {
                insightsOut += "<li>" + escapeHtml(variation) + "</li>";
              }
            });
            insightsOut += "</ul>";
            
            // Only add create test button if we have all required data
            if (item.url && item.text_to_replace && test_title) {
              const testUrl = safeUrl(item.url);
              const createTestUrl = testUrl === '#'
                ? '#'
                : testUrl + "?abmagic=1&test_title=" + encodeURIComponent(test_title) + "&text_to_replace=" + encodeURIComponent(item.text_to_replace) + "&variations=" + encodeURIComponent(item.variations.join('|'));
              createTestLink = "<a class='button button-primary' target='_blank' rel='noopener noreferrer' href='" + escapeHtml(createTestUrl) + "'>Create this test</a>";
            }
          }

          insightsOut += "<div class='insights-test-actions'>" + createTestLink +
            "<button class='button abst-add-to-test-ideas' " +
            "data-page='" + escapeHtml(item.url || '') + "' " +
            "data-problem='" + escapeHtml(item.text_to_replace || 'AI CRO suggestion') + "' " +
            "data-hypothesis='" + escapeHtml(stripHtml(thesisText)) + "' " +
            "data-impact='" + impact + "' " +
            "data-reach='" + reach + "' " +
            "data-confidence='" + confidence + "' " +
            "data-effort='" + effort + "'>Add to Test Ideas</button>";
          insightsOut += "</div>";
          
          insightsOut += "</div>";
        } catch (e) {
          console.error('Error processing test suggestion:', e, item);
        }
      });
      // No closing div needed - each test suggestion is its own panel
    } catch (e) {
      errorMessages.push("Test Suggestions section had formatting issues");
      hasErrors = true;
    }
  } else {
    errorMessages.push("Test Suggestions data was missing or incorrectly formatted");
    hasErrors = true;
  }
  
  // Add error summary if there were issues
  if (hasErrors && errorMessages.length > 0) {
    insightsOut += "<div class='insights-section error-section' style='background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 20px 0;'>";
    insightsOut += "<h3 style='color: #d32f2f;'>⚠️ AI Output Issues Detected</h3>";
    insightsOut += "<p>The following sections had formatting problems and may need regeneration:</p>";
    insightsOut += "<ul>";
    errorMessages.forEach(function(msg) {
      insightsOut += "<li>" + msg + "</li>";
    });
    insightsOut += "</ul>";
    insightsOut += "<p><strong>Recommendation:</strong> Click 'regenerate' to get a properly formatted response from the AI.</p>";
    insightsOut += "</div>";
  }
  
  jQuery('#abst-insights-content').html(insightsOut);
}

// This function will fetch pages one at a time along with their heatmap data
// Optionally, provide an onComplete callback to run code after all pages are fetched.
// Example usage:
// fetchPagesSequentially(sitemap, markdown, heatmapData, function(finalMarkdown, finalHeatmapData) {
//     alert('All pages fetched!');
// });
async function fetchPagesSequentially(sitemap, markdown, heatmapData, onComplete) {
  for (const item of sitemap) {
    // Normalize the URL to ensure it's absolute and properly formatted
    const normalizedUrl = normalizeUrl(item.url);

    jQuery('#insights-status').append("<p>Fetching: " + escapeHtml(item.page) + " - " + escapeHtml(normalizedUrl) + "</p>");

    try {
      // Fetch page content and heatmap data in parallel
      const [pageRes, heatmapSummary] = await Promise.all([
        fetch(normalizedUrl),
        fetchHeatmapSummary(item.url)
      ]);

      const html = await pageRes.text();
      markdown[item.page] = htmlToMarkdown(html);

      // Store heatmap data if available
      if (heatmapSummary && heatmapSummary.total_clicks > 0) {
        heatmapData[item.page] = heatmapSummary;
        jQuery('#insights-status').append("<p>Fetched: " + escapeHtml(item.page) + " (" + escapeHtml(heatmapSummary.total_clicks) + " clicks analyzed)</p>");
      } else {
        jQuery('#insights-status').append("<p>Fetched: " + escapeHtml(item.page) + "</p>");
      }
    } catch (err) {
      jQuery('#insights-status').append("<p>Error fetching " + escapeHtml(item.page) + ": " + escapeHtml(err.message) + "</p>");
    }
  }

  console.log(markdown);
  console.log(heatmapData);

  if (typeof onComplete === 'function') {
    onComplete(markdown, heatmapData);
  }
}

/**
 * Fetch heatmap summary for a specific page
 * @param {string} url - Page URL
 * @returns {object|null} - Heatmap summary data or null if no data/error
 */
async function fetchHeatmapSummary(url) {
    try {
        // Extract post ID from URL (simple approach)
        const urlObj = new URL(url, window.location.origin);
        const path = urlObj.pathname;
                
        // For now, we'll pass the URL path and let the backend resolve it
        // The backend will match against the post_id field in journey logs
        
        return new Promise((resolve) => {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ab_get_heatmap_summary',
                    page_id: path === '/' ? window.btab_vars.post_id : path,
                    days: 30
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        resolve(null);
                    }
                },
                error: function() {
                    resolve(null);
                }
            });
        });
    } catch (err) {
        console.log('Error fetching heatmap summary:', err);
        return null;
    }
}
function htmlToMarkdown(html) {
const parser = new DOMParser();
const doc = parser.parseFromString(html, 'text/html');

// Elements to remove
const selectorsToRemove = [
  'script',
  'style',
  'link',
  'meta',
  'noscript',
  'iframe',
  'object',
  'embed',
  'svg',
  'canvas',
  'picture',
  'source',
  'video',
  'audio',
  '#wpadminbar',
  '#ab-ai-form',
  '#querylist'
];
doc.querySelectorAll(selectorsToRemove.join(',')).forEach(el => el.remove());

// Remove comments
const removeComments = node => {
  for (let i = node.childNodes.length - 1; i >= 0; i--) {
    const child = node.childNodes[i];
    if (child.nodeType === Node.COMMENT_NODE) {
      node.removeChild(child);
    } else if (child.nodeType === Node.ELEMENT_NODE) {
      removeComments(child);
    }
  }
};
removeComments(doc.documentElement);
// Get the cleaned raw HTML
let cleanedHTML = doc.documentElement.outerHTML;

// Minify: remove extra whitespace between tags and inside text
cleanedHTML = cleanedHTML
  .replace(/\s{2,}/g, ' ')           // collapse multiple spaces
  .replace(/>\s+</g, '><')          // remove space between tags
  .replace(/\n|\t/g, '')            // remove line breaks and tabs
  .trim();                          // trim start/end whitespace

  
  //turndown it
  const turnD = new TurndownService();
  const markdown = turnD.turndown(cleanedHTML);
  return markdown;
}
