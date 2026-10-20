<script setup>
import {ref} from 'vue';const failed=ref(false);
defineProps({scene:{type:String,default:'communaute-eau'},alt:{type:String,required:true},eager:{type:Boolean,default:false},sizes:{type:String,default:'(max-width: 700px) 100vw, 50vw'}});
</script>
<template><figure :class="['editorial-photo',{'photo-unavailable':failed}]"><img :src="`/images/${scene}-1280.webp`" :srcset="`/images/${scene}-640.webp 640w, /images/${scene}-1280.webp 1280w`" :sizes="sizes" width="1536" height="1024" :alt="alt" :loading="eager?'eager':'lazy'" :fetchpriority="eager?'high':'auto'" decoding="async" @error="failed=true"><figcaption>Visuel illustratif généré par IA</figcaption><slot/></figure></template>
