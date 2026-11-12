{{--
    The one calendar date picker of the ERP (the HTML reference's "Tanggal Lahir"): a button that opens a month grid with
    month and year views, and a hidden input that posts the date as yyyy-mm-dd.
        <x-erp.field label="Tanggal Lahir" for="birth_of_date"><x-erp.date-picker id="birth_of_date" name="birth_of_date" :value="old('birth_of_date', $data->birth_of_date ?? '')" /></x-erp.field>
    The button carries the id so the label focuses it; the hidden input carries the name.
--}}
@props(['id', 'name', 'value' => '', 'placeholder' => 'dd/mm/yyyy'])
@php($initial = $value ? \Illuminate\Support\Str::substr((string) $value, 0, 10) : '')
<div class="relative" x-data="{
    value: @js($initial),
    calOpen: false, pickerDirection: 'down', calendarView: 'date',
    currentMonth: new Date().getMonth(), currentYear: new Date().getFullYear(),
    blankDays: [], noOfDays: [],
    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
    initDate() {
        if (this.value && !this.calOpen) {
            const parts = this.value.split('-');
            this.currentYear = parseInt(parts[0]);
            this.currentMonth = parseInt(parts[1]) - 1;
        }
        const daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
        const dayOfWeek = new Date(this.currentYear, this.currentMonth, 1).getDay();
        this.blankDays = Array.from({ length: dayOfWeek }, (_, i) => i);
        this.noOfDays = Array.from({ length: daysInMonth }, (_, i) => i + 1);
    },
    isDateSelected(day) {
        if (!this.value) return false;
        const parts = this.value.split('-');
        return parseInt(parts[0]) === this.currentYear && parseInt(parts[1]) - 1 === this.currentMonth && parseInt(parts[2]) === day;
    },
    selectDate(day) {
        this.value = this.currentYear + '-' + String(this.currentMonth + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        this.calOpen = false;
    },
    formatDate(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        return parts[2] + ' ' + this.monthNames[parseInt(parts[1]) - 1].substring(0, 3) + ' ' + parts[0];
    },
    navigateCalendar(step) {
        if (this.calendarView === 'date') {
            this.currentMonth += step;
            if (this.currentMonth < 0) { this.currentMonth = 11; this.currentYear--; }
            else if (this.currentMonth > 11) { this.currentMonth = 0; this.currentYear++; }
        } else if (this.calendarView === 'month') {
            this.currentYear += step;
        } else {
            this.currentYear += step * 12;
        }
        this.initDate();
    }
}" x-init="initDate()" @keydown.escape.window="calOpen = false">
    <input type="hidden" name="{{ $name }}" :value="value">
    <button type="button" id="{{ $id }}" aria-haspopup="dialog" :aria-expanded="calOpen"
        @click="const rect = $event.currentTarget.getBoundingClientRect(); pickerDirection = (window.innerHeight - rect.bottom) < 320 ? 'up' : 'down'; calOpen = !calOpen; if (calOpen) { initDate(); calendarView = 'date'; }"
        class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 flex items-center justify-between text-sm transition-all focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none"
        :class="value ? 'text-gray-900' : 'text-gray-400'">
        <span x-text="value ? formatDate(value) : @js($placeholder)"></span>
        <i class="ph-bold ph-calendar-blank text-gray-400 shrink-0" aria-hidden="true"></i>
    </button>

    <div x-show="calOpen" x-cloak style="display: none" class="fixed inset-0 z-[15]" @click="calOpen = false"></div>
    <div x-show="calOpen" x-cloak style="display: none" x-transition role="dialog" aria-label="Pilih tanggal"
        class="absolute z-20 w-[260px] sm:w-[280px] bg-white border border-gray-200 rounded-2xl shadow-xl overflow-hidden p-4 right-0 md:right-auto"
        :class="pickerDirection === 'up' ? 'bottom-full mb-1' : 'mt-1'">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-[13px] text-gray-800 cursor-pointer hover:text-emerald-600 transition"
                @click="calendarView = calendarView === 'date' ? 'month' : (calendarView === 'month' ? 'year' : 'date')"
                x-text="calendarView === 'date' ? monthNames[currentMonth] + ' ' + currentYear : (calendarView === 'month' ? currentYear : (currentYear - 4) + ' - ' + (currentYear + 7))"></h3>
            <div class="flex gap-1">
                <button type="button" @click.prevent="navigateCalendar(-1)" aria-label="Sebelumnya" class="w-7 h-7 flex items-center justify-center rounded-full bg-gray-50 hover:bg-gray-100 text-gray-600 transition"><i class="ph-bold ph-caret-left text-[12px]"></i></button>
                <button type="button" @click.prevent="navigateCalendar(1)" aria-label="Berikutnya" class="w-7 h-7 flex items-center justify-center rounded-full bg-gray-50 hover:bg-gray-100 text-gray-600 transition"><i class="ph-bold ph-caret-right text-[12px]"></i></button>
            </div>
        </div>

        <div x-show="calendarView === 'date'">
            <div class="grid grid-cols-7 gap-1 text-center mb-2">
                @foreach (['M', 'S', 'S', 'R', 'K', 'J', 'S'] as $dayName)<div class="text-[10px] font-medium text-gray-400">{{ $dayName }}</div>@endforeach
            </div>
            <div class="grid grid-cols-7 gap-y-1 text-center">
                <template x-for="blankday in blankDays"><div class="text-[12px] py-1 text-gray-300"></div></template>
                <template x-for="day in noOfDays">
                    <div class="relative mt-1 mb-1">
                        <div @click="selectDate(day)" class="text-[12px] py-1.5 cursor-pointer flex items-center justify-center mx-auto transition-colors"
                            :class="isDateSelected(day) ? 'font-bold text-gray-900 rounded-full shadow-sm relative z-10 w-7 h-7' : 'text-gray-700 hover:bg-gray-50 rounded-full w-7 h-7'"
                            :style="isDateSelected(day) ? 'background-color: #c5f037;' : ''" x-text="day"></div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="calendarView === 'month'" x-cloak style="display: none" class="grid grid-cols-3 gap-2 mt-2">
            <template x-for="(m, i) in monthNames">
                <div class="text-[12px] py-2.5 text-center rounded-xl cursor-pointer transition-colors font-medium"
                    :class="currentMonth === i ? 'text-gray-900 shadow-sm' : 'text-gray-700 hover:bg-gray-50'"
                    :style="currentMonth === i ? 'background-color: #c5f037;' : ''"
                    @click="currentMonth = i; calendarView = 'date'; initDate()" x-text="m.substring(0, 3)"></div>
            </template>
        </div>

        <div x-show="calendarView === 'year'" x-cloak style="display: none" class="grid grid-cols-3 gap-2 mt-2">
            <template x-for="y in Array.from({ length: 12 }, (_, i) => currentYear - 4 + i)">
                <div class="text-[12px] py-2.5 text-center rounded-xl cursor-pointer transition-colors font-medium"
                    :class="currentYear === y ? 'text-gray-900 shadow-sm' : 'text-gray-700 hover:bg-gray-50'"
                    :style="currentYear === y ? 'background-color: #c5f037;' : ''"
                    @click="currentYear = y; calendarView = 'month'; initDate()" x-text="y"></div>
            </template>
        </div>
    </div>
</div>
