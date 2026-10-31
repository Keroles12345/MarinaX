@php
   

    $footT = [
        'en' => [
            'brand' => 'Hurghada Speed Trip',
            'tagline' => 'Redefining luxury maritime experiences since 2018. We connect you with the world\'s most beautiful coastlines.',
            'quick_links' => 'Quick Links',
            'our_fleet' => 'Our Fleet',
            'featured_dest' => 'Featured Destinations',
            'special_offers' => 'Special Offers',
            'partner_with_us'=> 'Partner With Us',
            'support' => 'Support',
            'help_center' => 'Help Center',
            'safety_info' => 'Safety Information',
            'cancel_policy' => 'Cancellation Policy',
            'privacy_policy' => 'Privacy Policy',
            'newsletter' => 'Newsletter',
            'newsletter_txt' => 'Get travel inspiration and exclusive deals.',
            'email_ph' => 'Email',
            'join' => 'Join',
            'copyright' => '© 2024 Hurghada Speed Trip Charters. All rights reserved.',
        ],
        'ar' => [
            'brand' => 'هرمجة سبيد تريب',
            'tagline' => 'نعيد تعريف رحلات البحر الفاخرة منذ 2018، ونصل بك لأجمل السواحل في العالم.',
            'quick_links' => 'روابط سريعة',
            'our_fleet' => 'أسطولنا',
            'featured_dest' => 'وجهات مميزة',
            'special_offers' => 'عروض خاصة',
            'partner_with_us'=> 'كن شريكاً لنا',
            'support' => 'الدعم',
            'help_center' => 'مركز المساعدة',
            'safety_info' => 'معلومات السلامة',
            'cancel_policy' => 'سياسة الإلغاء',
            'privacy_policy' => 'سياسة الخصوصية',
            'newsletter' => 'النشرة البريدية',
            'newsletter_txt' => 'احصل على إلهام لرحلاتك وعروض حصرية.',
            'email_ph' => 'البريد الإلكتروني',
            'join' => 'اشترك',
            'copyright' => '© 2024 هرمجة سبيد تريب. جميع الحقوق محفوظة.',
        ],
    ];

    $footT = $footT[$lang] ?? $footT['en'];
@endphp

<footer class="hidden md:block bg-ocean-deep text-slate-300 pt-16 pb-10 border-t border-white/5">

<div class="mx-auto max-w-7xl px-6 lg:px-12">

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">

        {{-- BRAND + TAGLINE --}}
        <div>
            <div class="flex items-center gap-2 mb-5">
                <div class="flex size-8 items-center justify-center rounded bg-primary text-white">
                    <x-icon name="sailing" class="w-5 h-5" />
                </div>
                <h2 class="text-xl font-black text-white uppercase">
                    {{ $footT['brand'] }}
                </h2>
            </div>

            <p class="text-sm leading-relaxed mb-6">
                {{ $footT['tagline'] }}
            </p>
        </div>

        {{-- QUICK LINKS (ACCORDION MOBILE) --}}
        <div x-data="{ open: false }" class="md:block">

            <h4 @click="open = !open"
                class="text-white font-bold mb-4 flex justify-between items-center cursor-pointer md:cursor-default">

                {{ $footT['quick_links'] }}

                <span class="md:hidden" x-text="open ? '−' : '+'"></span>
            </h4>

            <ul
                class="space-y-3 text-sm md:block"
                x-show="open"
                x-transition
                :class="open ? 'block' : 'hidden md:block'">

                <li><a class="hover:text-primary" href="#">{{ $footT['our_fleet'] }}</a></li>
                <li><a class="hover:text-primary" href="#">{{ $footT['featured_dest'] }}</a></li>
                <li><a class="hover:text-primary" href="#">{{ $footT['special_offers'] }}</a></li>
                <li><a class="hover:text-primary" href="#">{{ $footT['partner_with_us'] }}</a></li>
            </ul>
        </div>

        {{-- SUPPORT --}}
        <div x-data="{ open: false }">

            <h4 @click="open = !open"
                class="text-white font-bold mb-4 flex justify-between items-center cursor-pointer md:cursor-default">

                {{ $footT['support'] }}

                <span class="md:hidden" x-text="open ? '−' : '+'"></span>
            </h4>

            <ul
                class="space-y-3 text-sm md:block"
                x-show="open"
                x-transition
                :class="open ? 'block' : 'hidden md:block'">

                <li><a class="hover:text-primary" href="#">{{ $footT['help_center'] }}</a></li>
                <li><a class="hover:text-primary" href="#">{{ $footT['safety_info'] }}</a></li>
                <li><a class="hover:text-primary" href="#">{{ $footT['cancel_policy'] }}</a></li>
                <li><a class="hover:text-primary" href="#">{{ $footT['privacy_policy'] }}</a></li>
            </ul>
        </div>

        {{-- NEWSLETTER --}}
        <div>
            <h4 class="text-white font-bold mb-4">
                {{ $footT['newsletter'] }}
            </h4>

            <p class="text-sm mb-4">
                {{ $footT['newsletter_txt'] }}
            </p>

            <div class="flex flex-col sm:flex-row gap-2">
                <input
                    type="email"
                    placeholder="{{ $footT['email_ph'] }}"
                    class="bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-sm w-full focus:ring-primary"
                />
                <button class="bg-primary px-4 py-2 rounded-lg text-white font-bold">
                    {{ $footT['join'] }}
                </button>
            </div>
        </div>

    </div>

    {{-- COPYRIGHT --}}
    <div class="pt-8 mt-10 border-t border-white/5 text-center text-xs text-slate-500">
        {{ $footT['copyright'] }}
    </div>

