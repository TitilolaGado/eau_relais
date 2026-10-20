import {test} from 'node:test';import assert from 'node:assert/strict';import {deliver} from '../resources/js/lib/queue-core.js';
test('ne supprime pas un signalement sans confirmation du serveur',async()=>{let success=0,failure=0;await deliver([{client_uuid:'a'}],async()=>({received:false}),async()=>success++,async()=>failure++);assert.equal(success,0);assert.equal(failure,1);});
test('conserve l’identifiant après une réponse réseau perdue',async()=>{const item={client_uuid:'same-id',tracking_secret:'same-secret'};const sent=[];const send=async v=>{sent.push(v);if(sent.length===1)throw new Error('réseau');return {received:true,reference:'EAU-1'};};let removed=0;await deliver([item],send,async()=>removed++,async()=>{});assert.equal(removed,0);await deliver([item],send,async()=>removed++,async()=>{});assert.deepEqual(sent[0],sent[1]);assert.equal(removed,1);});
test('une validation invalide ne bloque pas les demandes suivantes',async()=>{let received=0;await deliver([{id:1},{id:2}],async i=>{if(i.id===1){const e=new Error('invalide');e.status=422;throw e;}return {received:true}},async()=>received++,async()=>{});assert.equal(received,1);});
test('une panne serveur interrompt la synchronisation sans perdre les demandes',async()=>{let tries=0;await deliver([{},{}],async()=>{tries++;const e=new Error('indisponible');e.status=503;throw e;},async()=>{},async()=>{});assert.equal(tries,1);});

test('IndexedDB conserve un brouillon et synchronise sans conserver les contacts',async()=>{
 await import('fake-indexeddb/auto');
 Object.defineProperty(globalThis,'navigator',{configurable:true,value:{onLine:true}});
 globalThis.window=new EventTarget();
 let attempts=0;let fail=true;globalThis.fetch=async(url,options)=>{
 if(url==='/session-csrf')return {ok:true,json:async()=>({token:'csrf-test'})};
 attempts++;if(fail)throw new Error('Réseau perdu');
 assert.equal(options.body.get('client_uuid'),'persistent-id');
 assert.equal(options.body.get('tracking_secret'),'stable-secret');
 return {ok:true,json:async()=>({received:true,reference:'EAU-PERSIST'})};
 };
 const q=await import('../resources/js/lib/offline.js');
 await q.saveDraft({form:{reporter_name:'Déclarant'}});assert.equal((await q.loadDraft()).form.reporter_name,'Déclarant');
 await q.deleteDraft();assert.equal(await q.loadDraft(),undefined);
 await q.saveReport({client_uuid:'persistent-id',tracking_secret:'stable-secret',reporter_name:'Nom privé',phone:'0100000000'});
 await q.syncReports();assert.equal((await q.pending()).length,1);
 fail=false;await Promise.all([q.syncReports(),q.syncReports()]);assert.equal(attempts,2);assert.equal((await q.pending()).length,0);
 const receipt=(await q.receipts())[0];assert.equal(receipt.reference,'EAU-PERSIST');assert.equal(receipt.secret,'stable-secret');assert.equal(receipt.phone,undefined);assert.equal(receipt.reporter_name,undefined);
 await q.removeReceipt(receipt.reference);assert.equal((await q.receipts()).length,0);
});
