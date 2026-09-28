@props(['variable' => 'password'])

@php
    // har rule = [label, password ke x-model pe JS check]
    $passwordRules = [
        ['8+ characters', "{$variable}.length >= 8"],
        ['Upper & lowercase', "/[a-z]/.test({$variable}) && /[A-Z]/.test({$variable})"],
        ['A number', "/[0-9]/.test({$variable})"],
        ['A symbol', "/[^A-Za-z0-9]/.test({$variable})"],
    ];
@endphp

{{-- aise x-data ke andar rakho jahan {{ $variable }} password input ka x-model ho, jaise x-data="{ password: '' }" --}}
<ul class="mt-2 flex flex-wrap gap-2">
    @foreach ($passwordRules as [$ruleLabel, $ruleCheck])
        <li class="password-chip" :class="{ 'is-met': {{ $ruleCheck }} }">
            <iconify-icon :icon="{{ $ruleCheck }} ? 'tabler:check' : 'tabler:circle-dashed'"></iconify-icon>
            {{ $ruleLabel }}
        </li>
    @endforeach
</ul>
