<?php
$botGuard = new \App\Services\TurnstileService(new \App\Core\HttpClient(), (array)config('integrations.turnstile', []));
if ($botGuard->enabled()): ?>
<div class="turnstile-field my-3" data-turnstile-field data-sitekey="<?= e($botGuard->siteKey()) ?>" data-action="<?= e($turnstileAction) ?>">
    <div data-turnstile-widget></div>
    <small data-turnstile-status role="status">Đang chuẩn bị xác minh chống bot…</small>
</div>
<?php endif; ?>
