<?php

beforeEach(function () {
    seedLedger();
});

it('returns the dashboard summary', function () {
    $data = $this->getJson('/api/summary')->assertOk()->json();

    expect($data['tenant'])->toBe('Test client OÜ')
        ->and($data['months'])->toHaveCount(3)
        ->and($data['accounts'])->toHaveCount(7)
        ->and((float) $data['months'][2]['cashflow'])->toBe(23130.0)
        ->and((float) $data['months'][2]['profit'])->toBe(8800.0);
});

it('rejects an ask with no question (422) without calling the model', function () {
    $this->postJson('/api/ask', [])->assertStatus(422);
});
