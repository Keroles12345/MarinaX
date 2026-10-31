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
            app('blade.compiler')->compileString(file_get_contents(resource_path($template)));
            $compiledTemplates++;
        }

        $this->assertSame(count($templates), $compiledTemplates);
    }

    public function test_client_translations_include_dashboard_messages(): void
    {
        $translations = \App\Support\DashboardTemplateTranslator::clientTranslations();

        $this->assertSame('No results', $translations['لا توجد نتائج']);
        $this->assertSame('Hide sidebar', $translations['إخفاء القائمة الجانبية']);
    }

    public function test_dashboard_blade_text_and_javascript_literals_are_translated_at_compile_time(): void
    {
        $template = <<<'BLADE'
@extends('admin.layouts.app')
<section><h2>حفظ الشركة</h2><input placeholder="اسم الشركة"><form onsubmit="return confirm('هل تريد المتابعة؟')"></form><ul>@foreach(['عادي', 'تسوية'] as $type)<li>{{ $type }}</li>@endforeach</ul><script>const empty = 'لا توجد نتائج'; const summary = `لا توجد بيانات: ${count}`;</script></section>
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
        $this->assertStringContainsString('<li>Regular</li><li>Adjustment</li>', $rendered);
    }
}
