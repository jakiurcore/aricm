@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.fees_page.pay_now'))

@section('content')
    @php($openData = $open->map(fn ($invoice) => ['id' => $invoice->id, 'label' => ($invoice->billing_month ? \Modules\Core\Support\Bangla::monthYear($invoice->billing_month) : $invoice->type->label()).' · '.$invoice->invoice_no, 'due' => $invoice->due->poisha])->values())
    @php($advanceData = collect($advance)->map(fn ($month) => ['value' => $month['value'], 'label' => \Modules\Core\Support\Bangla::monthYear($month['label']), 'estimate' => $month['estimate']->poisha])->values())
    <div class="mx-auto max-w-2xl space-y-4" x-data="payFlow(@js($openData), @js($advanceData), @js($preselected), @js($gateway))">
        <a href="{{ route('portal.fees') }}" class="text-sm">← {{ __('student-portal::messages.fees') }}</a>
        <h1 class="font-head text-2xl text-heading">{{ __('student-portal::messages.fees_page.pay_now') }}</h1>

        <ol class="flex gap-2 text-sm">
            @foreach ([1, 2, 3] as $number)
                <li class="flex-1 rounded-full px-3 py-1.5 text-center" :class="step >= {{ $number }} ? 'bg-green-100 font-semibold text-green-700' : 'bg-surface-2 text-ink-muted'">{{ bn_digits($number) }}. {{ __("student-portal::messages.fees_page.step_{$number}") }}</li>
            @endforeach
        </ol>

        @if ($errors->any())<div class="rounded-md bg-danger-bg p-3 text-danger">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

        <form method="POST" action="{{ route('portal.fees.pay.store') }}" enctype="multipart/form-data" class="space-y-4" @submit="busy = true">
            @csrf

            {{-- Step 1: what to pay --}}
            <div x-show="step === 1" class="space-y-3">
                <template x-for="invoice in open" :key="invoice.id">
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-line bg-surface p-4">
                        <span class="flex items-center gap-3"><input type="checkbox" name="invoice_ids[]" :value="invoice.id" x-model.number="selected" class="size-5"><span x-text="invoice.label"></span></span>
                        <strong x-text="money(invoice.due)"></strong>
                    </label>
                </template>
                <p x-show="open.length === 0" class="text-ink-muted">{{ __('student-portal::messages.fees_page.nothing_open') }}</p>

                <template x-if="advance.length">
                    <div>
                        <p class="mb-2 mt-4 font-semibold">{{ __('student-portal::messages.fees_page.advance') }}</p>
                        <template x-for="month in advance" :key="month.value">
                            <label class="mb-2 flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-dashed border-line-strong bg-surface p-4">
                                <span class="flex items-center gap-3"><input type="checkbox" name="advance_months[]" :value="month.value" x-model="months" class="size-5"><span x-text="month.label"></span></span>
                                <span class="text-sm text-ink-muted" x-text="'≈ ' + money(month.estimate)"></span>
                            </label>
                        </template>
                    </div>
                </template>

                <div class="flex items-center justify-between rounded-xl bg-green-50 p-4"><span>{{ __('student-portal::messages.fees_page.you_pay') }}</span><strong class="font-head text-2xl" x-text="money(total)"></strong></div>
                <button type="button" class="min-h-12 w-full rounded-md bg-brand font-semibold text-white disabled:opacity-50" :disabled="total === 0" @click="step = 2">{{ __('student-portal::messages.fees_page.next') }}</button>
            </div>

            {{-- Step 2: how --}}
            <div x-show="step === 2" x-cloak style="display: none;" class="space-y-3">
                @foreach ($manualMethods as $method)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line bg-surface p-4">
                        <input type="radio" name="method" value="{{ $method['method'] }}" x-model="method" class="mt-1 size-5">
                        <span><strong>{{ __("fees::messages.payment_method.{$method['method']}") }}</strong>@if (! empty($method['number']))<span class="block text-sm">{{ __('student-portal::messages.fees_page.send_to') }}: <strong>{{ bn_digits($method['number']) }}</strong> ({{ __('student-portal::messages.fees_page.account_'.($method['account_type'] ?? 'personal')) }})</span>@endif</span>
                    </label>
                @endforeach
                @if ($gateway)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line bg-surface p-4"><input type="radio" name="method" value="gateway" x-model="method" class="mt-1 size-5"><span><strong>{{ __('student-portal::messages.fees_page.online') }}</strong><span class="block text-sm text-ink-muted">{{ __('student-portal::messages.fees_page.online_hint') }}</span></span></label>
                @endif
                @if ($manualMethods->isEmpty() && ! $gateway)<p class="text-ink-muted">{{ __('student-portal::messages.fees_page.no_methods') }}</p>@endif
                <div class="flex gap-2"><button type="button" class="min-h-12 flex-1 rounded-md border border-line-strong font-semibold" @click="step = 1">{{ __('student-portal::messages.fees_page.back') }}</button><button type="button" class="min-h-12 flex-1 rounded-md bg-brand font-semibold text-white disabled:opacity-50" :disabled="!method" @click="step = 3">{{ __('student-portal::messages.fees_page.next') }}</button></div>
            </div>

            {{-- Step 3: confirm --}}
            <div x-show="step === 3" x-cloak style="display: none;" class="space-y-4">
                @foreach ($manualMethods as $method)
                    <div x-show="method === '{{ $method['method'] }}'" class="rounded-xl bg-yellow-50 p-4 text-sm">{{ app()->getLocale() === 'en' ? ($method['instruction_en'] ?? $method['instruction_bn'] ?? '') : ($method['instruction_bn'] ?? '') }}</div>
                @endforeach
                <div x-show="method && method !== 'gateway'" class="space-y-3">
                    <div><label class="mb-1 block text-sm font-medium" for="txn">{{ __('student-portal::messages.fees_page.transaction_id') }}</label><input id="txn" name="transaction_id" value="{{ old('transaction_id') }}" autocomplete="off" class="w-full rounded-md border border-line-strong bg-surface px-3 uppercase" :required="method && method !== 'gateway'"></div>
                    <div x-show="method !== 'bank'"><label class="mb-1 block text-sm font-medium" for="sender">{{ __('student-portal::messages.fees_page.sender_number') }}</label><input id="sender" name="sender_number" type="tel" value="{{ old('sender_number') }}" class="w-full rounded-md border border-line-strong bg-surface px-3"></div>
                    <div><label class="mb-1 block text-sm font-medium" for="shot">{{ __('student-portal::messages.fees_page.screenshot') }}</label><input id="shot" name="screenshot" type="file" accept="image/*" capture="environment" class="w-full text-sm"></div>
                </div>
                <p class="rounded-xl bg-green-50 p-4 text-center">{{ __('student-portal::messages.fees_page.you_pay') }}: <strong class="font-head text-2xl" x-text="money(total)"></strong></p>
                <div class="flex gap-2"><button type="button" class="min-h-12 flex-1 rounded-md border border-line-strong font-semibold" @click="step = 2">{{ __('student-portal::messages.fees_page.back') }}</button><button type="submit" class="min-h-12 flex-1 rounded-md bg-brand font-semibold text-white disabled:opacity-50" :disabled="busy"><span x-text="method === 'gateway' ? @js(__('student-portal::messages.fees_page.go_online')) : @js(__('student-portal::messages.fees_page.submit'))"></span></button></div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('payFlow', (open, advance, preselected, gateway) => ({
                step: 1, busy: false, open, advance, method: '', months: [],
                selected: preselected ? [preselected] : open.map((invoice) => invoice.id),
                get total() {
                    return open.filter((invoice) => this.selected.includes(invoice.id)).reduce((sum, invoice) => sum + invoice.due, 0)
                        + advance.filter((month) => this.months.includes(month.value)).reduce((sum, month) => sum + month.estimate, 0);
                },
                money(poisha) {
                    const taka = Math.floor(poisha / 100);
                    const digits = String(taka);
                    const grouped = digits.length > 3 ? digits.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + digits.slice(-3) : digits;
                    const text = '৳' + grouped + (poisha % 100 ? '.' + String(poisha % 100).padStart(2, '0') : '');
                    return document.documentElement.dataset.lang === 'bn' ? text.replace(/\d/g, (d) => '০১২৩৪৫৬৭৮৯'[d]) : text;
                },
            }));
        });
    </script>
@endsection