</div>
</footer>

<!-- MOBILE FOOTER -->
<footer class="block md:hidden bg-ocean-deep text-slate-300 pt-3 pb-6 rounded-t-xl mt-10 shadow-lg border-t border-white/5">

    <div class="border-t border-white/5 mb-8 mx-0">
    </div>
    <div class="flex justify-center items-center gap-6 mb-8">
        {{-- Facebook --}}
        <svg class="w-8 h-8 fill-current hover:text-primary transition-colors cursor-pointer" viewBox="0 0 24 24"><path d="M14 13.5h2.5l1-4H14v-2c0-1.03 0-2 2-2h1.5V2.14c-.326-.043-1.557-.14-2.857-.14C11.928 2 10 3.657 10 6.7v2.8H7.5v4H10v9h4v-9z"/></svg>
        {{-- WhatsApp --}}
<svg class="w-8 h-8 fill-current  hover:text-primary transition-colors cursor-pointer" viewBox="0 0 24 24">
    <path d="M20.52 3.48A11.8 11.8 0 0 0 12.06 0C5.41 0 .02 5.39.02 12.04c0 2.12.55 4.19 1.6 6.02L0 24l6.12-1.6a11.95 11.95 0 0 0 5.94 1.52h.01c6.65 0 12.04-5.39 12.04-12.04 0-3.2-1.25-6.21-3.59-8.45ZM12.07 21.8h-.01a9.7 9.7 0 0 1-4.95-1.35l-.35-.2-3.63.95.97-3.54-.23-.36a9.72 9.72 0 0 1-1.5-5.23C2.37 6.03 6.54 1.86 12.06 1.86c2.6 0 5.04 1.01 6.88 2.85a9.65 9.65 0 0 1 2.86 6.88c0 5.52-4.17 9.7-9.73 9.7Zm5.65-7.45c-.31-.16-1.83-.9-2.12-1.01-.29-.1-.5-.16-.71.16-.21.31-.81 1.01-.99 1.22-.18.21-.37.24-.68.08-.31-.16-1.3-.48-2.48-1.53-.92-.82-1.54-1.83-1.72-2.14-.18-.31-.02-.48.14-.64.14-.14.31-.37.47-.56.16-.18.21-.31.31-.52.1-.21.05-.39-.03-.55-.08-.16-.71-1.71-.97-2.34-.25-.6-.51-.52-.71-.53h-.6c-.21 0-.55.08-.84.39-.29.31-1.1 1.08-1.1 2.63s1.13 3.05 1.29 3.26c.16.21 2.23 3.41 5.4 4.78.75.32 1.34.51 1.8.65.76.24 1.45.21 2 .13.61-.09 1.83-.75 2.09-1.47.26-.72.26-1.34.18-1.47-.08-.13-.29-.21-.6-.37Z"/>
</svg>
        {{-- YouTube --}}
        <svg class="w-9 h-9 fill-current hover:text-primary transition-colors cursor-pointer" viewBox="0 0 24 24"><path d="M21.582 6.186a2.6 2.6 0 0 0-1.838-1.844C18.127 3.9 12 3.9 12 3.9s-6.127 0-7.744.442a2.6 2.6 0 0 0-1.838 1.844C2 7.818 2 12 2 12s0 4.182.418 5.814a2.6 2.6 0 0 0 1.838 1.844C5.873 20.1 12 20.1 12 20.1s6.127 0 7.744-.442a2.6 2.6 0 0 0 1.838-1.844C22 16.182 22 12 22 12s0-4.182-.418-5.814zM9.8 15.5v-7l6.5 3.5-6.5 3.5z"/></svg>
        {{-- LinkedIn --}}
        <svg class="w-7 h-7 fill-current hover:text-primary transition-colors cursor-pointer" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
    </div>
    <div class="text-center text-[15px] font-medium leading-[1.8] px-4">
        <div>Debitis / laborum &nbsp;&nbsp;|&nbsp;&nbsp; Lorem &amp; cupiditate &nbsp;&nbsp;|</div>
        <div>Beguiled</div>
    </div>
    <div class="text-center text-[15px] font-medium mt-10 ">
        Deserunt Popular
    </div>
    <div class="text-center text-xs text-slate-500 mt-6 px-4">
    © 2024 Hurghada Speed Trip Charters. All rights reserved.
</div>
</footer>
