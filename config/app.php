<?php
return ['name'=>'EauRelais','env'=>env('APP_ENV','production'),'debug'=>(bool)env('APP_DEBUG',false),'url'=>env('APP_URL','http://localhost:8000'),'timezone'=>'Africa/Porto-Novo','locale'=>'fr','fallback_locale'=>'en','faker_locale'=>'fr_FR','key'=>env('APP_KEY'),'cipher'=>'AES-256-CBC','maintenance'=>['driver'=>'file']];
