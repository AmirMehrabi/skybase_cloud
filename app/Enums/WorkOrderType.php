<?php

namespace App\Enums;

enum WorkOrderType: string
{
    case NewInstallation = 'new_installation';
    case SiteSurvey = 'site_survey';
    case Relocation = 'relocation';
    case EquipmentInstallation = 'equipment_installation';
    case EquipmentReplacement = 'equipment_replacement';
    case Repair = 'repair';
    case PreventiveMaintenance = 'preventive_maintenance';
    case ServiceReconnection = 'service_reconnection';
    case ServiceDisconnection = 'service_disconnection';
    case EquipmentRecovery = 'equipment_recovery';
    case NetworkTask = 'network_task';
    case Other = 'other';

    public function requiresSubscription(): bool
    {
        return in_array($this, [
            self::Relocation,
            self::EquipmentReplacement,
            self::Repair,
            self::ServiceReconnection,
            self::ServiceDisconnection,
        ], true);
    }
}
