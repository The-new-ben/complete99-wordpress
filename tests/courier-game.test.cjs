// Actual reviewed courier HTML simulation. Canvas stubs do not verify graphics.
const {JSDOM}=require('../games/node_modules/jsdom');
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const source=fs.readFileSync(path.join(__dirname,'../games/courier/index.html'),'utf8');
const hook=`window.testGame={startMission,update,actCtx,doAct,finish,setPause,thiefStep,sloshStep,rideStep,curDest,
 get state(){return G},get bike(){return B},get walker(){return Pd},get thief(){return TH},get slosh(){return S},
 get saved(){return sv},get paused(){return paused},setInput(v){kd=v},clearTraffic(){cars=[];walkers=[];crossers=[]},pickup:PICK,missions:MS};`;
const testSource=source.replace('\nlayout();GND=', '\n'+hook+'layout();GND=');
assert.notEqual(testSource,source);let raf;const timers=[];
const noop=()=>{};
const ctx=new Proxy({measureText:s=>({width:s.length*7}),createLinearGradient:()=>({addColorStop:noop}),createRadialGradient:()=>({addColorStop:noop})},{get:(o,k)=>k in o?o[k]:noop,set:(o,k,v)=>(o[k]=v,true)});
const dom=new JSDOM(testSource,{url:'https://complete99.co.il/delivery-game/',runScripts:'dangerously',beforeParse(w){
 w.HTMLCanvasElement.prototype.getContext=()=>ctx;
 w.requestAnimationFrame=fn=>{raf=fn;return 1};w.setTimeout=fn=>{timers.push(fn);return timers.length};w.clearTimeout=noop;
}});
const w=dom.window,api=w.testGame,doc=w.document;
assert(api,'application initializes');
const tick=(s,fn=api.update)=>{for(let i=0;i<Math.ceil(s/.02);i++)fn(.02)};
function pickup(){Object.assign(api.bike,{x:api.pickup.x,y:api.pickup.y,vx:0,vy:0});assert.equal(api.actCtx().k,'collect');api.doAct('collect');api.doAct('secure');}
function park(){const d=api.curDest();Object.assign(api.bike,{x:d.x,y:d.y,vx:0,vy:0});api.doAct('park');}
for(let level=0;level<5;level++){
 api.startMission(level);pickup();assert(api.state.secured);
 for(let stop=0;stop<api.missions[level].stops.length;stop++){
  park();assert.equal(api.state.mode,'walk');api.doAct('lock');assert(api.bike.locked);
  Object.assign(api.walker,{x:api.curDest().dx,y:api.curDest().dy});assert.equal(api.actCtx().k,'deliver');api.doAct('deliver');
  if(!api.state.allDone){Object.assign(api.walker,{x:api.bike.x,y:api.bike.y});assert.equal(api.actCtx().k,'unlock');api.doAct('unlock');assert.equal(api.state.mode,'ride');}
 }
 api.finish();assert.equal(api.state.fr.stars,3);assert(api.state.fr.score>0);console.log(`Mission ${level+1}: ${api.state.res.length} cargo items, all stops completed`);
}
api.startMission(0);api.clearTraffic();let y=api.bike.y;api.setInput({u:1});tick(.5);assert(api.bike.y<y);let v=Math.hypot(api.bike.vx,api.bike.vy);api.setInput({d:1});tick(.2);assert(Math.hypot(api.bike.vx,api.bike.vy)<v,'brakes reduce speed');
api.setInput({});pickup();doc.querySelector('#act').click();tick(2);
api.startMission(1);api.clearTraffic();Object.assign(api.bike,{x:api.pickup.x,y:api.pickup.y});doc.querySelector('#act').click();tick(1.1);assert(api.state.cargoOn,'tap completes pickup without requiring a held button');
api.setPause(true);let t=api.state.t;raf(1000);assert.equal(api.state.t,t);api.setPause(false);assert(!api.paused);
api.state.secured=false;api.slosh.x=1;api.slosh.vx=2;const lvl=api.state.items[0].lvl;api.sloshStep(.05,0,0,0);assert(api.state.items[0].lvl<lvl,'slosh outside rim spills actual cargo');
api.startMission(3);pickup();park();api.walker.x=api.bike.x+150;api.walker.y=api.bike.y;tick(5,api.thiefStep);assert.equal(api.thief.mode,'approach');Object.assign(api.thief,{x:api.bike.x,y:api.bike.y});api.thiefStep(.01);assert(api.state.stolen);Object.assign(api.walker,{x:api.thief.x,y:api.thief.y});api.thiefStep(.01);assert(!api.state.stolen,'catching thief recovers bike');
api.startMission(3);pickup();park();api.doAct('lock');api.walker.x+=200;tick(15,api.thiefStep);assert.equal(api.thief.mode,'off','locked bike prevents theft');
api.startMission(0);const coins=api.saved.coins;api.finish();assert(!api.state.over,'cannot finish an undelivered run');
pickup();api.state.items.forEach(i=>i.lvl=0);api.update(.01);assert(api.state.over);assert.equal(api.saved.coins,coins,'empty cargo failure awards nothing');
api.startMission(0);api.state.t=api.missions[0].par*2.5;api.update(.1);assert(api.state.over,'timeout is a real loss');
doc.querySelector('[data-a="play"]').click();assert(!api.state.over);assert.equal(api.state.t,0);assert.equal(api.state.cargoOn,false);
timers.forEach(fn=>fn());assert(!api.state.over,'old completion timer cannot end a replay');
assert.equal(doc.querySelector('meta[name="robots"]').content,'noindex,follow');
assert(!doc.querySelector('script[src],link[rel="stylesheet"],img[src^="http"]'));
dom.window.close();console.log('Actual courier HTML: five mission flows, movement, braking, tap pickup, pause, spill, theft/recovery, lock, loss and replay passed.');
