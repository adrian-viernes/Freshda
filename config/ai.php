<?php

/*
|--------------------------------------------------------------------------
| Fish recognition model service
|--------------------------------------------------------------------------
|
| PHP cannot run a YOLO model itself, so species + freshness prediction is
| delegated to the trained YOLOv8 model served by a small FastAPI process.
| Point AI_SERVICE_URL at that process's base URL (for example
| http://127.0.0.1:8001) and every scan image is analysed by the model.
|
| The client POSTs a multipart "image" field to {url}/predict and expects
| JSON matching this contract:
|
|     {
|         "species": "Tilapia",
|         "species_label": "Oreochromis niloticus",   // optional
|         "species_confidence": 0.94,                 // 0..1 (percent also accepted)
|         "freshness": "fresh",                       // fresh | moderate | not_fresh
|         "freshness_percentage": 87,                 // 0..100
|         "freshness_confidence": 0.87                // optional
|     }
|
| While AI_SERVICE_URL is empty — and whenever the service is unreachable,
| slower than the timeout, answers with an error, or breaks the contract —
| FishAiService falls back to deterministic simulated results, so a scan
| never fails just because the model is offline.
|
*/

return [

    'url' => env('AI_SERVICE_URL'),

    'timeout' => (int) env('AI_SERVICE_TIMEOUT', 15),

];
