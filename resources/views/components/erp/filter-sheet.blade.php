{{--
    Mobile filter bottom sheet: cabang (searchable), status (chips) and periode tanggal
    (calendar + quick picks). The sheet edits a draft; the page only hears about it when
    "Terapkan" is pressed.

    Open it from any button:
        @click="$dispatch('erp-filter-open', { id: 'wholesale', values: { branch, status, start, end } })"
    Listen for the result:
        window.addEventListener('erp-filter-apply', (e) => { e.detail.id; e.detail.values })

    Element ids and the data-{id}-mobile-branch hook are derived from `id`; the filter
    stylesheet targets them. The dialog itself is `#{id}-filter-sheet`.

    desktop="hidden" (default): phone sheet only, hidden from 768px up (the page has its own
    desktop filter). desktop="modal": the same sheet becomes a centred dialog from 640px up.
    groups: more choice groups after status, each ['key', 'label', 'options' => [value => label],
    'type' => 'chips'|'radio', 'default' => 'all']. Chosen values come back as values.extra[key].
    Optional per group: 'showWhen' => [otherKey => value|[values]] (shown only while those groups match),
    'resets' => [otherKey] (choosing a value returns those groups to their default), 'scroll' => true (radio
    list scrolls after ~5 rows), 'disabled' => [values] (not selectable), and 'optionsBy' => otherKey with
    'options' => [otherValue => [value => label]] (the visible list follows the other group's value).
    dates-when: [groupKey => value|[values]] shows the period section only while those groups match.
    quick-ranges: which quick period buttons to show, in order: week, today, month.
    search-label / search-placeholder: add a text search box on top (returned as values.search).
    with-dates=false: branch and status only (no period section or calendar).
    default-branch: the branch id that means "no branch filter" (a single-branch user's own branch).
    timezone: IANA zone used for "Hari Ini" / "Minggu Ini". week-start: "sunday" | "monday".
    reset-applies: Reset also applies and closes (otherwise it only clears the draft).
--}}
@props([
    'id',
    'title',
    'subtitle' => null,
    'ariaLabel' => null,
    'branches' => [],
    'showBranchAll' => true,
    'branchLabel' => 'Cabang',
    'branchAllLabel' => 'Semua cabang',
    'defaultBranch' => 'all',
    'statuses' => [],
    'statusLabel' => 'Status',
    'dateLabel' => 'Periode Tanggal',
    'withDates' => true,
    'searchLabel' => null,
    'searchPlaceholder' => 'Cari...',
    'groups' => [],
    'datesWhen' => null,
    'quickRanges' => ['week', 'today'],
    'desktop' => 'hidden',
    'timezone' => null,
    'weekStart' => 'sunday',
    'resetApplies' => false,
    'resetLabel' => 'Reset',
    'applyLabel' => 'Terapkan',
])

@once
    <script src="{{ asset('assets/js/erp-filter-sheet.js') }}"></script>
@endonce

@php
    $branchRows = collect($branches)->map(fn ($branch) => ['id' => (string) data_get($branch, 'id'), 'name' => ucwords((string) data_get($branch, 'name'))])->values()->all();
    $sheetConfig = [
        'id' => $id,
        'branches' => $branchRows,
        'showBranchAll' => (bool) $showBranchAll,
        'defaultBranch' => (string) $defaultBranch,
        'timezone' => $timezone,
        'weekStart' => $weekStart,
        'resetApplies' => (bool) $resetApplies,
        'desktop' => $desktop,
        'search' => $searchLabel !== null,
        'groups' => collect($groups)->map(fn ($group) => ['key' => (string) $group['key'], 'default' => (string) ($group['default'] ?? 'all'), 'resets' => array_map('strval', $group['resets'] ?? [])])->values()->all(),
    ];
    $quickLabels = ['week' => 'Minggu Ini', 'today' => 'Hari Ini', 'month' => 'Bulan Ini'];
    $quickOutline = 'flex flex-1 cursor-pointer items-center justify-center gap-[6px] rounded-[12px] border-[1px] border-solid border-gray-200 bg-white px-[8px] py-[10px] text-[12px] font-semibold text-gray-700 shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition-colors hover:border-gray-300 hover:bg-gray-50 active:scale-95';
    $quickPrimary = 'flex flex-1 cursor-pointer items-center justify-center gap-[6px] rounded-[12px] bg-[#c5f037] px-[8px] py-[10px] text-[12px] font-semibold text-gray-800 shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition-colors active:scale-95';
    $isModal = $desktop === 'modal';
    $dialogClass = $isModal
        ? 'fixed inset-0 z-[1100] flex items-end justify-center p-0 sm:items-center sm:p-[16px]'
        : 'fixed inset-0 z-[100] flex flex-col justify-end md:hidden';
    $sectionClass = $isModal
        ? 'relative z-10 flex h-auto max-h-[88dvh] w-full max-w-[512px] flex-col overflow-hidden rounded-t-[24px] bg-white pb-0 shadow-2xl sm:rounded-[24px]'
        : 'relative flex max-h-[85vh] flex-col overflow-hidden rounded-t-[24px] bg-white pb-0 shadow-2xl';
    $handleClass = $isModal ? 'flex shrink-0 justify-center pt-[12px] pb-[4px] sm:hidden' : 'flex shrink-0 justify-center pt-[12px] pb-[4px]';
    $titleId = $id . '-filter-title';
    $labelClass = 'block text-[10px] font-semibold uppercase tracking-wider text-gray-500';
    $resetLinkClass = 'cursor-pointer text-[10px] font-semibold text-red-500 transition-colors hover:text-red-600';
    $radio = 'peer h-[16px] w-[16px] cursor-pointer appearance-none rounded-full border-[2px] border-solid border-gray-300 transition-colors checked:border-emerald-500';
    $radioDot = 'absolute h-[8px] w-[8px] scale-0 rounded-full bg-emerald-500 transition-transform duration-200 peer-checked:scale-100';
    $dateButton = 'flex min-w-0 flex-1 cursor-pointer items-center gap-[8px] rounded-[12px] border-[1px] border-solid border-gray-200 bg-white px-[12px] py-[10px] text-left text-[12px] text-gray-700 shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition-colors hover:border-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500';
    $calendarNav = 'flex h-[36px] w-[36px] items-center justify-center rounded-full text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500';
    $calendarCell = 'rounded-[12px] px-[8px] py-[12px] text-[14px] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500';
@endphp

<div x-data="erpFilterSheet(@js($sheetConfig))" @erp-filter-open.window="onOpen($event)" @keydown.escape.window="close()">
    <template x-teleport="body">
        <div id="{{ $id }}-filter-sheet" data-erp-filter-sheet x-show="open" x-cloak class="{{ $dialogClass }}" role="dialog" aria-modal="true" aria-label="{{ $ariaLabel ?? $title }}" aria-labelledby="{{ $titleId }}" @keydown.tab="trapFocus($event)">
            <button type="button" class="absolute inset-0 bg-black/70" @click="close()" aria-label="Tutup filter"></button>
            <section x-ref="panel" tabindex="-1" x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full" class="{{ $sectionClass }}">
                <div class="{{ $handleClass }}"><div class="h-[6px] w-[56px] rounded-full bg-gray-300"></div></div>
                <x-erp.filter-sheet-frame :title="$title" :title-id="$titleId" :subtitle="$subtitle" :subtitle-id="$id . '-filter-subtitle'" body-class="min-h-0 flex-1 overflow-y-auto overscroll-none">
                    <x-slot:close><button type="button" x-ref="closeButton" @click="close()" aria-label="Tutup"><i class="ph-bold ph-x" aria-hidden="true"></i></button></x-slot:close>
                    <x-slot:body>
                        <div x-show="!calendarOpen" class="space-y-[16px]">
                            @if ($searchLabel !== null)
                                <div>
                                    <label for="{{ $id }}-mobile-query" class="{{ $labelClass }} mb-[6px]">{{ $searchLabel }}</label>
                                    <input id="{{ $id }}-mobile-query" type="search" x-model="query" placeholder="{{ $searchPlaceholder }}" autocomplete="off" class="h-[40px] w-full rounded-[12px] border-[1px] border-solid border-gray-200 bg-white px-[12px] text-[12px] outline-none focus:border-emerald-500">
                                </div>
                            @endif
                            @if ($branchRows !== [])
                                <div>
                                    <div class="mb-[6px] flex items-center justify-between">
                                        <label for="{{ $id }}-mobile-branch-search" class="{{ $labelClass }}">{{ $branchLabel }}</label>
                                        <button type="button" x-show="branch !== defaultBranch" @click="branch = defaultBranch" class="{{ $resetLinkClass }}">Reset</button>
                                    </div>
                                    <div class="flex flex-col overflow-hidden rounded-[12px] border-[1px] border-solid border-gray-200 bg-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                                        <div class="relative shrink-0 border-b border-gray-100">
                                            <i class="ph-bold ph-magnifying-glass absolute left-[12px] top-1/2 -translate-y-1/2 text-[12px] text-gray-400" aria-hidden="true"></i>
                                            <input id="{{ $id }}-mobile-branch-search" type="search" x-model="branchSearch" placeholder="Cari cabang..." class="w-full bg-transparent py-[8px] pl-[32px] pr-[12px] text-[12px] outline-none transition-colors focus:bg-gray-50">
                                        </div>
                                        <div id="{{ $id }}-mobile-branches" class="max-h-[128px] divide-y divide-gray-100/60 overflow-y-auto overscroll-none bg-gray-50/30">
                                            @if ($showBranchAll)
                                                <label data-{{ $id }}-mobile-branch="all" x-show="branchMatches('semua cabang')" class="flex cursor-pointer items-center gap-[12px] px-[14px] py-[8px] transition-colors hover:bg-gray-50">
                                                    <span class="relative flex h-[16px] w-[16px] shrink-0 items-center justify-center"><input type="radio" name="{{ $id }}MobileBranch" value="all" x-model="branch" class="{{ $radio }}"><span class="{{ $radioDot }}"></span></span>
                                                    <span class="text-[12px] text-gray-700" :class="branch === 'all' ? 'font-bold text-gray-900' : 'font-medium'">{{ $branchAllLabel }}</span>
                                                </label>
                                            @endif
                                            <template x-for="row in branches" :key="row.id">
                                                <label :data-{{ $id }}-mobile-branch="row.id" x-show="branchMatches(row.search)" class="flex cursor-pointer items-center gap-[12px] px-[14px] py-[8px] transition-colors hover:bg-gray-50">
                                                    <span class="relative flex h-[16px] w-[16px] shrink-0 items-center justify-center"><input type="radio" name="{{ $id }}MobileBranch" :value="row.id" x-model="branch" class="{{ $radio }}"><span class="{{ $radioDot }}"></span></span>
                                                    <span class="text-[12px] text-gray-700" :class="branch === row.id ? 'font-bold text-gray-900' : 'font-medium'" x-text="row.name"></span>
                                                </label>
                                            </template>
                                            <p x-show="visibleBranchCount === 0" x-cloak class="py-[12px] text-center text-[12px] text-gray-400">Cabang tidak ditemukan</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($statuses !== [])
                                <div>
                                    <div class="mb-[6px] flex items-center justify-between">
                                        <span class="{{ $labelClass }}">{{ $statusLabel }}</span>
                                        <button type="button" x-show="status !== 'all'" @click="status = 'all'" class="{{ $resetLinkClass }}">Reset</button>
                                    </div>
                                    <div class="mt-[4px] flex flex-wrap gap-[8px]">
                                        @foreach ($statuses as $value => $label)
                                            <button type="button" @click="status = @js((string) $value)" :aria-pressed="status === @js((string) $value)" :class="status === @js((string) $value) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 font-semibold shadow-[0_1px_2px_rgba(0,0,0,0.05)]' : 'border-gray-200 bg-white text-gray-600 hover:border-emerald-300'" class="cursor-pointer rounded-full border-[1px] border-solid px-[12px] py-[6px] text-[12px] font-medium transition-colors">{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @foreach ($groups as $group)
                                @php
                                    $groupKey = (string) $group['key'];
                                    $groupDefault = (string) ($group['default'] ?? 'all');
                                    $groupRadio = ($group['type'] ?? 'chips') === 'radio';
                                    $groupDisabled = array_map('strval', $group['disabled'] ?? []);
                                    $groupLists = isset($group['optionsBy']) ? $group['options'] : ['' => $group['options']];
                                    $radioBox = 'flex flex-col overflow-hidden rounded-[12px] border-[1px] border-solid border-gray-200 bg-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]';
                                    $radioList = 'divide-y divide-gray-100/60 bg-gray-50/30' . (! empty($group['scroll']) ? ' max-h-[176px] overflow-y-auto overscroll-none' : '');
                                @endphp
                                <div @if (! empty($group['showWhen'])) x-show="matches(@js($group['showWhen']))" x-cloak @endif>
                                    <div class="mb-[6px] flex items-center justify-between">
                                        <span class="{{ $labelClass }}">{{ $group['label'] }}</span>
                                        <button type="button" x-show="extra[@js($groupKey)] !== @js($groupDefault)" @click="setExtra(@js($groupKey), @js($groupDefault))" class="{{ $resetLinkClass }}">Reset</button>
                                    </div>
                                    @foreach ($groupLists as $listKey => $options)
                                        <div @if (isset($group['optionsBy'])) x-show="extra[@js((string) $group['optionsBy'])] === @js((string) $listKey)" x-cloak @endif>
                                            @if ($groupRadio)
                                                <div class="{{ $radioBox }}" role="radiogroup">
                                                    <div class="{{ $radioList }}">
                                                        @foreach ($options as $value => $label)
                                                            @php($optionDisabled = in_array((string) $value, $groupDisabled, true))
                                                            <label class="flex items-center gap-[12px] px-[14px] py-[8px] transition-colors {{ $optionDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-gray-50' }}">
                                                                <span class="relative flex h-[16px] w-[16px] shrink-0 items-center justify-center"><input type="radio" name="{{ $id }}-{{ $groupKey }}-{{ $listKey }}" value="{{ $value }}" :checked="extra[@js($groupKey)] === @js((string) $value)" @change="setExtra(@js($groupKey), @js((string) $value))" @disabled($optionDisabled) class="{{ $radio }}"><span class="{{ $radioDot }}"></span></span>
                                                                <span class="text-[12px] text-gray-700" :class="extra[@js($groupKey)] === @js((string) $value) ? 'font-bold text-gray-900' : 'font-medium'">{{ $label }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @else
                                                <div class="mt-[4px] flex flex-wrap gap-[8px]">
                                                    @foreach ($options as $value => $label)
                                                        @php($optionDisabled = in_array((string) $value, $groupDisabled, true))
                                                        <button type="button" @click="setExtra(@js($groupKey), @js((string) $value))" :aria-pressed="extra[@js($groupKey)] === @js((string) $value)" @disabled($optionDisabled) :class="extra[@js($groupKey)] === @js((string) $value) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 font-semibold shadow-[0_1px_2px_rgba(0,0,0,0.05)]' : 'border-gray-200 bg-white text-gray-600 hover:border-emerald-300'" class="cursor-pointer rounded-full border-[1px] border-solid px-[12px] py-[6px] text-[12px] font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50">{{ $label }}</button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach

                            @if ($withDates)
                            <div @if ($datesWhen) x-show="matches(@js($datesWhen))" x-cloak @endif>
                                <div class="mb-[6px] flex items-center justify-between">
                                    <span class="{{ $labelClass }}">{{ $dateLabel }}</span>
                                    <button type="button" x-show="start || end" @click="start = ''; end = ''" class="{{ $resetLinkClass }}">Reset</button>
                                </div>
                                <div class="flex w-full flex-col gap-[8px] sm:flex-row">
                                    <button id="{{ $id }}-mobile-start-date-trigger" type="button" aria-label="Tanggal mulai" @click="openCalendar($event.currentTarget, 'start')" :aria-expanded="calendarOpen" aria-controls="{{ $id }}-mobile-calendar" class="{{ $dateButton }}">
                                        <span class="w-[32px] shrink-0 text-[10px] font-bold text-gray-400">DARI</span>
                                        <span class="min-w-0 truncate text-[12px] font-medium" :class="start ? 'text-gray-800' : 'text-gray-400'" x-text="formatDate(start)"></span>
                                    </button>
                                    <button id="{{ $id }}-mobile-end-date-trigger" type="button" aria-label="Tanggal akhir" @click="openCalendar($event.currentTarget, 'end')" :aria-expanded="calendarOpen" aria-controls="{{ $id }}-mobile-calendar" class="{{ $dateButton }}">
                                        <span class="w-[32px] shrink-0 text-[10px] font-bold text-gray-400">KE</span>
                                        <span class="min-w-0 truncate text-[12px] font-medium" :class="end ? 'text-gray-800' : 'text-gray-400'" x-text="formatDate(end)"></span>
                                    </button>
                                    <input id="{{ $id }}-mobile-start-date" type="hidden" x-model="start" aria-label="Tanggal mulai">
                                    <input id="{{ $id }}-mobile-end-date" type="hidden" x-model="end" aria-label="Tanggal akhir">
                                </div>
                                <div class="flex flex-col gap-[8px] pt-[8px]">
                                    <span class="{{ $labelClass }}">Pilihan Cepat</span>
                                    <div class="flex flex-row gap-[8px]">
                                        @foreach ($quickRanges as $range)
                                            <button type="button" @click="setDateRange(@js($range))" class="{{ $loop->first ? $quickPrimary : $quickOutline }}" title="Tampilkan {{ $quickLabels[$range] }}">{{ $quickLabels[$range] }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        @if ($withDates)
                        <section id="{{ $id }}-mobile-calendar" x-show="calendarOpen" x-cloak class="space-y-[16px]" aria-label="Pilih periode tanggal">
                            <button x-ref="calendarBack" type="button" data-erp-calendar-back @click="closeCalendar()" class="inline-flex min-h-[36px] items-center gap-[8px] rounded-[8px] px-[4px] text-[14px] font-semibold text-gray-700 transition-colors hover:text-gray-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                                <i class="ph-bold ph-arrow-left" aria-hidden="true"></i><span>Kembali</span>
                            </button>
                            <div class="rounded-[16px] border-[1px] border-solid border-gray-100 bg-white p-[16px] shadow-sm">
                                <div class="mb-[16px] flex items-center justify-between">
                                    <button type="button" @click="moveCalendar(-1)" aria-label="Sebelumnya" class="{{ $calendarNav }}"><i class="ph-bold ph-caret-left" aria-hidden="true"></i></button>
                                    <button type="button" data-erp-calendar-heading @click="cycleCalendarView()" class="rounded-[8px] px-[12px] py-[6px] text-[16px] font-bold text-gray-800 transition-colors hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" x-text="calendarHeading"></button>
                                    <button type="button" @click="moveCalendar(1)" aria-label="Berikutnya" class="{{ $calendarNav }}"><i class="ph-bold ph-caret-right" aria-hidden="true"></i></button>
                                </div>
                                <div x-show="calendarView === 'date'" class="grid grid-cols-7 gap-y-[4px] text-center">
                                    <template x-for="weekday in weekdays" :key="weekday.name">
                                        <span class="flex h-[36px] items-center justify-center text-[11px] font-semibold text-gray-400" :aria-label="weekday.name" x-text="weekday.initial"></span>
                                    </template>
                                    <template x-for="blank in calendarBlankDays" :key="'blank-' + blank"><span class="h-[36px]"></span></template>
                                    <template x-for="day in calendarDays" :key="calendarDate(day)">
                                        <div class="relative flex h-[36px] items-center justify-center">
                                            <span x-show="isCalendarDateInRange(day)" class="absolute inset-0 bg-emerald-50"></span>
                                            <button type="button" data-erp-calendar-day @click="selectCalendarDate(day)" :aria-label="formatDate(calendarDate(day))" :aria-pressed="isCalendarDateSelected(day)" :class="isCalendarDateSelected(day) ? 'relative z-10 rounded-full bg-[#c5f037] font-bold text-gray-900 shadow-sm' : (isCalendarDateInRange(day) ? 'relative z-10 font-semibold text-gray-800' : 'relative z-10 rounded-full text-gray-700 hover:bg-gray-100')" class="flex h-[36px] w-[36px] cursor-pointer items-center justify-center text-[14px] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" x-text="day"></button>
                                        </div>
                                    </template>
                                </div>
                                <div x-show="calendarView === 'month'" x-cloak class="grid grid-cols-3 gap-[8px] py-[8px]">
                                    <template x-for="(month, index) in calendarMonths" :key="month">
                                        <button type="button" @click="chooseCalendarMonth(index)" :class="calendarMonth === index ? 'bg-[#c5f037] font-bold text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="{{ $calendarCell }}" x-text="month"></button>
                                    </template>
                                </div>
                                <div x-show="calendarView === 'year'" x-cloak class="grid grid-cols-3 gap-[8px] py-[8px]">
                                    <template x-for="year in calendarYears" :key="year">
                                        <button type="button" @click="chooseCalendarYear(year)" :class="calendarYear === year ? 'bg-[#c5f037] font-bold text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="{{ $calendarCell }}" x-text="year"></button>
                                    </template>
                                </div>
                            </div>
                        </section>
                        @endif
                    </x-slot:body>
                    <x-slot:footer>
                        <button type="button" data-erp-filter-reset @click="reset()">{{ $resetLabel }}</button>
                        <button type="button" class="erp-filter-sheet__primary" data-erp-filter-apply @click="apply()">{{ $applyLabel }}</button>
                    </x-slot:footer>
                </x-erp.filter-sheet-frame>
            </section>
        </div>
    </template>
</div>
