@props(['name', 'checked' => true, 'label', 'note' => '', 'icon' => 'imgIconSemantic'])
<div class="preference"><span class="stat-icon"><x-figma-icon :name="$icon" /></span><span><strong>{{ $label }}</strong><small>{{ $note }}</small></span>
<label class="switch"><input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" name="{{ $name }}" value="1" @checked($checked) aria-label="{{ $label }}"><span class="toggle-on"><x-figma-icon name="imgToggle" /></span><span class="toggle-off" aria-hidden="true"></span></label></div>
