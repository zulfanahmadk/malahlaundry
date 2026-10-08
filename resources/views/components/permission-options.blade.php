@props(['name' => 'permissions', 'selected' => []])
<div data-permission-options class="stack">
@foreach(\App\Support\Access::features() as $feature => $label)
    <div data-access-feature="{{ $feature }}" style="border-bottom:1px solid var(--border-color, #e5e7eb);padding:10px 0">
        <strong>{{ $label }}</strong>
        <div style="display:flex;flex-wrap:wrap;gap:10px 20px;margin-top:8px">
        @foreach(\App\Support\Access::actions()[$feature] as $action => $actionLabel)
            <label data-permission-option style="display:flex;gap:6px;align-items:center;font-weight:normal"><input type="checkbox" name="{{ $name }}[]" value="{{ $feature.'.'.$action }}" data-permission-action="{{ $action }}" @checked(in_array($feature.'.'.$action, $selected))> {{ $actionLabel }}</label>
        @endforeach
        </div>
    </div>
@endforeach
</div>
