@props(['name', 'node' => null])
@php($source = \App\Support\Workspace::asset($node ?? trim($__env->yieldContent('figma_node', '127-45')), $name))
@if($source)<span {{ $attributes->class(['icon']) }} aria-hidden="true"><img src="{{ $source }}" alt=""></span>@endif
