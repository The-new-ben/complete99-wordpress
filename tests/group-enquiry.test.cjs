const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync('editorial-release/plugin/assets/group-enquiry.js', 'utf8');

function fixture({language='he', response, failure, valid=true, interest='group-order', endpoint='https://complete99.co.il/wp-admin/admin-post.php'}={}) {
  const state = {calls:0, payload:null};
  const button = {textContent:'שליחת בקשה',disabled:false};
  const attrs = {};
  const status = {hidden:true,setAttribute(k,v){this[k]=v;},focus(){this.focused=true;}};
  const form = {
    action:{value:'complete99_submit_lead'}, hidden:false, values:{contact_name:'Example',email:'test@example.invalid'},
    getAttribute(k){return k==='action'?endpoint:attrs[k];},
    querySelector(s){return s==='[name="interest"]'?{value:interest}:s==='[name="action"]'?{value:'complete99_submit_lead'}:s==='[name="language"]'?{value:language}:button;},
    hasAttribute(k){return k in attrs;}, setAttribute(k,v){attrs[k]=v;}, removeAttribute(k){delete attrs[k];},
    reportValidity(){return valid;},parentNode:{insertBefore(){}}, addEventListener(type,fn){this[type]=fn;}
  };
  const window = {
    location:{href:'https://complete99.co.il/request-proposal/',origin:'https://complete99.co.il'},
    FormData:class{constructor(f){this.values={...f.values};}},
    AbortController:class{constructor(){this.signal={};}abort(){}},
    setTimeout(){return 1;},clearTimeout(){},
    async fetch(url,options){state.calls++;state.payload=options; if(failure){throw Error('offline');} return response;}
  };
  const context={window,URL,document:{querySelector(){return form;},createElement(){return status;}}};
  vm.runInNewContext(source,context);
  return {form,button,status,attrs,state,context,submit:()=>form.submit({preventDefault(){}})};
}

(async()=>{
  for(const language of ['he','en']){
    let f=fixture({language,response:{ok:true,redirected:true,status:200,url:'https://complete99.co.il/request-proposal/?c99_sent=1'}});
    await f.submit(); assert.equal(f.form.hidden,true); assert.equal(f.status['data-state'],'success');
    assert(f.status.focused); assert.equal(f.state.calls,1); assert.equal(f.state.payload.credentials,'same-origin');
    await f.submit(); assert.equal(f.state.calls,1);
    for(const status of [400,403,429,500]){
      f=fixture({language,response:{ok:false,status,redirected:false,url:'https://complete99.co.il/wp-admin/admin-post.php'}});
      await f.submit(); assert.equal(f.form.hidden,false); assert.equal(f.status['data-state'],'error');
      assert.equal(f.form.values.contact_name,'Example'); assert.equal(f.button.disabled,false); assert(!('aria-busy' in f.attrs));
    }
  }
  for(const response of [
    {ok:true,redirected:false,url:'https://complete99.co.il/request-proposal/?c99_sent=1'},
    {ok:true,redirected:true,url:'https://outside.example/request-proposal/?c99_sent=1'},
    {ok:true,redirected:true,url:'https://complete99.co.il/other/?c99_sent=1'},
    {ok:true,redirected:true,url:'https://complete99.co.il/request-proposal/'},
  ]) { const f=fixture({response}); await f.submit(); assert.equal(f.status['data-state'],'error');assert(!f.form.hidden); }
  const offline=fixture({failure:true}); await offline.submit();assert.equal(offline.state.calls,1); assert.equal(offline.status['data-state'],'error');
  const invalid=fixture({valid:false});await invalid.submit();assert.equal(invalid.state.calls,0);
  for(const options of [{interest:'institutional-service'},{endpoint:'https://outside.example/wp-admin/admin-post.php'}]) {assert.equal(fixture(options).form.submit,undefined);}
  const pending=fixture();let resolve;
  pending.context.window.fetch=()=>{pending.state.calls++;return new Promise(r=>{resolve=r;});};
  const first=pending.submit(); await pending.submit();assert.equal(pending.state.calls,1);assert(pending.button.disabled);
  resolve({ok:false,status:500,url:pending.form.getAttribute('action')});await first;assert(!pending.button.disabled);
  const once=fixture();const handler=once.form.submit;vm.runInNewContext(source,once.context);assert.equal(once.form.submit,handler);
  assert(!/localStorage|sessionStorage|sendBeacon/.test(source));
  console.log('Group enquiry recovery contracts passed');
})().catch(error=>{console.error(error);process.exitCode=1;});
