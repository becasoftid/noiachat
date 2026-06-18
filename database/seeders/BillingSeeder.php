<?php

namespace Database\Seeders;

use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use App\Modules\Billing\Infrastructure\Persistence\Models\Feature;
use App\Modules\Billing\Infrastructure\Persistence\Models\Plan;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $plans = $this->seedPlans();
        $features = $this->seedFeatures();

        $this->syncPlanFeatures($plans, $features);
        $this->ensureDefaultCompanySubscription($plans['basic_trial']);
    }

    /**
     * @return array<string, Plan>
     */
    private function seedPlans(): array
    {
        $definitions = [
            'basic_trial' => [
                'name' => 'Plan basico de prueba',
                'description' => 'Periodo inicial para configurar empresa, sede, usuarios basicos y validar operacion WhatsApp.',
                'price_cents' => 0,
                'currency' => 'COP',
                'billing_period' => 'trial',
                'trial_days' => 14,
                'max_users' => 3,
                'max_branches' => 1,
                'max_contacts' => 100,
                'max_whatsapp_channels' => 1,
                'metadata' => [
                    'display_order' => 10,
                    'audience' => 'Empresas que estan validando NoiaChat por primera vez.',
                    'commercial_label' => 'Prueba inicial',
                    'price_note' => 'Sin costo durante el periodo de prueba.',
                    'recommended_next_plan' => 'basic',
                ],
                'is_active' => true,
            ],
            'basic' => [
                'name' => 'Basic',
                'description' => 'Operacion inicial para una empresa con una sede y canal WhatsApp.',
                'price_cents' => 0,
                'currency' => 'COP',
                'billing_period' => 'monthly',
                'trial_days' => 0,
                'max_users' => 3,
                'max_branches' => 1,
                'max_contacts' => 500,
                'max_whatsapp_channels' => 1,
                'metadata' => [
                    'display_order' => 20,
                    'audience' => 'Equipos pequenos que operan una sede y un canal WhatsApp.',
                    'commercial_label' => 'Operacion inicial',
                    'price_note' => 'Tarifa comercial por definir.',
                ],
                'is_active' => true,
            ],
            'pro' => [
                'name' => 'Pro',
                'description' => 'Operacion avanzada con importaciones, multimedia, plantillas y reportes.',
                'price_cents' => 0,
                'currency' => 'COP',
                'billing_period' => 'monthly',
                'trial_days' => 0,
                'max_users' => 15,
                'max_branches' => 5,
                'max_contacts' => 5000,
                'max_whatsapp_channels' => 5,
                'metadata' => [
                    'display_order' => 30,
                    'audience' => 'Equipos en crecimiento con importaciones, multimedia, plantillas y reportes.',
                    'commercial_label' => 'Plan recomendado',
                    'price_note' => 'Tarifa comercial por definir.',
                    'highlight' => true,
                ],
                'is_active' => true,
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'description' => 'Plan completo con limites personalizados y funcionalidades avanzadas.',
                'price_cents' => 0,
                'currency' => 'COP',
                'billing_period' => 'monthly',
                'trial_days' => 0,
                'max_users' => null,
                'max_branches' => null,
                'max_contacts' => null,
                'max_whatsapp_channels' => null,
                'metadata' => [
                    'display_order' => 40,
                    'audience' => 'Operaciones con multiples empresas, sedes o necesidades personalizadas.',
                    'commercial_label' => 'A medida',
                    'price_note' => 'Cotizacion personalizada.',
                    'custom_limits' => true,
                ],
                'is_active' => true,
            ],
        ];

        $plans = [];

        foreach ($definitions as $code => $attributes) {
            $plans[$code] = Plan::query()->updateOrCreate(['code' => $code], $attributes);
        }

        return $plans;
    }

    /**
     * @return array<string, Feature>
     */
    private function seedFeatures(): array
    {
        $definitions = [
            'contacts.create' => ['name' => 'Crear contactos', 'module' => 'contacts'],
            'contacts.import' => ['name' => 'Importar contactos', 'module' => 'contacts'],
            'contacts.merge' => ['name' => 'Fusionar contactos', 'module' => 'contacts'],
            'conversations.inbox' => ['name' => 'Inbox de conversaciones', 'module' => 'conversations'],
            'conversations.assignment' => ['name' => 'Asignacion de conversaciones', 'module' => 'conversations'],
            'whatsapp.text' => ['name' => 'Envio de texto WhatsApp', 'module' => 'whatsapp'],
            'whatsapp.media' => ['name' => 'Envio multimedia WhatsApp', 'module' => 'whatsapp'],
            'whatsapp.templates' => ['name' => 'Plantillas WhatsApp', 'module' => 'whatsapp'],
            'reports.dashboard' => ['name' => 'Dashboard operativo', 'module' => 'reports'],
            'reports.export' => ['name' => 'Exportacion de reportes', 'module' => 'reports'],
            'audit.view' => ['name' => 'Consulta de auditoria', 'module' => 'audit'],
            'audit.detail' => ['name' => 'Detalle de auditoria', 'module' => 'audit'],
            'settings.whatsapp_channel' => ['name' => 'Configuracion de canal WhatsApp', 'module' => 'settings'],
            'users.manage' => ['name' => 'Gestion de usuarios', 'module' => 'users'],
            'branches.manage' => ['name' => 'Gestion de sedes', 'module' => 'tenancy'],
            'health.view' => ['name' => 'Monitor de salud', 'module' => 'deploy'],
            'api.access' => ['name' => 'Acceso API', 'module' => 'api'],
        ];

        $features = [];

        foreach ($definitions as $code => $attributes) {
            $features[$code] = Feature::query()->updateOrCreate(
                ['code' => $code],
                $attributes + ['description' => null, 'is_active' => true],
            );
        }

        return $features;
    }

    /**
     * @param  array<string, Plan>  $plans
     * @param  array<string, Feature>  $features
     */
    private function syncPlanFeatures(array $plans, array $features): void
    {
        $matrix = [
            'basic_trial' => [
                'contacts.create',
                'conversations.inbox',
                'conversations.assignment',
                'whatsapp.text',
                'whatsapp.templates',
                'reports.dashboard',
                'settings.whatsapp_channel',
                'users.manage',
                'branches.manage',
            ],
            'basic' => [
                'contacts.create',
                'conversations.inbox',
                'conversations.assignment',
                'whatsapp.text',
                'whatsapp.templates',
                'reports.dashboard',
                'settings.whatsapp_channel',
                'users.manage',
                'branches.manage',
            ],
            'pro' => [
                'contacts.create',
                'contacts.import',
                'contacts.merge',
                'conversations.inbox',
                'conversations.assignment',
                'whatsapp.text',
                'whatsapp.media',
                'whatsapp.templates',
                'reports.dashboard',
                'reports.export',
                'audit.view',
                'audit.detail',
                'settings.whatsapp_channel',
                'users.manage',
                'branches.manage',
            ],
            'enterprise' => array_keys($features),
        ];

        foreach ($matrix as $planCode => $featureCodes) {
            $sync = [];

            foreach ($featureCodes as $featureCode) {
                $sync[$features[$featureCode]->id] = ['enabled' => true, 'limits' => null];
            }

            $plans[$planCode]->features()->sync($sync);
        }
    }

    private function ensureDefaultCompanySubscription(Plan $trialPlan): void
    {
        $company = Company::query()->where('slug', env('NOIACHAT_DEFAULT_COMPANY_SLUG', 'default'))->first();

        if ($company === null) {
            return;
        }

        CompanySubscription::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'plan_id' => $trialPlan->id,
                'status' => 'trialing',
            ],
            [
                'trial_started_at' => now(),
                'trial_ends_at' => now()->addDays($trialPlan->trial_days),
                'current_period_started_at' => now(),
                'current_period_ends_at' => now()->addDays($trialPlan->trial_days),
                'metadata' => ['source' => 'seeder'],
            ],
        );
    }
}
