{{--
    Input form Bootstrap dengan label, nilai lama (old), dan pesan error di bawah field.
    Contoh: <x-form.input name="email" label="Email" type="email" required />
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'help' => null,
    'required' => false,
    'prefix' => null,
])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $fieldValue = $type === 'password' ? null : old($name, $value);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }} @if ($required)<span class="text-danger">*</span>@endif
        </label>
    @endif

    <div @class(['input-group' => $prefix, 'has-validation' => $prefix && $hasError])>
        @if ($prefix)
            <span class="input-group-text">{{ $prefix }}</span>
        @endif

        <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}"
               @if ($fieldValue !== null) value="{{ $fieldValue }}" @endif
               @required($required)
               {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $hasError]) }}>

        @error($name)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
