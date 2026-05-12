<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="بروشور فارسی SkyBase برای نهادهای دولتی، دانشگاه‌های علوم پزشکی، بیمارستان‌ها و سازمان‌های چندواحدی.">
    <title>بروشور SkyBase برای نهادهای دولتی</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
            @font-face {
                font-family: 'Pelak';
                src: url('{{ asset('assets/fonts/PelakFA.woff2') }}') format('woff2'),
                     url('{{ asset('assets/fonts/PelakFA.woff2') }}') format('woff');
                font-weight: 400;
                font-style: normal;
                font-display: swap;
            }
            
            @font-face {
                font-family: 'Pelak';
                src: url('{{ asset('assets/fonts/PelakFA.woff2') }}') format('woff2'),
                     url('{{ asset('assets/fonts/PelakFA.woff2') }}') format('woff');
                font-weight: 500;
                font-style: normal;
                font-display: swap;
            }
            
            @font-face {
                font-family: 'Pelak';
                src: url('{{ asset('assets/fonts/PelakFA.woff2') }}') format('woff2'),
                     url('{{ asset('assets/fonts/PelakFA.woff2') }}') format('woff');
                font-weight: 600;
                font-style: normal;
                font-display: swap;
            }
            
            @font-face {
                font-family: 'Pelak';
                src: url('{{ asset('assets/fonts/PelakFA.woff2') }}') format('woff2'),
                     url('{{ asset('assets/fonts/PelakFA.woff2') }}') format('woff');
                font-weight: 700;
                font-style: normal;
                font-display: swap;
            }
        @page {
            size: A4;
            margin: 12mm;
        }

        @media print {
            html,
            body,
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            html,
            body {
                background: #ffffff !important;
            }

            body {
                font-size: 12px !important;
                line-height: 1.45 !important;
            }

            .print-hidden {
                display: none !important;
            }

            .sheet {
                min-height: 273mm;
                break-after: page;
                box-shadow: none !important;
                margin: 0 !important;
                border-radius: 0 !important;
                padding: 7mm !important;
                overflow: hidden !important;
                page-break-inside: avoid !important;
                break-inside: avoid-page !important;
            }

            .sheet:last-child {
                break-after: auto;
            }

            .sheet h1 {
                font-size: 32px !important;
                line-height: 1.3 !important;
            }

            .sheet h2 {
                font-size: 28px !important;
                line-height: 1.35 !important;
            }

            .sheet h3 {
                font-size: 20px !important;
                line-height: 1.4 !important;
            }

            .sheet p,
            .sheet li,
            .sheet td,
            .sheet th,
            .sheet span {
                line-height: 1.45 !important;
            }

            .sheet .text-5xl {
                font-size: 32px !important;
            }

            .sheet .text-4xl {
                font-size: 28px !important;
            }

            .sheet .text-3xl {
                font-size: 22px !important;
            }

            .sheet .text-2xl {
                font-size: 18px !important;
            }

            .sheet .text-xl {
                font-size: 16px !important;
            }

            .sheet .text-lg {
                font-size: 15px !important;
            }

            .sheet .mt-20 {
                margin-top: 2.5rem !important;
            }

            .sheet .mt-10 {
                margin-top: 1.5rem !important;
            }

            .sheet .mt-8 {
                margin-top: 1.2rem !important;
            }

            .sheet .mt-6 {
                margin-top: 1rem !important;
            }

            .sheet .p-10 {
                padding: 7mm !important;
            }

            .sheet .p-8 {
                padding: 6mm !important;
            }

            .sheet .p-7 {
                padding: 5mm !important;
            }

            .sheet .p-6 {
                padding: 4mm !important;
            }

            .sheet .p-5 {
                padding: 3.5mm !important;
            }

            .sheet table,
            .sheet tr,
            .sheet td,
            .sheet th,
            .sheet article,
            .sheet section,
            .sheet div {
                page-break-inside: avoid !important;
                break-inside: avoid-page !important;
            }
        }

        body {
            font-family: 'Pelak', Arial, sans-serif;
        }
    </style>
