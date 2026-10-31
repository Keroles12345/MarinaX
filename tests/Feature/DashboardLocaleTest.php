<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardLocaleTest extends TestCase
{
    public function test_arabic_is_the_default_dashboard_locale(): void
    {
        $this->get('/')->assertOk();

        $this->assertSame('ar', app()->getLocale());
    }

    public function test_locale_selection_is_saved_for_later_requests(): void
    {
        $this->from('/')->post('/locale', ['locale' => 'en'])->assertRedirect('/');

        $this->get('/')->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_only_supported_locales_can_be_selected(): void
    {
        $this->from('/')->post('/locale', ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');
    }

    public function test_dashboard_templates_compile(): void
    {
        $templates = [
            'views/admin/layouts/app.blade.php',
            'views/admin/pages/companies.blade.php',
            'views/admin/pages/accounts.blade.php',
            'views/admin/pages/accounts_tree.blade.php',
            'views/admin/pages/dashboard.blade.php',
            'views/staff/main.blade.php',
            'views/staff/layout/head.blade.php',
            'views/staff/layout/navbar.blade.php',
            'views/staff/layout/sidebar.blade.php',
            'views/staff/index.blade.php',
            'views/staff/operation-assignments/index.blade.php',
            'views/superadmin/layout.blade.php',
            'views/superadmin/tenants/index.blade.php',
            'views/shared/dashboard-translations.blade.php',
            'views/shared/language-switcher.blade.php',
        ];

        $compiledTemplates = 0;
        foreach ($templates as $template) {
            $compiled = app('blade.compiler')->compileString(file_get_contents(resource_path($template)));
            token_get_all($compiled, TOKEN_PARSE);
            $compiledTemplates++;
        }

        $this->assertSame(count($templates), $compiledTemplates);
    }

    public function test_account_tree_template_renders_without_blade_parsing_javascript_template_literals(): void
    {
        $template = \App\Support\DashboardTemplateTranslator::compile(
            file_get_contents(resource_path('views/admin/pages/accounts_tree.blade.php'))
        );
        $template = preg_replace('/^@extends\([\'"]admin\.layouts\.app[\'"]\)\s*/', '', $template);
        $compiled = app('blade.compiler')->compileString($template);

        $user = (object) ['role' => 'viewer'];
        $__env = app('view');
        app()->setLocale('en');
        ob_start();
        eval('?>'.$compiled);
        $rendered = ob_get_clean().$__env->yieldContent('scripts');
        app()->setLocale('ar');

        $this->assertStringContainsString('onclick="toggleNext(this)"', $rendered);
        $this->assertStringContainsString('CAN_MANAGE_ACCOUNTS = false', $rendered);
        $this->assertStringContainsString('Opening credit', $rendered);
        $this->assertStringContainsString('Opening debit', $rendered);
        $this->assertStringContainsString('Balanced', $rendered);
        $this->assertStringContainsString('treeText(count === 1 ? "account" : "accounts")', $rendered);
    }

    public function test_blade_expression_string_literals_are_translated_without_nested_blade_echoes(): void
    {
        $template = <<<'BLADE'
@extends('superadmin.layout')
<span>{{ $tenant->status ? 'نشطة' : 'موقوفة' }}</span>
BLADE;
        $template = \App\Support\DashboardTemplateTranslator::compile($template);
        $template = preg_replace('/^@extends\([\'"]superadmin\.layout[\'"]\)\s*/', '', $template);
        $compiled = app('blade.compiler')->compileString($template);

        app()->setLocale('en');
        $__env = app('view');
        $tenant = (object) ['status' => true];
        ob_start();
        eval('?>'.$compiled);
        $active = ob_get_clean();

        $tenant->status = false;
        ob_start();
        eval('?>'.$compiled);
        $suspended = ob_get_clean();
        app()->setLocale('ar');

        $this->assertSame('<span>Active</span>', $active);
        $this->assertSame('<span>Suspended</span>', $suspended);
    }

    public function test_client_translations_include_dashboard_messages(): void
    {
        $translations = \App\Support\DashboardTemplateTranslator::clientTranslations();

        $this->assertSame('No results', $translations['لا توجد نتائج']);
        $this->assertSame('Hide sidebar', $translations['إخفاء القائمة الجانبية']);
        $this->assertSame('Operating revenue', $translations['إيرادات التشغيل']);
        $this->assertSame('Long-term liabilities', $translations['خصوم طويلة الأجل']);
        $this->assertSame('accounts', $translations['حسابات']);
        $this->assertSame('Edit account details', $translations['تعديل تفاصيل الشركة']);
        $this->assertSame('Unable to load account tree data', $translations['تعذر تحميل بيانات شجرة الحسابات']);
    }

    public function test_dashboard_blade_text_and_javascript_literals_are_translated_at_compile_time(): void
    {
        $template = <<<'BLADE'
@extends('admin.layouts.app')
<section><h2>حفظ الشركة</h2><input placeholder="اسم الشركة"><form onsubmit="return confirm('هل تريد المتابعة؟')"></form><ul>@foreach(['عادي', 'تسوية'] as $type)<li>{{ $type }}</li>@endforeach</ul><script>const empty = 'لا توجد نتائج'; const summary = `لا توجد بيانات: ${count}`; const nested = `<div>${items.map(item => `<span>لا توجد نتائج: ${item.name}</span>`).join('')}</div>`;</script></section>
BLADE;

        $translatedTemplate = \App\Support\DashboardTemplateTranslator::compile($template);
        $translatedTemplate = preg_replace('/^@extends\(\'admin\.layouts\.app\'\)/', '', $translatedTemplate);
        $compiled = app('blade.compiler')->compileString($translatedTemplate);

        $this->assertStringContainsString('DashboardTemplateTranslator::translate', $compiled);
        $this->assertStringContainsString('حفظ الشركة', $compiled);
        $this->assertStringContainsString('لا توجد نتائج', $compiled);
        app()->setLocale('en');
        $this->assertSame('Save company', \App\Support\DashboardTemplateTranslator::translate('حفظ الشركة'));
        app()->setLocale('ar');
        $this->assertSame('حفظ الشركة', \App\Support\DashboardTemplateTranslator::translate('حفظ الشركة'));
        app()->setLocale('en');

        $__env = app('view');
        ob_start();
        eval('?>'.$compiled);
        $rendered = ob_get_clean();

        $this->assertStringContainsString('Save company', $rendered);
        $this->assertStringContainsString('placeholder="Company name"', $rendered);
        $this->assertStringContainsString('No results', $rendered);
        $this->assertStringContainsString('confirm(\'Do you want to continue?\')', $rendered);
        $this->assertStringContainsString('No data available:', $rendered);
        $this->assertStringContainsString('${"\u003Cspan\u003ENo results: "}${item.name}</span>', $rendered);
        $this->assertStringContainsString('<li>Regular</li><li>Adjustment</li>', $rendered);
    }
}
