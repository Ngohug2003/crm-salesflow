<?php

use App\Services\TaskDescriptionSanitizer;

it('keeps approved rich text and removes executable markup', function (): void {
    $html = <<<'HTML'
    <h2 onclick="alert(1)">Kế hoạch</h2>
    <p>Gọi <strong>khách hàng</strong> trước 10 giờ.</p>
    <a href="javascript:alert(1)" onmouseover="alert(2)">Liên kết xấu</a>
    <a href="https://salesflow.test/tasks/1">Liên kết an toàn</a>
    <img src=x onerror="alert(3)">
    <script>alert(4)</script>
    HTML;

    $safe = app(TaskDescriptionSanitizer::class)->sanitize($html);

    expect($safe)
        ->toContain('<h2>Kế hoạch</h2>')
        ->toContain('<strong>khách hàng</strong>')
        ->toContain('href="https://salesflow.test/tasks/1"')
        ->not->toContain('onclick')
        ->not->toContain('onmouseover')
        ->not->toContain('onerror')
        ->not->toContain('javascript:')
        ->not->toContain('<img')
        ->not->toContain('<script')
        ->not->toContain('alert(4)');
});

it('converts legacy plain text into safe paragraphs', function (): void {
    $safe = app(TaskDescriptionSanitizer::class)->sanitize("Dòng một\nDòng hai");

    expect($safe)->toBe("<p>Dòng một<br>\nDòng hai</p>");
});
