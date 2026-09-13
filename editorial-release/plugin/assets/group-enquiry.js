(function () {
  'use strict';
  if (!window.fetch || !window.FormData || !window.AbortController) { return; }
  var form = document.querySelector('.c99-lead-form');
  if (!form || form.hasAttribute('data-c99-enquiry-enhanced')) { return; }
  var interest = form.querySelector('[name="interest"]');
  var action = form.querySelector('[name="action"]');
  if (!interest || interest.value !== 'group-order' || !action || action.value !== 'complete99_submit_lead') { return; }
  // A hidden input named "action" shadows form.action in actual browsers.
  var endpoint = new URL(form.getAttribute('action'), window.location.href);
  if (endpoint.origin !== window.location.origin || endpoint.pathname !== '/wp-admin/admin-post.php') { return; }
  var button = form.querySelector('button[type="submit"]');
  if (!button) { return; }
  var language = form.querySelector('[name="language"]');
  var he = language && language.value === 'he';
  var copy = he ? {
    sending: 'שולחים את הבקשה…',
    success: 'הבקשה התקבלה. נחזור אליכם לגבי המנות, הכמויות והמועד שביקשתם.',
    expired: 'הטופס התיישן. הפרטים נשארו כאן. העתיקו אותם לפני רענון הדף ושליחה מחדש.',
    invalid: 'יש פרט שצריך לתקן. בדקו את השדות ונסו שוב. הפרטים שמילאתם נשארו כאן.',
    busy: 'נשלחו כמה בקשות בזמן קצר. הפרטים נשארו כאן, אפשר לנסות שוב מאוחר יותר.',
    uncertain: 'לא הצלחנו לאשר שהבקשה התקבלה. הפרטים נשארו כאן. לפני שליחה נוספת אפשר להתקשר אלינו, כדי להימנע מבקשה כפולה.'
  } : {
    sending: 'Sending your request…',
    success: 'Your request was received. We will contact you about the dishes, quantities and requested date.',
    expired: 'This form has expired. Your details are still here. Copy them before refreshing and submitting again.',
    invalid: 'Please check the fields and try again. The details you entered are still here.',
    busy: 'Several requests were sent in a short time. Your details are still here. Please try again later.',
    uncertain: 'We could not confirm receipt. Your details are still here. Before sending again, you can call us to avoid a duplicate request.'
  };
  var status = document.createElement('p');
  status.className = 'c99-enquiry-feedback';
  status.hidden = true;
  status.tabIndex = -1;
  status.setAttribute('role', 'status');
  status.setAttribute('aria-live', 'polite');
  form.parentNode.insertBefore(status, form);
  form.setAttribute('data-c99-enquiry-enhanced', '1');
  var pending = false;
  var originalLabel = button.textContent;
  function feedback(message, success) {
    status.textContent = message;
    status.hidden = false;
    status.setAttribute('data-state', success ? 'success' : 'error');
    status.focus();
  }
  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (pending || form.hidden) { return; }
    if (!form.reportValidity()) { return; }
    pending = true;
    button.disabled = true;
    button.textContent = copy.sending;
    form.setAttribute('aria-busy', 'true');
    status.hidden = true;
    var controller = new window.AbortController();
    var timer = window.setTimeout(function () { controller.abort(); }, 45000);
    try {
      var response = await window.fetch(endpoint.href, {
        method: 'POST', body: new window.FormData(form), credentials: 'same-origin',
        signal: controller.signal
      });
      var destination = new URL(response.url, window.location.href);
      var current = new URL(window.location.href);
      var expectedPath = destination.pathname === current.pathname || destination.pathname === '/';
      if (response.ok && response.redirected && destination.origin === current.origin && expectedPath && destination.searchParams.get('c99_sent') === '1') {
        form.hidden = true;
        feedback(copy.success, true);
      } else {
        feedback(response.status === 403 ? copy.expired : response.status === 400 ? copy.invalid : response.status === 429 ? copy.busy : copy.uncertain, false);
      }
    } catch (error) {
      // A network error is not proof the server rejected the request. Never replay it automatically.
      feedback(copy.uncertain, false);
    } finally {
      window.clearTimeout(timer);
      pending = false;
      button.disabled = false;
      button.textContent = originalLabel;
      form.removeAttribute('aria-busy');
    }
  });
}());
