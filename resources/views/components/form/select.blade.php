{{--
    Select Bootstrap dengan label, nilai lama (old), dan pesan error di bawah field.
    $options: array [value => label].
    Contoh: <x-form.select name="type" label="Tipe" :options="$options" placeholder="-- Pilih --" required />
--}}
@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'help' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id', $name);
    $selected = (string) old($name, $value);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }} @if ($required)<span class="text-danger">*</span>@endif
        </label>
    @endif

    <select name="{{ $name }}" id="{{ $id }}" @required($required)
            {{ $attributes->except('id')->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