</head>
<body class="bg-slate-200 text-slate-950 antialiased">
    <div class="print-hidden sticky top-0 z-50 border-b border-slate-900/10 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
            <div class="flex items-center gap-3">
                <img src="{{ asset('assets/images/logo/logo-black.png') }}" alt="SkyBase" class="h-9 w-9 rounded-lg bg-[#0d2f35] p-1.5">
                <div>
                    <p class="text-sm font-bold text-slate-950">بروشور دولتی SkyBase</p>
                    <p class="text-xs text-slate-500">نسخه HTML آماده چاپ A4</p>
                </div>
            </div>
            <button type="button" onclick="window.print()" class="rounded-lg bg-[#0d2f35] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#16474f]">
                چاپ یا ذخیره PDF
            </button>
        </div>
    </div>

    <main class="mx-auto my-6 max-w-[210mm] space-y-6 print:my-0 print:space-y-0">
        <section class="sheet relative flex min-h-[297mm] flex-col overflow-hidden rounded-[1.5rem] bg-[#0d2f35] p-10 text-white shadow-2xl print:p-8">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_18%_15%,rgba(245,197,66,0.28),transparent_28%),radial-gradient(circle_at_88%_18%,rgba(20,184,166,0.28),transparent_30%),linear-gradient(145deg,#071f25_0%,#0d2f35_45%,#123f3d_100%)]"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex items-center justify-center rounded p-4 bg-white shadow-xl">
                        <img src="{{ asset('assets/images/logo/logo-black.png') }}" alt="SkyBase" class="h-11 w-auto">
                    </span>
                    {{-- <div>
                        <p class="text-3xl font-black tracking-tight">SkyBase</p>
                        <p class="mt-1 text-sm font-bold text-[#f5c542]">Government Cloud Operations</p>
                    </div> --}}
                </div>
                <p class="rounded-full border border-white/20 bg-white/10 px-5 py-2 text-sm font-bold text-teal-50">بروشور معرفی محصول</p>
            </div>

            <div class="relative z-10 mt-20 max-w-3xl">
                <h1 class="text-5xl font-black leading-[1.35] tracking-tight">
                    سکوی یکپارچه SkyBase برای مدیریت زیرساخت، دسترسی، صورتحساب و حکمرانی سازمان‌های دولتی
                </h1>
                <p class="mt-8 text-xl leading-10 text-teal-50/85">
                    SkyBase برای نهادهای دولتی، دانشگاه‌های علوم پزشکی، بیمارستان‌ها، شهرداری‌ها و سازمان‌های چندواحدی طراحی شده است تا مدیریت کاربران، سرویس‌ها، شبکه، حسابداری و مجوزهای دسترسی در یک محیط ابری امن و قابل ممیزی انجام شود.
                </p>
            </div>

            <div class="relative z-10 mt-auto grid grid-cols-3 gap-4">
                <div class="rounded-2xl border border-white/15 bg-white/10 p-5">
                    <p class="text-sm text-teal-50/70">مدل استقرار</p>
                    <p class="mt-3 text-2xl font-black">جداسازی امن داده‌ها</p>
                    <p class="mt-2 text-sm leading-6 text-teal-50/75">جداسازی داده‌ها و عملیات هر سازمان یا واحد اجرایی.</p>
                </div>
                <div class="rounded-2xl border border-white/15 bg-white/10 p-5">
                    <p class="text-sm text-teal-50/70">تمرکز مدیریتی</p>
                    <p class="mt-3 text-2xl font-black">حسابداری و دسترسی</p>
                    <p class="mt-2 text-sm leading-6 text-teal-50/75">صورتحساب، پرداخت، نقش‌ها، مجوزها و گزارش‌ها.</p>
                </div>
                <div class="rounded-2xl border border-white/15 bg-[#f5c542] p-5 text-slate-950">
                    <p class="text-sm text-slate-700">کاربران هدف</p>
                    <p class="mt-3 text-2xl font-black">دولت، سلامت، آموزش</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">مناسب ساختارهای ستادی و زیرمجموعه‌های متعدد.</p>
                </div>
            </div>
        </section>

        <section class="sheet min-h-[297mm] rounded-[1.5rem] bg-white p-10 shadow-2xl print:p-8">
            <div class="flex items-start justify-between gap-8 border-b border-slate-200 pb-8">
                <div>
                    <p class="text-sm font-black text-teal-700">نمای کلی پروژه</p>
                    <h2 class="mt-3 text-4xl font-black leading-tight">SkyBase چه مسئله‌ای را برای نهادهای دولتی حل می‌کند؟</h2>
                </div>
                <div class="w-52 rounded-2xl bg-[#0d2f35] p-5 text-white">
                    <p class="text-sm text-teal-50/70">مدیریت متمرکز</p>
                    <p class="mt-2 text-3xl font-black">یک پنل</p>
                    <p class="mt-2 text-sm leading-6 text-teal-50/80">برای مشتریان، کاربران، شبکه، مالی و گزارش‌ها.</p>
                </div>
            </div>

            <div class="mt-10 grid grid-cols-2 gap-6">
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <h3 class="text-xl font-black text-slate-950">جداسازی داده‌های سازمانی</h3>
                    <p class="mt-4 text-base leading-8 text-slate-700">هر سازمان، دانشگاه، بیمارستان یا واحد اجرایی می‌تواند داده‌ها، کاربران، سرویس‌ها و گزارش‌های خود را در محدوده امن tenant اختصاصی مدیریت کند.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <h3 class="text-xl font-black text-slate-950">عملیات روزانه بدون پراکندگی ابزارها</h3>
                    <p class="mt-4 text-base leading-8 text-slate-700">CRM، اشتراک‌ها، روترها، IPAM، VPN، مصرف، پهنای باند، صورتحساب، پرداخت و گزارش مالی در یک تجربه واحد ارائه می‌شوند.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <h3 class="text-xl font-black text-slate-950">ممیزی و پاسخ‌گویی</h3>
                    <p class="mt-4 text-base leading-8 text-slate-700">تغییرات مهم کاربران، مشتریان، اشتراک‌ها و فاکتورها با تاریخچه فعالیت، کاربر انجام‌دهنده و جزئیات تغییر قابل پیگیری است.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <h3 class="text-xl font-black text-slate-950">مقیاس‌پذیری برای ساختارهای بزرگ</h3>
                    <p class="mt-4 text-base leading-8 text-slate-700">ساختارهای چندبخشی مانند دانشگاه علوم پزشکی با بیمارستان‌ها، مراکز بهداشت و واحدهای ستادی می‌توانند نقش‌ها و دسترسی‌ها را به شکل قابل کنترل تعریف کنند.</p>
                </article>
            </div>

            <div class="mt-10 rounded-3xl bg-[#f6f1e8] p-7">
                <h3 class="text-2xl font-black">نمونه سناریو: دانشگاه علوم پزشکی و بیمارستان‌های زیرمجموعه</h3>
                <div class="mt-6 grid grid-cols-[1fr_auto_1fr_auto_1fr] items-center gap-4 text-center">
                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <p class="font-black">ستاد دانشگاه</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">سیاست‌های مرکزی، گزارش کلان و حسابرسی</p>
                    </div>
                    <span class="text-2xl font-black text-teal-700">←</span>
                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <p class="font-black">گروه‌ها و واحدها</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">مالی، IT، NOC، پشتیبانی و مدیران بیمارستان</p>
                    </div>
                    <span class="text-2xl font-black text-teal-700">←</span>
                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <p class="font-black">بیمارستان‌ها</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">کاربران، دسترسی، شبکه و مصرف هر مرکز</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="sheet min-h-[297mm] rounded-[1.5rem] bg-white p-10 shadow-2xl print:p-8">
            <p class="text-sm font-black text-teal-700">صورت‌حساب و حسابداری</p>
            <h2 class="mt-3 text-4xl font-black leading-tight">صورتحساب، حسابداری و کنترل چرخه درآمد</h2>
            <p class="mt-5 max-w-3xl text-lg leading-9 text-slate-700">
                SkyBase جریان مالی سرویس‌ها را از تعریف پلن و اشتراک تا تولید فاکتور، ثبت پرداخت، اعتبار مشتری، پیگیری بدهی و گزارش مالی پوشش می‌دهد.
            </p>

            <div class="mt-10 grid grid-cols-3 gap-4">
                <div class="rounded-2xl bg-[#0d2f35] p-6 text-white">
                    <p class="text-sm text-teal-50/70">فاکتورهای دوره‌ای</p>
                    <p class="mt-3 text-3xl font-black">Recurring</p>
                    <p class="mt-3 text-sm leading-7 text-teal-50/80">تولید فاکتور بر اساس دوره ماهانه، فصلی یا سالانه اشتراک‌ها.</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-6 text-slate-950 ring-1 ring-emerald-100">
                    <p class="text-sm text-emerald-700">وضعیت مالی</p>
                    <p class="mt-3 text-3xl font-black">Paid / Overdue</p>
                    <p class="mt-3 text-sm leading-7 text-slate-700">مانده بدهی، فاکتورهای پرداخت‌شده، معوق و نیمه‌پرداخت‌شده.</p>
                </div>
                <div class="rounded-2xl bg-amber-50 p-6 text-slate-950 ring-1 ring-amber-100">
                    <p class="text-sm text-amber-700">کنترل سرویس</p>
                    <p class="mt-3 text-3xl font-black">Grace Days</p>
                    <p class="mt-3 text-sm leading-7 text-slate-700">مهلت پرداخت و تعلیق خودکار سرویس‌های دارای بدهی معوق.</p>
                </div>
            </div>

            <div class="mt-10 overflow-hidden rounded-3xl border border-slate-200">
                <table class="w-full border-collapse text-right text-sm">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="px-5 py-4 font-black">قابلیت</th>
                            <th class="px-5 py-4 font-black">کاربرد دولتی</th>
                            <th class="px-5 py-4 font-black">خروجی مدیریتی</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr>
                            <td class="px-5 py-4 font-bold">پلن‌ها و اشتراک‌ها</td>
                            <td class="px-5 py-4 leading-7 text-slate-700">تعریف بسته خدمات برای واحدها، ساختمان‌ها یا مراکز درمانی</td>
                            <td class="px-5 py-4 leading-7 text-slate-700">ارزش اشتراک فعال و درآمد دوره‌ای</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-4 font-bold">فاکتور و آیتم فاکتور</td>
                            <td class="px-5 py-4 leading-7 text-slate-700">ثبت ریزخدمات، مالیات، تخفیف و هزینه‌های دوره‌ای</td>
                            <td class="px-5 py-4 leading-7 text-slate-700">گزارش دقیق بدهی و وصولی</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-4 font-bold">پرداخت و اعتبار مشتری</td>
                            <td class="px-5 py-4 leading-7 text-slate-700">ثبت پرداخت نقدی، انتقال بانکی یا اعتبار اصلاحی</td>
                            <td class="px-5 py-4 leading-7 text-slate-700">مانده حساب و سابقه تراکنش</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-4 font-bold">گزارش مالی</td>
                            <td class="px-5 py-4 leading-7 text-slate-700">تحلیل درآمد، مطالبات، معوقات و مشتریان برتر</td>
                            <td class="px-5 py-4 leading-7 text-slate-700">داشبورد تصمیم‌گیری برای مدیران مالی</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="sheet min-h-[297mm] rounded-[1.5rem] bg-white p-10 shadow-2xl print:p-8">
            <p class="text-sm font-black text-teal-700">Authentication & Authorization</p>
            <h2 class="mt-3 text-4xl font-black leading-tight">احراز هویت، مجوزدهی و کنترل دسترسی سازمانی</h2>
            <p class="mt-5 max-w-3xl text-lg leading-9 text-slate-700">
                SkyBase دسترسی کاربران را به tenant سازمانی، وضعیت سازمان، نقش کاربر و مجوزهای تعریف‌شده متصل می‌کند تا هر کاربر فقط به محدوده مجاز خود دسترسی داشته باشد.
            </p>

            <div class="mt-10 grid grid-cols-2 gap-6">
                <div class="rounded-3xl bg-slate-950 p-7 text-white">
                    <h3 class="text-2xl font-black">امنیت ورود و نشست</h3>
                    <ul class="mt-5 space-y-3 text-sm leading-7 text-slate-200">
                        <li>ورود کاربران سازمانی با کنترل وضعیت tenant</li>
                        <li>جلوگیری از دسترسی سازمان تعلیق‌شده</li>
                        <li>ثبت آخرین ورود و تفکیک کاربر از tenant</li>
                        <li>خروج امن و بازتولید session token</li>
                    </ul>
                </div>
                <div class="rounded-3xl bg-[#f6f1e8] p-7">
                    <h3 class="text-2xl font-black">نقش‌ها و سطح دسترسی</h3>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        @foreach(['Owner', 'Admin', 'Billing', 'Support', 'NOC'] as $role)
                            <div class="rounded-2xl bg-white px-4 py-3 text-center text-sm font-black shadow-sm">{{ $role }}</div>
                        @endforeach
                    </div>
                    <p class="mt-5 text-sm leading-7 text-slate-700">مجوزها می‌توانند برای مشاهده مشتری، مدیریت مالی، روترها، گزارش‌ها و کاربران اعمال شوند.</p>
                </div>
            </div>

            <div class="mt-10 rounded-3xl border border-teal-100 bg-teal-50 p-7">
                <h3 class="text-2xl font-black">همگام‌سازی Active Directory و مدیریت گروه‌ها</h3>
                <p class="mt-4 text-base leading-8 text-slate-700">
                    همگام‌سازی Active Directory به سازمان اجازه می‌دهد کاربران، گروه‌ها و واحدهای سازمانی موجود را به SkyBase متصل کند. سپس مدیریت گروه‌ها برای ساختارهایی مانند «دانشگاه علوم پزشکی ← بیمارستان‌ها ← واحدهای IT، مالی، حراست و پشتیبانی» به نقش‌ها و دسترسی‌های قابل اجرا تبدیل می‌شود.
                </p>
                <div class="mt-6 grid grid-cols-4 gap-3 text-center">
                    <div class="rounded-2xl bg-white p-4 shadow-sm">
                        <p class="text-sm font-black">Directory Sync</p>
                        <p class="mt-2 text-xs leading-5 text-slate-600">همگام‌سازی کاربران و گروه‌ها</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 shadow-sm">
                        <p class="text-sm font-black">Group Mapping</p>
                        <p class="mt-2 text-xs leading-5 text-slate-600">تبدیل گروه به نقش و مجوز</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 shadow-sm">
                        <p class="text-sm font-black">Hospital Units</p>
                        <p class="mt-2 text-xs leading-5 text-slate-600">تفکیک دسترسی مراکز زیرمجموعه</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 shadow-sm">
                        <p class="text-sm font-black">Audit Trail</p>
                        <p class="mt-2 text-xs leading-5 text-slate-600">ردیابی تغییرات دسترسی</p>
                    </div>
                </div>
            </div>

            <div class="mt-10 rounded-3xl border border-slate-200 p-7">
                <h3 class="text-2xl font-black">مدل پیشنهادی مجوزدهی برای نهادهای دولتی</h3>
                <div class="mt-6 grid grid-cols-3 gap-4">
                    <div class="rounded-2xl bg-slate-50 p-5">
                        <p class="font-black">ستاد مرکزی</p>
                        <p class="mt-2 text-sm leading-7 text-slate-700">مشاهده گزارش کل، مدیریت سیاست‌ها، تعریف گروه‌ها و ممیزی.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-5">
                        <p class="font-black">واحد مالی</p>
                        <p class="mt-2 text-sm leading-7 text-slate-700">مدیریت صورتحساب، پرداخت، اعتبار، مطالبات و گزارش مالی.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-5">
                        <p class="font-black">واحد فنی و NOC</p>
                        <p class="mt-2 text-sm leading-7 text-slate-700">مدیریت روتر، VPN، IPAM، مصرف، پهنای باند و هشدارها.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="sheet min-h-[297mm] rounded-[1.5rem] bg-white p-10 shadow-2xl print:p-8">
            <p class="text-sm font-black text-teal-700">Network & Service Operations</p>
            <h2 class="mt-3 text-4xl font-black leading-tight">عملیات شبکه، سرویس و گزارش‌گیری</h2>
            <p class="mt-5 max-w-3xl text-lg leading-9 text-slate-700">
                SkyBase برای سازمان‌هایی که سرویس‌های داخلی، ارتباطات شعب، دسترسی VPN یا زیرساخت مبتنی بر روتر دارند، دید عملیاتی یکپارچه فراهم می‌کند.
            </p>

            <div class="mt-10 grid grid-cols-2 gap-6">
                <div class="rounded-3xl border border-slate-200 p-7">
                    <h3 class="text-2xl font-black">مدیریت روتر و وضعیت شبکه</h3>
                    <div class="mt-6 space-y-3">
                        @foreach(['وضعیت online/offline روترها', 'مصرف CPU و حافظه', 'تعداد نشست‌های فعال', 'هشدارهای شبکه و وضعیت سلامت'] as $item)
                            <div class="rounded-2xl bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700">{{ $item }}</div>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-3xl border border-slate-200 p-7">
                    <h3 class="text-2xl font-black">IPAM و مدیریت آدرس‌ها</h3>
                    <div class="mt-6 space-y-3">
                        @foreach(['تعریف IP Pool بر اساس سایت و روتر', 'اختصاص خودکار IP به اشتراک', 'رزرو، آزادسازی و مسدودسازی IP', 'هشدار ظرفیت و poolهای نزدیک به تکمیل'] as $item)
                            <div class="rounded-2xl bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700">{{ $item }}</div>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-3xl border border-slate-200 p-7">
                    <h3 class="text-2xl font-black">VPN و دسترسی امن</h3>
                    <div class="mt-6 space-y-3">
                        @foreach(['کاربران VPN با وضعیت فعال/غیرفعال', 'نمایش online/offline و زمان اتصال', 'IP داخلی VPN و IP واقعی', 'حجم ارسال و دریافت هر کاربر'] as $item)
                            <div class="rounded-2xl bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700">{{ $item }}</div>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-3xl border border-slate-200 p-7">
                    <h3 class="text-2xl font-black">مصرف و گزارش‌ها</h3>
                    <div class="mt-6 space-y-3">
                        @foreach(['گزارش مصرف روزانه و ماهانه', 'پهنای باند روتر و interface', 'گزارش مالی و نرخ وصول', 'گزارش استفاده بر اساس کاربر، پلن و روتر'] as $item)
                            <div class="rounded-2xl bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700">{{ $item }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-10 rounded-3xl bg-[#0d2f35] p-7 text-white">
                <h3 class="text-2xl font-black">خروجی مدیریتی برای مدیران دولتی</h3>
                <div class="mt-6 grid grid-cols-4 gap-4">
                    @foreach(['مالی' => 'درآمد، وصولی، بدهی و معوقات', 'دسترسی' => 'کاربر، نقش، گروه و مجوز', 'شبکه' => 'روتر، IPAM، VPN و هشدار', 'ممیزی' => 'ثبت تغییرات و پاسخ‌گویی سازمانی'] as $title => $description)
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-2xl font-black">{{ $title }}</p>
                            <p class="mt-2 text-xs leading-6 text-teal-50/75">{{ $description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="sheet flex min-h-[297mm] flex-col rounded-[1.5rem] bg-[#f6f1e8] p-10 shadow-2xl print:p-8">
            <p class="text-sm font-black text-teal-700">Government Readiness</p>
            <h2 class="mt-3 text-4xl font-black leading-tight">چرا SkyBase برای نهادهای دولتی مناسب است؟</h2>

            <div class="mt-10 grid grid-cols-2 gap-6">
                <div class="rounded-3xl bg-white p-7 shadow-sm">
                    <h3 class="text-2xl font-black">ساختارپذیر برای سازمان‌های چندواحدی</h3>
                    <p class="mt-4 text-base leading-8 text-slate-700">از ستاد مرکزی تا بیمارستان، اداره شهرستان، مرکز داده یا شعبه عملیاتی، هر واحد می‌تواند در یک ساختار قابل کنترل و قابل گزارش مدیریت شود.</p>
                </div>
                <div class="rounded-3xl bg-white p-7 shadow-sm">
                    <h3 class="text-2xl font-black">کاهش وابستگی به فرایندهای دستی</h3>
                    <p class="mt-4 text-base leading-8 text-slate-700">عملیات تکراری مانند صدور صورتحساب، پیگیری بدهی، تعلیق سرویس، تخصیص IP و مشاهده وضعیت شبکه از مسیرهای استاندارد انجام می‌شود.</p>
                </div>
                <div class="rounded-3xl bg-white p-7 shadow-sm">
                    <h3 class="text-2xl font-black">گزارش‌پذیری برای تصمیم‌گیری</h3>
                    <p class="mt-4 text-base leading-8 text-slate-700">مدیران می‌توانند وضعیت مالی، مصرف، سلامت شبکه، اشتراک‌ها و عملکرد واحدها را در قالب گزارش‌های روشن بررسی کنند.</p>
                </div>
                <div class="rounded-3xl bg-white p-7 shadow-sm">
                    <h3 class="text-2xl font-black">آماده برای سیاست‌های دسترسی سازمانی</h3>
                    <p class="mt-4 text-base leading-8 text-slate-700">Active Directory Sync، مدیریت گروه‌ها و نقش‌های داخلی SkyBase، مسیر تعریف سیاست‌های دسترسی هماهنگ با ساختار سازمان را فراهم می‌کند.</p>
                </div>
            </div>

            <div class="mt-10 rounded-3xl bg-white p-7 shadow-sm">
                <h3 class="text-2xl font-black">جمع‌بندی قابلیت‌ها</h3>
                <div class="mt-6 grid grid-cols-3 gap-3">
                    @foreach(['چندمستاجری امن', 'مدیریت کاربران سازمانی', 'Active Directory Sync', 'مدیریت گروه‌ها', 'نقش‌ها و مجوزها', 'صورتحساب دوره‌ای', 'پرداخت و اعتبار', 'گزارش مالی', 'روتر و شبکه', 'IPAM', 'VPN Users', 'گزارش مصرف'] as $capability)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-center text-sm font-black text-slate-700">{{ $capability }}</div>
                    @endforeach
                </div>
            </div>

            <div class="mt-auto rounded-3xl bg-[#0d2f35] p-8 text-white">
                <div class="flex items-center justify-between gap-8">
                    <div>
                        <p class="text-sm font-bold text-[#f5c542]">SkyBase Cloud</p>
                        <h3 class="mt-3 text-3xl font-black">آماده ارائه دمو برای نهادهای دولتی و سازمان‌های بزرگ</h3>
                        <p class="mt-4 max-w-2xl text-base leading-8 text-teal-50/80">برای بررسی سناریوی اختصاصی سازمان، ساختار گروه‌ها، نیازهای حسابداری و اتصال به زیرساخت دسترسی، جلسه دمو قابل برنامه‌ریزی است.</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 text-slate-950">
                        <p class="text-sm text-slate-500">وب‌سایت</p>
                        <p class="mt-2 text-xl font-black">skybase.app</p>
                        <p class="mt-4 text-sm text-slate-500">شماره تماس</p>
                        <p class="mt-2 text-xl font-black">09336337953</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
