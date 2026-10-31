<?php

namespace App\Http\Controllers;

use App\Models\accounts;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

class AccountingController extends Controller
{
    private const ENTRY_TYPES = ['عادي', 'تسوية', 'افتتاحي', 'إقفال'];

    private const ACCOUNT_TYPES = ['أصول', 'خصوم', 'حقوق ملكية', 'إيرادات', 'مصروفات', 'أخرى'];

    private const USER_ROLES = ['admin', 'accountant', 'viewer', 'account_user', 'staff', 'supervisor', 'customer'];

    public function loginForm()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ]);
        $key = Str::transliterate(Str::lower($data['username']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['username' => 'تم حظر تسجيل الدخول مؤقتًا — حاول بعد 5 دقائق'])->onlyInput('username');
        }

        $user = User::where('username', $data['username'])->first();
        if (! $user || ! $this->passwordMatches($data['password'], $user->password_hash)) {
            RateLimiter::hit($key, 300);

            $message = $user && ! in_array(password_get_info($user->password_hash)['algoName'], ['bcrypt', 'argon2i', 'argon2id'], true)
                ? 'كلمة المرور القديمة تحتاج إعادة ضبط بواسطة مدير النظام'
                : 'اسم المستخدم أو كلمة المرور غير صحيحة';

            return back()->withErrors(['username' => $message])->onlyInput('username');
        }

        if (! in_array($user->role, ['admin', 'accountant', 'viewer'], true)) {
            return back()->withErrors(['username' => 'هذا النوع من المستخدمين يسجل الدخول من صفحة الأدمن فقط'])->onlyInput('username');
        }

        Auth::guard('admin')->login($user);
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $this->audit($user->name, 'تسجيل دخول');

        return redirect('/accounting');
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('admin')->user();
        $this->audit($user->name, 'تسجيل خروج');
        Auth::guard('admin')->logout();
        $request->session()->regenerate();

        return redirect('/admin');
    }

    public function forgotForm()
    {
        return view('forgot', ['step' => 'ask', 'question' => '', 'username' => '']);
    }

    public function forgot(Request $request)
    {
        $rateKey = Str::transliterate('forgot|'.$request->ip());
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return back()->withErrors(['username' => 'محاولات كثيرة، يرجى الانتظار 5 دقائق'])->withInput();
        }
        $username = trim((string) $request->input('username'));
        $user = User::where('username', $username)->first();
        if (! $user) {
            RateLimiter::hit($rateKey, 300);
            return back()->withErrors(['username' => 'لا يوجد مستخدم بهذا الاسم'])->withInput();
        }

        if ($request->input('act') !== 'setup') {
            if (! $user->security_question || ! $user->security_answer_hash) {
                RateLimiter::hit($rateKey, 300);
                return back()->withErrors(['username' => 'هذا الحساب لا يوجد له سؤال أمان — تواصل مع مدير النظام'])->withInput();
            }

            return view('forgot', ['step' => 'answer', 'question' => $user->security_question, 'username' => $username]);
        }

        $request->validate([
            'answer' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/\d/'],
        ], [
            'new_password.regex' => 'كلمة المرور يجب أن تحتوي على حروف وأرقام',
        ]);
        if (! $this->passwordMatches(trim($request->input('answer')), $user->security_answer_hash)) {
            RateLimiter::hit($rateKey, 300);
            return view('forgot', [
                'step' => 'answer',
                'question' => $user->security_question,
                'username' => $username,
            ])->withErrors(['answer' => 'إجابة سؤال الأمان غير صحيحة']);
        }

        $user->password = $request->input('new_password');
        $user->password_hash = Hash::make($request->input('new_password'));
        $user->save();
        RateLimiter::clear($rateKey);
        $this->audit($user->name, 'استعادة كلمة مرور', 'تم تغيير كلمة المرور عبر سؤال الأمان');

        return view('forgot', ['step' => 'done', 'question' => '', 'username' => $username, 'ok' => 'تم تغيير كلمة المرور بنجاح — يمكنك الآن تسجيل الدخول']);
    }

    public function page(string $view)
    {
        return view('admin.pages.'.$view, $this->viewData());
    }

    public function dashboard()
    {
        return view('admin.pages.dashboard', $this->viewData());
    }

    public function usersPage()
    {
        return view('admin.pages.users', $this->viewData([
            'users' => User::query()
                ->with('regions:id,name')
                ->select('id', 'username', 'name', 'email', 'role', 'security_question', 'created_at')
                ->orderBy('id')
                ->get(),
            'regions' => \App\Support\TenantDatabase::table('regions')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]));
    }

    public function auditPage(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $query = \App\Support\TenantDatabase::table('audit_log');
        if (! empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(fn ($builder) => $builder
                ->where('user', 'like', "%{$term}%")
                ->orWhere('action', 'like', "%{$term}%")
                ->orWhere('details', 'like', "%{$term}%"));
        }
        if (! empty($filters['from'])) {
            $query->whereDate('at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('at', '<=', $filters['to']);
        }

        return view('admin.pages.audit', $this->viewData([
            'logs' => $query->orderByDesc('id')->paginate(50)->withQueryString(),
            'filters' => $filters,
        ]));
    }

    public function dashboardData(): JsonResponse
    {
        $stats = [
            'accounts' => \App\Support\TenantDatabase::table('accounts')->count(),
            'entries' => \App\Support\TenantDatabase::table('journal_entries')->count(),
            'drafts' => \App\Support\TenantDatabase::table('journal_entries')->where('status', 'draft')->count(),
            'total_debit' => (float) \App\Support\TenantDatabase::table('journal_lines')->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.entry_id')
                ->where('journal_entries.status', 'posted')->sum('journal_lines.debit'),
            'total_credit' => (float) \App\Support\TenantDatabase::table('journal_lines')->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.entry_id')
                ->where('journal_entries.status', 'posted')->sum('journal_lines.credit'),
        ];
        $recent = \App\Support\TenantDatabase::table('journal_entries')
            ->select('journal_entries.*')
            ->selectSub(
                \App\Support\TenantDatabase::table('journal_lines')->selectRaw('COALESCE(SUM(debit), 0)')->whereColumn('entry_id', 'journal_entries.id'),
                'total_debit'
            )->orderByDesc('id')->limit(8)->get();
        return response()->json(['stats' => $stats, 'recent' => $recent]);
    }

    public function accounts(Request $request): JsonResponse
    {
        $query = \App\Support\TenantDatabase::table('accounts')->select('accounts.*')->selectSub(
            \App\Support\TenantDatabase::table('journal_lines as jl')
                ->join('journal_entries as je', 'je.id', '=', 'jl.entry_id')
                ->whereColumn('jl.account_id', 'accounts.id')
                ->where('je.status', 'posted')
                ->selectRaw('COALESCE(SUM(jl.debit - jl.credit), 0)'),
            'posted_movement'
        );
        $accounts = $query->orderByRaw('CAST(acc_no AS UNSIGNED), acc_no')->get();
        $accounts->each(fn ($account) => $account->current_balance = round(
            (float) $account->opening_balance + (float) $account->posted_movement,
            2,
        ));
        foreach (['4103', '4104'] as $parentNumber) {
            $parent = $accounts->first(fn ($account) => (string) $account->acc_no === $parentNumber);
            if (! $parent) {
                continue;
            }

            $parent->current_balance = round(
                (float) $parent->current_balance
                    + (float) $accounts
                        ->filter(fn ($account) => str_starts_with((string) $account->acc_no, $parentNumber)
                            && (string) $account->acc_no !== $parentNumber)
                        ->sum('current_balance'),
                2,
            );
            $parent->current_balance_includes_children = true;
        }
        if ($search = trim((string) $request->query('q'))) {
            $accounts = $accounts->filter(fn ($account) => str_contains((string) $account->acc_no, $search)
                || str_contains((string) $account->name, $search))->values();
        }

        return response()->json(['accounts' => $accounts]);
    }

    public function createAccount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'acc_no' => ['required', 'string', 'max:100', Rule::unique('accounts', 'acc_no')->where('tenant_id', \App\Support\TenantContext::id())],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:'.implode(',', self::ACCOUNT_TYPES)],
            'opening_balance' => ['nullable', 'numeric'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);
        $id = \App\Support\TenantDatabase::table('accounts')->insertGetId([
            'acc_no' => trim($data['acc_no']),
            'name' => trim($data['name']),
            'type' => $data['type'] ?? '',
            'opening_balance' => $data['opening_balance'] ?? 0,
            'budget' => $data['budget'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->audit(Auth::user()->name, 'إضافة حساب', $data['acc_no'].' - '.$data['name']);

        return response()->json(['ok' => true, 'id' => $id]);
    }

    public function updateAccount(Request $request, int $id): JsonResponse
    {
        $account = \App\Support\TenantDatabase::table('accounts')->find($id);
        abort_if(! $account, 404, 'الحساب غير موجود');
        $data = $request->validate([
            'acc_no' => ['required', 'string', 'max:100', Rule::unique('accounts', 'acc_no')->ignore($id)->where('tenant_id', \App\Support\TenantContext::id())],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:'.implode(',', self::ACCOUNT_TYPES)],
            'opening_balance' => ['nullable', 'numeric'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);
        if ($account->is_system && (
            trim($data['acc_no']) !== (string) $account->acc_no
            || trim($data['name']) !== (string) $account->name
            || ($data['type'] ?? '') !== (string) $account->type
        )) {
            throw ValidationException::withMessages([
                'account' => 'الحساب أساسي ومشترك؛ لا يمكن تغيير رقمه أو اسمه أو نوعه.',
            ]);
        }
        if (in_array((string) $account->acc_no, ['4103', '4104'], true)) {
            if (! $account->is_system) {
                return response()->json(['error' => 'حسابات إيرادات العمليات الرئيسية محمية لأنها تحتوي حسابات فرعية للمركبات'], 400);
            }
        }
        \App\Support\TenantDatabase::table('accounts')->where('id', $id)->update([
            'acc_no' => $account->is_system ? $account->acc_no : trim($data['acc_no']),
            'name' => $account->is_system ? $account->name : trim($data['name']),
            'type' => $account->is_system ? $account->type : ($data['type'] ?? ''),
            'opening_balance' => $data['opening_balance'] ?? 0,
            'budget' => $data['budget'] ?? 0,
            'updated_at' => now(),
        ]);
        $this->audit(Auth::user()->name, 'تعديل حساب', $data['acc_no'].' - '.$data['name']);

        return response()->json(['ok' => true]);
    }

    public function deleteAccount(int $id): JsonResponse
    {
        $account = \App\Support\TenantDatabase::table('accounts')->find($id);
        abort_if(! $account, 404, 'الحساب غير موجود');
        if ($account->is_system) {
            return response()->json(['error' => 'لا يمكن حذف الحساب الأساسي المشترك.'], 400);
        }
        if (in_array((string) $account->acc_no, ['4103', '4104'], true)) {
            return response()->json(['error' => 'لا يمكن حذف حساب إيرادات العمليات الرئيسي'], 400);
        }
        if (\App\Support\TenantDatabase::table('journal_lines')->where('account_id', $id)->exists()) {
            return response()->json(['error' => 'لا يمكن حذف الحساب لوجود حركات مرتبطة به'], 400);
        }
        \App\Support\TenantDatabase::table('accounts')->where('id', $id)->delete();
        $this->audit(Auth::user()->name, 'حذف حساب', $account->acc_no);

        return response()->json(['ok' => true]);
    }

    public function wipeAccounts(): JsonResponse
    {
        if (\App\Support\TenantDatabase::table('accounts')->doesntExist()) {
            return response()->json(['error' => 'لا توجد حسابات لمسحها'], 400);
        }
        $counts = [
            'deleted_accounts' => \App\Support\TenantDatabase::table('accounts')->where('is_system', false)->count(),
            'deleted_entries' => \App\Support\TenantDatabase::table('journal_entries')->count(),
        ];
        DB::transaction(function () {
            \App\Support\TenantDatabase::table('journal_entries')->delete();
            \App\Support\TenantDatabase::table('accounts')->where('is_system', false)->delete();
        });
        $this->audit(Auth::user()->name, 'مسح شجرة الحسابات');

        return response()->json($counts);
    }

    public function updateBudgets(Request $request): JsonResponse
    {
        $data = $request->validate(['items' => ['required', 'array'], 'items.*.id' => ['required', 'integer', 'tenant_exists:accounts,id'], 'items.*.budget' => ['required', 'numeric', 'min:0']]);
        $containsOperationRevenueGroup = \App\Support\TenantDatabase::table('accounts')
            ->whereIn('id', collect($data['items'])->pluck('id')->unique())
            ->whereIn('acc_no', ['4103', '4104'])
            ->exists();
        if ($containsOperationRevenueGroup) {
            throw ValidationException::withMessages([
                'items' => 'حدد ميزانية حسابات المراكب والسيارات الفرعية، ولا تضع ميزانية على الحسابين التجميعيين 4103 و4104.',
            ]);
        }

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $item) {
                \App\Support\TenantDatabase::table('accounts')->where('id', $item['id'])->update(['budget' => $item['budget'], 'updated_at' => now()]);
            }
        });
        $this->audit(Auth::user()->name, 'تحديث ميزانيات الحسابات');

        return response()->json(['ok' => true]);
    }

    public function accountsTree(): JsonResponse
    {
        $tenantId = TenantContext::id();
        abort_unless(TenantContext::isInitialized() && $tenantId !== null, 403, 'تعذر تحديد الشركة الحالية');

        $accounts = accounts::query()->where('accounts.tenant_id', $tenantId)->select('accounts.*')->selectSub(
            \App\Support\TenantDatabase::table('journal_lines as jl')
                ->join('journal_entries as je', 'je.id', '=', 'jl.entry_id')
                ->whereColumn('jl.account_id', 'accounts.id')
                ->where('je.status', 'posted')
                ->selectRaw('COALESCE(SUM(jl.debit - jl.credit), 0)'),
            'posted_movement'
        )->get();
        $accounts->each(fn ($account) => $account->current_balance = round(
            (float) $account->opening_balance + (float) $account->posted_movement,
            2,
        ));
        $tree = [];
        foreach (self::ACCOUNT_TYPES as $type) {
            $groups = [];
            $typeAccounts = $type === 'أخرى'
                ? $accounts->filter(fn ($account) => ! in_array($account->type, array_slice(self::ACCOUNT_TYPES, 0, 5), true))
                : $accounts->where('type', $type);
            foreach ($typeAccounts as $account) {
                $key = substr((string) $account->acc_no, 0, 2);
                $groups[$key][] = $account;
            }
            if (! $groups) {
                continue;
            }
            if ($type === 'إيرادات' && isset($groups['41'])) {
                $items = collect($groups['41']);
                $nestedIds = [];
                foreach (['4103', '4104'] as $parentNumber) {
                    $parent = $items->first(fn ($account) => (string) $account->acc_no === $parentNumber);
                    if (! $parent) {
                        continue;
                    }

                    $children = $items->filter(fn ($account) => str_starts_with((string) $account->acc_no, $parentNumber)
                        && (string) $account->acc_no !== $parentNumber)->values();
                    $parent->tree_children = $children;
                    $parent->current_balance = round(
                        (float) $parent->current_balance + (float) $children->sum('current_balance'),
                        2,
                    );
                    $nestedIds = array_merge($nestedIds, $children->pluck('id')->all());
                }
                $groups['41'] = $items->reject(fn ($account) => in_array($account->id, $nestedIds, true))->values()->all();
            }
            $labels = [
                'أصول' => ['11' => 'أصول متداولة', '12' => 'حسابات مدينة', '13' => 'مخزون', '14' => 'أصول ثابتة', '15' => 'أخرى'],
                'خصوم' => ['21' => 'خصوم متداولة', '22' => 'خصوم طويلة الأجل', '25' => 'أخرى'],
                'حقوق ملكية' => ['31' => 'رأس المال والأرباح', '39' => 'أخرى'],
                'إيرادات' => ['41' => 'إيرادات التشغيل', '42' => 'إيرادات أخرى', '49' => 'أخرى'],
                'مصروفات' => ['51' => 'رواتب وأجور', '52' => 'خدمات', '53' => 'مصاريف إدارية', '54' => 'سفر ومهمات', '55' => 'صرفيات أخرى', '59' => 'أخرى'],
            ];
            $tree[] = [
                'type' => $type,
                'children' => collect($groups)->map(fn ($items, $key) => [
                    'code' => $key,
                    'label' => $labels[$type][$key] ?? 'حسابات عامة',
                    'children' => array_values($items),
                ])->values(),
            ];
        }

        return response()->json(['tree' => $tree]);
    }

    public function nextMovementNo(): JsonResponse
    {
        $max = 0;
        foreach (\App\Support\TenantDatabase::table('journal_entries')->pluck('movement_no') as $movement) {
            if (is_numeric(str_replace(',', '', $movement))) {
                $max = max($max, (int) str_replace(',', '', $movement));
            }
        }

        return response()->json(['no' => (string) ($max + 1)]);
    }

    public function journal(Request $request): JsonResponse
    {
        $query = \App\Support\TenantDatabase::table('journal_entries as je');
        $this->applyJournalFilters($query, $request);
        $limit = min(max((int) $request->query('limit', 500), 1), 2000);
        $entries = $query->select('je.*')
            ->orderByDesc('je.entry_date')->orderByDesc('je.id')->limit($limit)->get();
        $ids = $entries->pluck('id');
        $lines = \App\Support\TenantDatabase::table('journal_lines as jl')->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereIn('jl.entry_id', $ids)->select('jl.id', 'jl.entry_id', 'jl.account_id', 'a.acc_no', 'a.name', 'jl.debit', 'jl.credit')
            ->orderBy('jl.id')->get()->groupBy('entry_id');
        $result = $entries->map(function ($entry) use ($lines) {
            $entry->lines = $lines->get($entry->id, collect())->values();
            $entry->total_debit = round((float) $entry->lines->sum('debit'), 2);
            $entry->total_credit = round((float) $entry->lines->sum('credit'), 2);

            return $entry;
        });

        return response()->json(['entries' => $result, 'truncated' => $entries->count() >= $limit]);
    }

    public function createJournal(Request $request): JsonResponse
    {
        $data = $this->validatedJournal($request);
        if (\App\Support\TenantDatabase::table('journal_entries')->where('movement_no', $data['movement_no'])->exists()) {
            return response()->json(['error' => 'رقم الحركة مستخدم بالفعل'], 400);
        }
        $id = DB::transaction(function () use ($data) {
            $id = \App\Support\TenantDatabase::table('journal_entries')->insertGetId([
                'movement_no' => $data['movement_no'],
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'region' => $data['region'],
                'status' => $data['status'],
                'entry_type' => $data['entry_type'],
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($data['lines'] as $line) {
                \App\Support\TenantDatabase::table('journal_lines')->insert($line + ['entry_id' => $id]);
            }

            return $id;
        });
        $this->audit(Auth::user()->name, $data['status'] === 'posted' ? 'إنشاء قيد وترحيله' : 'إنشاء قيد (مسودة)', 'رقم الحركة '.$data['movement_no']);

        return response()->json(['ok' => true, 'id' => $id]);
    }

    public function updateJournal(Request $request, int $id): JsonResponse
    {
        $data = $this->validatedJournal($request);
        $error = DB::transaction(function () use ($id, $data): ?string {
            $entry = \App\Support\TenantDatabase::table('journal_entries')->where('id', $id)->lockForUpdate()->first();
            abort_if(! $entry, 404, 'القيد غير موجود');
            if ($entry->status === 'posted') {
                return 'لا يمكن تعديل قيد مرحل — قم بإلغاء الترحيل أولًا';
            }
            if ($this->isEmployeeCustodyEntry($id)) {
                return 'لا يمكن تعديل قيد توريد عهدة موظف';
            }
            if (\App\Support\TenantDatabase::table('journal_entries')->where('movement_no', $data['movement_no'])->where('id', '<>', $id)->exists()) {
                return 'رقم الحركة مستخدم بالفعل';
            }

            \App\Support\TenantDatabase::table('journal_entries')->where('id', $id)->update(collect($data)->except('lines')->all() + ['updated_at' => now()]);
            \App\Support\TenantDatabase::table('journal_lines')->where('entry_id', $id)->delete();
            foreach ($data['lines'] as $line) {
                \App\Support\TenantDatabase::table('journal_lines')->insert($line + ['entry_id' => $id]);
            }

            return null;
        });
        if ($error !== null) {
            return response()->json(['error' => $error], 400);
        }

        $this->audit(Auth::user()->name, 'تعديل قيد', 'رقم الحركة '.$data['movement_no']);

        return response()->json(['ok' => true]);
    }

    public function deleteJournal(int $id): JsonResponse
    {
        $entry = \App\Support\TenantDatabase::table('journal_entries')->find($id);
        abort_if(! $entry, 404, 'القيد غير موجود');
        if ($this->isEmployeeCustodyEntry($id)) {
            return response()->json(['error' => 'لا يمكن حذف قيد توريد عهدة موظف'], 400);
        }
        if ($entry->status === 'posted' && Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'حذف القيود المرحلة لمدير النظام فقط'], 403);
        }
        \App\Support\TenantDatabase::table('journal_entries')->where('id', $id)->delete();
        $this->audit(Auth::user()->name, 'حذف قيد', 'رقم الحركة '.$entry->movement_no);

        return response()->json(['ok' => true]);
    }

    public function bulkDeleteJournal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'in:all,month'],
            'month' => ['required_if:scope,month', 'nullable', 'date_format:Y-m'],
        ]);
        $query = \App\Support\TenantDatabase::table('journal_entries');
        $query->whereNotIn('id', \App\Support\TenantDatabase::table('employee_custody_deposits')->select('journal_entry_id')->whereNotNull('journal_entry_id'));
        if ($data['scope'] === 'month') {
            $query->whereBetween('entry_date', [$data['month'].'-01', date('Y-m-t', strtotime($data['month'].'-01'))]);
        }
        $deleted = $query->count();
        $query->delete();
        $this->audit(Auth::user()->name, 'حذف قيود متعددة', $deleted.' قيد');

        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }

    public function postJournal(int $id): JsonResponse
    {
        return $this->changeJournalStatus($id, 'posted');
    }

    public function unpostJournal(int $id): JsonResponse
    {
        return $this->changeJournalStatus($id, 'draft');
    }

    private function changeJournalStatus(int $id, string $status): JsonResponse
    {
        $entry = DB::transaction(function () use ($id, $status) {
            $entry = \App\Support\TenantDatabase::table('journal_entries')->where('id', $id)->lockForUpdate()->first();
            abort_if(! $entry, 404, 'القيد غير موجود');
            if ($this->isEmployeeCustodyEntry($id)) {
                throw ValidationException::withMessages([
                    'entry' => 'لا يمكن تغيير حالة قيد توريد عهدة موظف.',
                ]);
            }

            if ($status === 'posted') {
                $totals = \App\Support\TenantDatabase::table('journal_lines')->where('entry_id', $id)
                    ->selectRaw('COUNT(*) as line_count')
                    ->selectRaw('COALESCE(SUM(debit), 0) as debit_total')
                    ->selectRaw('COALESCE(SUM(credit), 0) as credit_total')
                    ->selectRaw('COALESCE(SUM(CASE WHEN debit < 0 OR credit < 0 OR (debit > 0 AND credit > 0) OR (debit = 0 AND credit = 0) THEN 1 ELSE 0 END), 0) as invalid_lines')
                    ->first();

                if (
                    $totals->line_count < 2
                    || $totals->debit_total <= 0
                    || abs((float) $totals->debit_total - (float) $totals->credit_total) > 0.004
                    || $totals->invalid_lines > 0
                ) {
                    throw ValidationException::withMessages([
                        'lines' => 'لا يمكن ترحيل القيد — يجب أن يحتوي على سطرين صالحين على الأقل وأن يتوازن المدين والدائن',
                    ]);
                }
            }

            \App\Support\TenantDatabase::table('journal_entries')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);

            return $entry;
        });
        $this->audit(Auth::user()->name, $status === 'posted' ? 'ترحيل قيد' : 'إلغاء ترحيل قيد', 'رقم الحركة '.$entry->movement_no);

        return response()->json(['ok' => true]);
    }

    private function validatedJournal(Request $request): array
    {
        $data = $request->validate([
            'movement_no' => ['required', 'string', 'max:120'],
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'region' => ['required', 'string', 'tenant_exists:regions,name'],
            'description' => ['nullable', 'string', 'max:4000'],
            'status' => ['nullable', 'in:draft,posted'],
            'entry_type' => ['nullable', 'in:'.implode(',', self::ENTRY_TYPES)],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'tenant_exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);
        $lines = [];
        foreach ($data['lines'] as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);
            if (($debit > 0 && $credit > 0) || ($debit == 0 && $credit == 0)) {
                if ($debit == 0 && $credit == 0) {
                    continue;
                }
                throw ValidationException::withMessages(['lines' => 'لا يمكن أن يكون السطر مدينًا ودائنًا معًا']);
            }
            $lines[] = ['account_id' => (int) $line['account_id'], 'debit' => $debit, 'credit' => $credit];
        }
        $debitTotal = round(array_sum(array_column($lines, 'debit')), 2);
        $creditTotal = round(array_sum(array_column($lines, 'credit')), 2);
        if (count($lines) < 2 || $debitTotal <= 0 || abs($debitTotal - $creditTotal) > 0.004) {
            throw ValidationException::withMessages(['lines' => 'القيد يحتاج سطرين على الأقل ويجب أن يتوازن المدين والدائن']);
        }

        return [
            'movement_no' => trim($data['movement_no']),
            'entry_date' => $data['entry_date'],
            'region' => $data['region'],
            'description' => trim($data['description'] ?? ''),
            'status' => $data['status'] ?? 'draft',
            'entry_type' => $data['entry_type'] ?? 'عادي',
            'lines' => $lines,
        ];
    }

    private function applyJournalFilters($query, Request $request): void
    {
        if ($from = $request->query('from')) {
            $query->where('je.entry_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->where('je.entry_date', '<=', $to);
        }
        if ($region = $request->query('region')) {
            $query->where('je.region', $region);
        }
        if (in_array($request->query('status'), ['draft', 'posted'], true)) {
            $query->where('je.status', $request->query('status'));
        }
        if (in_array($request->query('type'), self::ENTRY_TYPES, true)) {
            $query->where('je.entry_type', $request->query('type'));
        }
        if ($accountNo = $request->query('acc_no')) {
            $query->whereExists(function ($sub) use ($accountNo) {
                $sub->selectRaw('1')->from('journal_lines as jl')->join('accounts as a', 'a.id', '=', 'jl.account_id')
                    ->whereColumn('jl.entry_id', 'je.id')->where('a.acc_no', 'like', "%{$accountNo}%");
            });
        }
        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('je.movement_no', 'like', "%{$search}%")->orWhere('je.description', 'like', "%{$search}%"));
        }
    }

    public function ledger(Request $request): JsonResponse
    {
        $data = $request->validate(['acc_no' => ['required', 'string'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'], 'region' => ['nullable', 'string']]);
        $account = \App\Support\TenantDatabase::table('accounts')->where('acc_no', $data['acc_no'])->first();
        if (! $account) {
            return response()->json(['error' => 'رقم الحساب غير موجود'], 404);
        }
        $openQuery = \App\Support\TenantDatabase::table('journal_lines as jl')->join('journal_entries as je', 'je.id', '=', 'jl.entry_id')
            ->where('jl.account_id', $account->id)->where('je.status', 'posted');
        $periodQuery = \App\Support\TenantDatabase::table('journal_lines as jl')->join('journal_entries as je', 'je.id', '=', 'jl.entry_id')
            ->where('jl.account_id', $account->id)->where('je.status', 'posted');
        $this->applyLedgerFilters($openQuery, $data, true);
        $this->applyLedgerFilters($periodQuery, $data, false);
        $opening = (float) $account->opening_balance + (float) $openQuery->sum(DB::raw('jl.debit - jl.credit'));
        $rows = $periodQuery->select('je.entry_date as date', 'je.movement_no', 'je.description', 'je.region', 'jl.debit', 'jl.credit')
            ->orderBy('je.entry_date')->orderBy('je.id')->orderBy('jl.id')->get();
        $balance = round($opening, 2);
        $debit = $credit = 0.0;
        foreach ($rows as $row) {
            $row->debit = round((float) $row->debit, 2);
            $row->credit = round((float) $row->credit, 2);
            $debit += $row->debit;
            $credit += $row->credit;
            $balance = round($balance + $row->debit - $row->credit, 2);
            $row->balance = $balance;
        }

        return response()->json([
            'account' => $account,
            'opening' => round($opening, 2),
            'rows' => $rows,
            'totals' => ['debit' => round($debit, 2), 'credit' => round($credit, 2), 'balance' => round($balance, 2)],
        ]);
    }

    private function applyLedgerFilters($query, array $data, bool $before): void
    {
        if (! empty($data['region'])) {
            $query->where('je.region', $data['region']);
        }
        if ($before && empty($data['from'])) {
            $query->whereRaw('1 = 0');

            return;
        }
        if (! empty($data['from'])) {
            $query->where('je.entry_date', $before ? '<' : '>=', $data['from']);
        }
        if (! empty($data['to']) && ! $before) {
            $query->where('je.entry_date', '<=', $data['to']);
        }
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date'], 'region' => ['nullable', 'string']]);
        $accounts = \App\Support\TenantDatabase::table('accounts as a')->select('a.id', 'a.acc_no', 'a.name', 'a.opening_balance')
            ->selectSub(function ($query) use ($data) {
                $query->from('journal_lines as jl')->join('journal_entries as je', 'je.id', '=', 'jl.entry_id')
                    ->whereColumn('jl.account_id', 'a.id')->where('je.status', 'posted');
                if (! empty($data['region'])) {
                    $query->where('je.region', $data['region']);
                }
                if (! empty($data['from'])) {
                    $query->where('je.entry_date', '<', $data['from']);
                } else {
                    $query->whereRaw('1 = 0');
                }
                $query->selectRaw('COALESCE(SUM(jl.debit - jl.credit), 0)');
            }, 'pre')->get();
        $period = \App\Support\TenantDatabase::table('journal_lines as jl')->join('journal_entries as je', 'je.id', '=', 'jl.entry_id')
            ->where('je.status', 'posted')
            ->when(! empty($data['from']), fn ($q) => $q->where('je.entry_date', '>=', $data['from']))
            ->when(! empty($data['to']), fn ($q) => $q->where('je.entry_date', '<=', $data['to']))
            ->when(! empty($data['region']), fn ($q) => $q->where('je.region', $data['region']))
            ->groupBy('jl.account_id')->selectRaw('jl.account_id, SUM(jl.debit) d, SUM(jl.credit) c')->get()->keyBy('account_id');
        $totals = ['o_debit' => 0.0, 'o_credit' => 0.0, 'p_debit' => 0.0, 'p_credit' => 0.0, 'f_debit' => 0.0, 'f_credit' => 0.0];
        $rows = [];
        foreach ($accounts as $account) {
            $mov = $period->get($account->id);
            $pd = round((float) ($mov->d ?? 0), 2);
            $pc = round((float) ($mov->c ?? 0), 2);
            $opening = round((float) $account->opening_balance + (float) $account->pre, 2);
            $od = max($opening, 0);
            $oc = max(-$opening, 0);
            $final = round($opening + $pd - $pc, 2);
            $fd = max($final, 0);
            $fc = max(-$final, 0);
            if ($od == 0 && $oc == 0 && $pd == 0 && $pc == 0) {
                continue;
            }
            $rows[] = [$account->acc_no, $account->name, $od, $oc, $pd, $pc, $fd, $fc];
            foreach (['o_debit' => $od, 'o_credit' => $oc, 'p_debit' => $pd, 'p_credit' => $pc, 'f_debit' => $fd, 'f_credit' => $fc] as $key => $value) {
                $totals[$key] += $value;
            }
        }
        usort($rows, fn ($a, $b) => [strlen((string) $a[0]), $a[0]] <=> [strlen((string) $b[0]), $b[0]]);
        foreach ($totals as &$amount) {
            $amount = round($amount, 2);
        }

        return response()->json(['rows' => $rows, 'totals' => $totals, 'balanced' => abs($totals['p_debit'] - $totals['p_credit']) <= 0.01]);
    }

    public function budget(Request $request): JsonResponse
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date'], 'region' => ['nullable', 'string'], 'type' => ['nullable', 'string']]);
        $accounts = \App\Support\TenantDatabase::table('accounts')->orderByRaw('CAST(acc_no AS UNSIGNED), acc_no')
            ->when(! empty($data['type']), fn ($q) => $q->where('type', $data['type']))->get();
        $actuals = \App\Support\TenantDatabase::table('journal_lines as jl')->join('journal_entries as je', 'je.id', '=', 'jl.entry_id')
            ->where('je.status', 'posted')
            ->when(! empty($data['from']), fn ($q) => $q->where('je.entry_date', '>=', $data['from']))
            ->when(! empty($data['to']), fn ($q) => $q->where('je.entry_date', '<=', $data['to']))
            ->when(! empty($data['region']), fn ($q) => $q->where('je.region', $data['region']))
            ->groupBy('jl.account_id')->selectRaw('jl.account_id, SUM(jl.debit) d, SUM(jl.credit) c')->get()->keyBy('account_id');
        $totalBudget = $totalActual = 0.0;
        $rows = $accounts->map(function ($account) use ($actuals, &$totalBudget, &$totalActual) {
            $movement = $actuals->get($account->id);
            $debit = (float) ($movement->d ?? 0);
            $credit = (float) ($movement->c ?? 0);
            $actual = $this->budgetActual($account->type, $debit, $credit);
            $budget = (float) $account->budget;
            $pct = $budget ? round($actual / $budget * 100, 1) : 0;
            $status = ! $budget ? 'بدون ميزانية' : ($pct <= 70 ? 'أقل من المتوقع' : ($pct <= 90 ? 'ضمن الخطة' : ($pct <= 100 ? 'قريب من الحد' : ($pct <= 110 ? 'يزيد قليلاً' : 'تجاوز الحد'))));
            $totalBudget += $budget;
            $totalActual += $actual;

            return ['id' => $account->id, 'acc_no' => $account->acc_no, 'name' => $account->name, 'type' => $account->type, 'budget' => $budget, 'actual' => round($actual, 2), 'variance' => round($budget - $actual, 2), 'pct' => $pct, 'status' => $status];
        });
        $totalBudget = round($totalBudget, 2);
        $totalActual = round($totalActual, 2);

        return response()->json([
            'entries' => $rows,
            'summary' => ['budget' => $totalBudget, 'actual' => $totalActual, 'variance' => round($totalBudget - $totalActual, 2), 'pct' => $totalBudget ? round($totalActual / $totalBudget * 100, 1) : 0],
        ]);
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date'], 'region' => ['nullable', 'string']]);
        $rows = \App\Support\TenantDatabase::table('accounts as a')
            ->leftJoin('journal_lines as jl', 'jl.account_id', '=', 'a.id')
            ->leftJoin('journal_entries as je', function ($join) use ($data) {
                $join->on('je.id', '=', 'jl.entry_id')->where('je.status', 'posted');
                if (! empty($data['region'])) {
                    $join->where('je.region', $data['region']);
                }
                if (! empty($data['from'])) {
                    $join->where('je.entry_date', '>=', $data['from']);
                }
                if (! empty($data['to'])) {
                    $join->where('je.entry_date', '<=', $data['to']);
                }
            })
            ->whereIn('a.type', ['إيرادات', 'مصروفات'])
            ->groupBy('a.id', 'a.acc_no', 'a.name', 'a.type')
            ->orderByRaw('CAST(a.acc_no AS UNSIGNED), a.acc_no')
            ->select('a.acc_no', 'a.name', 'a.type')
            ->selectRaw('COALESCE(SUM(CASE WHEN je.id IS NULL THEN 0 ELSE jl.debit END), 0) d')
            ->selectRaw('COALESCE(SUM(CASE WHEN je.id IS NULL THEN 0 ELSE jl.credit END), 0) c')
            ->get();
        $revenues = [];
        $expenses = [];
        foreach ($rows as $row) {
            if ($row->type === 'إيرادات') {
                $revenues[] = ['acc_no' => $row->acc_no, 'name' => $row->name, 'amount' => round((float) $row->c - (float) $row->d, 2)];
            } else {
                $expenses[] = ['acc_no' => $row->acc_no, 'name' => $row->name, 'amount' => round((float) $row->d - (float) $row->c, 2)];
            }
        }
        $revenueTotal = round(array_sum(array_column($revenues, 'amount')), 2);
        $expenseTotal = round(array_sum(array_column($expenses, 'amount')), 2);

        return response()->json([
            'revenues' => $revenues,
            'revenues_total' => $revenueTotal,
            'expenses' => $expenses,
            'expenses_total' => $expenseTotal,
            'net' => round($revenueTotal - $expenseTotal, 2),
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date'], 'region' => ['nullable', 'string']]);
        $balances = $this->balancesAsOf($data['to'] ?? null, $data['region'] ?? null);
        $assets = [];
        $liabilitiesEquity = [];
        $assetsTotal = $liabilitiesTotal = $netCumulative = 0.0;
        foreach ($balances as $account) {
            if ($account['type'] === 'أصول') {
                $assets[] = $account;
                $assetsTotal += $account['amount'];
            } elseif (in_array($account['type'], ['خصوم', 'حقوق ملكية'], true)) {
                $account['amount'] = round(-$account['amount'], 2);
                $liabilitiesEquity[] = $account;
                $liabilitiesTotal += $account['amount'];
            }
            if ($account['type'] === 'إيرادات') {
                $netCumulative -= $account['amount'];
            } elseif ($account['type'] === 'مصروفات') {
                $netCumulative -= $account['amount'];
            }
        }
        $netCumulative = round($netCumulative, 2);
        $income = $this->incomeStatementData($data['from'] ?? null, $data['to'] ?? null, $data['region'] ?? null);
        $totalWithIncome = round($liabilitiesTotal + $netCumulative, 2);
        $difference = round($assetsTotal - $totalWithIncome, 2);

        return response()->json([
            'assets' => $assets,
            'assets_total' => round($assetsTotal, 2),
            'liabilities_equity' => $liabilitiesEquity,
            'liab_equity_total' => round($liabilitiesTotal, 2),
            'net_cumulative' => $netCumulative,
            'net_period' => $income['net'],
            'total_with_income' => $totalWithIncome,
            'difference' => $difference,
            'balanced' => abs($difference) <= 0.02,
        ]);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date'], 'region' => ['nullable', 'string']]);
        $cashAccounts = \App\Support\TenantDatabase::table('accounts')->where('type', 'أصول')
            ->where(function ($query) {
                $query->where('name', 'like', '%صندوق%')->orWhere('name', 'like', '%بنك%')->orWhere('name', 'like', '%نقد%');
            })->orderByRaw('CAST(acc_no AS UNSIGNED), acc_no')->get();
        $totals = ['opening' => 0.0, 'inflow' => 0.0, 'outflow' => 0.0, 'closing' => 0.0];
        $rows = [];
        foreach ($cashAccounts as $account) {
            $openingQuery = \App\Support\TenantDatabase::table('journal_lines as jl')->join('journal_entries as je', 'je.id', '=', 'jl.entry_id')
                ->where('jl.account_id', $account->id)->where('je.status', 'posted');
            if (! empty($data['region'])) {
                $openingQuery->where('je.region', $data['region']);
            }
            if (! empty($data['from'])) {
                $openingQuery->where('je.entry_date', '<', $data['from']);
            }
            $opening = (float) $account->opening_balance + (float) $openingQuery->sum(DB::raw('jl.debit - jl.credit'));
            $periodQuery = \App\Support\TenantDatabase::table('journal_lines as jl')->join('journal_entries as je', 'je.id', '=', 'jl.entry_id')
                ->where('jl.account_id', $account->id)->where('je.status', 'posted')
                ->when(! empty($data['from']), fn ($q) => $q->where('je.entry_date', '>=', $data['from']))
                ->when(! empty($data['to']), fn ($q) => $q->where('je.entry_date', '<=', $data['to']))
                ->when(! empty($data['region']), fn ($q) => $q->where('je.region', $data['region']));
            $inflow = (float) $periodQuery->sum('jl.debit');
            $outflow = (float) $periodQuery->sum('jl.credit');
            $closing = round($opening + $inflow - $outflow, 2);
            $rows[] = ['acc_no' => $account->acc_no, 'name' => $account->name, 'opening' => round($opening, 2), 'inflow' => round($inflow, 2), 'outflow' => round($outflow, 2), 'closing' => $closing];
            $totals['opening'] += $opening;
            $totals['inflow'] += $inflow;
            $totals['outflow'] += $outflow;
            $totals['closing'] += $closing;
        }
        foreach ($totals as &$amount) {
            $amount = round($amount, 2);
        }

        return response()->json(['rows' => $rows, 'totals' => $totals, 'net_flow' => round($totals['inflow'] - $totals['outflow'], 2)]);
    }

    private function balancesAsOf(?string $to, ?string $region): array
    {
        $accounts = \App\Support\TenantDatabase::table('accounts as a')
            ->leftJoin('journal_lines as jl', 'jl.account_id', '=', 'a.id')
            ->leftJoin('journal_entries as je', function ($join) use ($to, $region) {
                $join->on('je.id', '=', 'jl.entry_id')->where('je.status', 'posted');
                if ($to) {
                    $join->where('je.entry_date', '<=', $to);
                }
                if ($region) {
                    $join->where('je.region', $region);
                }
            })
            ->groupBy('a.id', 'a.acc_no', 'a.name', 'a.type', 'a.opening_balance')
            ->orderByRaw('CAST(a.acc_no AS UNSIGNED), a.acc_no')
            ->select('a.id', 'a.acc_no', 'a.name', 'a.type', 'a.opening_balance')
            ->selectRaw('COALESCE(SUM(CASE WHEN je.id IS NULL THEN 0 ELSE jl.debit - jl.credit END), 0) movement')
            ->get();

        return $accounts->mapWithKeys(fn ($a) => [$a->acc_no => [
            'id' => $a->id,
            'acc_no' => $a->acc_no,
            'name' => $a->name,
            'type' => $a->type,
            'amount' => round((float) $a->opening_balance + (float) $a->movement, 2),
        ]])->all();
    }

    private function incomeStatementData(?string $from, ?string $to, ?string $region): array
    {
        $request = Request::create('/api/income-statement', 'GET', array_filter(['from' => $from, 'to' => $to, 'region' => $region]));

        return $this->incomeStatement($request)->getData(true);
    }

    private function budgetActual(string $type, float $debit, float $credit): float
    {
        return match ($type) {
            'مصروفات' => $debit - $credit,
            'إيرادات' => $credit - $debit,
            default => $debit - $credit,
        };
    }

    public function users(): JsonResponse
    {
        return response()->json(['users' => User::query()->select('id', 'username', 'name', 'email', 'role', 'security_question', 'created_at')->orderBy('id')->get()]);
    }

    public function createUser(Request $request): JsonResponse
    {
        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);
        $tenantId = Auth::user()->tenant_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->where('tenant_id', $tenantId)],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:'.implode(',', self::USER_ROLES)],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/\d/'],
            'security_question' => ['nullable', 'string', 'max:500'],
            'security_answer' => ['nullable', 'string', 'required_with:security_question'],
        ]);
        User::create([
            'username' => Str::lower(trim($data['username'])),
            'name' => trim($data['name']),
            'email' => Str::lower(trim($data['email'])),
            'password' => $data['password'],
            'password_hash' => Hash::make($data['password']),
            'role' => $data['role'],
            'security_question' => trim($data['security_question'] ?? ''),
            'security_answer_hash' => filled($data['security_answer'] ?? null) ? Hash::make(trim($data['security_answer'])) : '',
            'status' => true,
            'tenant_id' => Auth::user()->tenant_id,
        ]);
        $this->audit(Auth::user()->name, 'إضافة مستخدم', trim($data['username']));

        return response()->json(['ok' => true]);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->where('tenant_id', $user->tenant_id)->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$id],
            'role' => ['required', 'in:'.implode(',', self::USER_ROLES)],
        ]);

        if ($user->role === 'admin' && $data['role'] !== 'admin') {
            if ($user->id === Auth::id() || User::where('role', 'admin')->count() <= 1) {
                return response()->json(['error' => 'لا يمكن تغيير صلاحية آخر مدير نظام'], 400);
            }
        }

        if ($user->role === 'staff' && $data['role'] !== 'staff') {
            $user->regions()->sync([]);
        }

        $user->name = trim($data['name']);
        $user->username = Str::lower(trim($data['username']));
        $user->email = Str::lower(trim($data['email']));
        $user->role = $data['role'];
        $user->save();
        $this->audit(Auth::user()->name, 'تعديل بيانات مستخدم', $user->username);

        return response()->json(['ok' => true]);
    }

    public function setUserSecurity(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['security_question' => ['required', 'string', 'max:500'], 'security_answer' => ['required', 'string']]);
        $user = User::findOrFail($id);
        $user->security_question = trim($data['security_question']);
        $user->security_answer_hash = Hash::make(trim($data['security_answer']));
        $user->save();
        $this->audit(Auth::user()->name, 'تحديث سؤال أمان', $user->username);

        return response()->json(['ok' => true]);
    }

    public function setUserPassword(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/\d/']]);
        $user = User::findOrFail($id);
        $user->password = $data['password'];
        $user->password_hash = Hash::make($data['password']);
        $user->save();
        $this->audit(Auth::user()->name, 'تغيير كلمة مرور', $user->username);

        return response()->json(['ok' => true]);
    }

    public function deleteUser(int $id): JsonResponse
    {
        if ($id === Auth::id()) {
            return response()->json(['error' => 'لا يمكنك حذف نفسك'], 400);
        }
        $user = User::findOrFail($id);
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json(['error' => 'لا يمكن حذف آخر مدير نظام'], 400);
        }
        $user->delete();
        $this->audit(Auth::user()->name, 'حذف مستخدم', $user->username);

        return response()->json(['ok' => true]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'old' => ['required', 'string'],
            'new' => ['required', 'string', 'min:8', 'different:old', 'regex:/[A-Za-z]/', 'regex:/\d/'],
        ]);
        $user = Auth::user();
        if (! $this->passwordMatches($data['old'], $user->password_hash)) {
            return response()->json(['error' => 'كلمة المرور الحالية غير صحيحة'], 400);
        }
        $user->password = $data['new'];
        $user->password_hash = Hash::make($data['new']);
        $user->save();
        $this->audit($user->name, 'تغيير كلمة مروره الشخصية');

        return response()->json(['ok' => true]);
    }

    public function regions(): JsonResponse
    {
        return response()->json(['regions' => \App\Support\TenantDatabase::table('regions')->orderBy('id')->get()->map(function ($region) {
            $region->usage_count = \App\Support\TenantDatabase::table('journal_entries')->where('region', $region->name)->count()
                + \App\Support\TenantDatabase::table('hotels')->where('region_id', $region->id)->count()
                + \App\Support\TenantDatabase::table('trips')->where('region_id', $region->id)->count()
                + \App\Support\TenantDatabase::table('boats')->where('region_id', $region->id)->count()
                + \App\Support\TenantDatabase::table('vehicles')->where('region_id', $region->id)->count()
                + \App\Support\TenantDatabase::table('staff_region_assignments')->where('region_id', $region->id)->count();

            return $region;
        })]);
    }

    public function regionHotels(): JsonResponse
    {
        return response()->json([
            'regions' => \App\Support\TenantDatabase::table('regions')->orderByDesc('is_active')->orderBy('name')->get(['id', 'name', 'is_active']),
            'hotels' => \App\Support\TenantDatabase::table('hotels')
                ->leftJoin('regions', 'regions.id', '=', 'hotels.region_id')
                ->orderBy('hotels.name')
                ->get([
                    'hotels.id',
                    'hotels.name',
                    'hotels.region_id',
                    'regions.name as region_name',
                    'regions.is_active as region_is_active',
                ]),
        ]);
    }

    public function regionTrips(): JsonResponse
    {
        return response()->json([
            'regions' => \App\Support\TenantDatabase::table('regions')->orderByDesc('is_active')->orderBy('name')->get(['id', 'name', 'is_active']),
            'trips' => \App\Support\TenantDatabase::table('trips')
                ->leftJoin('regions', 'regions.id', '=', 'trips.region_id')
                ->orderBy('trips.name')
                ->get([
                    'trips.id',
                    'trips.name',
                    'trips.region_id',
                    'regions.name as region_name',
                    'regions.is_active as region_is_active',
                ]),
        ]);
    }

    public function tripHotels(): JsonResponse
    {
        return response()->json([
            'trips' => \App\Support\TenantDatabase::table('trips')
                ->leftJoin('regions', 'regions.id', '=', 'trips.region_id')
                ->orderBy('trips.name')
                ->get([
                    'trips.id',
                    'trips.name',
                    'trips.region_id',
                    'regions.name as region_name',
                    'regions.is_active as region_is_active',
                ]),
            'hotels' => \App\Support\TenantDatabase::table('hotels')
                ->join('regions', 'regions.id', '=', 'hotels.region_id')
                ->where('hotels.status', true)
                ->where('regions.is_active', true)
                ->orderBy('hotels.name')
                ->get([
                    'hotels.id',
                    'hotels.name',
                    'hotels.region_id',
                    'regions.name as region_name',
                ]),
            'assignments' => \App\Support\TenantDatabase::table('trips_hotels')
                ->where('status', true)
                ->get(['trip_id', 'hotel_id', 'pickup_time']),
        ]);
    }

    public function updateTripHotels(Request $request, int $id): JsonResponse
    {
        $trip = \App\Support\TenantDatabase::table('trips')->where('id', $id)->first();
        abort_if(! $trip, 404, 'الرحلة غير موجودة');

        $data = $request->validate([
            'hotel_ids' => ['present', 'array'],
            'hotel_ids.*' => ['required', 'integer', 'distinct', 'tenant_exists:hotels,id'],
            'hotel_times' => ['sometimes', 'array'],
            'hotel_times.*' => ['nullable', 'date_format:H:i'],
        ]);

        if (! $trip->region_id || ! \App\Support\TenantDatabase::table('regions')->where('id', $trip->region_id)->where('is_active', true)->exists()) {
            return response()->json(['error' => 'حدد منطقة نشطة للرحلة أولًا.'], 422);
        }

        $hotelIds = collect($data['hotel_ids'])->map(fn ($hotelId) => (int) $hotelId)->all();
        $validHotelCount = \App\Support\TenantDatabase::table('hotels')
            ->join('regions', 'regions.id', '=', 'hotels.region_id')
            ->whereIn('hotels.id', $hotelIds)
            ->where('hotels.region_id', $trip->region_id)
            ->where('hotels.status', true)
            ->where('regions.is_active', true)
            ->count();

        if ($validHotelCount !== count($hotelIds)) {
            return response()->json(['error' => 'اختر فنادق نشطة تابعة لمنطقة الرحلة فقط.'], 422);
        }

        DB::transaction(function () use ($id, $hotelIds, $data): void {
            $existingAssignments = \App\Support\TenantDatabase::table('trips_hotels')
                ->where('trip_id', $id)
                ->get(['hotel_id', 'pickup_time'])
                ->keyBy(fn ($assignment) => (int) $assignment->hotel_id);

            \App\Support\TenantDatabase::table('trips_hotels')
                ->where('trip_id', $id)
                ->whereNotIn('hotel_id', $hotelIds ?: [0])
                ->where('status', true)
                ->update(['status' => false, 'updated_at' => now()]);

            foreach ($hotelIds as $hotelId) {
                $pickupTime = array_key_exists($hotelId, $data['hotel_times'] ?? [])
                    ? ($data['hotel_times'][$hotelId] ?: null)
                    : ($existingAssignments->get($hotelId)?->pickup_time);
                \App\Support\TenantDatabase::table('trips_hotels')->updateOrInsert(
                    ['trip_id' => $id, 'hotel_id' => $hotelId],
                    [
                        'status' => true,
                        'pickup_time' => $pickupTime,
                        'updated_at' => now(),
                        ...(! $existingAssignments->has($hotelId) ? ['created_at' => now()] : []),
                    ],
                );
            }
        });

        $hotelNames = \App\Support\TenantDatabase::table('hotels')->whereIn('id', $hotelIds)->orderBy('name')->pluck('name')->implode('، ');
        $this->audit(Auth::user()->name, 'ربط فنادق برحلة', $trip->name.' — '.($hotelNames ?: 'تم إلغاء كل الفنادق'));

        return response()->json(['ok' => true]);
    }

    public function assignTripRegion(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'region_id' => [
                'present',
                'nullable',
                'integer',
                Rule::exists('regions', 'id')->where('is_active', true)->where('tenant_id', \App\Support\TenantContext::id()),
            ],
        ]);
        $trip = \App\Support\TenantDatabase::table('trips')->where('id', $id)->first();
        abort_if(! $trip, 404, 'الرحلة غير موجودة');

        \App\Support\TenantDatabase::table('trips')->where('id', $id)->update([
            'region_id' => $data['region_id'],
            'updated_at' => now(),
        ]);
        $regionName = $data['region_id']
            ? \App\Support\TenantDatabase::table('regions')->where('id', $data['region_id'])->value('name')
            : 'بدون منطقة';
        $this->audit(Auth::user()->name, 'تحديد منطقة رحلة', $trip->name.' — '.$regionName);

        return response()->json(['ok' => true]);
    }

    public function assignHotelRegion(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'region_id' => [
                'present',
                'nullable',
                'integer',
                Rule::exists('regions', 'id')->where('is_active', true)->where('tenant_id', \App\Support\TenantContext::id()),
            ],
        ]);
        $hotel = \App\Support\TenantDatabase::table('hotels')->where('id', $id)->first();
        abort_if(! $hotel, 404, 'الفندق غير موجود');

        \App\Support\TenantDatabase::table('hotels')->where('id', $id)->update([
            'region_id' => $data['region_id'],
            'updated_at' => now(),
        ]);
        $this->audit(Auth::user()->name, 'تحديد منطقة فندق', $hotel->name.' — '.($data['region_id'] ? \App\Support\TenantDatabase::table('regions')->where('id', $data['region_id'])->value('name') : 'بدون منطقة'));

        return response()->json(['ok' => true]);
    }

    public function updateUserRegions(Request $request, int $id): JsonResponse
    {
        $user = User::query()->where('role', 'staff')->findOrFail($id);
        $data = $request->validate([
            'region_ids' => ['present', 'array'],
            'region_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('regions', 'id')->where('is_active', true)->where('tenant_id', \App\Support\TenantContext::id()),
            ],
        ]);

        $regionAssignments = array_fill_keys(
            $data['region_ids'],
            ['tenant_id' => \App\Support\TenantContext::id()],
        );
        $user->regions()->sync($regionAssignments);
        $regionNames = \App\Support\TenantDatabase::table('regions')->whereIn('id', $data['region_ids'])->orderBy('name')->pluck('name')->implode('، ');
        $this->audit(Auth::user()->name, 'تحديد مناطق موظف', $user->name.' — '.($regionNames ?: 'بدون مناطق'));

        return response()->json(['ok' => true]);
    }

    public function createRegion(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', Rule::unique('regions', 'name')->where('tenant_id', \App\Support\TenantContext::id())]]);
        $id = \App\Support\TenantDatabase::table('regions')->insertGetId(['name' => trim($data['name']), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->audit(Auth::user()->name, 'إضافة منطقة', $data['name']);

        return response()->json(['ok' => true, 'id' => $id]);
    }

    public function renameRegion(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', Rule::unique('regions', 'name')->ignore($id)->where('tenant_id', \App\Support\TenantContext::id())]]);
        $region = \App\Support\TenantDatabase::table('regions')->find($id);
        abort_if(! $region, 404, 'المنطقة غير موجودة');
        DB::transaction(function () use ($id, $region, $data) {
            \App\Support\TenantDatabase::table('regions')->where('id', $id)->update(['name' => trim($data['name']), 'updated_at' => now()]);
            \App\Support\TenantDatabase::table('journal_entries')->where('region', $region->name)->update(['region' => trim($data['name'])]);
        });
        $this->audit(Auth::user()->name, 'تعديل منطقة', $data['name']);

        return response()->json(['ok' => true]);
    }

    public function deactivateRegion(int $id): JsonResponse
    {
        \App\Support\TenantDatabase::table('regions')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);
        $this->audit(Auth::user()->name, 'تعطيل منطقة', (string) $id);

        return response()->json(['ok' => true]);
    }

    public function activateRegion(int $id): JsonResponse
    {
        \App\Support\TenantDatabase::table('regions')->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]);
        $this->audit(Auth::user()->name, 'تفعيل منطقة', (string) $id);

        return response()->json(['ok' => true]);
    }

    public function deleteRegion(int $id): JsonResponse
    {
        $region = \App\Support\TenantDatabase::table('regions')->find($id);
        abort_if(! $region, 404, 'المنطقة غير موجودة');
        $count = \App\Support\TenantDatabase::table('journal_entries')->where('region', $region->name)->count()
            + \App\Support\TenantDatabase::table('hotels')->where('region_id', $region->id)->count()
            + \App\Support\TenantDatabase::table('trips')->where('region_id', $region->id)->count()
            + \App\Support\TenantDatabase::table('boats')->where('region_id', $region->id)->count()
            + \App\Support\TenantDatabase::table('vehicles')->where('region_id', $region->id)->count()
            + \App\Support\TenantDatabase::table('staff_region_assignments')->where('region_id', $region->id)->count();
        if ($count > 0) {
            return response()->json(['error' => "لا يمكن الحذف — توجد {$count} ارتباطات بهذه المنطقة. أزل الارتباطات أو عطّل المنطقة بدلًا من الحذف"], 400);
        }
        \App\Support\TenantDatabase::table('regions')->where('id', $id)->delete();
        $this->audit(Auth::user()->name, 'حذف منطقة', (string) $id);

        return response()->json(['ok' => true]);
    }

    public function importAccounts(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:10240']]);
        $rows = $this->spreadsheetRows($request->file('file')->getRealPath());
        [$headerIndex, $headers] = $this->findSpreadsheetHeaders($rows, [
            'name' => ['اسم الحساب', 'الحساب', 'الاسم'],
            'acc_no' => ['رقم الحساب', 'كود الحساب', 'الكود', 'رقم'],
            'type' => ['النوع', 'نوع الحساب'],
            'opening' => ['رصيد افتتاحي', 'افتتاحي'],
        ]);
        if ($headerIndex === null || ! isset($headers['name'], $headers['acc_no'])) {
            return response()->json(['error' => 'لم يتم العثور على عمودي (اسم الحساب / رقم الحساب). تأكد من استخدام القالب الصحيح.'], 400);
        }

        $added = $updated = 0;
        $errors = [];
        DB::transaction(function () use ($rows, $headerIndex, $headers, &$added, &$updated, &$errors) {
            foreach (array_slice($rows, $headerIndex + 1, null, true) as $index => $row) {
                $number = $this->normalizeCell($row[$headers['acc_no']] ?? '');
                $name = $this->normalizeCell($row[$headers['name']] ?? '');
                if ($number === '' && $name === '') {
                    continue;
                }
                if ($number === '' || $name === '') {
                    $errors[] = 'صف '.($index + 1).': اسم الحساب ورقم الحساب مطلوبان معًا — تم تخطيه';
                    continue;
                }
                $account = \App\Support\TenantDatabase::table('accounts')->where('acc_no', $number)->first();
                $values = ['updated_at' => now()];
                if ($account?->is_system) {
                    $values['opening_balance'] = $this->spreadsheetNumber($row[$headers['opening']] ?? 0);
                    if ($name !== $account->name || $this->normalizeCell($row[$headers['type']] ?? '') !== $account->type) {
                        $errors[] = 'صف '.($index + 1).': الحساب أساسي ومشترك؛ تم تحديث رصيده الافتتاحي فقط';
                    }
                } else {
                    $values += [
                        'name' => $name,
                        'type' => $this->normalizeCell($row[$headers['type']] ?? ''),
                        'opening_balance' => $this->spreadsheetNumber($row[$headers['opening']] ?? 0),
                    ];
                }
                if ($account) {
                    \App\Support\TenantDatabase::table('accounts')->where('id', $account->id)->update($values);
                    $updated++;
                } else {
                    \App\Support\TenantDatabase::table('accounts')->insert($values + ['acc_no' => $number, 'budget' => 0, 'created_at' => now()]);
                    $added++;
                }
            }
        });
        $this->audit(Auth::user()->name, 'استيراد دليل حسابات', "جديد: {$added} | محدّث: {$updated}");

        return response()->json(['ok' => true, 'added' => $added, 'updated' => $updated, 'errors' => array_slice($errors, 0, 10)]);
    }

    public function importJournal(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            'region' => ['required', 'string', 'tenant_exists:regions,name'],
            'post' => ['nullable', 'in:0,1'],
        ]);
        $rows = $this->spreadsheetRows($request->file('file')->getRealPath());
        [$headerIndex, $headers] = $this->findSpreadsheetHeaders($rows, [
            'movement' => ['رقم الحركة', 'رقم الحركة', 'رقم القيد', 'قيد', 'الحركة'],
            'date' => ['التاريخ', 'تاريخ'],
            'debit' => ['مدين', 'له'],
            'credit' => ['دائن', 'عليه'],
            'name' => ['اسم الحساب', 'إسم الحساب', 'الحساب', 'الاسم'],
            'acc_no' => ['رقم الحساب', 'كود الحساب', 'الكود'],
            'description' => ['البيان', 'شرح', 'الوصف', 'ملاحظات'],
            'region' => ['المنطقة', 'منطقة', 'الفرع'],
        ]);
        if ($headerIndex === null || ! isset($headers['movement'], $headers['acc_no'])) {
            return response()->json(['error' => 'تعذر التعرف على أعمدة قالب دفتر اليومية.'], 400);
        }

        $groups = [];
        $errors = [];
        $auto = 0;
        foreach (array_slice($rows, $headerIndex + 1, null, true) as $index => $row) {
            $accountNo = $this->normalizeCell($row[$headers['acc_no']] ?? '');
            $debit = round($this->spreadsheetNumber($row[$headers['debit']] ?? 0), 2);
            $credit = round($this->spreadsheetNumber($row[$headers['credit']] ?? 0), 2);
            if ($accountNo === '' && $debit == 0 && $credit == 0) {
                continue;
            }
            if ($debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0) || ($debit == 0 && $credit == 0)) {
                $errors[] = 'صف '.($index + 1).': قيمة المدين أو الدائن غير صالحة — تم تخطيه';
                continue;
            }
            $movement = $this->normalizeCell($row[$headers['movement']] ?? '');
            if ($movement === '') {
                $movement = '#تلقائي-'.(++$auto);
            }
            $date = $this->spreadsheetDate($row[$headers['date']] ?? null);
            $groups[$movement] ??= [
                'movement' => $movement,
                'date' => $date,
                'description' => $this->normalizeCell($row[$headers['description']] ?? ''),
                'region' => $this->normalizeCell($row[$headers['region']] ?? ''),
                'lines' => [],
            ];
            $groups[$movement]['date'] ??= $date;
            if ($groups[$movement]['description'] === '') {
                $groups[$movement]['description'] = $this->normalizeCell($row[$headers['description']] ?? '');
            }
            if ($groups[$movement]['region'] === '') {
                $groups[$movement]['region'] = $this->normalizeCell($row[$headers['region']] ?? '');
            }
            $groups[$movement]['lines'][] = [
                'acc_no' => $accountNo,
                'name' => $this->normalizeCell($row[$headers['name']] ?? ''),
                'debit' => $debit,
                'credit' => $credit,
            ];
        }
        if (! $groups) {
            return response()->json(['ok' => false, 'added' => 0, 'drafts' => 0, 'new_accounts' => 0, 'skipped' => count($errors), 'errors' => array_slice($errors ?: ['لم يتم العثور على بيانات صالحة في الملف'], 0, 10)]);
        }

        $added = $drafts = $newAccounts = 0;
        $fallbackRegion = $request->input('region');
        $shouldPost = $request->input('post') === '1';
        DB::transaction(function () use ($groups, &$errors, &$added, &$drafts, &$newAccounts, $fallbackRegion, $shouldPost) {
            foreach ($groups as $group) {
                $lines = [];
                foreach ($group['lines'] as $line) {
                    if ($line['acc_no'] === '') {
                        $errors[] = 'رقم الحركة '.$group['movement'].': سطر بدون رقم حساب — تم تخطيه';
                        continue;
                    }
                    $account = \App\Support\TenantDatabase::table('accounts')->where('acc_no', $line['acc_no'])->first();
                    if (! $account) {
                        $accountId = \App\Support\TenantDatabase::table('accounts')->insertGetId([
                            'acc_no' => $line['acc_no'],
                            'name' => $line['name'] ?: 'حساب مستورد '.$line['acc_no'],
                            'type' => '',
                            'opening_balance' => 0,
                            'budget' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $newAccounts++;
                    } else {
                        $accountId = $account->id;
                    }
                    $lines[] = ['account_id' => $accountId, 'debit' => $line['debit'], 'credit' => $line['credit']];
                }
                if (count($lines) < 2) {
                    $errors[] = 'رقم الحركة '.$group['movement'].': يحتاج القيد سطرين صالحين على الأقل — تم تخطيه';
                    continue;
                }
                $debitTotal = round(array_sum(array_column($lines, 'debit')), 2);
                $creditTotal = round(array_sum(array_column($lines, 'credit')), 2);
                $balanced = abs($debitTotal - $creditTotal) <= 0.004 && $debitTotal > 0;
                $status = $balanced && $shouldPost ? 'posted' : 'draft';
                if (! $balanced) {
                    $drafts++;
                    $errors[] = 'رقم الحركة '.$group['movement'].' غير متوازن وأُضيف كمسودة';
                }
                $movement = $group['movement'];
                $suffix = 2;
                while (\App\Support\TenantDatabase::table('journal_entries')->where('movement_no', $movement)->exists()) {
                    $movement = $group['movement'].'-'.$suffix++;
                }
                $region = in_array($group['region'], \App\Support\TenantDatabase::table('regions')->where('is_active', true)->pluck('name')->all(), true)
                    ? $group['region']
                    : $fallbackRegion;
                if ($group['region'] && $region !== $group['region']) {
                    $errors[] = 'رقم الحركة '.$movement.': المنطقة غير معروفة — استُخدمت المنطقة المختارة';
                }
                $entryId = \App\Support\TenantDatabase::table('journal_entries')->insertGetId([
                    'movement_no' => $movement,
                    'entry_date' => $group['date'] ?: now()->toDateString(),
                    'description' => $group['description'],
                    'region' => $region,
                    'status' => $status,
                    'entry_type' => 'عادي',
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                foreach ($lines as $line) {
                    \App\Support\TenantDatabase::table('journal_lines')->insert($line + ['entry_id' => $entryId]);
                }
                $added++;
            }
        });
        $this->audit(Auth::user()->name, 'استيراد دفتر يومية', "قيود: {$added} | حسابات جديدة: {$newAccounts}");

        return response()->json(['ok' => true, 'added' => $added, 'drafts' => $drafts, 'new_accounts' => $newAccounts, 'skipped' => count($errors), 'errors' => array_slice($errors, 0, 10)]);
    }

    private function spreadsheetRows(string $path): array
    {
        $reader = new Reader();
        $reader->open($path);
        $rows = [];
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = array_map(fn ($cell) => $cell->getValue(), $row->getCells());
                }
                break;
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    private function findSpreadsheetHeaders(array $rows, array $synonyms): array
    {
        foreach (array_slice($rows, 0, 30, true) as $rowIndex => $row) {
            $map = [];
            foreach ($row as $column => $value) {
                $value = $this->normalizeHeader($value);
                foreach ($synonyms as $field => $labels) {
                    foreach ($labels as $label) {
                        if ($value === $this->normalizeHeader($label) || str_contains($value, $this->normalizeHeader($label))) {
                            $map[$field] = $column;
                            break 2;
                        }
                    }
                }
            }
            if (count($map) >= 2) {
                return [$rowIndex, $map];
            }
        }

        return [null, []];
    }

    private function normalizeHeader(mixed $value): string
    {
        return str_replace(['إ', 'أ', 'آ', 'ة', 'ى', ' '], ['ا', 'ا', 'ا', 'ه', 'ي', ''], mb_strtolower(trim((string) $value)));
    }

    private function normalizeCell(mixed $value): string
    {
        $digits = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'];

        return trim(preg_replace('/\s+/u', ' ', strtr((string) $value, $digits)) ?? '');
    }

    private function spreadsheetNumber(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        $value = str_replace(['٬', ',', '٫'], ['', '', '.'], $this->normalizeCell($value));
        if (preg_match('/^\((.*)\)$/', $value, $matches)) {
            return -(float) $matches[1];
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function spreadsheetDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $value = $this->normalizeCell($value);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y'] as $format) {
            $date = \DateTime::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    public function exportAccounts()
    {
        $accounts = \App\Support\TenantDatabase::table('accounts')->orderByRaw('CAST(acc_no AS UNSIGNED), acc_no')->get();
        $rows = $accounts->map(fn ($a) => [$a->acc_no, $a->name, $a->type, $a->opening_balance, $a->budget])->all();
        $rows[] = ['', 'الإجمالي', '', $accounts->sum('opening_balance'), $accounts->sum('budget')];

        return $this->downloadWorkbook('دليل الحسابات.xlsx', 'دليل الحسابات', ['رقم الحساب', 'اسم الحساب', 'النوع', 'الرصيد الافتتاحي', 'الميزانية التقديرية'], $rows);
    }

    public function exportJournal(Request $request)
    {
        $query = \App\Support\TenantDatabase::table('journal_entries as je');
        $this->applyJournalFilters($query, $request);
        $entries = $query->orderBy('je.entry_date')->orderBy('je.id')->get();
        $rows = [];
        $debitTotal = $creditTotal = 0.0;
        foreach ($entries as $entry) {
            $lines = \App\Support\TenantDatabase::table('journal_lines as jl')->join('accounts as a', 'a.id', '=', 'jl.account_id')->where('jl.entry_id', $entry->id)->orderBy('jl.id')->get();
            foreach ($lines as $line) {
                $rows[] = [$entry->movement_no, $entry->entry_date, $line->acc_no, $line->name, $entry->description, $entry->region, $entry->entry_type, $entry->status === 'posted' ? 'مرحل' : 'مسودة', $line->debit, $line->credit];
                $debitTotal += (float) $line->debit;
                $creditTotal += (float) $line->credit;
            }
        }
        $rows[] = ['', '', '', '', 'الإجمالي', '', '', '', round($debitTotal, 2), round($creditTotal, 2)];

        return $this->downloadWorkbook('دفتر اليومية.xlsx', 'دفتر اليومية', ['رقم الحركة', 'التاريخ', 'رقم الحساب', 'اسم الحساب', 'البيان', 'المنطقة', 'نوع القيد', 'الحالة', 'مدين', 'دائن'], $rows);
    }

    public function exportLedger(Request $request)
    {
        $data = $this->ledger($request)->getData(true);
        if (isset($data['error'])) {
            abort(404);
        }
        $rows = array_map(fn ($r) => [$r['date'], $r['movement_no'], $r['description'], $r['region'], $r['debit'], $r['credit'], $r['balance']], $data['rows']);
        $rows[] = ['', '', 'الإجمالي', '', $data['totals']['debit'], $data['totals']['credit'], $data['totals']['balance']];

        return $this->downloadWorkbook('الأستاذ العام.xlsx', 'الأستاذ العام', ['التاريخ', 'رقم الحركة', 'البيان', 'المنطقة', 'مدين', 'دائن', 'الرصيد'], $rows, ['الحساب: '.$data['account']['name'], 'الرصيد الافتتاحي: '.$data['opening']]);
    }

    public function exportTrialBalance(Request $request)
    {
        $data = $this->trialBalance($request)->getData(true);

        $rows = $data['rows'];
        $rows[] = ['', 'الإجمالي', $data['totals']['o_debit'], $data['totals']['o_credit'], $data['totals']['p_debit'], $data['totals']['p_credit'], $data['totals']['f_debit'], $data['totals']['f_credit']];

        return $this->downloadWorkbook('ميزان المراجعة.xlsx', 'ميزان المراجعة', ['رقم الحساب', 'اسم الحساب', 'افتتاحي مدين', 'افتتاحي دائن', 'حركة مدين', 'حركة دائن', 'رصيد مدين', 'رصيد دائن'], $rows, ['متوازن: '.($data['balanced'] ? 'نعم' : 'لا')]);
    }

    public function exportBudget(Request $request)
    {
        $data = $this->budget($request)->getData(true);
        $rows = array_map(fn ($a) => [$a['acc_no'], $a['name'], $a['type'], $a['budget'], $a['actual'], $a['variance'], $a['pct'], $a['status']], $data['entries']);
        $rows[] = ['', 'الإجمالي', '', $data['summary']['budget'], $data['summary']['actual'], $data['summary']['variance'], $data['summary']['pct'], ''];

        return $this->downloadWorkbook('الموازنة التقديرية.xlsx', 'الموازنة التقديرية', ['رقم الحساب', 'اسم الحساب', 'النوع', 'الميزانية', 'المنفذ', 'الفرق', 'النسبة %', 'الحالة'], $rows);
    }

    public function exportIncomeStatement(Request $request)
    {
        $data = $this->incomeStatement($request)->getData(true);
        $rows = [['', 'أولاً: الإيرادات', ''], ...array_map(fn ($r) => [$r['acc_no'], $r['name'], $r['amount']], $data['revenues']), ['', 'إجمالي الإيرادات', $data['revenues_total']], ['', 'ثانياً: المصروفات', ''], ...array_map(fn ($r) => [$r['acc_no'], $r['name'], $r['amount']], $data['expenses']), ['', 'إجمالي المصروفات', $data['expenses_total']], ['', 'صافي الدخل / (الخسارة)', $data['net']]];

        return $this->downloadWorkbook('قائمة الدخل.xlsx', 'قائمة الدخل', ['رقم الحساب', 'البيان', 'المبلغ'], $rows);
    }

    public function exportBalanceSheet(Request $request)
    {
        $data = $this->balanceSheet($request)->getData(true);
        $rows = [['', 'أولاً: الموجودات (الأصول)', ''], ...array_map(fn ($r) => [$r['acc_no'], $r['name'], $r['amount']], $data['assets']), ['', 'إجمالي الموجودات', $data['assets_total']], ['', 'ثانياً: الالتزامات وحقوق الملكية', ''], ...array_map(fn ($r) => [$r['acc_no'], $r['name'], $r['amount']], $data['liabilities_equity']), ['', 'إجمالي الالتزامات وحقوق الملكية', $data['total_with_income']]];

        return $this->downloadWorkbook('المركز المالي.xlsx', 'المركز المالي', ['رقم الحساب', 'البيان', 'المبلغ'], $rows);
    }

    public function exportCashFlow(Request $request)
    {
        $data = $this->cashFlow($request)->getData(true);
        $rows = array_map(fn ($r) => [$r['acc_no'], $r['name'], $r['opening'], $r['inflow'], $r['outflow'], $r['closing']], $data['rows']);
        $rows[] = ['', 'الإجمالي', $data['totals']['opening'], $data['totals']['inflow'], $data['totals']['outflow'], $data['totals']['closing']];

        return $this->downloadWorkbook('التدفقات النقدية.xlsx', 'التدفقات النقدية', ['رقم الحساب', 'الحساب', 'رصيد افتتاحي', 'تدفقات داخلة', 'تدفقات خارجة', 'رصيد نهائي'], $rows);
    }

    public function accountsTemplate()
    {
        return $this->downloadWorkbook('قالب دليل حسابات.xlsx', 'دليل حسابات', ['اسم الحساب', 'رقم الحساب'], []);
    }

    public function journalTemplate()
    {
        return $this->downloadWorkbook('قالب دفتر اليومية.xlsx', 'دفتر اليومية', ['رقم الحركة', 'التاريخ', 'مدين', 'دائن', 'اسم الحساب', 'رقم الحساب', 'البيان', 'المنطقة'], []);
    }

    private function downloadWorkbook(string $filename, string $sheetName, array $headers, array $rows, array $subtitles = [])
    {
        $path = tempnam(storage_path('app'), 'xlsx_');
        if ($path === false) {
            throw new \RuntimeException('تعذر إنشاء ملف التصدير المؤقت');
        }
        $writer = new Writer();
        $writer->openToFile($path);
        $sheet = $writer->getCurrentSheet();
        $sheet->setName(mb_substr($sheetName, 0, 31));
        $writer->addRow(Row::fromValues([$this->setting('company_name', 'النظام المحاسبي المتكامل')]));
        $from = $this->setting('period_from');
        $to = $this->setting('period_to');
        if ($from || $to) {
            $writer->addRow(Row::fromValues(['الفترة من '.$from.' إلى '.$to]));
        }
        foreach ($subtitles as $subtitle) {
            $writer->addRow(Row::fromValues([$subtitle]));
        }
        $writer->addRow(Row::fromValues($headers, (new Style())->setFontBold()));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_values($row)));
        }
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['التوقيع: محاسب / '.Auth::user()->name]));
        foreach (json_decode($this->setting('signatures', '[]'), true) ?: [] as $signature) {
            $writer->addRow(Row::fromValues(['التوقيع: '.$signature['name'], $signature['title']]));
        }
        $writer->close();

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function createBackup(): JsonResponse
    {
        $name = 'manual-'.now()->format('Y-m-d_His').'.json';
        $this->storeBackup($name);
        $this->audit(Auth::user()->name, 'نسخة احتياطية يدوية', $name);

        return response()->json(['ok' => true, 'name' => $name]);
    }

    public function backups(): JsonResponse
    {
        $files = collect(Storage::disk('local')->files('backups'))
            ->filter(fn ($file) => preg_match('/^(manual|auto)-[A-Za-z0-9_.-]+\.json$/', basename($file)))
            ->map(fn ($file) => [
                'name' => basename($file),
                'size' => Storage::disk('local')->size($file),
                'modified' => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($file)),
            ])->sortByDesc('modified')->values();

        return response()->json(['backups' => $files]);
    }

    public function downloadBackup(string $name)
    {
        $path = $this->backupStoragePath($name);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $name);
    }

    public function restoreBackup(string $name): JsonResponse
    {
        $path = $this->backupStoragePath($name);
        abort_unless(Storage::disk('local')->exists($path), 404);
        $this->storeBackup('auto-'.now()->format('Y-m-d_His').'.json');
        $contents = Storage::disk('local')->get($path);
        $this->restoreSnapshot($contents);
        $this->audit(Auth::user()->name, 'استعادة نسخة احتياطية', $name);

        return response()->json(['ok' => true, 'message' => 'تمت الاستعادة بنجاح']);
    }

    public function restoreUploadedBackup(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:json,txt', 'max:51200']]);
        $contents = file_get_contents($request->file('file')->getRealPath());
        $this->storeBackup('auto-'.now()->format('Y-m-d_His').'.json');
        $this->restoreSnapshot($contents);
        $this->audit(Auth::user()->name, 'استعادة نسخة من ملف مرفوع', $request->file('file')->getClientOriginalName());

        return response()->json(['ok' => true, 'message' => 'تمت الاستعادة بنجاح']);
    }

    private function backupStoragePath(string $name): string
    {
        abort_unless(preg_match('/^(manual|auto)-[A-Za-z0-9_.-]+\.json$/', $name), 400);

        return 'backups/'.$name;
    }


    private function storeBackup(string $name): void
    {
        $tables = ['users', 'accounts', 'regions', 'journal_entries', 'journal_lines', 'settings', 'audit_log', 'employee_custody_deposits'];
        $snapshot = ['format' => 'smartaccounting-mysql-v1', 'created_at' => now()->toIso8601String(), 'tables' => []];
        foreach ($tables as $table) {
            $snapshot['tables'][$table] = \App\Support\TenantDatabase::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }
        Storage::disk('local')->put('backups/'.$name, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $files = collect(Storage::disk('local')->files('backups'))
            ->sortByDesc(fn ($file) => Storage::disk('local')->lastModified($file))
            ->values();
        foreach ($files->slice(24) as $oldFile) {
            Storage::disk('local')->delete($oldFile);
        }
    }

    private function restoreSnapshot(string $contents): void
    {
        try {
            $snapshot = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw ValidationException::withMessages(['file' => 'ملف النسخة غير صالح أو تالف']);
        }
        if (($snapshot['format'] ?? null) !== 'smartaccounting-mysql-v1' || ! is_array($snapshot['tables'] ?? null)) {
            throw ValidationException::withMessages(['file' => 'تنسيق النسخة غير مدعوم']);
        }
        $tables = ['users', 'accounts', 'regions', 'journal_entries', 'journal_lines', 'settings', 'audit_log', 'employee_custody_deposits'];
        foreach ($tables as $table) {
            if ($table === 'employee_custody_deposits' && ! array_key_exists($table, $snapshot['tables'])) {
                $snapshot['tables'][$table] = [];
            }
            if (! is_array($snapshot['tables'][$table] ?? null)) {
                throw ValidationException::withMessages(['file' => "النسخة لا تحتوي جدول {$table}"]);
            }
        }
        DB::transaction(function () use ($tables, $snapshot) {
            foreach (['employee_custody_deposits', 'journal_lines', 'journal_entries', 'accounts', 'regions', 'settings', 'audit_log', 'users'] as $table) {
                \App\Support\TenantDatabase::table($table)->delete();
            }
            foreach ($tables as $table) {
                foreach (array_chunk($snapshot['tables'][$table], 500) as $chunk) {
                    if ($chunk) {
                        \App\Support\TenantDatabase::table($table)->insert($chunk);
                    }
                }
            }
        });
    }

    public function saveSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'period_from' => ['nullable', 'date_format:Y-m-d'],
            'period_to' => ['nullable', 'date_format:Y-m-d'],
            'signatures' => ['nullable', 'array'],
            'signatures.*.title' => ['required_with:signatures', 'string', 'max:255'],
            'signatures.*.name' => ['required_with:signatures', 'string', 'max:255'],
        ]);
        $settings = [
            'company_name' => trim($data['company_name'] ?? '') ?: 'اسم الشركة',
            'signatures' => json_encode($data['signatures'] ?? [], JSON_UNESCAPED_UNICODE),
        ];
        foreach (['period_from', 'period_to'] as $key) {
            if (! empty($data[$key])) {
                $settings[$key] = $data[$key];
            }
        }
        foreach ($settings as $key => $value) {
            \App\Support\TenantDatabase::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        }
        $this->audit(Auth::user()->name, 'تعديل الإعدادات', $settings['company_name']);

        return response()->json(['ok' => true]);
    }

    private function viewData(array $extra = []): array
    {
        $settings = \App\Support\TenantDatabase::table('settings')->pluck('value', 'key');

        return array_merge([
            'regions' => \App\Support\TenantDatabase::table('regions')->where('is_active', true)->orderBy('id')->pluck('name'),
            'companyName' => $settings->get('company_name', 'النظام المحاسبي المتكامل'),
            'periodFrom' => $settings->get('period_from', now()->startOfYear()->toDateString()),
            'periodTo' => $settings->get('period_to', now()->endOfYear()->toDateString()),
            'signatures' => json_decode($settings->get('signatures', '[]'), true) ?: [],
            'today' => now()->toDateString(),
            'user' => Auth::user(),
            'roleName' => [
                'admin' => 'مدير النظام',
                'accountant' => 'محاسب',
                'viewer' => 'مشاهدة فقط',
                'account_user' => 'موظف حسابات',
            ][Auth::user()->role] ?? '',
        ], $extra);
    }

    private function setting(string $key, string $default = ''): string
    {
        return (string) (\App\Support\TenantDatabase::table('settings')->where('key', $key)->value('value') ?? $default);
    }

    private function audit(string $user, string $action, string $details = ''): void
    {
        \App\Support\TenantDatabase::table('audit_log')->insert(['user' => $user, 'action' => $action, 'details' => $details, 'at' => now()]);
    }

    private function isEmployeeCustodyEntry(int $entryId): bool
    {
        return \App\Support\TenantDatabase::table('employee_custody_deposits')
            ->where('journal_entry_id', $entryId)
            ->exists();
    }

    private function passwordMatches(string $plain, ?string $hash): bool
    {
        if (! $hash || ! in_array(password_get_info($hash)['algoName'], ['bcrypt', 'argon2i', 'argon2id'], true)) {
            return false;
        }

        return Hash::check($plain, $hash);
    }
}
