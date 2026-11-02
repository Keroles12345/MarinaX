{{-- <form class="locale-switcher" method="post" action="{{ route('locale.update') }}">
    @csrf
    <label class="locale-switcher-label" for="dashboard-locale">{{ __('اللغة') }}</label>
    <select id="dashboard-locale" name="locale" onchange="this.form.submit()" aria-label="{{ __('اللغة') }}">
        <option value="ar" @selected(app()->getLocale() === 'ar')>العربية</option>
        <option value="en" @selected(app()->getLocale() === 'en')>English</option>
    </select>
</form>
<style>
    .locale-switcher{display:flex;align-items:center;gap:6px;flex:0 0 auto}
    .locale-switcher-label{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    .locale-switcher select{min-height:38px;max-width:116px;padding:0 9px;border:1px solid #dce3ec;border-radius:10px;background:#fff;color:#344054;font:inherit;font-size:12px;cursor:pointer}
    .locale-switcher select:focus-visible{outline:3px solid rgba(217,164,65,.35);outline-offset:2px}
</style> --}}
