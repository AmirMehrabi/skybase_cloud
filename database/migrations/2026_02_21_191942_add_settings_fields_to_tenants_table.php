<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Company Information
            $table->string('tagline')->nullable()->after('company_name');
            $table->string('business_license')->nullable()->after('tagline');
            $table->string('tax_id')->nullable()->after('business_license');
            $table->string('website_url')->nullable()->after('tax_id');
            $table->string('support_phone')->nullable()->after('phone');
            $table->text('address')->nullable()->after('support_phone');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('zip')->nullable()->after('state');

            // Localization
            $table->string('date_format')->default('m/d/Y')->after('timezone');
            $table->string('time_format')->default('12h')->after('date_format');
            $table->enum('first_day_of_week', ['sunday', 'monday'])->default('sunday')->after('time_format');
            $table->string('currency')->default('USD')->after('first_day_of_week');
            $table->enum('currency_symbol_position', ['before', 'after'])->default('before')->after('currency');
            $table->string('thousands_separator')->default(',')->after('currency_symbol_position');
            $table->string('decimal_separator')->default('.')->after('thousands_separator');
            $table->string('locale')->default('en')->after('decimal_separator');

            // Branding
            $table->string('primary_color')->default('#3b82f6')->after('locale');
            $table->string('secondary_color')->default('#64748b')->after('primary_color');
            $table->string('accent_color')->default('#8b5cf6')->after('secondary_color');
            $table->boolean('dark_mode_enabled')->default(false)->after('accent_color');
            $table->text('custom_css')->nullable()->after('dark_mode_enabled');

            // Assets (stored as paths)
            $table->string('company_logo')->nullable()->after('custom_css');
            $table->string('company_logo_dark')->nullable()->after('company_logo');
            $table->string('favicon')->nullable()->after('company_logo_dark');
            $table->string('login_logo')->nullable()->after('favicon');
            $table->string('email_header_logo')->nullable()->after('login_logo');
            $table->string('email_footer_logo')->nullable()->after('email_header_logo');
            $table->string('invoice_logo')->nullable()->after('email_footer_logo');
            $table->string('login_background')->nullable()->after('invoice_logo');

            // System
            $table->boolean('maintenance_mode')->default(false)->after('login_background');
            $table->string('custom_domain')->nullable()->after('maintenance_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'tagline',
                'business_license',
                'tax_id',
                'website_url',
                'support_phone',
                'address',
                'city',
                'state',
                'zip',
                'date_format',
                'time_format',
                'first_day_of_week',
                'currency',
                'currency_symbol_position',
                'thousands_separator',
                'decimal_separator',
                'locale',
                'primary_color',
                'secondary_color',
                'accent_color',
                'dark_mode_enabled',
                'custom_css',
                'company_logo',
                'company_logo_dark',
                'favicon',
                'login_logo',
                'email_header_logo',
                'email_footer_logo',
                'invoice_logo',
                'login_background',
                'maintenance_mode',
                'custom_domain',
            ]);
        });
    }
};
