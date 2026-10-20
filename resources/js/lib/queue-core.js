// Le même identifiant et le même secret sont conservés après une réponse perdue.
export async function deliver(items,send,onSuccess,onFailure){for(const item of items){try{const result=await send(item);if(result.received!==true)throw new Error('Réception non confirmée.');await onSuccess(item,result);}catch(error){await onFailure(item,error);if(!error.status || error.status>=500)break;}}}
