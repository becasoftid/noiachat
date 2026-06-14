<?php

namespace App\Modules\Compliance\Domain\Enums;

enum EligibilityStatus: string
{
    case ALLOWED = 'allowed';
    case BLOCKED_NO_CONSENT = 'blocked_no_consent';
    case BLOCKED_BLACKLIST = 'blocked_blacklist';
    case BLOCKED_INVALID_CONTACT = 'blocked_invalid_contact';
    case BLOCKED_FREQUENCY = 'blocked_frequency';
    case BLOCKED_CHANNEL_INACTIVE = 'blocked_channel_inactive';
    case BLOCKED_TEMPLATE_INACTIVE = 'blocked_template_inactive';
    case BLOCKED_CUSTOMER_CARE_WINDOW = 'blocked_customer_care_window';

    public function label(): string
    {
        return match ($this) {
            self::ALLOWED => 'Permitido',
            self::BLOCKED_NO_CONSENT => 'Sin consentimiento',
            self::BLOCKED_BLACKLIST => 'Contacto excluido',
            self::BLOCKED_INVALID_CONTACT => 'Contacto invalido',
            self::BLOCKED_FREQUENCY => 'Limite de frecuencia',
            self::BLOCKED_CHANNEL_INACTIVE => 'Canal inactivo',
            self::BLOCKED_TEMPLATE_INACTIVE => 'Plantilla inactiva',
            self::BLOCKED_CUSTOMER_CARE_WINDOW => 'Ventana 24h cerrada',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ALLOWED => 'El mensaje cumple las reglas de envio.',
            self::BLOCKED_NO_CONSENT => 'El contacto no tiene consentimiento vigente para este canal.',
            self::BLOCKED_BLACKLIST => 'El contacto esta en la lista de exclusion para este canal.',
            self::BLOCKED_INVALID_CONTACT => 'El contacto no esta activo o tiene un estado que impide enviarle mensajes.',
            self::BLOCKED_FREQUENCY => 'El contacto alcanzo el limite de frecuencia configurado.',
            self::BLOCKED_CHANNEL_INACTIVE => 'El canal seleccionado esta inactivo o no esta disponible.',
            self::BLOCKED_TEMPLATE_INACTIVE => 'La plantilla seleccionada esta inactiva.',
            self::BLOCKED_CUSTOMER_CARE_WINDOW => 'La ventana de atencion de 24 horas esta cerrada. Usa una plantilla aprobada.',
        };
    }
}
