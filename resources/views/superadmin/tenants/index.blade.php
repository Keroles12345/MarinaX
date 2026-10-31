@extends('superadmin.layout')

@section('title', ['dashboard' => 'لوحة تحكم سوبر فايزر', 'companies' => 'إدارة الشركات', 'create-company' => 'إضافة شركة', 'create-user' => 'إضافة مستخدم', 'users' => 'إدارة المستخدمين'][$page] ?? 'لوحة سوبر فايزر')
@section('page-title', ['dashboard' => 'لوحة التحكم', 'companies' => 'إدارة الشركات', 'create-company' => 'إضافة شركة', 'create-user' => 'إضافة مستخدم', 'users' => 'إدارة المستخدمين'][$page] ?? 'لوحة سوبر فايزر')

@section('body')
    <style>
        :root{color-scheme:light;--ink:#172b4d;--muted:#66758b;--line:#e7edf5;--blue:#245ca6;--surface:#fff}
        .tenant-page{max-width:none;margin:0;padding:0 0 24px}
        .tenant-header{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:24px;background:linear-gradient(118deg,#142746 0%,#1b4779 58%,#2866a7 100%);color:#fff;border-radius:22px;padding:32px 36px;box-shadow:0 18px 42px #17365b24}
        .tenant-header:after{content:"";position:absolute;width:330px;height:330px;border:1px solid #ffffff18;border-radius:50%;left:10%;top:-210px;box-shadow:0 0 0 42px #ffffff08,0 0 0 84px #ffffff06;pointer-events:none}
        .tenant-header-copy,.tenant-header-actions{position:relative;z-index:1}
        .tenant-eyebrow{display:flex;align-items:center;gap:8px;margin-bottom:10px;color:#c5d9f3;font-size:12px;font-weight:750;letter-spacing:.04em}
        .tenant-eyebrow:before{content:"";width:8px;height:8px;border-radius:50%;background:#79d7ad;box-shadow:0 0 0 4px #79d7ad22}
        .tenant-header h1{margin:0;font-size:clamp(23px,3vw,32px);letter-spacing:-.03em}
        .tenant-header p{max-width:650px;margin:9px 0 0;color:#d2e0f2;font-size:14px;line-height:1.8}
        .tenant-header-actions{display:flex;flex-wrap:wrap;gap:9px;align-items:center}
        .tenant-header-actions a{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 15px;border:1px solid #ffffff35;border-radius:10px;background:#ffffff12;color:#fff;text-decoration:none;font:inherit;font-size:13px;font-weight:750;transition:background .18s,transform .18s}
        .tenant-header-actions a.primary{border-color:#fff;background:#fff;color:#173c6a}
        .tenant-header-actions a:hover{background:#ffffff25;transform:translateY(-1px)}
        .tenant-header-actions a.primary:hover{background:#eef5ff}
        .section-kicker{margin:24px 2px 11px;color:#8290a3;font-size:11px;font-weight:800;letter-spacing:.08em}
        .tenant-stats{display:grid;grid-template-columns:repeat(7,minmax(130px,1fr));gap:12px;margin:0 0 20px}
        .tenant-stat,.tenant-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:0 6px 20px #142c4b08}
        .tenant-stat{position:relative;overflow:hidden;min-height:118px;padding:17px}
        .tenant-stat:after{content:"";position:absolute;inset:auto -12px -30px auto;width:80px;height:80px;border-radius:50%;background:var(--stat-tint,#edf4fc)}
        .tenant-stat-label{position:relative;z-index:1;display:flex;align-items:center;gap:8px;color:#68778c;font-size:12px;font-weight:700}
        .tenant-stat-icon{display:grid;place-items:center;width:27px;height:27px;border-radius:8px;background:var(--stat-tint,#edf4fc);color:var(--stat-color,#245ca6);font-size:13px}
        .tenant-stat strong{position:relative;z-index:1;display:block;margin-top:12px;color:var(--stat-color,#183961);font-size:29px;line-height:1}
        .tenant-overview{display:grid;grid-template-columns:minmax(0,1fr);gap:16px;margin:18px 0 22px}
        .tenant-card{padding:22px;margin:18px 0;scroll-margin-top:16px}
        .tenant-overview .tenant-card{margin:0}
        .tenant-card-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:17px}
        .tenant-card-heading h2,.tenant-card h2{margin:0;color:#1b3558;font-size:18px;letter-spacing:-.02em}
        .tenant-card-heading p{margin:5px 0 0;color:#8490a2;font-size:12px}
        .tenant-card-link{color:#245ca6;font-size:12px;font-weight:750;text-decoration:none;white-space:nowrap}
        .tenant-card-link:hover{text-decoration:underline}
        .recent-users{display:grid;gap:2px}
        .recent-user{display:grid;grid-template-columns:minmax(0,1fr) auto auto;align-items:center;gap:14px;padding:11px 0;border-top:1px solid #eef2f7}
        .recent-user-name{overflow:hidden;color:#263a58;font-size:13px;font-weight:750;text-overflow:ellipsis;white-space:nowrap}
        .recent-user-email{display:block;overflow:hidden;margin-top:3px;color:#8a96a7;font-size:11px;text-overflow:ellipsis;white-space:nowrap}
        .recent-user-company{max-width:145px;overflow:hidden;color:#66758b;font-size:12px;text-overflow:ellipsis;white-space:nowrap}
        .profit-chart{display:grid;gap:11px;padding:2px 3px}
        .profit-chart-legend{display:flex;flex-wrap:wrap;gap:16px;margin-bottom:15px;color:#66758b;font-size:12px}
        .profit-chart-legend span{display:inline-flex;align-items:center;gap:7px}
        .profit-chart-legend i{width:10px;height:10px;border-radius:3px;background:#3985ce}
        .profit-chart-legend .expense-key{background:#e5a33b}
        .profit-chart-item{display:grid;grid-template-columns:minmax(170px,.8fr) minmax(300px,2fr) minmax(125px,.55fr);align-items:center;gap:20px;padding:15px 16px;border:1px solid #edf1f6;border-radius:12px;background:#fcfdff}
        .profit-chart-company{min-width:0}
        .profit-chart-name{display:block;overflow:hidden;color:#263a58;font-size:14px;font-weight:800;text-overflow:ellipsis;white-space:nowrap}
        .profit-chart-meta{display:flex;flex-wrap:wrap;gap:7px;margin-top:7px;color:#8490a2;font-size:11px}
        .profit-chart-bars{display:grid;gap:9px}
        .profit-chart-bar-row{display:grid;grid-template-columns:68px minmax(80px,1fr) minmax(72px,auto);align-items:center;gap:10px;color:#738197;font-size:11px}
        .profit-chart-track{height:10px;overflow:hidden;border-radius:99px;background:#edf1f6}
        .profit-chart-bar{height:100%;min-width:2px;border-radius:inherit;background:linear-gradient(90deg,#3985ce,#75b4ed)}
        .profit-chart-bar.expense{background:linear-gradient(90deg,#e5a33b,#f2c46b)}
        .profit-chart-bar.negative{background:linear-gradient(90deg,#e36c61,#f3a198)}
        .profit-chart-amount{color:#45546a;text-align:left;direction:ltr;font-size:11px;font-weight:700}
        .profit-chart-net{padding-inline-start:15px;border-inline-start:1px solid #e8edf3}
        .profit-chart-net small{display:block;margin-bottom:5px;color:#8490a2;font-size:10px}
        .profit-chart-net strong{font-size:15px;direction:ltr}
        .profit-chart-net.positive strong{color:#13804b}
        .profit-chart-net.negative strong{color:#b42318}
        .empty-note{padding:20px 10px;color:#8793a4;font-size:13px;text-align:center}
        .tenant-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px}
        .tenant-grid label,.tenant-field{display:grid;gap:6px;color:#45546a;font-size:13px;font-weight:650}
        .tenant-grid input,.tenant-grid select,.user-edit-form input,.user-edit-form select,.filter-select{width:100%;min-height:40px;padding:9px 11px;border:1px solid #d5deea;border-radius:9px;background:#fff;color:#172033;font:inherit;font-size:13px;transition:border-color .15s,box-shadow .15s}
        .tenant-grid input:focus,.tenant-grid select:focus,.user-edit-form input:focus,.user-edit-form select:focus,.filter-select:focus{outline:0;border-color:#5f94d0;box-shadow:0 0 0 3px #2c69ae18}
        .tenant-card button{min-height:39px;padding:9px 15px;border:0;border-radius:9px;background:#245ca6;color:#fff;font:inherit;font-size:13px;font-weight:750;cursor:pointer;transition:background .18s,transform .18s}
        .tenant-card button:hover{background:#194b8b;transform:translateY(-1px)}
        .tenant-form-actions{display:flex;align-items:center;gap:12px;margin-top:14px}
        .tenant-form-note{color:#8793a4;font-size:12px}
        .company-list{display:grid;gap:12px}
        .company-item{padding:16px;border:1px solid #e8edf4;border-radius:13px;background:#fcfdff}
        .company-item-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:13px}
        .company-item-head strong{color:#223a5e;font-size:14px}
        .tenant-status{display:inline-flex;align-items:center;gap:6px;border-radius:99px;padding:5px 10px;font-size:11px;font-weight:800;white-space:nowrap}
        .tenant-status:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
        .tenant-status.active{background:#e9f8ef;color:#13804b}
        .tenant-status.suspended{background:#fff1ed;color:#b54726}
        .company-form{display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:11px;align-items:end;margin:0}
        .company-form label{display:grid;gap:6px;color:#65748a;font-size:11px;font-weight:700}
        .company-form input,.company-form select{min-width:0;width:100%;height:38px;padding:8px 10px;border:1px solid #dce4ee;border-radius:8px;background:#fff;color:#24364e;font:inherit;font-size:12px}
        .company-form .company-save{display:flex;align-items:center;justify-content:space-between;gap:10px;grid-column:1/-1}
        .company-users-count{color:#738197;font-size:12px}
        .tenant-table-wrap{overflow:auto;border:1px solid #e9eef5;border-radius:12px}
        .tenant-table{width:100%;border-collapse:collapse}
        .tenant-table th,.tenant-table td{padding:12px;text-align:start;border-bottom:1px solid #edf1f6;vertical-align:middle}
        .tenant-table th{background:#f8fafc;color:#718096;font-size:11px;font-weight:800;white-space:nowrap}
        .tenant-table td{color:#35465f;font-size:12px}
        .tenant-table tbody tr:last-child td{border-bottom:0}
        .profit-positive{color:#13804b!important;font-weight:800}
        .profit-negative{color:#b42318!important;font-weight:800}
        .user-primary{color:#203754;font-size:13px;font-weight:750}
        .user-secondary{display:block;margin-top:3px;color:#8995a6;font-size:11px}
        .user-edit summary{color:#245ca6;font-size:12px;font-weight:750;cursor:pointer;white-space:nowrap}
        .user-edit[open]{min-width:300px}
        .user-edit-form{display:grid;grid-template-columns:repeat(2,minmax(150px,1fr));gap:9px;min-width:340px;margin:12px 0 2px}
        .user-edit-form input,.user-edit-form select{min-width:0;min-height:36px;padding:7px 9px;font-size:12px}
        .user-edit-form button{grid-column:1/-1;justify-self:start}
        .tenant-filter{display:flex;flex-wrap:wrap;align-items:end;gap:9px;margin:0 0 15px}
        .tenant-filter label{display:grid;gap:6px;color:#596a80;font-size:12px;font-weight:750}
        .tenant-filter select{min-width:220px}
        .tenant-filter a{padding:10px 4px;color:#66758b;font-size:12px;font-weight:700;text-decoration:none}
        .tenant-filter a:hover{color:#245ca6}
        .tenant-success{padding:13px 16px;background:#ecfdf3;color:#067647;border:1px solid #b7ebc9;border-radius:10px;margin:16px 0}
        .tenant-error{padding:11px 14px;background:#fff1f0;color:#b42318;border:1px solid #fee4e2;border-radius:10px;margin:12px 0;font-size:13px}
        @media(max-width:1150px){.tenant-stats{grid-template-columns:repeat(4,minmax(130px,1fr))}.company-form{grid-template-columns:repeat(3,minmax(140px,1fr))}}
        @media(max-width:850px){.tenant-header{align-items:flex-start;padding:26px}.tenant-header-actions{justify-content:flex-start}.tenant-stats{grid-template-columns:repeat(3,minmax(120px,1fr))}.profit-chart-item{grid-template-columns:minmax(140px,.7fr) minmax(240px,1.6fr) minmax(115px,.5fr);gap:13px}}
        @media(max-width:600px){.tenant-header{display:grid;gap:20px;padding:21px;border-radius:17px}.tenant-header p{font-size:13px}.tenant-header-actions a{flex:1}.tenant-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.tenant-stat{min-height:102px;padding:14px}.tenant-stat strong{font-size:25px}.tenant-card{padding:16px}.tenant-overview{gap:11px}.recent-user{grid-template-columns:minmax(0,1fr) auto;gap:8px}.recent-user-company{display:none}.profit-chart-item{grid-template-columns:minmax(0,1fr) auto;gap:12px}.profit-chart-bars{grid-column:1/-1;grid-row:2}.profit-chart-net{grid-column:2;grid-row:1;padding-inline-start:10px}.profit-chart-bar-row{grid-template-columns:58px minmax(60px,1fr) minmax(68px,auto);gap:7px}.company-form{grid-template-columns:repeat(2,minmax(0,1fr))}.company-form .company-save{align-items:flex-start;flex-direction:column}.user-edit-form{min-width:290px}.tenant-filter select{min-width:190px}}
    </style>
<main class="tenant-page">
    @php($roleLabels = ['admin' => __('مدير الشركة'), 'staff' => __('موظف'), 'accountant' => __('محاسب'), 'viewer' => __('مشاهد'), 'account_user' => __('مستخدم حسابات'), 'supervisor' => __('مشرف'), 'customer' => __('عميل')])
    <header class="tenant-header">
        <div class="tenant-header-copy">
            <div class="tenant-eyebrow">{{ __('إدارة المنصة') }}</div>
            <h1>{{ __(['dashboard' => 'مرحباً بك في لوحة سوبر فايزر', 'companies' => 'إدارة الشركات', 'create-company' => 'إضافة شركة جديدة', 'create-user' => 'إضافة مستخدم لشركة', 'users' => 'إدارة المستخدمين'][$page] ?? 'لوحة سوبر فايزر') }}</h1>
            <p>{{ __(['dashboard' => 'نظرة عامة على الشركات والمستخدمين وحالة الحسابات.', 'companies' => 'عرض بيانات الشركات وتحديثها وإدارة حالتها.', 'create-company' => 'أضف شركة جديدة إلى المنصة.', 'create-user' => 'أنشئ حساباً واربطه بإحدى الشركات النشطة.', 'users' => 'عرض حسابات المستخدمين وتعديل بياناتها وحالتها.'][$page] ?? '') }}</p>
        </div>
        <div class="tenant-header-actions">
            @if($page === 'dashboard' || $page === 'companies')
                <a class="primary" href="{{ route('superadmin.tenants.create') }}">＋ {{ __('إضافة شركة') }}</a>
            @endif
            @if($page === 'dashboard' || $page === 'users')
                <a href="{{ route('superadmin.users.create') }}">＋ {{ __('إضافة مستخدم') }}</a>
            @endif
        </div>
    </header>

    @if(session('status'))
        <div class="tenant-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="tenant-error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    @if($page === 'dashboard')
    <div class="section-kicker">{{ __('ملخص المنصة') }}</div>
    <section class="tenant-stats" aria-label="{{ __('ملخص النظام') }}">
        <article class="tenant-stat" style="--stat-tint:#edf4fc;--stat-color:#245ca6"><span class="tenant-stat-label"><span class="tenant-stat-icon">▦</span>{{ __('إجمالي الشركات') }}</span><strong>{{ $stats['companies'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#e9f8ef;--stat-color:#13804b"><span class="tenant-stat-label"><span class="tenant-stat-icon">✓</span>{{ __('شركات نشطة') }}</span><strong>{{ $stats['active_companies'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#fff1ed;--stat-color:#b54726"><span class="tenant-stat-label"><span class="tenant-stat-icon">Ⅱ</span>{{ __('شركات موقوفة') }}</span><strong>{{ $stats['suspended_companies'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#edf4fc;--stat-color:#245ca6"><span class="tenant-stat-label"><span class="tenant-stat-icon">♙</span>{{ __('إجمالي المستخدمين') }}</span><strong>{{ $stats['users'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#e9f8ef;--stat-color:#13804b"><span class="tenant-stat-label"><span class="tenant-stat-icon">✓</span>{{ __('مستخدمون نشطون') }}</span><strong>{{ $stats['active_users'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#fff1ed;--stat-color:#b54726"><span class="tenant-stat-label"><span class="tenant-stat-icon">Ⅱ</span>{{ __('مستخدمون موقوفون') }}</span><strong>{{ $stats['inactive_users'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#fff7e6;--stat-color:#9a6700"><span class="tenant-stat-label"><span class="tenant-stat-icon">!</span>{{ __('بلا شركة') }}</span><strong>{{ $stats['unassigned_users'] }}</strong></article>
    </section>

    <section class="tenant-overview" id="overview" aria-label="{{ __('نظرة عامة') }}">
        <article class="tenant-card">
            <div class="tenant-card-heading">
                <div><h2>{{ __('الأداء المالي لكل شركة') }}</h2><p>{{ __('مقارنة الإيرادات والمصروفات وصافي الربح التراكمي من القيود المرحلة') }}</p></div>
                <a class="tenant-card-link" href="{{ route('superadmin.tenants.index') }}">{{ __('إدارة الشركات ←') }}</a>
            </div>
            <div class="profit-chart-legend" aria-label="{{ __('مفتاح الرسم البياني') }}">
                <span><i></i>{{ __('الإيرادات') }}</span>
                <span><i class="expense-key"></i>{{ __('المصروفات') }}</span>
            </div>
            <div class="profit-chart" aria-label="{{ __('مقارنة الأداء المالي للشركات') }}">
                @php($maxFinancialAmount = max(1, (float) $tenants->max(fn ($tenant) => max(abs($tenant->revenue_total), abs($tenant->expense_total)))))
                @forelse($tenants as $tenant)
                    @php($revenueWidth = min(100, (int) round(abs($tenant->revenue_total) / $maxFinancialAmount * 100)))
                    @php($expenseWidth = min(100, (int) round(abs($tenant->expense_total) / $maxFinancialAmount * 100)))
                    <article class="profit-chart-item">
                        <div class="profit-chart-company">
                            <span class="profit-chart-name" title="{{ $tenant->name }}">{{ $tenant->name }}</span>
                            <span class="profit-chart-meta">
                                <span class="tenant-status {{ $tenant->status ? 'active' : 'suspended' }}">{{ $tenant->status ? __('نشطة') : __('موقوفة') }}</span>
                                <span>{{ $tenant->users_count }} {{ __('مستخدم') }}</span>
                            </span>
                        </div>
                        <div class="profit-chart-bars">
                            <div class="profit-chart-bar-row">
                                <span>{{ __('الإيرادات') }}</span>
                                <div class="profit-chart-track" aria-label="{{ __('الإيرادات') }} {{ number_format($tenant->revenue_total, 2) }}">
                                    <div class="profit-chart-bar {{ $tenant->revenue_total < 0 ? 'negative' : '' }}" style="width:{{ $revenueWidth }}%"></div>
                                </div>
                                <span class="profit-chart-amount">{{ number_format($tenant->revenue_total, 2) }}</span>
                            </div>
                            <div class="profit-chart-bar-row">
                                <span>{{ __('المصروفات') }}</span>
                                <div class="profit-chart-track" aria-label="{{ __('المصروفات') }} {{ number_format($tenant->expense_total, 2) }}">
                                    <div class="profit-chart-bar expense {{ $tenant->expense_total < 0 ? 'negative' : '' }}" style="width:{{ $expenseWidth }}%"></div>
                                </div>
                                <span class="profit-chart-amount">{{ number_format($tenant->expense_total, 2) }}</span>
                            </div>
                        </div>
                        <div class="profit-chart-net {{ $tenant->net_profit >= 0 ? 'positive' : 'negative' }}">
                            <small>{{ __('صافي الربح') }}</small>
                            <strong>{{ number_format($tenant->net_profit, 2) }}</strong>
                        </div>
                    </article>
                @empty
                    <div class="empty-note">{{ __('لا توجد شركات لعرض أرباحها.') }}</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="tenant-card">
        <div class="tenant-card-heading">
            <div><h2>{{ __('أحدث الموظفين والمستخدمين') }}</h2><p>{{ __('آخر الحسابات المسجلة على المنصة') }}</p></div>
            <a class="tenant-card-link" href="{{ route('superadmin.users.index') }}">{{ __('إدارة المستخدمين ←') }}</a>
        </div>
        <div class="recent-users">
            @forelse($recentUsers as $recentUser)
                <div class="recent-user">
                    <div>
                        <span class="recent-user-name">{{ $recentUser->name }}</span>
                        <span class="recent-user-email">{{ $roleLabels[$recentUser->role] ?? $recentUser->role }} · {{ $recentUser->email }}</span>
                    </div>
                    <span class="recent-user-company">{{ $recentUser->tenant?->name ?? __('بدون شركة') }}</span>
                    <span class="tenant-status {{ $recentUser->status ? 'active' : 'suspended' }}">{{ $recentUser->status ? __('نشط') : __('موقوف') }}</span>
                </div>
            @empty
                <div class="empty-note">{{ __('لا يوجد مستخدمون مسجلون حتى الآن.') }}</div>
            @endforelse
        </div>
    </section>

    @endif

    @if($page === 'create-company')
    <section class="tenant-card">
        <h2>إضافة شركة</h2>
        <form method="post" action="{{ route('superadmin.tenants.store') }}">
            @csrf
            <div class="tenant-grid">
                <label>اسم الشركة<input name="name" required maxlength="255" value="{{ old('name') }}"></label>
                <label>البريد الإلكتروني<input name="email" type="email" value="{{ old('email') }}"></label>
                <label>الهاتف<input name="phone" value="{{ old('phone') }}"></label>
                <label>العنوان<input name="address" value="{{ old('address') }}"></label>
            </div>
            <div class="tenant-form-actions"><button type="submit">إنشاء الشركة</button><span class="tenant-form-note">سيتم تفعيل الشركة تلقائياً بعد الإنشاء.</span></div>
        </form>
    </section>
    @endif

    @if($page === 'create-user')
    <section class="tenant-card">
        <h2>إضافة مستخدم لشركة</h2>
        <form method="post" action="{{ route('superadmin.users.store') }}">
            @csrf
            <div class="tenant-grid">
                <label>الشركة<select name="tenant_id" required>@foreach($tenants->where('status', true) as $tenant)<option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>{{ $tenant->name }}</option>@endforeach</select></label>
                <label>الاسم<input name="name" required maxlength="255" value="{{ old('name') }}"></label>
                <label>اسم المستخدم<input name="username" required maxlength="255" value="{{ old('username') }}"></label>
                <label>البريد الإلكتروني<input name="email" type="email" required value="{{ old('email') }}"></label>
                <label>الدور<select name="role" required><option value="admin">مدير الشركة</option><option value="staff">موظف</option><option value="accountant">محاسب</option><option value="viewer">مشاهد</option><option value="account_user">مستخدم حسابات</option><option value="supervisor">مشرف</option><option value="customer">عميل</option></select></label>
                <label>كلمة المرور<input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
            </div>
            <div class="tenant-form-actions"><button type="submit">إنشاء المستخدم النشط</button><span class="tenant-form-note">سيكون المستخدم نشطاً فور إنشائه.</span></div>
        </form>
    </section>
    @endif

    @if($page === 'companies')
    <section class="tenant-card">
        <div class="tenant-card-heading">
            <div><h2>إدارة الشركات</h2><p>{{ $tenants->count() }} شركة مسجلة · يمكنك تعديل بيانات الشركة أو تغيير حالتها</p></div>
            <a class="tenant-card-link" href="{{ route('superadmin.tenants.create') }}">إضافة شركة جديدة ↑</a>
        </div>
        @if($tenants->isEmpty())
            <div class="empty-note">لا توجد شركات حتى الآن. ابدأ بإضافة شركة جديدة.</div>
        @endif
        <div class="company-list">
        @foreach($tenants as $tenant)
            <div class="company-item">
            <div class="company-item-head">
                <strong>{{ $tenant->name }}</strong>
                <span class="tenant-status {{ $tenant->status ? 'active' : 'suspended' }}">{{ $tenant->status ? 'نشطة' : 'موقوفة' }}</span>
            </div>
            <form method="post" action="{{ route('superadmin.tenants.update', $tenant) }}" class="company-form">
                @csrf
                @method('put')
                <label>اسم الشركة<input name="name" required value="{{ $tenant->name }}"></label>
                <label>البريد الإلكتروني<input name="email" type="email" value="{{ $tenant->email }}"></label>
                <label>الهاتف<input name="phone" value="{{ $tenant->phone }}"></label>
                <label>العنوان<input name="address" value="{{ $tenant->address }}"></label>
                <label>الحالة<select name="status"><option value="1" @selected($tenant->status)>مفعلة</option><option value="0" @selected(!$tenant->status)>موقوفة</option></select></label>
                <div class="company-save"><span class="company-users-count">{{ $tenant->users_count }} مستخدم مرتبط بهذه الشركة</span><button type="submit">حفظ التعديلات</button></div>
            </form>
            </div>
        @endforeach
        </div>
    </section>
    @endif

    @if($page === 'users')
    <section class="tenant-card">
        <div class="tenant-card-heading">
            <div><h2>إدارة المستخدمين</h2><p>{{ $users->count() }} مستخدم في القائمة الحالية · تفعيل المستخدم يتم تلقائياً عند إنشائه</p></div>
        </div>
        <form method="get" action="{{ route('superadmin.users.index') }}" class="tenant-filter">
            <label>
                تصفية المستخدمين حسب الشركة
                <select name="tenant_id" class="filter-select">
                    <option value="">كل الشركات</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected((string) $selectedTenantId === (string) $tenant->id)>{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">تصفية</button>
            @if($selectedTenantId !== '')
                <a href="{{ route('superadmin.users.index') }}">مسح الفلتر</a>
            @endif
        </form>
        <div class="tenant-table-wrap">
            <table class="tenant-table">
                <thead><tr><th>المستخدم</th><th>الشركة</th><th>الدور</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td><span class="user-primary">{{ $user->name }}</span><span class="user-secondary">{{ $user->username }} · {{ $user->email }}</span></td>
                        <td>{{ $user->tenant?->name ?? 'بدون شركة' }}</td>
                        <td>{{ $roleLabels[$user->role] ?? $user->role }}</td>
                        <td><span class="tenant-status {{ $user->status ? 'active' : 'suspended' }}">{{ $user->status ? 'نشط' : 'موقوف' }}</span></td>
                        <td>
                            <details class="user-edit">
                                <summary>تعديل البيانات</summary>
                                <form method="post" action="{{ route('superadmin.users.update', $user->id) }}" class="user-edit-form">
                                    @csrf
                                    @method('put')
                                    <input name="name" aria-label="اسم المستخدم" required value="{{ $user->name }}">
                                    <input name="username" aria-label="اسم الدخول" required value="{{ $user->username }}">
                                    <input name="email" aria-label="البريد الإلكتروني" type="email" required value="{{ $user->email }}">
                                    <select name="tenant_id" aria-label="الشركة" required>
                                        @foreach($tenants->where('status', true) as $tenant)
                                            <option value="{{ $tenant->id }}" @selected($user->tenant_id === $tenant->id)>{{ $tenant->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="role" aria-label="دور المستخدم" required>
                                        @foreach($roleLabels as $role => $label)
                                            <option value="{{ $role }}" @selected($user->role === $role)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="status" aria-label="حالة المستخدم" required>
                                        <option value="1" @selected($user->status)>نشط</option>
                                        <option value="0" @selected(!$user->status)>موقوف</option>
                                    </select>
                                    <input name="password" aria-label="كلمة مرور جديدة اختيارية" type="password" minlength="8" placeholder="كلمة مرور جديدة (اختياري)" autocomplete="new-password">
                                    <button type="submit">حفظ المستخدم</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @endforeach
                @if($users->isEmpty())
                    <tr><td colspan="5"><div class="empty-note">لا يوجد مستخدمون مطابقون لهذا الفلتر.</div></td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </section>
    @endif
</main>
@endsection
