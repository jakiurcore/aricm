<div role="tablist" style="display: flex; gap: 6px; overflow-x: auto; padding: 4px; border-radius: 999px; background: var(--surface-2); border: 1px solid var(--line);">
    @foreach ([['portal.fees', 'overview', 'tab_overview'], ['portal.fees.payments', 'history', 'tab_history'], ['portal.fees.ledger', 'ledger', 'tab_ledger']] as [$route, $key, $label])
        <a role="tab" aria-selected="{{ $active === $key ? 'true' : 'false' }}" href="{{ route($route) }}" style="flex: 1 0 auto; min-height: 44px; padding: 0 16px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 700; font-size: 15px; white-space: nowrap; background: {{ $active === $key ? 'var(--surface)' : 'transparent' }}; color: {{ $active === $key ? 'var(--green-700)' : 'var(--ds-muted)' }}; box-shadow: {{ $active === $key ? 'var(--shadow-sm)' : 'none' }};">{{ __('student-portal::messages.fees_page.'.$label) }}</a>
    @endforeach
</div>
