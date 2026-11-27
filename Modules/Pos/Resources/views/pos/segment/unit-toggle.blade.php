{{-- Rp / % switch on the title row of a discount field; the field's own prefix shows the chosen unit. $model is the
     Alpine expression holding 'amount' or 'percent', $set the method that changes it. Neither the title row nor the field
     may be a <label>: a click on a label presses its first button. --}}
<span class="pos-unit" role="group" aria-label="{{ $label }}">
    <button type="button" class="pos-unit__option" :class="{ 'is-active': {{ $model }} !== 'percent' }" :aria-pressed="{{ $model }} !== 'percent'" @click.stop="{{ $set }}('amount')" @isset($disabled) :disabled="{{ $disabled }}" @endisset>Rp</button>
    <button type="button" class="pos-unit__option" :class="{ 'is-active': {{ $model }} === 'percent' }" :aria-pressed="{{ $model }} === 'percent'" @click.stop="{{ $set }}('percent')" @isset($disabled) :disabled="{{ $disabled }}" @endisset>%</button>
</span>
