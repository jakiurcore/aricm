@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.fees'))

@section('content')
    @php
        $locale = app()->getLocale();
        $openData = $open->map(fn ($invoice) => [
            'id' => $invoice->id,
            'title' => \Modules\StudentPortal\Support\FeeDisplay::invoiceTitle($invoice),
            'meta' => ($invoice->status === \Modules\Fees\Enums\InvoiceStatus::Partial ? __('student-portal::messages.fees_page.partly_paid').' · ' : '').__('student-portal::messages.fees_page.deadline').': '.bn_date($invoice->due_date).($invoice->late_fine->isPositive() ? ' · '.__('student-portal::messages.fees_page.with_fine') : ''),
            'due' => $invoice->due->poisha,
        ])->values();
        $advanceData = collect($advance)->map(fn ($month) => ['value' => $month['value'], 'label' => \Modules\Core\Support\Bangla::monthYear($month['label']), 'estimate' => $month['estimate']->poisha])->values();
        $methodData = $manualMethods->map(function ($method) use ($locale): array {
            $wallet = \Modules\StudentPortal\Support\FeeDisplay::wallet($method['method']);

            return [
                'id' => $method['method'], 'name' => __("fees::messages.payment_method.{$method['method']}"), 'icon' => $wallet['icon'], 'color' => $wallet['color'],
                'type' => __('student-portal::messages.fees_page.account_'.($method['account_type'] ?? 'personal')),
                'number' => ! empty($method['number']) ? bn_digits($method['number']) : '',
                'how' => $locale === 'en' ? ($method['instruction_en'] ?? $method['instruction_bn'] ?? '') : ($method['instruction_bn'] ?? ''),
            ];
        })->values();
        if ($gateway) {
            $gatewayWallet = \Modules\StudentPortal\Support\FeeDisplay::wallet('gateway');
            $methodData->push(['id' => 'gateway', 'name' => __('student-portal::messages.fees_page.online'), 'icon' => $gatewayWallet['icon'], 'color' => $gatewayWallet['color'], 'type' => '', 'number' => '', 'how' => '']);
        }
        $config = [
            'open' => $openData, 'advance' => $advanceData, 'methods' => $methodData->values(), 'preselected' => $preselected,
            'step' => $errors->any() && old('method') ? 3 : 1, 'method' => old('method', ''), 'txn' => old('transaction_id', ''), 'sender' => old('sender_number', ''),
            'text' => ['copy' => __('student-portal::messages.fees_page.copy'), 'copied' => __('student-portal::messages.fees_page.copied'), 'shot' => __('student-portal::messages.fees_page.add_screenshot'), 'cancel' => __('student-portal::messages.fees_page.cancel'), 'back' => __('student-portal::messages.fees_page.back'), 'next' => __('student-portal::messages.fees_page.next').' →', 'submit' => __('student-portal::messages.fees_page.submit'), 'payOnline' => __('student-portal::messages.fees_page.pay_online'), 'wait' => __('student-portal::messages.fees_page.wait'), 'perMonth' => __('student-portal::messages.fees_page.per_month'), 'monthsLeft' => __('student-portal::messages.fees_page.months_left'), 'allMonthsPaid' => __('student-portal::messages.fees_page.all_months_paid')],
        ];
        $inputStyle = 'height: 52px; padding: 0 14px; border-radius: 12px; border: 1px solid var(--line-strong); background: var(--surface); width: 100%; font-size: 17px;';
        $primary = 'min-height: 52px; padding: 0 22px; border-radius: 999px; border: 0; background: var(--red-600); color: #fff; font-weight: 700; font-size: 17px;';
    @endphp

    <div data-screen-label="Pay flow" x-data="payFlow(@js($config))" style="display: grid; gap: 14px; max-width: 720px; width: 100%; justify-self: center;">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
            @foreach ([1, 2, 3] as $number)
                <div style="display: grid; gap: 6px; justify-items: center; text-align: center;">
                    <span style="height: 6px; width: 100%; border-radius: 999px;" :style="{ background: step >= {{ $number }} ? 'var(--green-700)' : 'var(--line)' }"></span>
                    <span style="display: flex; gap: 6px; align-items: center; font-size: 13px; font-weight: 700;" :style="{ color: step === {{ $number }} ? 'var(--green-700)' : (step > {{ $number }} ? 'var(--ink)' : 'var(--ds-muted)') }">
                        <span style="width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; font-size: 12px;" :style="{ background: step >= {{ $number }} ? 'var(--green-700)' : 'var(--surface-2)', color: step >= {{ $number }} ? '#fff' : 'var(--ds-muted)' }" x-text="step > {{ $number }} ? '✓' : @js(bn_digits($number))">{{ bn_digits($number) }}</span>{{ __("student-portal::messages.fees_page.step_{$number}") }}
                    </span>
                </div>
            @endforeach
        </div>

        @if ($errors->any())<div role="alert" class="pp-bad" style="padding: 10px 14px; border-radius: 12px; font-weight: 600;">@foreach ($errors->all() as $error)<p style="margin: 0;">{{ $error }}</p>@endforeach</div>@endif

        <form method="POST" action="{{ route('portal.fees.pay.store') }}" enctype="multipart/form-data" style="display: grid; gap: 14px;" @submit="busy = true">
            @csrf
            <input type="hidden" name="method" :value="method">
            <template x-for="id in selected" :key="id"><input type="hidden" name="invoice_ids[]" :value="id"></template>
            <template x-for="month in advance.slice(0, adv)" :key="month.value"><input type="hidden" name="advance_months[]" :value="month.value"></template>

            {{-- Step 1: what to pay --}}
            <div x-show="step === 1" class="grid" style="gap: 12px;">
                <h3 class="pp-head" style="margin: 0; font-size: 18px; color: var(--heading);">{{ __('student-portal::messages.fees_page.what_to_pay') }}</h3>
                <p x-show="open.length === 0" class="pp-card" style="margin: 0; padding: 14px 16px; color: var(--ds-muted);">{{ __('student-portal::messages.fees_page.nothing_open') }}</p>
                <template x-for="invoice in open" :key="invoice.id">
                    <button type="button" @click="toggle(invoice.id)" style="display: flex; gap: 12px; align-items: center; min-height: 64px; padding: 10px 14px; border-radius: 16px; border: 2px solid; text-align: left; color: var(--ink);" :style="{ borderColor: selected.includes(invoice.id) ? 'var(--green-700)' : 'var(--line)', background: selected.includes(invoice.id) ? 'var(--green-50)' : 'var(--surface)' }">
                        <span style="width: 26px; height: 26px; flex: none; border-radius: 8px; border: 2px solid; color: #fff; display: grid; place-items: center; font-weight: 700;" :style="{ borderColor: selected.includes(invoice.id) ? 'var(--green-700)' : 'var(--line-strong)', background: selected.includes(invoice.id) ? 'var(--green-700)' : 'transparent' }" x-text="selected.includes(invoice.id) ? '✓' : ''"></span>
                        <span style="flex: 1; display: grid; line-height: 1.4;"><strong style="font-size: 16px;" x-text="invoice.title"></strong><span style="font-size: 13px; color: var(--ds-muted);" x-text="invoice.meta"></span></span>
                        <strong style="font-size: 17px; white-space: nowrap;" x-text="money(invoice.due)"></strong>
                    </button>
                </template>
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; padding: 12px 14px; border-radius: 16px; border: 1px dashed var(--green-700); background: var(--green-50);">
                    <span style="flex: 1; min-width: 180px; display: grid; line-height: 1.4;"><strong style="font-size: 16px;">{{ __('student-portal::messages.fees_page.add_advance') }}</strong><span style="font-size: 13px; color: var(--ds-muted);" x-text="advanceMeta"></span></span>
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" @click="adv = Math.max(0, adv - 1)" aria-label="{{ __('student-portal::messages.fees_page.decrease') }}" style="width: 44px; height: 44px; border-radius: 999px; border: 1px solid var(--line-strong); background: var(--surface); font-size: 20px;">−</button>
                        <strong style="min-width: 28px; text-align: center; font-size: 20px;" x-text="digits(adv)">{{ bn_digits(0) }}</strong>
                        <button type="button" @click="adv = Math.min(advance.length, adv + 1)" aria-label="{{ __('student-portal::messages.fees_page.increase') }}" style="width: 44px; height: 44px; border-radius: 999px; border: 1px solid var(--line-strong); background: var(--surface); font-size: 20px;">+</button>
                    </span>
                </div>
            </div>

            {{-- Step 2: how --}}
            <div x-show="step === 2" x-cloak class="grid" style="gap: 12px;">
                <h3 class="pp-head" style="margin: 0; font-size: 18px; color: var(--heading);">{{ __('student-portal::messages.fees_page.choose_method') }}</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr)); gap: 10px;">
                    <template x-for="item in methods" :key="item.id">
                        <button type="button" @click="method = item.id; step = 3" style="display: flex; gap: 12px; align-items: center; min-height: 72px; padding: 12px 14px; border-radius: 18px; border: 2px solid; background: var(--surface); text-align: left; color: var(--ink);" :style="{ borderColor: method === item.id ? item.color : 'var(--line)' }">
                            <span class="pp-en" style="width: 44px; height: 44px; flex: none; border-radius: 12px; display: grid; place-items: center; color: #fff; font-weight: 700; font-size: 18px;" :style="{ background: item.color }" x-text="item.icon"></span>
                            <span style="flex: 1; display: grid; line-height: 1.4;"><strong style="font-size: 17px;" x-text="item.name"></strong><span style="font-size: 13px; color: var(--ds-muted);" x-text="item.id === 'gateway' ? @js(__('student-portal::messages.fees_page.online_hint')) : (item.type + ' · ' + item.number)"></span></span>
                        </button>
                    </template>
                </div>
                <p x-show="methods.length === 0" style="margin: 0; color: var(--ds-muted);">{{ __('student-portal::messages.fees_page.no_methods') }}</p>
                <div style="padding: 14px 16px; border-radius: 18px; background: var(--surface-2); border: 1px solid var(--line); display: flex; gap: 12px; align-items: flex-start;">
                    <span style="width: 44px; height: 44px; flex: none; border-radius: 12px; display: grid; place-items: center; background: var(--green-700); color: #fff; font-size: 20px;">🏫</span>
                    <span style="display: grid; line-height: 1.55;"><strong style="font-size: 16px;">{{ __('student-portal::messages.fees_page.office_cash') }}</strong><span style="font-size: 14px;">{{ __('student-portal::messages.fees_page.office_cash_body', ['id' => $student->student_id]) }}</span></span>
                </div>
            </div>

            {{-- Step 3: pay and confirm --}}
            <div x-show="step === 3" x-cloak class="grid" style="gap: 12px;">
                <template x-if="current && current.id !== 'gateway'">
                    <div style="display: grid; gap: 12px;">
                        <div style="padding: 18px; border-radius: 22px; background: var(--surface); border: 2px solid; display: grid; gap: 12px; justify-items: center; text-align: center;" :style="{ borderColor: current.color }">
                            <span style="display: flex; gap: 8px; align-items: center;"><span class="pp-en" style="width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; color: #fff; font-weight: 700;" :style="{ background: current.color }" x-text="current.icon"></span><strong style="font-size: 18px;" x-text="current.name + (current.type ? ' (' + current.type + ')' : '')"></strong></span>
                            <template x-if="current.number">
                                <div style="display: grid; gap: 12px; justify-items: center;">
                                    <span style="font-size: 14px; color: var(--ds-muted);">{{ __('student-portal::messages.fees_page.send_to') }}</span>
                                    <span style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; justify-content: center;"><strong class="pp-en" style="font-size: 30px; letter-spacing: .04em; line-height: 1.2;" x-text="current.number"></strong><button type="button" @click="copy()" class="pp-btn" style="min-height: 44px;" x-text="copied ? '✓ ' + text.copied : '⧉ ' + text.copy"></button></span>
                                </div>
                            </template>
                            <span style="display: grid; gap: 0; padding: 10px 18px; border-radius: 16px; background: var(--yellow-50); line-height: 1.4;"><span style="font-size: 13px; font-weight: 700; color: var(--warn);">{{ __('student-portal::messages.fees_page.send_exact') }}</span><strong class="pp-head" style="font-size: 30px;" x-text="money(total)"></strong></span>
                            <span class="pp-info" style="padding: 10px 14px; border-radius: 12px; font-weight: 700; font-size: 15px;">ℹ {{ __('student-portal::messages.fees_page.reference_hint') }} <span class="pp-en">{{ $student->student_id }}</span></span>
                            <span style="font-size: 13px; color: var(--ds-muted); line-height: 1.55;" x-text="current.how"></span>
                        </div>
                        <label style="display: grid; gap: 4px; font-weight: 700; font-size: 15px;">{{ __('student-portal::messages.fees_page.transaction_id') }} *<input name="transaction_id" x-model="txn" @input="txn = txn.toUpperCase()" :required="step === 3 && method && method !== 'gateway'" autocomplete="off" autocapitalize="characters" placeholder="{{ __('student-portal::messages.fees_page.transaction_placeholder') }}" class="pp-en" style="{{ $inputStyle }} letter-spacing: .04em;"></label>
                        <label x-show="method !== 'bank'" style="display: grid; gap: 4px; font-weight: 700; font-size: 15px;">{{ __('student-portal::messages.fees_page.sender_number') }} *<input name="sender_number" type="tel" inputmode="numeric" x-model="sender" :required="step === 3 && method && method !== 'gateway' && method !== 'bank'" placeholder="01XXXXXXXXX" class="pp-en" style="{{ $inputStyle }}"></label>
                        <label style="display: grid; gap: 6px; font-weight: 700; font-size: 15px;">{{ __('student-portal::messages.fees_page.screenshot') }}
                            <span style="min-height: 56px; padding: 8px 14px; border-radius: 14px; border: 2px dashed var(--line-strong); background: var(--surface-2); display: flex; gap: 10px; align-items: center; cursor: pointer; font-weight: 600;"><input type="file" name="screenshot" accept="image/*" @change="shot = $event.target.files[0]?.name || ''" style="display: none; min-height: 0;">📷 <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="shot || text.shot"></span></span>
                        </label>
                    </div>
                </template>
                <template x-if="current && current.id === 'gateway'">
                    <div class="pp-card" style="padding: 22px; border-radius: 22px; display: grid; gap: 12px; justify-items: center; text-align: center;">
                        <span style="width: 56px; height: 56px; border-radius: 16px; display: grid; place-items: center; background: var(--navy-800); color: #fff; font-size: 26px;">⌁</span>
                        <strong style="font-size: 18px;">{{ __('student-portal::messages.fees_page.secure_online') }}</strong>
                        <span style="color: var(--ds-muted); font-size: 14px; max-width: 420px;">{{ __('student-portal::messages.fees_page.secure_online_body') }}</span>
                        <strong class="pp-head" style="font-size: 32px;" x-text="money(total)"></strong>
                        <span x-show="busy" style="font-weight: 700; color: var(--navy-800);">{{ __('student-portal::messages.fees_page.connecting') }}</span>
                    </div>
                </template>
            </div>

            <div style="position: sticky; bottom: 0; z-index: 5; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; padding: 12px 14px; border-radius: 18px; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-lg);">
                <template x-if="step === 1"><a href="{{ route('portal.fees') }}" class="pp-btn" style="min-height: 52px;">← {{ __('student-portal::messages.fees_page.cancel') }}</a></template>
                <template x-if="step > 1"><button type="button" @click="step -= 1" class="pp-btn" style="min-height: 52px;">← {{ __('student-portal::messages.fees_page.back') }}</button></template>
                <span style="flex: 1; min-width: 110px; display: grid; line-height: 1.3; text-align: right;"><span style="font-size: 13px; color: var(--ds-muted);">{{ __('student-portal::messages.fees_page.total') }}</span><strong class="pp-head" style="font-size: 24px;" x-text="money(total)"></strong></span>
                <button type="button" x-show="step === 1" @click="if (total > 0) step = 2" :disabled="total === 0" :style="{ opacity: total === 0 ? .5 : 1 }" style="{{ $primary }}">{{ __('student-portal::messages.fees_page.next') }} →</button>
                <button type="submit" x-show="step === 3" x-cloak style="{{ $primary }}" :disabled="busy || total === 0" :style="{ opacity: busy ? .5 : 1 }" x-text="method === 'gateway' ? (busy ? text.wait : text.payOnline) : text.submit"></button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('payFlow', (config) => ({
                step: config.step, busy: false, open: config.open, advance: config.advance, methods: config.methods, text: config.text,
                method: config.method, txn: config.txn, sender: config.sender, shot: '', copied: false, adv: 0,
                selected: config.preselected ? [config.preselected] : config.open.map((invoice) => invoice.id),
                get current() { return this.methods.find((item) => item.id === this.method) || null; },
                get total() {
                    return this.open.filter((invoice) => this.selected.includes(invoice.id)).reduce((sum, invoice) => sum + invoice.due, 0)
                        + this.advance.slice(0, this.adv).reduce((sum, month) => sum + month.estimate, 0);
                },
                get advanceMeta() {
                    if (! this.advance.length) { return this.text.allMonthsPaid; }
                    return this.text.perMonth.replace(':amount', this.money(this.advance[0].estimate)).replace(':count', this.digits(this.advance.length));
                },
                toggle(id) { this.selected = this.selected.includes(id) ? this.selected.filter((item) => item !== id) : [...this.selected, id]; },
                digits(value) { const text = String(value); return document.documentElement.dataset.lang === 'bn' ? text.replace(/\d/g, (d) => '০১২৩৪৫৬৭৮৯'[d]) : text; },
                money(poisha) {
                    const taka = Math.floor(poisha / 100);
                    const digits = String(taka);
                    const grouped = digits.length > 3 ? digits.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + digits.slice(-3) : digits;
                    return this.digits('৳' + grouped + (poisha % 100 ? '.' + String(poisha % 100).padStart(2, '0') : ''));
                },
                copy() {
                    try { navigator.clipboard.writeText(this.current.number.replace(/[০-৯]/g, (d) => '০১২৩৪৫৬৭৮৯'.indexOf(d))); } catch (e) {}
                    this.copied = true; setTimeout(() => this.copied = false, 1600);
                },
            }));
        });
    </script>
@endsection
