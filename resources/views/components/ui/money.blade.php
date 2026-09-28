@props(['amount'])
<span {{ $attributes }}>{{ \App\Helpers\MoneyFormatter::format($amount) }}</span>
