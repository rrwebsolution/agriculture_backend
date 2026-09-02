<?php

namespace Database\Seeders;

use App\Models\SystemLabel;
use Illuminate\Database\Seeder;

class SystemLabelSeeder extends Seeder
{
    public function run(): void
    {
        $labels = [
            // Sidebar — group headings
            ['key' => 'sidebar.group.overview', 'group' => 'sidebar', 'default_value' => 'OVERVIEW'],
            ['key' => 'sidebar.group.registries', 'group' => 'sidebar', 'default_value' => 'REGISTRIES'],
            ['key' => 'sidebar.group.locations', 'group' => 'sidebar', 'default_value' => 'LOCATIONS'],
            ['key' => 'sidebar.group.sector_operations', 'group' => 'sidebar', 'default_value' => 'SECTOR OPERATIONS'],
            ['key' => 'sidebar.group.management', 'group' => 'sidebar', 'default_value' => 'MANAGEMENT'],
            ['key' => 'sidebar.group.employees', 'group' => 'sidebar', 'default_value' => 'EMPLOYEES'],
            ['key' => 'sidebar.group.administration', 'group' => 'sidebar', 'default_value' => 'ADMINISTRATION'],

            // Sidebar — menu items
            ['key' => 'sidebar.item.dashboard', 'group' => 'sidebar', 'default_value' => 'Dashboard'],
            ['key' => 'sidebar.item.farmer_registry', 'group' => 'sidebar', 'default_value' => 'Farmer Registry'],
            ['key' => 'sidebar.item.fisherfolk_registry', 'group' => 'sidebar', 'default_value' => 'Fisherfolk Registry'],
            ['key' => 'sidebar.item.cooperatives', 'group' => 'sidebar', 'default_value' => 'FFCA (Cooperatives)'],
            ['key' => 'sidebar.item.barangay_profile', 'group' => 'sidebar', 'default_value' => 'Barangay Profile'],
            ['key' => 'sidebar.item.work_location', 'group' => 'sidebar', 'default_value' => 'Work Location'],
            ['key' => 'sidebar.item.danger_zones', 'group' => 'sidebar', 'default_value' => 'Danger Zones'],
            ['key' => 'sidebar.item.crop_agriculture', 'group' => 'sidebar', 'default_value' => 'Crop Agriculture'],
            ['key' => 'sidebar.item.crops', 'group' => 'sidebar', 'default_value' => 'Crops'],
            ['key' => 'sidebar.item.planting_logs', 'group' => 'sidebar', 'default_value' => 'Planting Logs'],
            ['key' => 'sidebar.item.harvest_records', 'group' => 'sidebar', 'default_value' => 'Harvest Records'],
            ['key' => 'sidebar.item.nursery_production', 'group' => 'sidebar', 'default_value' => 'City Plant Nursery Production'],
            ['key' => 'sidebar.item.fishery', 'group' => 'sidebar', 'default_value' => 'Fishery'],
            ['key' => 'sidebar.item.resources', 'group' => 'sidebar', 'default_value' => 'Resources'],
            ['key' => 'sidebar.item.inventory', 'group' => 'sidebar', 'default_value' => 'Inventory'],
            ['key' => 'sidebar.item.equipments', 'group' => 'sidebar', 'default_value' => 'Equipments'],
            ['key' => 'sidebar.item.expense', 'group' => 'sidebar', 'default_value' => 'Expense'],
            ['key' => 'sidebar.item.reports', 'group' => 'sidebar', 'default_value' => 'Reports'],
            ['key' => 'sidebar.item.employee_information', 'group' => 'sidebar', 'default_value' => 'Employee Information'],
            ['key' => 'sidebar.item.employee_logs', 'group' => 'sidebar', 'default_value' => 'Employee Logs'],
            ['key' => 'sidebar.item.access_control', 'group' => 'sidebar', 'default_value' => 'Access Control'],
            ['key' => 'sidebar.item.role_management', 'group' => 'sidebar', 'default_value' => 'Role Management'],
            ['key' => 'sidebar.item.user_management', 'group' => 'sidebar', 'default_value' => 'User Management'],
            ['key' => 'sidebar.item.server_health', 'group' => 'sidebar', 'default_value' => 'Server Health'],

            // Dashboard
            ['key' => 'dashboard.heading.crop_harvest_leaders', 'group' => 'dashboard', 'default_value' => 'Crop Harvest Leaders'],
            ['key' => 'dashboard.heading.recent_activities', 'group' => 'dashboard', 'default_value' => 'Recent Activities'],

            // Common buttons (shared across pages)
            ['key' => 'common.button.save', 'group' => 'common', 'default_value' => 'Save'],
            ['key' => 'common.button.cancel', 'group' => 'common', 'default_value' => 'Cancel'],
            ['key' => 'common.button.delete', 'group' => 'common', 'default_value' => 'Delete'],
            ['key' => 'common.button.edit', 'group' => 'common', 'default_value' => 'Edit'],
            ['key' => 'common.button.add_new', 'group' => 'common', 'default_value' => 'Add New'],
            ['key' => 'common.button.processing', 'group' => 'common', 'default_value' => 'Processing...'],

            // Crops page
            ['key' => 'crops.page.search_placeholder', 'group' => 'crops', 'default_value' => 'Search category...'],
            ['key' => 'crops.tab.table', 'group' => 'crops', 'default_value' => 'Land Use List'],
            ['key' => 'crops.tab.distribution', 'group' => 'crops', 'default_value' => 'Farmer Distribution'],
            ['key' => 'crops.dialog.title_edit', 'group' => 'crops', 'default_value' => 'Update Crop Record'],
            ['key' => 'crops.dialog.title_new', 'group' => 'crops', 'default_value' => 'New Crop Record'],
            ['key' => 'crops.dialog.save_button_edit', 'group' => 'crops', 'default_value' => 'Update Record'],
            ['key' => 'crops.dialog.save_button_new', 'group' => 'crops', 'default_value' => 'Save Record'],
        ];

        foreach ($labels as $label) {
            // firstOrCreate seeds a brand-new key with value=null (use default).
            // The follow-up update() re-syncs group/default_value with the code
            // on every seed run without ever touching an admin-edited `value`.
            $record = SystemLabel::firstOrCreate(
                ['key' => $label['key']],
                ['group' => $label['group'], 'default_value' => $label['default_value'], 'value' => null]
            );

            $record->update([
                'group' => $label['group'],
                'default_value' => $label['default_value'],
            ]);
        }
    }
}
