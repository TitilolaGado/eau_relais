export async function postJSON(url,data){
 const csrf=await fetch('/session-csrf',{credentials:'same-origin',cache:'no-store'});if(!csrf.ok)throw new Error('Connexion au serveur impossible.');
 const {token}=await csrf.json();const body=data instanceof FormData?data:JSON.stringify(data);
 const response=await fetch(url,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-TOKEN':token,...(data instanceof FormData?{}:{'Content-Type':'application/json'})},body});
 const result=await response.json().catch(()=>({message:'Le serveur ne peut pas traiter cette demande.'}));if(!response.ok){const e=new Error(Object.values(result.errors||{}).flat().join(' ')||result.message||'Échec de la demande.');e.status=response.status;throw e;}return result;
}
