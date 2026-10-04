@props(['points'])
@php($max = max(1, $points->max('value')))
<div class="chart" role="img" aria-label="{{ $points->map(fn ($point) => $point['label'].': '.\App\Support\Workspace::money($point['value']))->join('; ') }}">
@foreach($points as $point)
    <div class="bar-column"><span class="value" title="{{ \App\Support\Workspace::money($point['value']) }}">{{ \App\Support\Workspace::money($point['value']) }}</span><div class="bar" style="height:{{ max(1, round($point['value'] / $max * 125)) }}px"></div><span class="label">{{ $point['label'] }}</span></div>
@endforeach
</div>
