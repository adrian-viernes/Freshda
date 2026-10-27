{{--
  One KPI card from a stats grid. The dashboard home, inventory, suppliers
  and deliveries pages all render four of these, with only the numbers and
  the icon changing.

    variant     modifier class, used on both the card and its icon box
                ("total", "fresh", "moderate", "notfresh", ...)
    icon        Remix icon class inside the icon box
    label       small heading shown above the value
    value       the big number (escaped as-is)
    valueAttrs  attribute => value pairs for the value span, e.g.
                ['id' => 'statTotal'] or ['style' => 'font-size: 1.15rem']
    sub         supporting line under the value. Raw HTML so a page can wrap
                part of it (inventory wraps the weight); escape anything
                dynamic with e().
--}}
<div class="stat-card {{ $variant }}">
  <div class="stat-top">
    <span class="stat-label">{{ $label }}</span>
    <div class="stat-icon {{ $variant }}">
      <i class="{{ $icon }}"></i>
    </div>
  </div>
  <span class="stat-value"@foreach ($valueAttrs as $attr => $attrValue) {!! ' '.$attr.'="'.e($attrValue).'"' !!}@endforeach>{{ $value }}</span>
  <span class="stat-sub">{!! $sub !!}</span>
</div>
