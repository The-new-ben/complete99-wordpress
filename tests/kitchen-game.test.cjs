// Execute the reviewed HTML's actual simulation in a DOM fixture, not a second engine.
// Native desktop/mobile layout and pointer QA are separate from these tests.
const {JSDOM}=require('../games/node_modules/jsdom');
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const source=fs.readFileSync(path.join(__dirname,'../games/kitchen/index.html'),'utf8');
const hook=`window.testGame={startShift,update,finish,openSheet,drawSheet,go,serve,autoServe,setPause,title,needOf,fmt,
 get state(){return G},get selection(){return SEL},get paused(){return paused},get saved(){return sv},dishes:D,shifts:SH};`;
const testSource=source.replace('\npaintMute();title();','\n'+hook+'paintMute();title();');
assert.notEqual(testSource,source);
let raf;
const dom=new JSDOM(testSource,{url:'https://complete99.co.il/kitchen-game/',runScripts:'dangerously',
 beforeParse(w){w.requestAnimationFrame=fn=>{raf=fn;return 1};w.setTimeout=()=>1;w.clearTimeout=()=>{};}});
const w=dom.window,api=w.testGame,doc=w.document;
assert(api,'application initialized without script errors');
assert.equal(api.fmt(.75),'0.75','recipe scaling must not round three quarters to 0.8');

for(let level=0;level<6;level++){
 api.startShift(level);let iterations=0;
 while(!api.state.over&&iterations++<2000){
  api.update(.2);
  const s=api.state;
  for(const ticket of s.tickets.filter(t=>!t.closed))for(let k=0;k<ticket.lines.length;k++){
   const line=ticket.lines[k];
   if(line.done<line.q&&s.b.some(b=>b.state==='ready'&&b.dish===line.d))api.serve(ticket,k);
  }
  for(let b=0;b<2;b++)if(s.b[b].state==='empty'){
   const needed=s.tickets.filter(t=>!t.closed).flatMap(t=>t.lines).find(l=>api.needOf(l.d)>0);
   if(!needed)continue;
   api.openSheet(b);const sel=api.selection;sel.d=needed.d;
   sel.n=Math.min(s.pots[sel.p].cap,api.needOf(sel.d));
   sel.answer=api.dishes[sel.d].ing[0][1]*sel.n/4;api.go();
  }
 }
 assert(iterations<2000,`shift ${level+1} terminates`);
 api.finish();
 assert.equal(api.state.served,api.state.s.d,`all diners served in shift ${level+1}`);
 assert.equal(api.state.stars,3,`shift ${level+1} supports perfect completion`);
 console.log(`Shift ${level+1}: ${api.state.served} diners, ${api.state.score} points, 3 stars`);
}

api.startShift(1);api.update(.1);api.openSheet(0);
const correct=api.dishes[api.selection.d].ing[0][1]*api.selection.n/4;
for(const key of ['Enter',' ']){
 const answer=[...doc.querySelectorAll('[data-a="answer"]')].find(b=>+b.dataset.v===correct);
 answer.focus();const event=new w.KeyboardEvent('keydown',{key,bubbles:true,cancelable:true});answer.dispatchEvent(event);
 assert.equal(event.defaultPrevented,false,`${key} preserves native focused answer activation`);
 assert.equal(api.state.mathErrors,0,'focused Enter must not submit the sheet prematurely');
 // jsdom does not implement keyboard default activation; simulate the preserved native click.
 answer.click();assert.equal(api.selection.answer,correct);
}
api.selection.answer=-1;api.go();assert.equal(api.state.b[0].state,'empty','wrong scaling does not light burner');
assert.equal(api.state.mathErrors,1);assert(doc.querySelector('#toast').textContent.includes('מחשבים יחד'));
api.selection.answer=api.dishes[api.selection.d].ing[0][1]*api.selection.n/4;api.go();
assert.equal(api.state.b[0].state,'cook');
api.setPause(true);const before=api.state.t;raf(1000);assert.equal(api.state.t,before,'animation loop respects pause');
api.setPause(false);assert.equal(api.paused,false);
for(let n=0;n<200;n++)api.update(.2);
assert.equal(api.state.b[0].state,'burnt','unserved food burns');
doc.querySelector('[data-b="0"]').click();assert.equal(api.state.b[0].state,'empty','burnt pot can be cleared');

api.startShift(0);for(let n=0;n<600;n++)api.update(.2);api.finish();
assert.equal(api.state.stars,0,'doing nothing fails instead of awarding progress');
doc.querySelector('[data-a="play"]').click();assert.equal(api.state.served,0,'replay starts fresh');

assert.equal(doc.querySelector('meta[name="robots"]').content,'noindex,follow');
assert(!doc.querySelector('script[src],link[rel="stylesheet"],img[src^="http"]'),'no remote runtime assets');
dom.window.close();
console.log('Actual kitchen HTML: six full shifts, quantity rejection, pause, burning, recovery and replay passed.');
