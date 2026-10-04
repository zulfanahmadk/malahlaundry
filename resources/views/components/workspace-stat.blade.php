@props(['label', 'value', 'note' => '', 'icon' => null, 'tone' => '', 'date' => false])
<div class="stat-card">
    <div class="stat-label">@if($icon)<span class="stat-icon {{ $tone }}"><x-figma-icon :name="$icon" /></span>@endif<span>{{ $label }}</span></div>
    <div class="stat-value {{ $date ? 'date' : '' }}">{{ $value }}</div>
    <p class="stat-note {{ $tone }}">{{ $note }}</p>
</div>
