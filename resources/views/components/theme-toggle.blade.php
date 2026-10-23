@props(['label' => false])

<button
    type="button"
    {{ $attributes->class(['theme-toggle', 'theme-toggle--labelled' => $label]) }}
    data-theme-toggle
    aria-label="Chuyển sang giao diện sáng"
    aria-pressed="true"
    title="Chuyển sang giao diện sáng"
>
    <span class="theme-toggle__icon theme-toggle__icon--sun"><x-icon name="sun" :size="17" /></span>
    <span class="theme-toggle__icon theme-toggle__icon--moon"><x-icon name="moon" :size="17" /></span>
    @if($label)<span class="theme-toggle__text" data-theme-label>Giao diện tối</span>@endif
</button>
